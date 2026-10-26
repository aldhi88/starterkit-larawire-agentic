<?php

use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Livewire\Cms\ContentForm;
use Aldhi88\StarterKit\Livewire\Cms\ContentIndex;
use Aldhi88\StarterKit\Livewire\Cms\ContentTable;
use Aldhi88\StarterKit\Livewire\Cms\Dashboard;
use Aldhi88\StarterKit\Livewire\Cms\MediaPicker;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Gallery;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Cms\Menu;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\App as StarterApp;
use Aldhi88\StarterKit\Models\Starter\AppMod;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Cms\CmsRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Cms\CmsPostActions;
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
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('u', 32))]);
    app()->register(LivewireServiceProvider::class);
    app()->register(PowerGridServiceProvider::class);
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185233_create_starter_app_metadata_tables.php', '2026_07_27_185235_create_starter_logs_table.php'] as $migration) {
        (require StarterPaths::path('database/migrations/starter/'.$migration))->up();
    }
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000000_create_cms_tables.php'))->up();
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000001_add_cms_rich_content.php'))->up();
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000002_add_post_category_hierarchy.php'))->up();
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    app()->bind(CmsRepositoryInterface::class, CmsRepository::class);
    app()->scoped(AuditLogService::class);
    $role = ClientRole::create(['code' => 'superuser', 'name' => 'Superuser', 'is_system' => true]);
    $this->cmsActor = ClientLogin::create(['client_role_id' => $role->id, 'status' => 'active', 'name' => 'Editor', 'username' => 'cms-ui', 'email' => 'cms-ui@example.test']);
    $this->actingAs($this->cmsActor);
    $app = StarterApp::create(['name' => 'CMS', 'subdomain' => 'cms']);
    foreach (['dashboard', 'posts', 'pages', 'cards', 'media', 'menus', 'comments'] as $module) {
        AppMod::create(['app_id' => $app->id, 'code' => $module, 'name' => $module]);
    }
    foreach (CmsResource::cases() as $resource) {
        Route::get('/'.$resource->value, fn () => 'index')->name($resource->routePrefix().'.index');
        Route::get('/'.$resource->value.'/create', fn () => 'create')->name($resource->routePrefix().'.create');
        Route::get('/'.$resource->value.'/{contentId}/edit', fn () => 'edit')->name($resource->routePrefix().'.edit');
    }
    Route::get('/', fn () => 'landing')->name('landing');
    Route::get('/cms/media/{publicId}', fn () => 'media')->name('starter.cms.media');
    Route::get('/cms/documentation', fn () => 'documentation')->name('starter.cms.documentation');
    Livewire::component('cms.table', ContentTable::class);
    Livewire::component('cms.media-picker', MediaPicker::class);
    Storage::fake('local');
    $this->cmsManager = app(CmsManager::class);
});

function cmsWorkspaceTheme(string $theme): void
{
    config(['starter.theme' => $theme, 'livewire-powergrid.persist_driver' => 'session', 'livewire-powergrid.filter' => 'inline', 'livewire-powergrid.theme' => match ($theme) {
        'dashcode' => DashcodePowerGridTheme::class, 'vuexy' => VuexyPowerGridTheme::class, default => TablerPowerGridTheme::class,
    }]);
    View::addLocation(StarterPaths::path('resources/themes/'.$theme.'/views'));
}

it('renders every CMS editor with its native theme and dedicated form actions', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    foreach (CmsResource::cases() as $resource) {
        Livewire::test(ContentForm::class, ['resource' => $resource->value])
            ->assertOk()->assertSee('Simpan '.$resource->singular())->assertSee('Kembali')->assertDontSee('CMS /')
            ->assertSeeHtml('data-starter-region="content-form"');
    }
})->with(['tabler', 'vuexy', 'dashcode']);

it('provides a visual editor and integrated media picker for every content type', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    foreach (['posts', 'pages', 'cards'] as $resource) {
        $parent = Livewire::test(ContentForm::class, ['resource' => $resource])
            ->assertSeeHtml('data-cms-editor')
            ->assertDontSeeHtml('data-cms-image-choose')
            ->assertSeeHtml('data-cms-media-base="'.route('starter.cms.media', ['publicId' => str_repeat('0', 26)]).'"')
            ->assertSet('form.body_format', 'html')
            ->assertSeeHtml('data-starter-dialog-component');
        if ($theme === 'tabler' && $resource === 'posts') {
            $parent->assertSeeHtml('data-cms-toolbar-media')->assertDontSeeHtml('cms-editor-heading');
        } else {
            $parent->assertSee('Tambahkan Media');
        }
        $parent->call('openMediaPicker')->assertDispatched('cms-open-media-picker');
        Livewire::test(MediaPicker::class, ['ownerId' => 'cms-editor-owner', 'resource' => $resource])
            ->call('openMediaPicker', 'cms-editor-owner')->assertSet('mediaPickerOpen', true)
            ->assertSee('Tambahkan Media')->assertSee('Unggah ke Pustaka Media')->assertSee('Jenis File')
            ->call('closeMediaPicker')->assertSet('mediaPickerOpen', false);
    }
})->with(['tabler', 'vuexy', 'dashcode']);

