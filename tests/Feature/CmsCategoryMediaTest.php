<?php

use Aldhi88\StarterKit\Contracts\Cms\CmsReadRepositoryInterface;
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
use Aldhi88\StarterKit\Repositories\Cms\CmsReadRepository;
use Aldhi88\StarterKit\Repositories\Cms\CmsRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Support\Cms\CmsCardPresets;
use Aldhi88\StarterKit\Support\Cms\CmsPermalink;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Aldhi88\StarterKit\Themes\Starter\DashcodePowerGridTheme;
use Aldhi88\StarterKit\Themes\Starter\TablerPowerGridTheme;
use Aldhi88\StarterKit\Themes\Starter\VuexyPowerGridTheme;
use Carbon\Carbon;
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
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185233_create_starter_app_metadata_tables.php', '2026_07_27_185235_create_starter_logs_table.php', '2026_11_02_000000_add_access_aggregation_to_starter_logs.php'] as $file) {
        (require StarterPaths::path('database/migrations/starter/'.$file))->up();
    }
    foreach (glob(StarterPaths::path('installer/templates/cms/*.php')) as $file) {
        (require $file)->up();
    }
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    app()->bind(CmsReadRepositoryInterface::class, CmsReadRepository::class);
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
        ->assertSee('Judul Konten')->assertSee('Sekarang')->assertSee('Pilih Thumbnail')
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
        ->and($xpath->query('//*[@data-starter-region="publication-form"]//input[@id="cms-published_at" and @type="text"]')->length)->toBe(1)
        ->and($xpath->query('//*[@data-starter-dialog-component][@*[name()="wire:id"]]')->length)->toBe(1);
    Livewire::test(ContentForm::class, ['resource' => 'cards'])->assertSee('Nama Card')
        ->assertSee('Gambar & Identitas Visual')->assertSee('Urutan')->assertSeeHtml('id="cms-logo_id"');
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

it('links nested CMS screens to their visible sidebar entries and preserves direct create shortcuts', function (): void {
    config(['app.domain' => 'example.test']);
    Route::setRoutes(new RouteCollection);
    Route::domain('cms.example.test')->group(StarterPaths::path('routes/cms.php'));
    Route::getRoutes()->refreshNameLookups();

    foreach ([
        'cms.media.albums.index' => 'cms.media.galleries.index',
        'cms.media.galleries.create' => 'cms.media.galleries.index',
        'cms.media.galleries.edit' => 'cms.media.galleries.index',
        'cms.media.edit' => 'cms.media.index',
        'cms.media.create' => 'cms.media.index',
        'cms.posts.edit' => 'cms.posts.index',
        'cms.cards.categories.edit' => 'cms.cards.categories.index',
        'cms.menus.create' => 'cms.menus.index',
        'cms.comments.edit' => 'cms.comments.index',
    ] as $screen => $menu) {
        expect(Route::getRoutes()->getByName($screen)->getAction('starter_menu_route'))->toBe($menu);
    }
    foreach (['cms.media.index', 'cms.media.galleries.index', 'cms.posts.create', 'cms.pages.create', 'cms.cards.create'] as $direct) {
        expect(Route::getRoutes()->getByName($direct)->getAction('starter_menu_route'))->toBeNull();
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
    $table->call('moveCategory', $tech->id, $travel->id, 'after', $newState['version'])->assertHasNoErrors();
    expect($tech->fresh()->parent_id)->toBeNull();
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

it('reparents whole category branches and promotes them back to roots', function (): void {
    $a = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'A');
    $b = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'B');
    $c = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'C', parentId: $a->id, updateParent: true);
    $d = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'D', parentId: $b->id, updateParent: true);
    $version = fn () => $this->libraryManager->postCategoryOrder($this->libraryActor)['version'];
    $this->libraryManager->movePostCategory($this->libraryActor, $a->id, $d->id, 'inside', $version());
    expect($a->fresh()->parent_id)->toBe($d->id)->and($c->fresh()->parent_id)->toBe($a->id);
    $this->libraryManager->movePostCategory($this->libraryActor, $a->id, $a->id, 'root', $version());
    expect($a->fresh()->parent_id)->toBeNull()->and($c->fresh()->parent_id)->toBe($a->id);
});

