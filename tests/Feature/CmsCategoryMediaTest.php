<?php

use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Livewire\Cms\Categories;
use Aldhi88\StarterKit\Livewire\Cms\ContentForm;
use Aldhi88\StarterKit\Livewire\Cms\ContentTable;
use Aldhi88\StarterKit\Livewire\Cms\MediaLibrary;
use Aldhi88\StarterKit\Livewire\Cms\MediaPicker;
use Aldhi88\StarterKit\Models\Cms\Category;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\App as StarterApp;
use Aldhi88\StarterKit\Models\Starter\AppMod;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Cms\CmsRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Aldhi88\StarterKit\Themes\Starter\DashcodePowerGridTheme;
use Aldhi88\StarterKit\Themes\Starter\TablerPowerGridTheme;
use Aldhi88\StarterKit\Themes\Starter\VuexyPowerGridTheme;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use PowerComponents\LivewirePowerGrid\Providers\PowerGridServiceProvider;

beforeEach(function (): void {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('m', 32))]);
    app()->register(LivewireServiceProvider::class);
    app()->register(PowerGridServiceProvider::class);
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185233_create_starter_app_metadata_tables.php', '2026_07_27_185235_create_starter_logs_table.php'] as $file) {
        (require StarterPaths::path('database/migrations/starter/'.$file))->up();
    }
    foreach (glob(StarterPaths::path('installer/templates/cms/*.php')) as $file) {
        (require $file)->up();
    }
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    app()->bind(CmsRepositoryInterface::class, CmsRepository::class);
    app()->scoped(AuditLogService::class);
    $role = ClientRole::create(['code' => 'superuser', 'name' => 'Superuser', 'is_system' => true]);
    $this->libraryActor = ClientLogin::create(['client_role_id' => $role->id, 'status' => 'active', 'name' => 'Editor', 'username' => 'library-editor', 'email' => 'library@example.test']);
    $this->actingAs($this->libraryActor);
    $app = StarterApp::create(['name' => 'CMS', 'subdomain' => 'cms']);
    foreach (['dashboard', 'posts', 'pages', 'cards', 'media', 'menus', 'comments'] as $module) {
        AppMod::create(['app_id' => $app->id, 'code' => $module, 'name' => $module]);
    }
    foreach (CmsResource::cases() as $resource) {
        Route::get('/'.$resource->value, fn () => 'index')->name($resource->routePrefix().'.index');
        Route::get('/'.$resource->value.'/create', fn () => 'create')->name($resource->routePrefix().'.create');
        Route::get('/'.$resource->value.'/{contentId}/edit', fn () => 'edit')->name($resource->routePrefix().'.edit');
    }
    Route::get('/albums', fn () => 'albums')->name('cms.media.albums.index');
    Route::get('/media-file/{publicId}', fn () => 'file')->name('starter.cms.media');
    Livewire::component('cms.table', ContentTable::class);
    Livewire::component('cms.media-picker', MediaPicker::class);
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
    Storage::fake('local');
    $this->libraryManager = app(CmsManager::class);
});

function cmsLibraryTheme(string $theme): void
{
    config(['starter.theme' => $theme, 'livewire-powergrid.filter' => 'inline', 'livewire-powergrid.persist_driver' => 'session', 'livewire-powergrid.theme' => match ($theme) {
        'dashcode' => DashcodePowerGridTheme::class, 'vuexy' => VuexyPowerGridTheme::class, default => TablerPowerGridTheme::class,
    }]);
    View::addLocation(StarterPaths::path('resources/themes/'.$theme.'/views'));
}

it('keeps category creation and editing alongside the list and preserves mapping', function (string $theme): void {
    cmsLibraryTheme($theme);
    $component = Livewire::test(Categories::class, ['resource' => 'card_categories'])
        ->assertSeeHtml('data-starter-region="category-workspace"')->assertSee('Tambah Kategori')
        ->set('name', '')->call('save')->assertHasErrors('name')
        ->set('name', 'Logo Klien')->call('save')->assertHasNoErrors()->assertSet('name', '');
    $category = Category::where('kind', 'card')->firstOrFail();
    expect($category->code)->toBe('logo-klien')->and($category->created_by)->toBe($this->libraryActor->id);
    $component->call('editCategory', 'card_categories', $category->id)->assertSet('name', 'Logo Klien')
        ->assertSet('mappingCode', 'logo-klien')->set('name', 'Mitra Kami')->call('save')->assertHasNoErrors();
    expect($category->fresh()->name)->toBe('Mitra Kami')->and($category->fresh()->code)->toBe('logo-klien');
})->with(['tabler', 'vuexy', 'dashcode']);