it('shows a full-width media empty state and resets picker filters', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    Livewire::test(MediaPicker::class, ['ownerId' => 'post-editor', 'resource' => 'posts'])
        ->call('openMediaPicker', 'post-editor', 'image')
        ->assertSeeHtml('class="cms-picker-empty"')
        ->assertSee('Pilih File')
        ->assertSee('Belum ada gambar')
        ->assertSee('Tampilkan semua media')
        ->assertDontSee('Halaman 1 dari 1')
        ->set('pickerSearch', 'tidak-ada')
        ->assertSee('Media tidak ditemukan')
        ->call('resetPickerView')
        ->assertSet('pickerSearch', '')
        ->assertSet('pickerType', 'all')
        ->assertSee('Pustaka media masih kosong');
})->with(['tabler', 'vuexy', 'dashcode']);

it('uploads editor media into the shared library and inserts its safe persistent URL', function (): void {
    cmsWorkspaceTheme('tabler');
    $component = Livewire::test(ContentForm::class, ['resource' => 'posts'])
        ->set('editorUploads', [UploadedFile::fake()->image('story-photo.png')])
        ->call('finishEditorUploads')->assertHasNoErrors()
        ->assertDispatched('cms-editor-insert')->assertSet('editorUploads', []);
    $media = Media::firstOrFail();
    Livewire::test(MediaPicker::class, ['ownerId' => 'cms-editor-owner', 'resource' => 'posts'])
        ->call('openMediaPicker', 'cms-editor-owner')
        ->set('selectedMediaIds', [(string) $media->id])->call('insertSelectedMedia')
        ->assertHasNoErrors()->assertDispatched('cms-editor-insert')
        ->assertDispatched('cms-editor-picker-closed')->assertSet('mediaPickerOpen', false);
    expect($media->created_by)->toBe($this->cmsActor->id);
});

it('uploads and selects multiple files inside the modal without changing the parent draft', function (): void {
    cmsWorkspaceTheme('tabler');
    $parent = Livewire::test(ContentForm::class, ['resource' => 'posts'])
        ->set('form.title', 'Draf yang sedang diketik')
        ->set('form.body', '<p>Kalimat belum disimpan</p>');
    $picker = Livewire::test(MediaPicker::class, ['ownerId' => 'post-editor', 'resource' => 'posts'])
        ->call('openMediaPicker', 'another-editor')->assertSet('mediaPickerOpen', false)
        ->call('openMediaPicker', 'post-editor')->assertSet('mediaPickerOpen', true)
        ->set('editorUploads', [UploadedFile::fake()->image('one.png'), UploadedFile::fake()->image('two.png')])
        ->call('finishEditorUploads')->assertHasNoErrors()->assertSet('editorUploads', []);
    $ids = Media::query()->orderBy('id')->pluck('id')->all();
    expect($ids)->toHaveCount(2);
    $picker->assertSet('selectedMediaIds', $ids)
        ->call('insertSelectedMedia')->assertHasNoErrors()
        ->assertDispatched('cms-editor-insert')->assertDispatched('cms-editor-picker-closed')
        ->assertSet('mediaPickerOpen', false);
    expect($parent->get('form.title'))->toBe('Draf yang sedang diketik')
        ->and($parent->get('form.body'))->toBe('<p>Kalimat belum disimpan</p>');
});