it('rejects category cycles and unavailable destinations without changing hierarchy', function (): void {
    $a = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'A');
    $b = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'B', parentId: $a->id, updateParent: true);
    $version = $this->libraryManager->postCategoryOrder($this->libraryActor)['version'];
    foreach ([[$b->id, 'inside'], [$b->id, 'after'], [$a->id, 'inside'], [999999, 'inside']] as [$target, $placement]) {
        expect(fn () => $this->libraryManager->movePostCategory($this->libraryActor, $a->id, $target, $placement, $version))->toThrow(ValidationException::class);
    }
    expect($a->fresh()->parent_id)->toBeNull()->and($b->fresh()->parent_id)->toBe($a->id);
});

it('authors static pages using the focused editor in every theme', function (string $theme): void {
    cmsLibraryTheme($theme);
    $category = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PageCategories, 'Profil');
    $component = Livewire::test(ContentForm::class, ['resource' => 'pages'])
        ->assertSee('Judul Halaman')->assertSee('Pilih Thumbnail')->assertDontSee('Nama Internal')->assertDontSee('Media dan Tautan')
        ->set('form.title', 'Visi dan Misi')->set('form.body', '<p>Menjadi kampus unggul.</p>')
        ->set('form.category_id', $category->id)->set('form.status', 'published');
    $component->call('save')->assertHasNoErrors()->assertRedirect(route('cms.pages.index'));
    $page = Content::where('kind', 'page')->firstOrFail();
    expect($page->name)->toBe('Visi dan Misi')->and($page->slug)->toBe('visi-dan-misi')
        ->and($page->category_id)->toBe($category->id)->and($page->published_at)->not->toBeNull();
    Livewire::test(ContentForm::class, ['resource' => 'pages', 'contentId' => $page->id])
        ->assertSee('Salin tautan')->assertSee('/halaman/visi-dan-misi')
        ->set('form.title', 'Visi Kampus')->call('save')->assertHasNoErrors();
    expect($page->fresh()->slug)->toBe('visi-dan-misi')->and($page->fresh()->name)->toBe('Visi Kampus');
})->with(['tabler', 'vuexy', 'dashcode']);

it('validates page titles category kind and duplicate slugs while preserving legacy positions', function (): void {
    $page = $this->libraryManager->save($this->libraryActor, CmsResource::Pages, ['category_id' => Category::firstOrCreate(['kind' => 'page', 'code' => 'test-profil'], ['name' => 'Profil', 'status' => 'active'])->id, 'title' => 'Profil', 'status' => 'draft']);
    $page->update(['position' => 17]);
    $this->libraryManager->save($this->libraryActor, CmsResource::Pages, ['category_id' => Category::firstOrCreate(['kind' => 'page', 'code' => 'test-profil'], ['name' => 'Profil', 'status' => 'active'])->id, 'title' => 'Profil', 'slug' => 'profil', 'status' => 'draft'], $page->id);
    expect($page->fresh()->position)->toBe(17);
    foreach ([['title' => ''], ['title' => 'Lain', 'slug' => 'profil']] as $data) {
        expect(fn () => $this->libraryManager->save($this->libraryActor, CmsResource::Pages, $data + ['status' => 'draft']))->toThrow(ValidationException::class);
    }
    $wrong = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::PostCategories, 'Berita');
    expect(fn () => $this->libraryManager->save($this->libraryActor, CmsResource::Pages, ['title' => 'Lain', 'status' => 'draft', 'category_id' => $wrong->id]))->toThrow(ValidationException::class);
});

it('builds configurable root website permalinks without using the cms request host', function (): void {
    config(['app.url' => 'https://campus.test', 'starter.cms.permalinks.page' => '/profil/{slug}']);
    expect(CmsPermalink::for('page', 'visi-misi'))->toBe('https://campus.test/profil/visi-misi')
        ->and(CmsPermalink::for('post', 'berita'))->toBe('https://campus.test/konten/berita')
        ->and(CmsPermalink::for('page', ''))->toBeNull();
    config(['starter.cms.permalinks.page' => '//other.test/{slug}']);
    expect(CmsPermalink::for('page', 'profil'))->toBeNull();
});