it('dispatches inline category editing rather than navigating to a separate form', function (): void {
    cmsLibraryTheme('tabler');
    $category = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Berita');
    Livewire::test(ContentTable::class, ['resource' => 'post_categories'])->assertSee('cms-edit-category', false)
        ->assertDontSee('href="'.route('cms.posts.categories.edit', ['contentId' => $category->id]).'"', false);
});

it('lets Tabler create subcategories and assign several categories to one post', function (): void {
    cmsLibraryTheme('tabler');
    Livewire::test(Categories::class, ['resource' => 'post_categories'])
        ->set('name', 'Berita')->call('save')->assertHasNoErrors();
    $news = Category::where('code', 'berita')->firstOrFail();
    Livewire::test(Categories::class, ['resource' => 'post_categories'])
        ->assertSee('Kategori Induk')->set('name', 'Daerah')->set('parentId', (string) $news->id)
        ->call('save')->assertHasNoErrors();
    $local = Category::where('code', 'daerah')->firstOrFail();

    Livewire::test(ContentForm::class, ['resource' => 'posts'])
        ->assertSee('Berita › Daerah')->assertSeeHtml('data-cms-toolbar-media')
        ->set('form.title', 'Kabar Daerah')->set('form.category_ids', [(string) $news->id, (string) $local->id])
        ->set('publishedAtDisplay', '25/10/2026 17:30')->call('save')->assertHasNoErrors();
    $post = Content::where('slug', 'kabar-daerah')->firstOrFail();
    expect($local->fresh()->parent_id)->toBe($news->id)
        ->and($post->categories()->count())->toBe(2);
});

it('rejects an impossible publication date in the Tabler date mask', function (): void {
    cmsLibraryTheme('tabler');
    Livewire::test(ContentForm::class, ['resource' => 'posts'])
        ->set('form.title', 'Tanggal Salah')->set('publishedAtDisplay', '31/02/2026 18:30')
        ->call('save')->assertHasErrors('form.published_at');
    expect(Content::where('title', 'Tanggal Salah')->exists())->toBeFalse();
});

it('creates and selects a category without leaving a partially written Tabler post', function (): void {
    cmsLibraryTheme('tabler');
    $form = Livewire::test(ContentForm::class, ['resource' => 'posts'])
        ->set('form.title', 'Berita yang Sedang Ditulis')
        ->set('newCategoryName', 'Teknologi')
        ->call('createPostCategory')->assertHasNoErrors()
        ->assertSet('form.title', 'Berita yang Sedang Ditulis')
        ->assertSet('newCategoryName', '');
    $category = Category::where('code', 'teknologi')->firstOrFail();
    $form->assertSet('form.category_ids', [$category->id])
        ->assertSee('Teknologi')->call('save')->assertHasNoErrors();
    expect(Content::where('title', 'Berita yang Sedang Ditulis')->firstOrFail()->categories()->pluck('cms_categories.id')->all())
        ->toBe([$category->id]);
});

it('renders upload and searchable visual library surfaces in every theme', function (string $theme): void {
    cmsLibraryTheme($theme);
    Livewire::test(MediaLibrary::class)->assertSee('Tarik file ke sini')->assertSee('Pilih File')
        ->assertSeeHtml('data-starter-region="media-grid"')->assertSee('Cari Media')->assertSee('Belum ada media yang sesuai');
    Livewire::test(MediaLibrary::class, ['mode' => 'gallery', 'resource' => 'galleries'])
        ->assertSee('Galeri')->assertDontSee('Tarik file ke sini')->assertSee('Kelola Album')->assertSee('Cari Media');
})->with(['tabler', 'vuexy', 'dashcode']);