it('selects one active image for a thumbnail and rejects a PDF before dispatch', function (): void {
    cmsWorkspaceTheme('tabler');
    $image = $this->cmsManager->save($this->cmsActor, CmsResource::Media, ['name' => 'Cover', 'status' => 'active'], upload: UploadedFile::fake()->image('cover.png'));
    $pdf = $this->cmsManager->save($this->cmsActor, CmsResource::Media, ['name' => 'Document', 'status' => 'active'], upload: UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'));
    $picker = Livewire::test(MediaPicker::class, ['ownerId' => 'thumbnail-owner', 'resource' => 'posts'])
        ->call('openMediaPicker', 'thumbnail-owner', 'all', 'thumbnail')
        ->assertSet('pickerType', 'image')
        ->set('selectedMediaIds', [(string) $pdf->id])->call('insertSelectedMedia')
        ->assertHasErrors(['selectedMediaIds'])
        ->assertNotDispatched('cms-thumbnail-selected');
    $picker->set('selectedMediaIds', [(string) $image->id])->call('insertSelectedMedia')
        ->assertHasNoErrors()->assertDispatched('cms-thumbnail-selected')
        ->assertDispatched('cms-editor-picker-closed');
});

it('reorders and nests menu items without losing their labels or allowing cycles', function (): void {
    cmsWorkspaceTheme('tabler');
    $component = Livewire::test(ContentForm::class, ['resource' => 'menus'])
        ->set('form.items', [
            ['key' => 'home', 'label' => 'Beranda', 'url' => '/', 'parent_key' => ''],
            ['key' => 'about', 'label' => 'Tentang', 'url' => '/tentang', 'parent_key' => ''],
            ['key' => 'team', 'label' => 'Tim', 'url' => '/tim', 'parent_key' => ''],
        ])->call('reorderMenuItems', [
            ['key' => 'about', 'parent_key' => null],
            ['key' => 'team', 'parent_key' => 'about'],
            ['key' => 'home', 'parent_key' => null],
        ])->assertSet('form.items.0.label', 'Tentang')->assertSet('form.items.1.parent_key', 'about')
        ->assertSeeHtml('data-cms-menu-root')->assertSeeHtml('data-cms-menu-handle');
    $component->call('reorderMenuItems', [
        ['key' => 'about', 'parent_key' => 'team'],
        ['key' => 'team', 'parent_key' => 'about'],
        ['key' => 'home', 'parent_key' => null],
    ])->assertHasErrors(['form.items'])->assertSet('form.items.0.parent_key', '');
});

it('offers only valid menu parents and preserves a rejected fourth level until corrected', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    $items = [
        ['key' => 'root', 'label' => 'Induk', 'url' => '/', 'parent_key' => ''],
        ['key' => 'child', 'label' => 'Anak', 'url' => '/anak', 'parent_key' => 'root'],
        ['key' => 'grandchild', 'label' => 'Cucu dengan nama menu yang panjang', 'url' => '/cucu', 'parent_key' => 'child'],
        ['key' => 'branch', 'label' => 'Cabang', 'url' => '/cabang', 'parent_key' => ''],
        ['key' => 'branch-child', 'label' => 'Anak Cabang', 'url' => '/anak-cabang', 'parent_key' => 'branch'],
        ['key' => 'spare', 'label' => 'Menu Lain', 'url' => '/lain', 'parent_key' => ''],
    ];
    $component = Livewire::test(ContentForm::class, ['resource' => 'menus'])
        ->set('form.name', 'Navigasi')->set('form.code', 'navigasi')->set('form.items', $items);
    $document = new DOMDocument;
    @$document->loadHTML($component->html());
    $xpath = new DOMXPath($document);
    $options = fn (string $key): array => array_map(fn (DOMElement $option): string => $option->getAttribute('value'),
        iterator_to_array($xpath->query('//select[@id="cms-menu-parent-'.$key.'"]/option')));
    expect($options('root'))->toBe([''])
        ->and($options('spare'))->not->toContain('grandchild', 'spare')
        ->and($options('branch'))->toContain('root')->not->toContain('child', 'branch-child', 'grandchild')
        ->and($options('grandchild'))->toContain('root', 'child', 'branch-child');
    $component->assertSeeHtml('title="Cucu dengan nama menu yang panjang"')
        ->call('changeMenuParent', 5, 'grandchild')->assertHasErrors(['form.items'])
        ->assertSet('form.items.5.parent_key', 'grandchild')->assertSee('Induk tidak valid:')
        ->call('save')->assertHasErrors(['form.items'])->assertSet('form.items.5.parent_key', 'grandchild');
    expect(Menu::count())->toBe(0);
    $component->call('changeMenuParent', 5, 'child')->assertHasNoErrors(['form.items'])
        ->call('save')->assertHasNoErrors();
    expect(collect(Menu::firstOrFail()->items)->firstWhere('key', 'spare')['parent_key'])->toBe('child');
})->with(['tabler', 'vuexy', 'dashcode']);

it('keeps invalid field values visible and saves universal cards from the dashboard', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    $component = Livewire::test(ContentForm::class, ['resource' => 'cards'])
        ->set('form.name', '')->set('form.subtitle', 'Tetap tersimpan di form')
        ->set('form.body', '<p><strong>Konten tetap tersimpan</strong></p>')->call('openMediaPicker')
        ->assertDispatched('cms-open-media-picker')
        ->call('save')->assertHasErrors(['form.name'])->assertSet('form.subtitle', 'Tetap tersimpan di form')
        ->assertSet('form.body', '<p><strong>Konten tetap tersimpan</strong></p>');
    $component->set('form.name', 'Klien Contoh')->set('form.title', '')->call('save')
        ->assertHasNoErrors()->assertRedirect(route('cms.cards.index'));
    $card = Content::where('kind', 'card')->firstOrFail();
    expect($card->name)->toBe('Klien Contoh')->and($card->title)->toBeNull()->and($card->created_by)->toBe($this->cmsActor->id);
})->with(['tabler', 'vuexy', 'dashcode']);