it('requires a page category and creates one inline without losing editor input', function (string $theme): void {
    cmsLibraryTheme($theme);
    $this->travelTo(Carbon::parse('2026-11-04 13:45:00', 'Asia/Jakarta'));
    try {
        $form = Livewire::test(ContentForm::class, ['resource' => 'pages'])
            ->assertSeeHtml('data-cms-toolbar-media')->assertDontSee('Tanpa kategori')
            ->assertSet('form.published_at', '2026-11-04T13:45')
            ->set('form.title', 'Profil Kampus')->set('form.body', '<p>Profil kampus contoh.</p>')
            ->call('save')->assertHasErrors('form.category_id')
            ->set('newCategoryName', 'Profil Institusi')->call('createPageCategory')->assertHasNoErrors('newCategoryName')
            ->assertSet('form.title', 'Profil Kampus')->assertSet('form.body', '<p>Profil kampus contoh.</p>');
        $category = Category::where('kind', 'page')->where('name', 'Profil Institusi')->firstOrFail();
        $form->assertSet('form.category_id', $category->id)->call('save')->assertHasNoErrors();
        expect(Content::where('kind', 'page')->firstOrFail()->category_id)->toBe($category->id);
    } finally {
        $this->travelBack();
    }
})->with(['tabler', 'vuexy', 'dashcode']);

it('renders edit forms with a valid single root and permalink outside the slug input', function (string $theme, string $kind): void {
    cmsLibraryTheme($theme);
    config(['app.debug' => true]);
    $category = $this->libraryManager->saveCategoryName($this->libraryActor, $kind === 'pages' ? CmsResource::PageCategories : CmsResource::PostCategories, 'Akademik');
    $resource = CmsResource::from($kind);
    $record = $this->libraryManager->save($this->libraryActor, $resource, ['title' => 'Profil Akademik', 'status' => 'draft', 'category_id' => $category->id]);
    $form = Livewire::test(ContentForm::class, ['resource' => $kind, 'contentId' => $record->id])
        ->assertSee('Salin tautan')->assertSeeHtml('class="cms-permalink"');
    $dom = new DOMDocument;
    @$dom->loadHTML($form->html());
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//input[@id="cms-slug"]')->length)->toBe(1)
        ->and($xpath->query('//div[@class="cms-permalink"]')->length)->toBe(1);
    $form->set('form.subtitle', 'Informasi kampus')->call('save')->assertHasNoErrors();
    expect($record->fresh()->subtitle)->toBe('Informasi kampus');
    Livewire::test(ContentTable::class, ['resource' => $kind])->assertSeeHtml('cms-title-copy')->assertSee('Salin tautan');
})->with(['tabler', 'vuexy', 'dashcode'])->with(['posts', 'pages']);

it('counts assigned category entries once including drafts but excluding trash and other kinds', function (string $kind): void {
    $category = Category::create(['kind' => $kind, 'name' => 'Akademik', 'code' => 'akademik', 'status' => 'active']);
    $empty = Category::create(['kind' => $kind, 'name' => 'Kosong', 'code' => 'kosong', 'status' => 'active']);
    foreach (['draft', 'published', 'draft'] as $index => $status) {
        $entry = Content::create(['kind' => $kind, 'name' => 'Entri '.$index, 'title' => 'Entri '.$index, 'slug' => 'entri-'.$index, 'status' => $status, 'category_id' => $category->id]);
        if ($kind === 'post') {
            $entry->categories()->attach($category->id);
        }
        if ($index === 2) {
            $entry->delete();
        }
    }
    Content::create(['kind' => 'card', 'name' => 'Lain', 'slug' => 'lain', 'status' => 'draft', 'category_id' => $category->id]);
    if ($kind === 'post') {
        $additional = Content::create(['kind' => 'post', 'name' => 'Multi', 'slug' => 'multi', 'status' => 'draft']);
        $additional->categories()->attach($category->id);
    }
    $counts = app(CmsRepository::class)->categoryTable($kind, 'active')->get()->keyBy('id');
    expect((int) $counts[$category->id]->usage_count)->toBe($kind === 'post' ? 3 : 2)
        ->and((int) $counts[$empty->id]->usage_count)->toBe(0);
    foreach (['tabler', 'vuexy', 'dashcode'] as $theme) {
        cmsLibraryTheme($theme);
        Livewire::test(ContentTable::class, ['resource' => $kind === 'post' ? 'post_categories' : 'page_categories'])
            ->assertSeeHtml($kind === 'post' ? 'Jumlah<br>Konten' : 'Jumlah<br>Halaman');
    }
})->with(['post', 'page']);