it('focuses post authoring on the title and editor while keeping optional details beside it', function (string $theme): void {
    cmsLibraryTheme($theme);
    $component = Livewire::test(ContentForm::class, ['resource' => 'posts'])
        ->assertSee('Judul Postingan')->assertSee('Sekarang')->assertSee('Pilih Thumbnail')
        ->assertDontSee('Nama Internal')->assertDontSee('Media dan Tautan');
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8" ?><html><body>'.$component->html().'</body></html>');
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    $main = $xpath->query('//*[@data-starter-region="content-form"]')->item(0);
    expect($main)->toBeInstanceOf(DOMElement::class);
    expect($xpath->query('.//input[@id="cms-title"][@required]', $main)->length)->toBe(1)
        ->and($xpath->query('.//input[@id="cms-slug"]', $main)->length)->toBe(1)
        ->and($xpath->query('.//*[@data-cms-editor]', $main)->length)->toBe(1)
        ->and($xpath->query('.//*[@id="cms-subtitle" or @id="cms-excerpt"]', $main)->length)->toBe(0);
    foreach (['cms-name', 'cms-position', 'cms-image_id', 'cms-logo_id', 'cms-link_label', 'cms-link_url', 'cms-media-search'] as $id) {
        expect($xpath->query('//*[@id="'.$id.'"]')->length)->toBe(0);
    }
    expect($xpath->query('//*[@data-starter-region="post-details-form"]//*[@id="cms-subtitle" or @id="cms-excerpt"]')->length)->toBe(2)
        ->and($xpath->query('//*[@data-starter-region="publication-form"]//input[@id="cms-published_at" and @type="'.($theme === 'tabler' ? 'text' : 'datetime-local').'"]')->length)->toBe(1)
        ->and($xpath->query('//*[@data-starter-dialog-component][@*[name()="wire:id"]]')->length)->toBe(1);
    Livewire::test(ContentForm::class, ['resource' => 'cards'])->assertSee('Nama Internal')
        ->assertSee('Media dan Tautan')->assertSee('Urutan')->assertSeeHtml('id="cms-logo_id"');
})->with(['tabler', 'vuexy', 'dashcode']);

it('keeps interactive category and media workspaces inside their Livewire snapshot owner', function (string $theme): void {
    cmsLibraryTheme($theme);
    foreach ([
        [Livewire::test(Categories::class, ['resource' => 'post_categories']), 'category-form'],
        [Livewire::test(MediaLibrary::class), 'media-grid'],
    ] as [$component, $region]) {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><html><body>'.$component->html().'</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $owner = $xpath->query('//*[@*[name()="wire:id"]="'.$component->id().'"]')->item(0);
        expect($owner)->toBeInstanceOf(DOMElement::class);
        expect($xpath->query('.//*[@data-starter-region="'.$region.'"]', $owner)->length)->toBe(1);
        if ($region === 'category-form') {
            expect($xpath->query('.//form[@*[name()="wire:submit"]="save"]', $owner)->length)->toBe(1);
        }
    }
})->with(['tabler', 'vuexy', 'dashcode']);

it('mounts actual CMS workspace HTTP routes with route defaults and complete interactive roots', function (): void {
    cmsLibraryTheme('tabler');
    config(['app.domain' => 'example.test']);
    View::addNamespace('layouts', __DIR__.'/../Fixtures/cms-layout');
    $this->withoutMiddleware();
    $this->withoutExceptionHandling();
    Route::setRoutes(new RouteCollection);
    Route::domain('cms.example.test')->group(StarterPaths::path('routes/cms.php'));
    Route::getRoutes()->refreshNameLookups();
    $media = $this->libraryManager->save($this->libraryActor, CmsResource::Media, ['name' => 'Logo Route', 'status' => 'active'], upload: UploadedFile::fake()->image('route-logo.png'));
    foreach (['cms.media.index', 'cms.media.create', 'cms.media.edit', 'cms.media.galleries.index', 'cms.media.albums.index', 'cms.posts.categories.index'] as $name) {
        $response = $this->get(route($name, ['contentId' => $media->id]))->assertOk();
        if ($name === 'cms.media.edit') {
            $response->assertSee('Detail Media')->assertSee('Logo Route');
        } elseif ($name === 'cms.media.galleries.index') {
            $response->assertSee('Kelola Album')->assertDontSee('Tarik file ke sini');
        }
        if ($name !== 'cms.media.albums.index') {
            $document = new DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML($response->getContent());
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $region = $name === 'cms.posts.categories.index' ? 'category-form' : 'media-grid';
            $owners = (new DOMXPath($document))->query('//*[@data-starter-region="'.$region.'"]/ancestor::*[@*[name()="wire:snapshot"]]');
            expect($owners->length)->toBe(1);
        }
    }
});