it('orders gallery photos and menu items while preserving their content mapping', function (): void {
    cmsWorkspaceTheme('tabler');
    $first = $this->cmsManager->save($this->cmsActor, CmsResource::Media, ['name' => 'Foto Satu', 'status' => 'active'], upload: UploadedFile::fake()->image('one.png'));
    $second = $this->cmsManager->save($this->cmsActor, CmsResource::Media, ['name' => 'Foto Dua', 'status' => 'active'], upload: UploadedFile::fake()->image('two.png'));
    Livewire::test(ContentForm::class, ['resource' => 'galleries'])
        ->set('form.name', 'Kegiatan')->set('form.slug', 'kegiatan')->set('selectedMediaId', (string) $first->id)->call('addGalleryMedia')
        ->set('selectedMediaId', (string) $second->id)->call('addGalleryMedia')->call('moveGalleryMedia', 1, -1)
        ->assertSet('form.media_ids', [$second->id, $first->id])->call('save')->assertHasNoErrors();
    expect(Gallery::firstOrFail()->media()->orderByPivot('position')->pluck('cms_media.id')->all())->toBe([$second->id, $first->id]);

    $category = $this->cmsManager->save($this->cmsActor, CmsResource::MenuCategories, ['name' => 'Utama', 'code' => 'main', 'status' => 'active']);
    Livewire::test(ContentForm::class, ['resource' => 'menus'])
        ->set('form.name', 'Navigasi')->set('form.code', 'navigasi')->set('form.category_id', $category->id)
        ->set('form.items', [
            ['key' => 'parent', 'label' => 'Profil', 'url' => '/profil', 'parent_key' => ''],
            ['key' => 'child', 'label' => 'Tentang', 'url' => '/tentang', 'parent_key' => 'parent'],
        ])->call('moveMenuItem', 1, -1)->call('save')->assertHasNoErrors();
    expect(Menu::firstOrFail()->items[0]['key'])->toBe('child')->and(Menu::firstOrFail()->items[0]['parent_key'])->toBe('parent');
});

it('renders native PowerGrid lists with one search and actions before the data columns', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    $this->cmsManager->save($this->cmsActor, CmsResource::Cards, ['name' => 'Logo Perusahaan', 'status' => 'draft']);
    Livewire::test(ContentTable::class, ['resource' => 'cards'])->assertOk()->assertSee('Logo Perusahaan')
        ->assertSeeHtml('aria-label="Aksi"')->assertSeeHtml('placeholder="Cari nama…"')
        ->assertSeeHtml('wire:model.live="archiveStatus"');
})->with(['tabler', 'vuexy', 'dashcode']);

it('lists posts by publication time without exposing legacy manual order', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'CMS Post Review QA Earlier', 'status' => 'draft', 'published_at' => '2026-10-24 10:00']);
    $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'CMS Post Review QA Later', 'status' => 'draft', 'published_at' => '2026-10-25 10:00']);
    $table = Livewire::test(ContentTable::class, ['resource' => 'posts'])
        ->assertOk()->assertSet('sortField', 'cms_contents.published_at')
        ->assertSee('Waktu Terbit')->assertSee('Judul')->assertDontSee('Urutan')
        ->assertSeeInOrder(['CMS Post Review QA Later', 'CMS Post Review QA Earlier']);
    $table->set('sortField', 'cms_contents.position')->assertHasErrors(['tableFilters']);
})->with(['tabler', 'vuexy', 'dashcode']);

it('shows and saves post publication time in WIB across themes', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    config(['app.timezone' => 'UTC']);
    $component = Livewire::test(ContentForm::class, ['resource' => 'posts'])
        ->assertSee('Waktu Terbit (WIB)')
        ->assertSee('Draf belum tampil di website.')
        ->set('form.title', 'Jadwal WIB');
    $component->set($theme === 'tabler' ? 'publishedAtDisplay' : 'form.published_at', $theme === 'tabler' ? '25/10/2026 17:30' : '2026-10-25T17:30')
        ->call('save')->assertHasNoErrors();

    $post = Content::where('kind', 'post')->firstOrFail();
    expect($post->getRawOriginal('published_at'))->toBe('2026-10-25 10:30:00');
    Livewire::test(ContentForm::class, ['resource' => 'posts', 'contentId' => $post->id])
        ->assertSet($theme === 'tabler' ? 'publishedAtDisplay' : 'form.published_at', $theme === 'tabler' ? '25/10/2026 17:30' : '2026-10-25T17:30');
    Livewire::test(ContentTable::class, ['resource' => 'posts'])
        ->assertSee('Waktu Terbit (WIB)')
        ->assertSee('25/10/2026 17:30');
})->with(['tabler', 'vuexy', 'dashcode']);

it('requires explicit permanent deletion confirmation and applies filtered archive scope', function (): void {
    cmsWorkspaceTheme('tabler');
    $draft = $this->cmsManager->save($this->cmsActor, CmsResource::Cards, ['name' => 'Draft Card', 'status' => 'draft']);
    $published = $this->cmsManager->save($this->cmsActor, CmsResource::Cards, ['name' => 'Published Card', 'status' => 'published']);
    Livewire::test(ContentTable::class, ['resource' => 'cards'])
        ->set('filters.select.cms_contents.status', 'draft')->call('prepareBulkAction', 'archive', true)
        ->assertSet('pendingCount', 1)->call('executePendingAction')->assertHasNoErrors();
    expect($draft->fresh()->trashed())->toBeTrue()->and($published->fresh()->trashed())->toBeFalse();
    Livewire::test(ContentTable::class, ['resource' => 'cards'])
        ->set('archiveStatus', 'archived')->call('prepareRowAction', 'delete_permanently', $draft->id)
        ->call('executePendingAction')->assertHasErrors(['lifecycle'])
        ->set('confirmation', 'HAPUS')->call('executePendingAction')->assertHasNoErrors();
    expect(Content::withTrashed()->find($draft->id))->toBeNull();
});