it('uses matching publication status badges for content and pages', function (string $resource): void {
    cmsLibraryTheme('tabler');
    $kind = $resource === 'posts' ? 'post' : 'page';
    foreach ([['draft', null], ['published', now()->subDay()], ['published', now()->addDay()]] as $index => [$status, $date]) {
        Content::create(['kind' => $kind, 'name' => 'Status '.$index, 'title' => 'Status '.$index, 'slug' => 'status-'.$index, 'status' => $status, 'published_at' => $date]);
    }
    Livewire::test(ContentTable::class, ['resource' => $resource])
        ->assertSeeHtml('<span class="badge bg-secondary-lt"><span class="cms-status-label">Draf</span></span>')
        ->assertSeeHtml('<span class="badge bg-green-lt"><span class="cms-status-label">Terbit</span></span>')
        ->assertSeeHtml('<span class="badge bg-azure-lt"><span class="cms-status-label">Terjadwal</span></span>');
})->with(['posts', 'pages']);

it('persists every card preset and structured fields without changing order or losing hidden content', function (string $theme): void {
    cmsLibraryTheme($theme);
    $category = $this->libraryManager->saveCategoryName($this->libraryActor, CmsResource::CardCategories, 'Beranda Kampus');
    foreach (array_keys(CmsCardPresets::all()) as $preset) {
        $card = $this->libraryManager->save($this->libraryActor, CmsResource::Cards, ['name' => 'Card '.$preset, 'card_preset' => $preset, 'card_data' => ['person' => 'Ratna', 'value' => '12.500'], 'body' => '<p>Isi tersimpan</p>', 'body_format' => 'html', 'status' => 'draft', 'position' => 8, 'category_id' => $category->id]);
        expect($card->fresh()->card_preset)->toBe($preset)->and($card->fresh()->card_data['value'])->toBe('12.500')->and($card->fresh()->position)->toBe(8);
        $form = Livewire::test(ContentForm::class, ['resource' => 'cards', 'contentId' => $card->id])
            ->assertSeeHtml('data-cms-card-form')->assertSet('form.card_preset', $preset)
            ->set('form.card_preset', 'faq')->call('save')->assertHasNoErrors();
        expect($card->fresh()->card_data['person'])->toBe('Ratna')->and($card->fresh()->body)->toContain('Isi tersimpan');
    }
})->with(['tabler', 'vuexy', 'dashcode']);

it('rejects unknown card presets metadata and invalid event dates without saving', function (): void {
    foreach ([['card_preset' => 'unknown'], ['card_data' => ['untrusted' => 'value']], ['card_data' => ['starts_at' => '2026-11-10T09:00', 'ends_at' => '2026-11-09T10:00']], ['card_data' => ['person' => ['invalid']]]] as $bad) {
        expect(fn () => $this->libraryManager->save($this->libraryActor, CmsResource::Cards, $bad + ['name' => 'Invalid', 'status' => 'draft']))->toThrow(ValidationException::class);
    }
    expect(Content::where('kind', 'card')->count())->toBe(0);
});

it('keeps invalid optional editorial fields accessible and preserves card inputs after validation', function (string $theme): void {
    cmsLibraryTheme($theme);
    foreach (['posts', 'pages'] as $resource) {
        $component = Livewire::test(ContentForm::class, ['resource' => $resource])
            ->set('form.title', 'Profil Kampus')
            ->set('form.subtitle', str_repeat('a', 256))
            ->call('save')->assertHasErrors('form.subtitle');
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><html><body>'.$component->html().'</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        expect($xpath->query('//*[@data-starter-region="post-details-form"]/details[@open]')->length)->toBe(1)
            ->and($xpath->query('//*[@data-starter-region="publication-form"]//input[@type="radio"]')->length)->toBe(2);
    }
    Livewire::test(ContentForm::class, ['resource' => 'cards'])
        ->set('form.name', '')
        ->set('form.card_preset', 'profile')
        ->set('form.title', 'Dr. Ratna Pratiwi')
        ->set('form.card_data.value', '12.500')
        ->call('save')->assertHasErrors('form.name')
        ->assertSet('form.title', 'Dr. Ratna Pratiwi')
        ->assertSet('form.card_data.value', '12.500')
        ->assertSeeHtml('data-cms-preset-option="profile"');
})->with(['tabler', 'vuexy', 'dashcode']);