it('uploads several files automatically and reports unsupported files individually', function (): void {
    cmsLibraryTheme('tabler');
    Livewire::test(MediaLibrary::class)->set('uploads', [
        UploadedFile::fake()->image('logo.png'), UploadedFile::fake()->image('foto.png'),
        UploadedFile::fake()->create('unsafe.txt', 1, 'text/plain'),
    ])->assertSet('uploadedCount', 2)->assertCount('uploadFailures', 1)->assertSet('uploads', [])
        ->assertSee('logo')->assertSee('foto');
    expect(Media::count())->toBe(2);
});

it('filters the complete library by name and MIME type with bounded pagination', function (): void {
    cmsLibraryTheme('tabler');
    $image = $this->libraryManager->save($this->libraryActor, CmsResource::Media, ['name' => 'Logo Contoh', 'status' => 'active'], upload: UploadedFile::fake()->image('logo.png'));
    $pdf = $this->libraryManager->save($this->libraryActor, CmsResource::Media, ['name' => 'Panduan PDF', 'status' => 'active'], upload: UploadedFile::fake()->create('guide.pdf', 1, 'application/pdf'));
    Livewire::test(MediaLibrary::class, ['mode' => 'gallery'])->assertSee('Logo Contoh')->assertSee('Panduan PDF')
        ->set('type', 'pdf')->assertSee('Panduan PDF')->assertDontSee('Logo Contoh')
        ->call('selectPage')->assertSet('selected', [$pdf->id])->set('search', 'Tidak Ada')->assertSet('selected', [])->assertDontSee('Panduan PDF');
    expect($image->exists)->toBeTrue();
});

it('edits file details in a modal and preserves invalid input', function (): void {
    cmsLibraryTheme('tabler');
    $file = $this->libraryManager->save($this->libraryActor, CmsResource::Media, ['name' => 'Logo', 'status' => 'active'], upload: UploadedFile::fake()->image('logo.png'));
    Livewire::test(MediaLibrary::class)->call('openDetails', $file->id)->assertSee('Detail Media')
        ->set('detail.name', '')->set('detail.caption', 'Dipertahankan')->call('saveDetails')
        ->assertHasErrors('detail.name')->assertSet('detail.caption', 'Dipertahankan')
        ->set('detail.name', 'Logo Baru')->call('saveDetails')->assertHasNoErrors()->assertSet('detailId', null);
    expect($file->fresh()->name)->toBe('Logo Baru')->and($file->fresh()->caption)->toBe('Dipertahankan');
});

it('applies selected and MIME-filtered media lifecycle actions to the exact confirmed scope', function (): void {
    cmsLibraryTheme('tabler');
    $image = $this->libraryManager->save($this->libraryActor, CmsResource::Media, ['name' => 'Logo', 'status' => 'active'], upload: UploadedFile::fake()->image('logo.png'));
    $pdf = $this->libraryManager->save($this->libraryActor, CmsResource::Media, ['name' => 'Guide', 'status' => 'active'], upload: UploadedFile::fake()->create('guide.pdf', 1, 'application/pdf'));
    Livewire::test(MediaLibrary::class)->set('type', 'pdf')->call('prepareAction', 'archive', true)
        ->assertSet('pendingCount', 1)->call('executePendingAction')->assertHasNoErrors();
    expect($pdf->fresh()->trashed())->toBeTrue()->and($image->fresh()->trashed())->toBeFalse();
    Livewire::test(MediaLibrary::class)->set('archiveStatus', 'archived')->set('selected', [$pdf->id])
        ->call('prepareAction', 'restore')->assertSet('pendingCount', 1)->call('executePendingAction')->assertHasNoErrors()->assertSet('selected', []);
    expect($pdf->fresh()->trashed())->toBeFalse();
});

it('rejects malformed library filters without losing the last valid scope', function (): void {
    cmsLibraryTheme('tabler');
    Livewire::test(MediaLibrary::class)->set('type', 'pdf')->set('perPage', 100000)
        ->assertHasErrors('filters')->assertSet('perPage', 24)->assertSet('type', 'pdf');
});