it('uses the real PowerGrid date event state for inclusive filtered actions', function (): void {
    cmsWorkspaceTheme('tabler');
    $inside = $this->cmsManager->save($this->cmsActor, CmsResource::Cards, ['name' => 'Inside Range', 'status' => 'draft']);
    $outside = $this->cmsManager->save($this->cmsActor, CmsResource::Cards, ['name' => 'Outside Range', 'status' => 'draft']);
    Content::whereKey($inside->id)->update(['updated_at' => '2026-10-25 23:59:59']);
    Content::whereKey($outside->id)->update(['updated_at' => '2026-10-26 00:00:00']);
    Livewire::test(ContentTable::class, ['resource' => 'cards'])
        ->call('datePickerChanged', ['2026-10-25', '2026-10-25'], 'cms_contents.updated_at', '2026-10-25 to 2026-10-25', 'Diperbarui', 'date', 'UTC', 'Y-m-d')
        ->assertSee('Inside Range')->assertDontSee('Outside Range')
        ->call('prepareBulkAction', 'archive', true)->assertSet('pendingCount', 1)
        ->assertSet('pendingFilters.date_from', '2026-10-25')->assertSet('pendingFilters.date_to', '2026-10-25')
        ->call('executePendingAction')->assertHasNoErrors();
    expect($inside->fresh()->trashed())->toBeTrue()->and($outside->fresh()->trashed())->toBeFalse();
});

it('shows comment content and post context and preserves its filtered moderation scope', function (): void {
    cmsWorkspaceTheme('tabler');
    $post = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Pengumuman', 'status' => 'published']);
    $this->cmsManager->save($this->cmsActor, CmsResource::Comments, ['content_id' => $post->id, 'author_name' => 'Pengunjung', 'author_email' => 'reader@example.test', 'body' => 'Komentar yang perlu ditinjau', 'status' => 'pending']);
    Livewire::test(ContentTable::class, ['resource' => 'comments'])
        ->assertSee('Pengunjung')->assertSee('Pengumuman')->assertSee('Komentar yang perlu ditinjau')
        ->set('filters.input_text.cms_comments.body', 'perlu ditinjau')
        ->set('filters.select.cms_comments.content_id', (string) $post->id)
        ->call('prepareBulkAction', 'archive', true)->assertSet('pendingCount', 1)
        ->assertSet('pendingFilters.body', 'perlu ditinjau')->assertSet('pendingFilters.content_id', (string) $post->id);
});

it('audits one completed browse state without counting unchanged hydration cycles', function (): void {
    cmsWorkspaceTheme('tabler');
    $component = Livewire::test(ContentIndex::class, ['resource' => 'cards'])->assertOk();
    expect(ActivityLog::where('action_key', 'cms.cards.browse')->count())->toBe(1);
    $component->call('$refresh')->assertOk();
    expect(ActivityLog::where('action_key', 'cms.cards.browse')->count())->toBe(1);

    $table = Livewire::test(ContentTable::class, ['resource' => 'cards'])->assertOk();
    expect(ActivityLog::where('action_key', 'cms.cards.browse')->count())->toBe(2);
    $table->set('search', 'Klien')->assertOk();
    expect(ActivityLog::where('action_key', 'cms.cards.browse')->count())->toBe(3);
    $table->call('$refresh')->assertOk();
    expect(ActivityLog::where('action_key', 'cms.cards.browse')->count())->toBe(3);
});

it('escapes editor supplied names and comment text in raw PowerGrid cells', function (): void {
    cmsWorkspaceTheme('tabler');
    $payload = '<img src=x onerror=alert(1)>';
    $post = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Safe Post', 'status' => 'published']);
    $this->cmsManager->save($this->cmsActor, CmsResource::Comments, ['content_id' => $post->id, 'author_name' => $payload, 'author_email' => 'reader@example.test', 'body' => $payload, 'status' => 'pending']);
    $html = Livewire::test(ContentTable::class, ['resource' => 'comments'])->assertOk()->html();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    expect((new DOMXPath($document))->query('//*[@onerror]')->length)->toBe(0)
        ->and($document->textContent)->toContain($payload);
});

it('bounds visible and bulk text search equally and discards unknown column filters', function (): void {
    cmsWorkspaceTheme('tabler');
    $this->cmsManager->save($this->cmsActor, CmsResource::Cards, ['name' => str_repeat('A', 100).' Card', 'status' => 'draft']);
    Livewire::test(ContentTable::class, ['resource' => 'cards'])
        ->set('search', str_repeat('A', 150))->assertSet('search', str_repeat('A', 100))
        ->set('filters.input_text.cms_contents.name', str_repeat('A', 150))
        ->assertSet('filters.input_text.cms_contents.name', str_repeat('A', 100))
        ->set('filters.select.nonexistent.secret', 'forged')->assertOk()
        ->assertSet('filters.select.nonexistent.secret', null)
        ->call('prepareBulkAction', 'archive', true)->assertSet('pendingCount', 1)
        ->assertSet('pendingFilters.search', str_repeat('A', 100))->assertSet('pendingFilters.name', str_repeat('A', 100));
});