it('stores repeatable entries in one Card and preserves ordering and legacy values on edit', function (string $theme): void {
    cmsLibraryTheme($theme);
    foreach (array_keys(CmsCardPresets::collectionLabels()) as $preset) {
        $items = [
            ['key' => 'second', 'title' => 'Pendidikan dan Riset', 'subtitle' => 'Bidang Akademik', 'body' => 'Keterangan lengkap', 'starts_at' => '2026-11-10T08:30', 'ends_at' => '2026-11-10T09:00'],
            ['key' => 'first', 'title' => 'Kemahasiswaan', 'link_url' => '/kemahasiswaan'],
        ];
        $card = $this->libraryManager->save($this->libraryActor, CmsResource::Cards, [
            'name' => 'Kelompok '.$preset, 'card_preset' => $preset, 'status' => 'published',
            'body' => 'Isi tunggal sebelumnya', 'card_data' => ['mode' => 'collection', 'items' => $items],
        ]);
        expect($card->card_data['items'])->toBe($items)->and($card->category_id)->toBeNull();
        $component = Livewire::test(ContentForm::class, ['resource' => 'cards', 'contentId' => $card->id])
            ->assertSet('form.card_data.mode', 'collection')
            ->assertSet('form.card_data.items.0.key', 'second')
            ->set('form.card_data.items', array_reverse($items))
            ->set('form.card_data.mode', 'single')->call('save')->assertHasNoErrors();
        expect($card->fresh()->card_data['items'][0]['key'])->toBe('first')
            ->and($card->fresh()->body)->toContain('Isi tunggal sebelumnya');
    }
})->with(['tabler', 'vuexy', 'dashcode']);

it('rejects malformed repeatable items without partially changing a saved Card', function (): void {
    $base = ['name' => 'Tim Pimpinan', 'status' => 'published', 'card_preset' => 'profile', 'card_data' => ['mode' => 'collection', 'items' => [['key' => 'rektor', 'title' => 'Ratna Pratiwi']]]];
    $card = $this->libraryManager->save($this->libraryActor, CmsResource::Cards, $base);
    foreach ([
        [],
        [['key' => 'empty', 'title' => '']],
        [['key' => 'same', 'title' => 'A'], ['key' => 'same', 'title' => 'B']],
        [['key' => 'bad', 'title' => 'A', 'link_url' => 'javascript:alert(1)']],
        [['key' => 'bad', 'title' => 'A', 'image_id' => 99999]],
        [['key' => 'bad', 'title' => 'A', 'untrusted' => 'value']],
        [['key' => 'bad', 'title' => 'A', 'starts_at' => '2026-11-10T10:00', 'ends_at' => '2026-11-09T10:00']],
        array_map(fn ($i) => ['key' => 'item-'.$i, 'title' => 'Anggota '.$i], range(1, 51)),
    ] as $items) {
        $bad = $base;
        $bad['card_data']['items'] = $items;
        expect(fn () => $this->libraryManager->save($this->libraryActor, CmsResource::Cards, $bad, $card->id))->toThrow(ValidationException::class);
        expect($card->fresh()->card_data['items'])->toBe($base['card_data']['items']);
    }
});

it('protects images referenced by Card items and releases them only when removed', function (): void {
    Storage::fake('local');
    $this->libraryManager->uploadMediaBatch($this->libraryActor, [UploadedFile::fake()->image('rektor.png')]);
    $image = Media::firstOrFail();
    $data = ['name' => 'Tim', 'status' => 'draft', 'card_preset' => 'profile', 'card_data' => ['mode' => 'collection', 'items' => [['key' => 'rektor', 'title' => 'Ratna', 'image_id' => $image->id]]]];
    $card = $this->libraryManager->save($this->libraryActor, CmsResource::Cards, $data);
    expect($card->embeddedMedia()->pluck('cms_media.id')->all())->toBe([$image->id]);
    expect(fn () => $this->libraryManager->lifecycle($this->libraryActor, CmsResource::Media, 'archive', [$image->id]))->toThrow(ValidationException::class);
    $this->libraryManager->save($this->libraryActor, CmsResource::Cards, ['name' => 'Tim baru', 'status' => 'draft'], $card->id);
    expect($card->fresh()->embeddedMedia()->pluck('cms_media.id')->all())->toBe([$image->id]);
    $data['card_data']['items'] = [];
    $this->libraryManager->save($this->libraryActor, CmsResource::Cards, $data, $card->id);
    expect($this->libraryManager->lifecycle($this->libraryActor, CmsResource::Media, 'archive', [$image->id]))->toBe(1);
});