it('shows post category ancestry in tree order and retains it on filtered pages', function (): void {
    cmsLibraryTheme('tabler');
    $article = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Artikel');
    $news = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Berita');
    $tech = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Teknologi', parentId: $article->id, updateParent: true);
    $web = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Web', parentId: $tech->id, updateParent: true);
    $query = app(CmsRepositoryInterface::class)->categoryTable('post', 'active');
    expect((clone $query)->orderBy('hierarchy_order')->pluck('id')->all())->toBe([$article->id, $tech->id, $web->id, $news->id]);
    $page = (clone $query)->where('cms_categories.id', $web->id)->paginate(1);
    expect($page->first()->hierarchy_path)->toBe('Artikel › Teknologi › Web')
        ->and($page->first()->hierarchy_depth)->toBe(2);
    $table = Livewire::test(ContentTable::class, ['resource' => 'post_categories'])
        ->assertSet('sortField', 'hierarchy_order')->assertSet('sortDirection', 'asc')
        ->assertSeeHtml('cms-category-depth-1')->assertSeeHtml('cms-category-depth-2')
        ->assertSeeInOrder(['Artikel', 'Teknologi', 'Web', 'Berita']);
    $table->set('search', 'Web')->assertSee('Artikel › Teknologi › Web')->assertHasNoErrors();
    $article->delete();
    expect(app(CmsRepositoryInterface::class)->categoryTable('post', 'active')->find($tech->id)->hierarchy_path)->toBe('Artikel › Teknologi');
});

it('refreshes the mounted category table after creating and editing without a reload', function (): void {
    cmsLibraryTheme('tabler');
    $table = Livewire::test(ContentTable::class, ['resource' => 'post_categories'])
        ->assertSet('tableName', 'cms-post_categories-table')->assertDontSee('Kategori Langsung');
    $form = Livewire::test(Categories::class, ['resource' => 'post_categories'])
        ->set('name', 'Kategori Langsung')->call('save')
        ->assertDispatched('pg:eventRefresh-cms-post_categories-table');
    $table->dispatch('pg:eventRefresh-cms-post_categories-table')->assertSee('Kategori Langsung');
    $id = Category::where('code', 'kategori-langsung')->value('id');
    $form->call('editCategory', 'post_categories', $id)->set('name', 'Kategori Diperbarui')->call('save')
        ->assertDispatched('pg:eventRefresh-cms-post_categories-table');
    $table->dispatch('pg:eventRefresh-cms-post_categories-table')->assertSee('Kategori Diperbarui')->assertDontSee('Kategori Langsung');
});

it('saves sibling ordering atomically while preserving ancestry and refreshing hierarchy order', function (): void {
    cmsLibraryTheme('tabler');
    $article = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Artikel');
    $news = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Berita');
    $tech = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Teknologi', parentId: $article->id, updateParent: true);
    $health = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Kesehatan', parentId: $article->id, updateParent: true);
    $other = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::CardCategories, 'Logo');
    Livewire::test(Categories::class, ['resource' => 'post_categories'])->assertDontSee('Atur urutan');
    $state = $this->libraryManager->postCategoryOrder($this->libraryActor);
    $this->libraryManager->reorderPostCategories($this->libraryActor, [$news->id, $article->id, $tech->id, $health->id], $state['version']);
    expect($news->fresh()->position)->toBe(1)->and($article->fresh()->position)->toBe(2)
        ->and($tech->fresh()->position)->toBe(1)->and($health->fresh()->position)->toBe(2)
        ->and($tech->fresh()->parent_id)->toBe($article->id)->and($other->fresh()->position)->toBe(0)
        ->and($news->fresh()->updated_by)->toBe($this->libraryActor->id);
    $log = ActivityLog::where('action_key', 'cms.post_categories.reorder')->firstOrFail();
    expect($log->client_login_id)->toBe($this->libraryActor->id)->and($log->new_values['positions'][$news->id])->toBe(1);
    $table = Livewire::test(ContentTable::class, ['resource' => 'post_categories']);
    $table->set('sortField', 'cms_categories.name')->set('sortDirection', 'desc')
        ->call('showCategoryOrder')->assertSet('sortField', 'hierarchy_order')->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Berita', 'Artikel', 'Teknologi', 'Kesehatan']);
    expect(array_column($this->libraryManager->postCategoryOrder($this->libraryActor)['categories'], 'id'))
        ->toBe([$news->id, $article->id, $tech->id, $health->id]);
    expect($news->fresh()->position)->toBe(1);
});