it('rejects malformed table enum number date sort and unbounded page size before querying', function (string $property, mixed $value): void {
    cmsWorkspaceTheme('tabler');
    Livewire::test(ContentTable::class, ['resource' => 'cards'])->set($property, $value)->assertHasErrors(['tableFilters']);
    expect(ActivityLog::where('action_key', 'cms.cards.browse_failed')->where('client_login_id', $this->cmsActor->id)
        ->where('metadata->outcome', 'validation_failed')->count())->toBe(1);
})->with([
    ['filters.select.cms_contents.status', 'unknown-status'],
    ['filters.number.cms_contents.position.start', 'not-a-number'],
    ['filters.date.cms_contents.updated_at', ['start' => 'not-a-date', 'end' => 'not-a-date']],
    ['sortField', 'starter_client_logins.password'],
    ['setUp.footer.perPage', 0],
]);

it('keeps the Dashcode CMS icon composition native and excludes unshipped link utilities', function (): void {
    $root = StarterPaths::path('resources/themes/dashcode/views/starter/cms');
    expect(file_get_contents($root.'/dashboard.blade.php'))->toContain('rounded-full bg-primary-100 text-primary-500')
        ->and(file_get_contents($root.'/form.blade.php'))->not->toContain('link-primary');
});

it('renders the dashboard shortcuts and public usage guide in every theme', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    Livewire::test(Dashboard::class)->assertOk()->assertSee('Beranda')->assertSee('Panduan Penggunaan CMS');
    $html = View::make('starter.cms.documentation', ['clientLogoUrl' => null, 'clientName' => 'CMS Test'])->render();
    expect($html)->toContain('beranda-carousel', 'beranda-klien', 'CmsReader', 'menuTree');
})->with(['tabler', 'vuexy', 'dashcode']);

it('keeps the CMS guide usable when the developer replaces the named landing route', function (string $theme): void {
    cmsWorkspaceTheme($theme);
    Route::setRoutes(new RouteCollection);
    expect(View::make('starter.cms.documentation', ['clientLogoUrl' => null, 'clientName' => 'CMS Test'])->render())
        ->toContain('Panduan Penggunaan CMS', 'Kembali ke Beranda', 'href="'.url('/').'"');
})->with(['tabler', 'vuexy', 'dashcode']);

it('validates empty Tabler posts on the server without browser validation', function (): void {
    cmsWorkspaceTheme('tabler');
    Livewire::test(ContentForm::class, ['resource' => 'posts'])
        ->assertSeeHtml('<form novalidate')
        ->call('save')->assertHasErrors(['form.title'])
        ->assertSeeHtml('invalid-feedback d-block')
        ->assertSee('Perubahan belum tersimpan.');
    expect(Content::where('kind', 'post')->count())->toBe(0);
});

it('publishes and drafts posts from the table without replacing content or existing publication dates', function (): void {
    cmsWorkspaceTheme('tabler');
    $scheduled = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Scheduled story', 'status' => 'published', 'published_at' => now()->addDay()->format('Y-m-d H:i'), 'body' => 'Keep this body']);
    $published = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Older story', 'status' => 'published', 'published_at' => now()->subDay()->format('Y-m-d H:i')]);
    $original = $published->getRawOriginal('published_at');
    $table = Livewire::test(ContentTable::class, ['resource' => 'posts'])
        ->assertSee('Terjadwal')->assertSee('Terbitkan sekarang')
        ->set('checkboxValues', [(string) $scheduled->id, (string) $published->id])
        ->call('preparePostAction', 'publish_now')->assertSet('postScheduled', 1)
        ->call('executePostAction')->assertHasNoErrors()->assertSet('postAction', null);
    expect($scheduled->refresh()->status)->toBe('published')
        ->and($scheduled->body)->toBe('Keep this body')
        ->and($scheduled->published_at->isFuture())->toBeFalse()
        ->and($published->refresh()->getRawOriginal('published_at'))->toBe($original);
    $table->call('preparePostAction', 'draft', $scheduled->id)->call('executePostAction')->assertHasNoErrors()->assertSee('Draf');
    expect($scheduled->refresh()->status)->toBe('draft')
        ->and(ActivityLog::query()->where('event', 'quick_publish_now')->exists())->toBeTrue();
});

it('adds replaces removes and clears categories in bulk while preserving the primary category and other fields', function (): void {
    cmsWorkspaceTheme('tabler');
    $first = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Artikel', 'status' => 'active']);
    $second = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Teknologi', 'status' => 'active', 'parent_id' => $first->id]);
    $third = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Berita', 'status' => 'active']);
    $one = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'One', 'status' => 'draft', 'category_ids' => [$second->id, $first->id]]);
    $two = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Two', 'status' => 'draft']);
    $actions = app(CmsPostActions::class);
    foreach (['add', 'replace', 'remove', 'clear'] as $mode) {
        $scope = $actions->prepare($this->cmsActor, [$one->id, $two->id]);
        $actions->apply($this->cmsActor, 'categories', $scope['ids'], $scope['version'], $mode, [$third->id]);
        $one->refresh();
        $two->refresh();
        if ($mode === 'add') {
            expect($one->category_id)->toBe($second->id)->and($one->categories->modelKeys())->toHaveCount(3)->and($two->category_id)->toBe($third->id);
        } elseif ($mode === 'replace') {
            expect($one->categories->modelKeys())->toBe([$third->id])->and($two->categories->modelKeys())->toBe([$third->id]);
        } else {
            expect($one->category_id)->toBeNull()->and($one->categories)->toHaveCount(0)->and($two->categories)->toHaveCount(0);
        }
        expect($one->status)->toBe('draft')->and($one->title)->toBe('One');
    }
});

it('keeps invalid category choices in the dialog and allows an empty selection to clear categories', function (): void {
    cmsWorkspaceTheme('tabler');
    $post = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Category action', 'status' => 'draft']);
    Livewire::test(ContentTable::class, ['resource' => 'posts'])
        ->call('preparePostAction', 'categories', $post->id)->assertSet('postCategoryIds', [])
        ->assertDontSee('Perubahan kategori')->assertDontSee('Ganti seluruh kategori')
        ->assertSeeHtml('data-cms-post-action')->assertSeeHtml('novalidate')
        ->set('postCategoryIds', [99999])->call('executePostAction')->assertHasErrors('postCategoryIds')->assertSet('postAction', 'categories')
        ->set('postCategoryIds', [])->call('executePostAction')->assertHasNoErrors()->assertSet('postAction', null);
});

it('rejects stale or archived post selections without partially updating other rows', function (): void {
    $one = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'First', 'status' => 'draft']);
    $two = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Second', 'status' => 'draft']);
    $actions = app(CmsPostActions::class);
    $scope = $actions->prepare($this->cmsActor, [$one->id, $two->id]);
    $two->update(['title' => 'Changed by another editor']);
    expect(fn () => $actions->apply($this->cmsActor, 'publish_now', $scope['ids'], $scope['version']))->toThrow(ValidationException::class);
    expect($one->refresh()->status)->toBe('draft');
    $scope = $actions->prepare($this->cmsActor, [$one->id, $two->id]);
    $two->delete();
    expect(fn () => $actions->apply($this->cmsActor, 'publish_now', $scope['ids'], $scope['version']))->toThrow(ValidationException::class);
    expect($one->refresh()->status)->toBe('draft');
});

it('matches secondary post categories and rejects a changed filtered selection even when its count is unchanged', function (): void {
    cmsWorkspaceTheme('tabler');
    $first = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'News', 'status' => 'active']);
    $second = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Science', 'status' => 'active']);
    $one = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Matching post', 'status' => 'draft', 'category_ids' => [$first->id, $second->id]]);
    $two = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Other post', 'status' => 'published']);
    Livewire::test(ContentTable::class, ['resource' => 'posts'])->set('filters.select.cms_contents.category_id', $second->id)->assertSee('Matching post')->assertDontSee('Other post');
    $actions = app(CmsPostActions::class);
    $categoryScope = $actions->prepare($this->cmsActor, [], ['category_id' => $second->id]);
    expect($categoryScope['ids'])->toBe([$one->id]);
    $scope = $actions->prepare($this->cmsActor, [], ['status' => 'draft']);
    $one->update(['status' => 'published']);
    $two->update(['status' => 'draft']);
    expect(fn () => $actions->apply($this->cmsActor, 'publish_now', $scope['ids'], $scope['version'], filters: ['status' => 'draft']))->toThrow(ValidationException::class);
    expect($two->refresh()->status)->toBe('draft');
});

it('rejects invalid taxonomy and rolls back publishing if a later row is invalid', function (): void {
    $one = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Valid story', 'status' => 'draft']);
    $two = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Invalid legacy story', 'status' => 'draft']);
    $two->update(['title' => '']);
    $actions = app(CmsPostActions::class);
    $scope = $actions->prepare($this->cmsActor, [$one->id, $two->id]);
    expect(fn () => $actions->apply($this->cmsActor, 'publish_now', $scope['ids'], $scope['version']))->toThrow(ValidationException::class);
    expect($one->refresh()->status)->toBe('draft')->and($one->published_at)->toBeNull();
    $wrongKind = $this->cmsManager->save($this->cmsActor, CmsResource::CardCategories, ['name' => 'Card only', 'status' => 'active']);
    expect(fn () => $actions->apply($this->cmsActor, 'categories', $scope['ids'], $scope['version'], 'replace', [$wrongKind->id]))->toThrow(ValidationException::class);
    expect($one->refresh()->category_id)->toBeNull();
    $category = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Inactive', 'status' => 'inactive']);
    expect(fn () => $actions->apply($this->cmsActor, 'categories', $scope['ids'], $scope['version'], 'add', [$category->id]))->toThrow(ValidationException::class);
});

it('rechecks access and bounds the selection for post quick actions', function (): void {
    $post = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Permission check', 'status' => 'draft']);
    $page = $this->cmsManager->save($this->cmsActor, CmsResource::Pages, ['name' => 'Not a post', 'status' => 'draft']);
    $actions = app(CmsPostActions::class);
    expect(fn () => $actions->prepare($this->cmsActor, [$page->id]))->toThrow(ValidationException::class);
    expect(fn () => $actions->prepare($this->cmsActor, range(1, 1001)))->toThrow(ValidationException::class);
    $scope = $actions->prepare($this->cmsActor, [$post->id]);
    $this->cmsActor->update(['status' => 'inactive']);
    expect(fn () => $actions->apply($this->cmsActor, 'publish_now', $scope['ids'], $scope['version']))->toThrow(HttpException::class);
    expect($post->refresh()->status)->toBe('draft');
});