it('rejects stale, duplicate, omitted and foreign category ordering without partial writes', function (string $case): void {
    cmsLibraryTheme('tabler');
    $a = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Artikel');
    $b = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Berita');
    $other = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::CardCategories, 'Logo');
    $state = $this->libraryManager->postCategoryOrder($this->libraryActor);
    $ids = match ($case) {
        'duplicate' => [$a->id, $a->id], 'omitted' => [$b->id], 'foreign' => [$b->id, $other->id],
        default => [$b->id, $a->id],
    };
    if ($case === 'stale') {
        $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Baru');
    }
    if ($case === 'reparented') {
        $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Berita', $b->id, $a->id, true);
    }
    expect(fn () => $this->libraryManager->reorderPostCategories($this->libraryActor, $ids, $state['version']))->toThrow(ValidationException::class);
    expect($a->fresh()->position)->toBe(0)->and($b->fresh()->position)->toBe(0)
        ->and(ActivityLog::where('action_key', 'cms.post_categories.reorder')->count())->toBe(0)
        ->and(ActivityLog::where('action_key', 'cms.post_categories.failed')->count())->toBeGreaterThan(0);
})->with(['duplicate', 'omitted', 'foreign', 'stale', 'reparented']);

it('denies category ordering after access is revoked', function (): void {
    cmsLibraryTheme('tabler');
    $a = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Artikel');
    $state = $this->libraryManager->postCategoryOrder($this->libraryActor);
    $component = Livewire::test(ContentTable::class, ['resource' => 'post_categories']);
    $this->libraryActor->update(['status' => 'inactive']);
    $component->call('moveCategory', $a->id, $a->id, 'before', $state['version'])->assertForbidden();
    expect($a->fresh()->position)->toBe(0)
        ->and(ActivityLog::where('action_key', 'cms.post_categories.denied')->count())->toBeGreaterThan(0);
});

it('moves a row directly and preserves hidden siblings and descendants', function (): void {
    cmsLibraryTheme('tabler');
    $article = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Artikel');
    $hidden = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Berita');
    $travel = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Wisata');
    $tech = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Teknologi', parentId: $article->id, updateParent: true);
    $state = $this->libraryManager->postCategoryOrder($this->libraryActor);
    $row = app(CmsRepositoryInterface::class)->categoryTable('post', 'active')->findOrFail($article->id);
    expect($row->category_order_version)->toBe($state['version']);
    $table = Livewire::test(ContentTable::class, ['resource' => 'post_categories'])->assertSeeHtml('data-cms-category-handle')
        ->set('search', 'Wisata')->assertDontSee('Artikel')
        ->call('moveCategory', $travel->id, $article->id, 'before', $state['version'])->assertHasNoErrors()
        ->assertDispatched('starter-toast')->assertSet('search', 'Wisata');
    expect(array_column($this->libraryManager->postCategoryOrder($this->libraryActor)['categories'], 'id'))
        ->toBe([$travel->id, $article->id, $tech->id, $hidden->id]);
    expect($tech->fresh()->parent_id)->toBe($article->id)->and($article->fresh()->code)->toBe('artikel');
    $table->set('search', '')->assertSeeInOrder(['Wisata', 'Artikel', 'Teknologi', 'Berita']);
    // A stale browser cannot overwrite the order saved by another request.
    $table->call('moveCategory', $article->id, $travel->id, 'before', $state['version'])->assertHasErrors('order');
    $newState = $this->libraryManager->postCategoryOrder($this->libraryActor);
    $table->call('moveCategory', $tech->id, $travel->id, 'after', $newState['version'])->assertHasErrors('order');
    expect($tech->fresh()->parent_id)->toBe($article->id);
});

it('keeps row dragging unavailable in archived and manually sorted tables', function (): void {
    cmsLibraryTheme('tabler');
    $a = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Artikel');
    $b = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Berita');
    $version = $this->libraryManager->postCategoryOrder($this->libraryActor)['version'];
    Livewire::test(ContentTable::class, ['resource' => 'post_categories'])->set('sortField', 'cms_categories.name')
        ->assertDontSeeHtml('data-cms-category-handle')->assertSee('Tampilkan urutan hierarki')
        ->call('moveCategory', $a->id, $b->id, 'after', $version)->assertStatus(422);
    Livewire::test(ContentTable::class, ['resource' => 'post_categories'])->set('archiveStatus', 'archived')
        ->call('moveCategory', $a->id, $b->id, 'after', $version)->assertStatus(422);
    expect($a->fresh()->position)->toBe(0);
});