it('starts bulk category selection empty and replaces different assignments with one hierarchical selection', function (): void {
    cmsWorkspaceTheme('tabler');
    $parent = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Artikel', 'status' => 'active']);
    $child = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Teknologi', 'status' => 'active', 'parent_id' => $parent->id]);
    $other = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Berita', 'status' => 'active']);
    $one = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'First categories', 'status' => 'draft', 'category_ids' => [$parent->id]]);
    $two = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Second categories', 'status' => 'draft', 'category_ids' => [$other->id]]);
    Livewire::test(ContentTable::class, ['resource' => 'posts'])
        ->set('checkboxValues', [$one->id, $two->id])->call('preparePostAction', 'categories')->assertSet('postCategoryIds', [])
        ->set('postCategoryIds', [$child->id])->call('executePostAction')->assertHasNoErrors();
    expect($one->refresh()->categories->modelKeys())->toEqualCanonicalizing([$parent->id, $child->id])
        ->and($two->refresh()->categories->modelKeys())->toEqualCanonicalizing([$parent->id, $child->id]);
    Livewire::test(ContentTable::class, ['resource' => 'posts'])
        ->call('preparePostAction', 'categories', $one->id)->assertSet('postCategoryIds', [$child->id, $parent->id])
        ->set('postCategoryIds', [])->call('executePostAction')->assertHasNoErrors();
    expect($one->refresh()->categories)->toHaveCount(0)->and($one->category_id)->toBeNull();
});

it('includes ancestors when editing or saving Tabler post categories without selecting siblings', function (): void {
    cmsWorkspaceTheme('tabler');
    $parent = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Root', 'status' => 'active']);
    $child = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Child', 'status' => 'active', 'parent_id' => $parent->id]);
    $sibling = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Sibling', 'status' => 'active', 'parent_id' => $parent->id]);
    $post = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Legacy child selection', 'status' => 'draft', 'category_ids' => [$child->id]]);
    Livewire::test(ContentForm::class, ['resource' => 'posts', 'contentId' => $post->id])
        ->assertSet('form.category_ids', [$child->id, $parent->id])
        ->set('form.category_ids', [$child->id])->call('save')->assertHasNoErrors();
    expect($post->refresh()->categories->modelKeys())->toEqualCanonicalizing([$child->id, $parent->id]);
});

it('normalizes legacy parent-only category selections without changing stored data on open', function (): void {
    cmsWorkspaceTheme('tabler');
    $parent = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Artikel', 'status' => 'active']);
    $child = $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Teknologi', 'status' => 'active', 'parent_id' => $parent->id]);
    $post = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Legacy parent', 'status' => 'draft', 'category_ids' => [$parent->id]]);
    Livewire::test(ContentTable::class, ['resource' => 'posts'])->call('preparePostAction', 'categories', $post->id)
        ->assertSet('postCategoryIds', [$parent->id, $child->id])->call('cancelPostAction');
    expect($post->refresh()->categories->modelKeys())->toBe([$parent->id]);
    Livewire::test(ContentForm::class, ['resource' => 'posts', 'contentId' => $post->id])
        ->assertSet('form.category_ids', [$parent->id, $child->id]);
});

it('renders the compact category picker with a standard Tabler modal backdrop', function (): void {
    cmsWorkspaceTheme('tabler');
    $this->cmsManager->save($this->cmsActor, CmsResource::PostCategories, ['name' => 'Artikel', 'status' => 'active']);
    $post = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Category tree', 'status' => 'draft']);
    Livewire::test(ContentTable::class, ['resource' => 'posts'])->call('preparePostAction', 'categories', $post->id)
        ->assertSeeHtml('data-cms-post-backdrop')->assertSeeHtml('modal-backdrop fade show')
        ->assertSeeHtml('cms-category-tree')->assertSeeHtml('StarterCms.categoryPicker');
});

it('provides category editing directly from an active post category cell', function () {
    cmsWorkspaceTheme('tabler');
    $post = $this->cmsManager->save($this->cmsActor, CmsResource::Posts, ['title' => 'Quick category cell', 'status' => 'draft']);
    Livewire::test(ContentTable::class, ['resource' => 'posts'])
        ->assertSeeHtml("preparePostAction('categories', {$post->id})")
        ->call('preparePostAction', 'categories', $post->id)
        ->assertSet('postIds', [$post->id])->assertSet('postAction', 'categories');
});

it('loads the Tabler CMS runtime in the head and the Bootstrap singleton once', function () {
    $layout = file_get_contents(__DIR__.'/../../resources/themes/tabler/views/starter/templates/layouts/app.blade.php');
    expect(strpos($layout, 'cms.runtime-assets'))->toBeLessThan(strpos($layout, '</head>'));
    expect($layout)->toMatch('/tabler\.min\.js[^\n]+data-navigate-once/');
    $assets = file_get_contents(__DIR__.'/../../resources/views/cms/runtime-assets.blade.php');
    expect(substr_count($assets, 'data-navigate-track'))->toBe(2);
});
