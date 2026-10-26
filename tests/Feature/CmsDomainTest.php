<?php

use Aldhi88\StarterKit\Contracts\Cms\CmsReadRepositoryInterface;
use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Models\Cms\Category;
use Aldhi88\StarterKit\Models\Cms\Comment;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Gallery;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Cms\Menu;
use Aldhi88\StarterKit\Models\Cms\MenuCategory;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\App as StarterApp;
use Aldhi88\StarterKit\Models\Starter\AppMod;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Cms\CmsReadRepository;
use Aldhi88\StarterKit\Repositories\Cms\CmsRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Services\Cms\CmsCommentSubmission;
use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Cms\CmsReader;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('c', 32))]);
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185233_create_starter_app_metadata_tables.php', '2026_07_27_185235_create_starter_logs_table.php'] as $migration) {
        (require StarterPaths::path('database/migrations/starter/'.$migration))->up();
    }
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000000_create_cms_tables.php'))->up();
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000001_add_cms_rich_content.php'))->up();
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000002_add_post_category_hierarchy.php'))->up();
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    app()->bind(CmsRepositoryInterface::class, CmsRepository::class);
    app()->bind(CmsReadRepositoryInterface::class, CmsReadRepository::class);
    app()->scoped(AuditLogService::class);
    app()->scoped(CmsManager::class);
    foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
        foreach ([Content::class, Category::class, Media::class, Gallery::class, Menu::class, MenuCategory::class, Comment::class] as $model) {
            Event::listen('eloquent.'.$event.': '.$model, fn ($record) => app(AuditLogService::class)->recordModelEvent($event, $record));
        }
    }
    $role = ClientRole::create(['code' => 'superuser', 'name' => 'Superuser', 'is_system' => true]);
    $this->actor = ClientLogin::create(['client_role_id' => $role->id, 'status' => 'active', 'name' => 'CMS Editor', 'username' => 'cms-editor', 'email' => 'cms@example.test']);
    $this->actingAs($this->actor);
    $app = StarterApp::create(['name' => 'CMS', 'subdomain' => 'cms']);
    foreach (['dashboard', 'posts', 'pages', 'cards', 'media', 'menus', 'comments'] as $module) {
        AppMod::create(['app_id' => $app->id, 'code' => $module, 'name' => $module]);
    }
    Route::get('/cms/media/{publicId}', fn () => 'media')->name('starter.cms.media');
    Storage::fake('local');
    $this->cms = app(CmsManager::class);
    $this->reader = app(CmsReader::class);
});

function cmsData(array $override = []): array
{
    return ['name' => 'Contoh', 'title' => $override['name'] ?? 'Contoh', 'status' => 'draft', 'position' => 0, ...$override];
}

function cmsImage(string $name = 'gambar.png'): UploadedFile
{
    return UploadedFile::fake()->image($name, 120, 80);
}

it('requires an editorial title for posts without accepting the legacy internal name', function (mixed $title): void {
    try {
        $this->cms->save($this->actor, CmsResource::Posts, ['name' => 'Legacy internal name', 'title' => $title, 'status' => 'draft']);
        $this->fail('A post title must be required.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('title')->not->toHaveKey('name');
    }
    expect(Content::where('kind', 'post')->count())->toBe(0);
})->with([null, '', '   ', 'invalid array' => [['invalid']]]);

it('derives the post management name from its title and ignores manual order input', function (): void {
    $post = $this->cms->save($this->actor, CmsResource::Posts, ['title' => '  Berita Hari Ini  ', 'name' => 'Ignored internal name', 'position' => -300, 'status' => 'draft']);
    expect($post->name)->toBe('Berita Hari Ini')->and($post->title)->toBe('Berita Hari Ini')->and($post->slug)->toBe('berita-hari-ini')->and($post->position)->toBe(0);
    $post->update(['position' => 42]);
    $updated = $this->cms->save($this->actor, CmsResource::Posts, ['title' => 'Judul Diperbarui', 'slug' => $post->slug, 'name' => 'Ignored again', 'position' => 999, 'status' => 'draft'], $post->id);
    expect($updated->name)->toBe('Judul Diperbarui')->and($updated->position)->toBe(42)->and($updated->slug)->toBe('berita-hari-ini');
});

it('defaults a new draft post date to local now and preserves the publication fallback', function (): void {
    $timezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Jakarta');
    $this->travelTo(Carbon::parse('2026-10-25 17:26:40', 'Asia/Jakarta'));
    try {
        $editor = $this->cms->editor($this->actor, CmsResource::Posts);
        expect($editor['data']['published_at'])->toBe('2026-10-25T17:26')->and($editor['data']['status'])->toBe('draft');
        expect($this->cms->editor($this->actor, CmsResource::Pages)['data']['published_at'])->toBeNull();
        $draft = $this->cms->save($this->actor, CmsResource::Posts, ['title' => 'Draft without date', 'status' => 'draft', 'published_at' => null]);
        expect($this->cms->editor($this->actor, CmsResource::Posts, $draft->id)['data']['published_at'])->toBeNull();
        $published = $this->cms->save($this->actor, CmsResource::Posts, ['title' => 'Published without date', 'status' => 'published', 'published_at' => '']);
        expect($published->published_at->format('Y-m-d H:i:s'))->toBe('2026-10-25 17:26:40');
    } finally {
        $this->travelBack();
        date_default_timezone_set($timezone);
    }
});

it('orders public posts by publication time rather than legacy position with stable ties', function (): void {
    $older = $this->cms->save($this->actor, CmsResource::Posts, ['title' => 'Older news', 'status' => 'published', 'published_at' => now()->subHours(2)]);
    $latest = $this->cms->save($this->actor, CmsResource::Posts, ['title' => 'Latest news', 'status' => 'published', 'published_at' => now()->subHour()]);
    $tied = $this->cms->save($this->actor, CmsResource::Posts, ['title' => 'Latest tied news', 'status' => 'published', 'published_at' => $latest->published_at]);
    $older->update(['position' => 0]);
    $latest->update(['position' => 900]);
    $tied->update(['position' => 999]);
    expect($this->reader->posts()->pluck('id')->all())->toBe([$tied->id, $latest->id, $older->id]);
});

it('treats a cleared optional position as zero for every ordered CMS resource', function (string $resourceName, mixed $position): void {
    $resource = CmsResource::from($resourceName);
    $record = $this->cms->save($this->actor, $resource, cmsData([
        'name' => 'Without ordering',
        'status' => array_key_first($resource->statusOptions()),
        'position' => $position,
    ]), upload: $resource === CmsResource::Media ? cmsImage() : null);
    expect($record->getAttribute('position'))->toBe(0);
})->with([
    'posts', 'post_categories', 'pages', 'page_categories', 'cards',
    'card_categories', 'media', 'galleries', 'menus', 'menu_categories',
])->with([null, '']);

it('uses readable CMS field names in validation messages', function (): void {
    try {
        $this->cms->save($this->actor, CmsResource::Posts, cmsData(['category_id' => 'invalid']));
        $this->fail('An invalid category must be rejected.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['category_id'][0])->toContain('kategori')->not->toContain('category id', 'category_id');
    }
    try {
        $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => '']));
        $this->fail('The internal name must be required.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['name'][0])->toContain('nama internal');
    }
});

it('gives every CMS table physical indexed nullable actor foreign keys', function (): void {
    foreach (['cms_contents', 'cms_categories', 'cms_media', 'cms_content_media', 'cms_galleries', 'cms_gallery_media', 'cms_menus', 'cms_menu_categories', 'cms_comments'] as $table) {
        expect(Schema::hasColumns($table, ['created_by', 'updated_by']))->toBeTrue();
        foreach (['created_by', 'updated_by'] as $column) {
            expect(collect(Schema::getIndexes($table))->contains(fn ($index) => in_array($column, $index['columns'], true)))->toBeTrue()
                ->and(collect(Schema::getForeignKeys($table))->contains(fn ($foreign) => $foreign['foreign_table'] === 'starter_client_logins' && in_array($column, $foreign['columns'], true)))->toBeTrue();
        }
    }
});

it('stores universal cards with optional visible fields and immutable creator ownership', function (): void {
    $category = $this->cms->save($this->actor, CmsResource::CardCategories, cmsData(['name' => 'Logo Klien', 'code' => 'beranda-klien', 'status' => 'active']));
    $logo = $this->cms->save($this->actor, CmsResource::Media, cmsData(['name' => 'Logo', 'status' => 'active']), upload: cmsImage());
    $card = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'PT Contoh', 'title' => '', 'category_id' => $category->id, 'logo_id' => $logo->id, 'created_by' => 999, 'updated_by' => 999]));
    expect($card->getAttribute('title'))->toBeNull()->and($card->getAttribute('slug'))->toBeNull()
        ->and($card->getAttribute('created_by'))->toBe($this->actor->id)->and($card->getAttribute('updated_by'))->toBe($this->actor->id);
    $second = ClientLogin::create(['client_role_id' => $this->actor->client_role_id, 'status' => 'active', 'name' => 'Second', 'username' => 'second', 'email' => 'second@example.test']);
    $this->actingAs($second);
    $this->cms->save($second, CmsResource::Cards, cmsData(['name' => 'Klien Baru', 'body' => str_repeat('teks ', 1000)]), $card->id);
    expect($card->fresh()->getAttribute('created_by'))->toBe($this->actor->id)->and($card->fresh()->getAttribute('updated_by'))->toBe($second->id)
        ->and($card->fresh()->creator->id)->toBe($this->actor->id)->and($card->fresh()->updater->id)->toBe($second->id);
});

it('keeps content kinds isolated in lookups taxonomy validation and slugs', function (): void {
    $category = $this->cms->save($this->actor, CmsResource::PostCategories, cmsData(['name' => 'Berita', 'code' => 'umum', 'status' => 'active']));
    $pageCategory = $this->cms->save($this->actor, CmsResource::PageCategories, cmsData(['name' => 'Umum', 'code' => 'umum', 'status' => 'active']));
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Berita', 'category_id' => $category->id, 'slug' => 'contoh']));
    $page = $this->cms->save($this->actor, CmsResource::Pages, cmsData(['name' => 'Tentang', 'category_id' => $pageCategory->id, 'slug' => 'contoh']));
    expect(fn () => $this->cms->editor($this->actor, CmsResource::Pages, $post->id))->toThrow(HttpException::class)
        ->and(fn () => $this->cms->save($this->actor, CmsResource::Cards, cmsData(['category_id' => $category->id])))->toThrow(ValidationException::class)
        ->and($this->cms->tableQuery($this->actor, CmsResource::Pages)->pluck('cms_contents.id')->all())->toBe([$page->id]);
});

it('requires the owning parent module and immediately respects revoked access', function (): void {
    $role = ClientRole::create(['code' => 'writer', 'name' => 'Writer']);
    $module = AppMod::where('code', 'posts')->firstOrFail();
    $role->mods()->attach($module);
    $writer = ClientLogin::create(['client_role_id' => $role->id, 'status' => 'active', 'name' => 'Writer', 'username' => 'writer', 'email' => 'writer@example.test']);
    $this->actingAs($writer);
    $this->cms->save($writer, CmsResource::PostCategories, cmsData(['status' => 'active']));
    expect(fn () => $this->cms->save($writer, CmsResource::Pages, cmsData()))->toThrow(HttpException::class);
    $role->mods()->detach();
    expect(fn () => $this->cms->save($writer, CmsResource::Posts, cmsData()))->toThrow(HttpException::class)
        ->and(ActivityLog::where('action_key', 'cms.posts.denied')->where('client_login_id', $writer->id)->count())->toBe(1);
});

it('safely uploads files hides private paths and rejects unsafe image references', function (): void {
    $image = $this->cms->save($this->actor, CmsResource::Media, cmsData(['status' => 'active']), upload: cmsImage('logo.png'));
    Storage::disk('local')->assertExists($image->getAttribute('path'));
    expect($image->toArray())->not->toHaveKeys(['path', 'original_name', 'created_by', 'updated_by']);
    $pdf = $this->cms->save($this->actor, CmsResource::Media, cmsData(['name' => 'PDF', 'status' => 'active']), upload: UploadedFile::fake()->createWithContent('manual.pdf', "%PDF-1.4\n1 0 obj <<>> endobj\n%%EOF"));
    expect(fn () => $this->cms->save($this->actor, CmsResource::Cards, cmsData(['image_id' => $pdf->id])))->toThrow(ValidationException::class)
        ->and(fn () => $this->cms->save($this->actor, CmsResource::Media, cmsData(['status' => 'active']), upload: UploadedFile::fake()->createWithContent('unsafe.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')))->toThrow(ValidationException::class);
});

it('orders gallery images and stamps pivot ownership without accepting PDF files', function (): void {
    $first = $this->cms->save($this->actor, CmsResource::Media, cmsData(['name' => 'First', 'status' => 'active']), upload: cmsImage('first.png'));
    $second = $this->cms->save($this->actor, CmsResource::Media, cmsData(['status' => 'active', 'name' => 'Second', 'status' => 'active']), upload: cmsImage('second.png'));
    $gallery = $this->cms->save($this->actor, CmsResource::Galleries, cmsData(['name' => 'Kegiatan', 'status' => 'published', 'media_ids' => [$second->id, $first->id]]));
    expect($this->reader->gallery('kegiatan')->media->modelKeys())->toBe([$second->id, $first->id])
        ->and(DB::table('cms_gallery_media')->where('gallery_id', $gallery->id)->pluck('created_by')->all())->toBe([$this->actor->id, $this->actor->id]);
    expect($this->reader->gallery('kegiatan')->toJson())
        ->not->toContain('created_by', 'updated_by', 'cms/media/');
    expect(fn () => $this->cms->lifecycle($this->actor, CmsResource::Media, 'archive', [$first->id]))->toThrow(ValidationException::class);
});

it('preserves a referenced image when a replacement would turn it into a PDF', function (): void {
    $media = $this->cms->save($this->actor, CmsResource::Media, cmsData(['name' => 'Client logo', 'status' => 'active']), upload: cmsImage());
    $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Client', 'logo_id' => $media->id]));
    $originalPath = $media->getAttribute('path');
    $files = Storage::disk('local')->allFiles();
    expect(fn () => $this->cms->save(
        $this->actor, CmsResource::Media, cmsData(['name' => 'Client logo', 'status' => 'active']),
        $media->id, UploadedFile::fake()->create('replacement.pdf', 1, 'application/pdf'),
    ))->toThrow(ValidationException::class);
    expect($media->fresh()->getAttribute('path'))->toBe($originalPath)
        ->and($media->fresh()->getAttribute('mime_type'))->toBe('image/png')
        ->and(Storage::disk('local')->allFiles())->toBe($files);
});

it('only publishes elapsed nonarchived content with active matching categories and eager media', function (): void {
    $category = $this->cms->save($this->actor, CmsResource::PostCategories, cmsData(['name' => 'Berita', 'code' => 'berita', 'status' => 'active']));
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Terbit', 'category_id' => $category->id, 'status' => 'published', 'published_at' => now()->subMinute()->toDateTimeString()]));
    $future = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Nanti', 'status' => 'published', 'published_at' => now()->addDay()->toDateTimeString()]));
    $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Draf']));
    expect($this->reader->posts('berita')->total())->toBe(1)->and($this->reader->post('nanti'))->toBeNull()
        ->and($this->reader->post('terbit')->relationLoaded('image'))->toBeTrue();
    $this->cms->save($this->actor, CmsResource::PostCategories, cmsData(['name' => 'Berita', 'code' => 'berita', 'status' => 'inactive']), $category->id);
    expect($this->reader->post('terbit'))->toBeNull()->and($this->reader->postCategories())->toHaveCount(0);
});

it('stores multiple hierarchical post categories and includes descendants in public category results', function (): void {
    $news = $this->cms->saveCategoryName($this->actor, CmsResource::PostCategories, 'Berita');
    $local = $this->cms->saveCategoryName($this->actor, CmsResource::PostCategories, 'Daerah', parentId: $news->id, updateParent: true);
    $article = $this->cms->saveCategoryName($this->actor, CmsResource::PostCategories, 'Artikel');
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData([
        'title' => 'Lintas Kategori', 'slug' => 'lintas-kategori', 'category_ids' => [$local->id, $article->id],
        'status' => 'published', 'published_at' => now()->subMinute()->toDateTimeString(),
    ]));

    expect($local->fresh()->parent_id)->toBe($news->id)
        ->and($post->fresh()->categories()->pluck('cms_categories.id')->map(fn ($id) => (int) $id)->sort()->values()->all())->toBe(collect([$local->id, $article->id])->sort()->values()->all())
        ->and($this->reader->posts('berita')->total())->toBe(1)
        ->and($this->reader->posts('artikel')->total())->toBe(1)
        ->and($this->reader->post('lintas-kategori')->categories)->toHaveCount(2);

    expect(fn () => $this->cms->saveCategoryName($this->actor, CmsResource::PostCategories, 'Berita', $news->id, $local->id, true))
        ->toThrow(ValidationException::class);
    $this->cms->save($this->actor, CmsResource::Posts, cmsData(['title' => 'Lintas Kategori', 'slug' => 'lintas-kategori', 'category_ids' => [$article->id]]), $post->id);
    expect($post->fresh()->categories()->pluck('cms_categories.id')->all())->toBe([$article->id]);
});

it('provides ordered reusable cards and bounds frontend pagination', function (): void {
    $category = $this->cms->save($this->actor, CmsResource::CardCategories, cmsData(['name' => 'Layanan', 'code' => 'beranda-layanan', 'status' => 'active']));
    $last = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Akhir', 'category_id' => $category->id, 'status' => 'published', 'position' => 2]));
    $first = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Awal', 'category_id' => $category->id, 'status' => 'published', 'position' => 1]));
    expect($this->reader->cards('beranda-layanan')->modelKeys())->toBe([$first->id, $last->id])
        ->and($this->reader->posts(null, 500)->perPage())->toBe(100)
        ->and(fn () => $this->reader->categories('unknown'))->toThrow(InvalidArgumentException::class);
});

it('validates nested menu keys cycles depth URLs and exposes a frontend tree', function (): void {
    $this->cms->installDefaults();
    $this->cms->installDefaults();
    expect(MenuCategory::count())->toBe(3)->and(MenuCategory::whereNotNull('created_by')->count())->toBe(0);
    $category = MenuCategory::where('code', 'main')->firstOrFail();
    $items = [['key' => 'parent', 'label' => 'Tentang', 'url' => '/tentang', 'parent_key' => null], ['key' => 'child', 'label' => 'Tim', 'url' => '/tim', 'parent_key' => 'parent']];
    $menu = $this->cms->save($this->actor, CmsResource::Menus, cmsData(['name' => 'Utama', 'status' => 'active', 'code' => 'utama', 'category_id' => $category->id, 'items' => $items]));
    expect($this->reader->menuTree($this->reader->menu('utama'))[0]['children'][0]['label'])->toBe('Tim')
        ->and($this->reader->menus('main')->modelKeys())->toBe([$menu->id]);
    $items[0]['parent_key'] = 'child';
    expect(fn () => $this->cms->save($this->actor, CmsResource::Menus, cmsData(['status' => 'active', 'items' => $items])))->toThrow(ValidationException::class);
    expect(fn () => $this->cms->save($this->actor, CmsResource::Menus, cmsData(['status' => 'active', 'items' => [['key' => 'a', 'label' => 'X', 'url' => 'javascript:alert(1)']]])))->toThrow(ValidationException::class);
});

it('keeps approved comments private until their post is public and never exposes email', function (): void {
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Posting']));
    $comment = $this->cms->save($this->actor, CmsResource::Comments, ['content_id' => $post->id, 'author_name' => 'Pembaca', 'author_email' => 'private@example.test', 'body' => 'Komentar', 'status' => 'approved']);
    expect($this->reader->comments('posting')->total())->toBe(0);
    $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Posting', 'status' => 'published']), $post->id);
    expect($this->reader->comments('posting')->total())->toBe(1)->and($this->reader->comments('posting')->first()->toArray())->not->toHaveKey('author_email');
    $this->cms->lifecycle($this->actor, CmsResource::Posts, 'archive', [$post->id]);
    expect($this->reader->comments('posting')->total())->toBe(0);
    $this->cms->lifecycle($this->actor, CmsResource::Posts, 'delete_permanently', [$post->id]);
    expect(Comment::withTrashed()->find($comment->id))->toBeNull();
});

it('prevalidates a selected lifecycle action and only permanently deletes archived records', function (): void {
    $one = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Satu']));
    $two = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Dua']));
    expect(fn () => $this->cms->lifecycle($this->actor, CmsResource::Cards, 'delete_permanently', [$one->id]))->toThrow(ValidationException::class);
    $this->cms->lifecycle($this->actor, CmsResource::Cards, 'archive', [$two->id]);
    expect(fn () => $this->cms->lifecycle($this->actor, CmsResource::Cards, 'archive', [$one->id, $two->id]))->toThrow(ValidationException::class)
        ->and($one->fresh()->trashed())->toBeFalse();
    $this->cms->lifecycle($this->actor, CmsResource::Cards, 'restore', [$two->id]);
    expect($two->fresh()->trashed())->toBeFalse();
});

it('rebuilds exactly the same bounded scope for count and filtered lifecycle actions', function (): void {
    $first = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Target A', 'status' => 'published', 'position' => 3]));
    $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Target B', 'status' => 'draft', 'position' => 3]));
    $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Other', 'status' => 'published', 'position' => 3]));
    $scope = ['search' => 'Target', 'status' => 'published', 'position_min' => 0, 'position_max' => 1, 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString()];
    expect($this->cms->countFiltered($this->actor, CmsResource::Posts, $scope))->toBe(1)
        ->and($this->cms->lifecycleFiltered($this->actor, CmsResource::Posts, 'archive', $scope))->toBe(1)
        ->and($first->fresh()->trashed())->toBeTrue()->and(Content::count())->toBe(2);
});

it('keeps successful audits atomic and records safe failures when persistence rolls back', function (): void {
    Event::listen('eloquent.created: '.Media::class, fn () => throw new RuntimeException('Failed persistence'));
    expect(fn () => $this->cms->save($this->actor, CmsResource::Media, cmsData(['status' => 'active']), upload: cmsImage()))->toThrow(RuntimeException::class);
    expect(Media::count())->toBe(0)->and(Storage::disk('local')->allFiles('cms/media'))->toBe([])
        ->and(ActivityLog::where('action_key', 'cms.media.create')->count())->toBe(0)
        ->and(ActivityLog::where('action_key', 'cms.media.failed')->where('client_login_id', $this->actor->id)->count())->toBe(1);
});

it('audits authenticated reads and mutations without storing unrestricted content or email', function (): void {
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['body' => 'Highly private article body', 'excerpt' => 'Private summary']));
    $this->cms->browse($this->actor, CmsResource::Posts, ['search' => 'private input', 'page' => 2]);
    $logs = ActivityLog::where('client_login_id', $this->actor->id)->get();
    expect($logs)->not->toBeEmpty()->and($logs->toJson())->not->toContain('Highly private article body')->not->toContain('Private summary')->not->toContain('private input')
        ->and($logs->pluck('action_key')->all())->toContain('cms.posts.create', 'cms.posts.browse');
});

it('preserves selected media outside the bounded search window and prevents referenced media deletion', function (): void {
    $media = $this->cms->save($this->actor, CmsResource::Media, cmsData(['name' => 'Original', 'status' => 'active']), upload: cmsImage());
    for ($i = 0; $i < 101; $i++) {
        Media::create(['public_id' => (string) Str::ulid(), 'name' => 'New '.$i, 'path' => 'cms/media/'.$i.'.png', 'original_name' => 'file.png', 'mime_type' => 'image/png', 'size' => 12, 'status' => 'active']);
    }
    $card = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['image_id' => $media->id]));
    expect(array_column($this->cms->editor($this->actor, CmsResource::Cards, $card->id)['media'], 'id'))->toContain($media->id)
        ->and(fn () => $this->cms->lifecycle($this->actor, CmsResource::Media, 'archive', [$media->id]))->toThrow(ValidationException::class);
});

it('renders stored Markdown with unsafe HTML and links removed', function (): void {
    $html = $this->reader->renderBody("**Tebal**\n\n<script>alert(1)</script>\n\n[bad](javascript:alert(1))");
    expect($html)->toContain('<strong>Tebal</strong>')->not->toContain('<script>')->not->toContain('href="javascript:');
});

it('accepts pending plain text comments on public posts with server-derived actors only', function (): void {
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Publik', 'status' => 'published']));
    $submission = app(CmsCommentSubmission::class);
    $comment = $submission->submit('publik', ' Pembaca ', 'person@example.test', '<b>Halo</b>');
    expect($comment->getAttribute('status'))->toBe('pending')->and($comment->getAttribute('body'))->toBe('Halo')
        ->and($comment->getAttribute('created_by'))->toBe($this->actor->id)->and($comment->getAttribute('updated_by'))->toBe($this->actor->id)
        ->and(ActivityLog::where('action_key', 'cms.comments.submit')->where('client_login_id', $this->actor->id)->count())->toBeGreaterThan(0);
    auth()->logout();
    $guest = $submission->submit('publik', 'Tamu', null, 'Komentar tamu');
    expect($guest->getAttribute('created_by'))->toBeNull()->and($guest->getAttribute('updated_by'))->toBeNull()
        ->and($this->reader->comments('publik')->total())->toBe(0);
});

it('rejects submissions to drafts scheduled archived and nonpost content', function (): void {
    $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Draf']));
    $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Terjadwal', 'status' => 'published', 'published_at' => now()->addDay()->toDateTimeString()]));
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Arsip', 'status' => 'published']));
    $this->cms->lifecycle($this->actor, CmsResource::Posts, 'archive', [$post->id]);
    $this->cms->save($this->actor, CmsResource::Pages, cmsData(['name' => 'Halaman', 'status' => 'published']));
    $submission = app(CmsCommentSubmission::class);
    foreach (['draf', 'terjadwal', 'arsip', 'halaman'] as $slug) {
        expect(fn () => $submission->submit($slug, 'Name', null, 'Body'))->toThrow(HttpException::class);
    }
    expect(Comment::count())->toBe(0);
});

it('retains historical CMS rows when an actor is removed', function (): void {
    Schema::enableForeignKeyConstraints();
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData());
    $this->actor->forceDelete();
    expect($post->fresh()->getAttribute('created_by'))->toBeNull()->and($post->fresh()->getAttribute('updated_by'))->toBeNull();
});

it('audits failed lifecycle validation and filtered confirmation reads', function (): void {
    $this->cms->save($this->actor, CmsResource::Cards, cmsData());
    expect($this->cms->countFiltered($this->actor, CmsResource::Cards))->toBe(1);
    expect(fn () => $this->cms->lifecycle($this->actor, CmsResource::Cards, 'unknown', []))->toThrow(ValidationException::class)
        ->and(ActivityLog::where('action_key', 'cms.cards.failed')->count())->toBe(1)
        ->and(ActivityLog::where('action_key', 'cms.cards.scope_count')->count())->toBe(1);
});

it('keeps universal Card slugs optional while enforcing explicit slug uniqueness', function (): void {
    $first = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Klien yang sama']));
    $second = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Klien yang sama', 'slug' => '']));
    $nonLatin = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => '你好 🌟']));
    $punctuation = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => '!!!']));
    expect($first->getAttribute('slug'))->toBeNull()->and($second->getAttribute('slug'))->toBeNull()
        ->and($nonLatin->getAttribute('slug'))->toBeNull()->and($punctuation->getAttribute('slug'))->toBeNull();
    $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Pemetaan', 'slug' => 'card-key']));
    expect(fn () => $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Lain', 'slug' => 'card-key'])))->toThrow(ValidationException::class);
});

it('rejects filtered action count drift inside its transaction without changing records', function (): void {
    $one = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Target A']));
    $confirmedCount = $this->cms->countFiltered($this->actor, CmsResource::Cards, ['search' => 'Target']);
    $two = $this->cms->save($this->actor, CmsResource::Cards, cmsData(['name' => 'Target B']));
    expect(fn () => $this->cms->lifecycleFiltered($this->actor, CmsResource::Cards, 'archive', ['search' => 'Target'], 'active', $confirmedCount))->toThrow(ValidationException::class)
        ->and($one->fresh()->trashed())->toBeFalse()->and($two->fresh()->trashed())->toBeFalse();
});

it('applies moderation post and body filters to counts and mutations consistently', function (): void {
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Berita A']));
    $otherPost = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Berita B']));
    $first = $this->cms->save($this->actor, CmsResource::Comments, ['content_id' => $post->id, 'author_name' => 'Pembaca', 'body' => 'Target komentar', 'status' => 'pending']);
    $this->cms->save($this->actor, CmsResource::Comments, ['content_id' => $otherPost->id, 'author_name' => 'Pembaca', 'body' => 'Target komentar', 'status' => 'pending']);
    expect($this->cms->tableQuery($this->actor, CmsResource::Comments)->first()->getAttribute('content_name'))->toBe('Berita A')
        ->and($this->cms->countFiltered($this->actor, CmsResource::Comments, ['content_id' => $post->id, 'body' => 'Target']))->toBe(1)
        ->and($this->cms->lifecycleFiltered($this->actor, CmsResource::Comments, 'archive', ['content_id' => $post->id, 'body' => 'Target'], 'active', 1))->toBe(1)
        ->and($first->fresh()->trashed())->toBeTrue()->and(Comment::count())->toBe(1);
});

it('keeps independently named content paginators on the same page isolated', function (): void {
    $category = $this->cms->save($this->actor, CmsResource::PostCategories, cmsData(['name' => 'News', 'code' => 'news', 'status' => 'active']));
    for ($number = 1; $number <= 3; $number++) {
        $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'News '.$number, 'category_id' => $category->id, 'status' => 'published']));
    }
    request()->merge(['allPostsPage' => 2, 'newsPage' => 1]);
    $all = $this->reader->posts(perPage: 1, pageName: 'allPostsPage');
    $news = $this->reader->posts(category: 'news', perPage: 1, pageName: 'newsPage');
    expect($all->currentPage())->toBe(2)->and($news->currentPage())->toBe(1)
        ->and($all->first()->getKey())->not->toBe($news->first()->getKey())
        ->and($all->url(3))->toContain('allPostsPage=3')->and($news->url(3))->toContain('newsPage=3');
});

it('supports custom page and comment paginator names and rejects malformed identifiers', function (): void {
    $this->cms->save($this->actor, CmsResource::Pages, cmsData(['name' => 'Page One', 'status' => 'published']));
    $this->cms->save($this->actor, CmsResource::Pages, cmsData(['name' => 'Page Two', 'status' => 'published']));
    $post = $this->cms->save($this->actor, CmsResource::Posts, cmsData(['name' => 'Comment Target', 'status' => 'published']));
    foreach (['First', 'Second'] as $author) {
        $this->cms->save($this->actor, CmsResource::Comments, ['content_id' => $post->id, 'author_name' => $author, 'body' => 'Comment', 'status' => 'approved']);
    }
    request()->merge(['customPages' => 2, 'discussionPage' => 2]);
    expect($this->reader->pages(perPage: 1, pageName: 'customPages')->currentPage())->toBe(2)
        ->and($this->reader->comments('comment-target', perPage: 1, pageName: 'discussionPage')->currentPage())->toBe(2);
    foreach (['', 'bad[name]', 'nested.name', str_repeat('a', 65)] as $invalid) {
        expect(fn () => $this->reader->posts(pageName: $invalid))->toThrow(InvalidArgumentException::class);
    }
});

it('derives an optional post excerpt without persisting or freezing the fallback', function (): void {
    $post = $this->cms->save($this->actor, CmsResource::Posts, [
        'title' => 'Ringkasan otomatis', 'status' => 'draft', 'body_format' => 'html',
        'body' => '<p>Berita <strong>utama</strong> &amp; terbaru.</p><p>Paragraf kedua.</p>', 'excerpt' => '',
    ]);
    expect($post->fresh()->excerpt)->toBe('Berita utama & terbaru. Paragraf kedua.');
    expect($this->cms->editor($this->actor, CmsResource::Posts, $post->id)['data']['excerpt'])->toBeEmpty();
    $post->body = '<p>Isi diperbarui.</p>';
    expect($post->excerpt)->toBe('Isi diperbarui.');
    $post->excerpt = 'Ringkasan pilihan penulis.';
    expect($post->excerpt)->toBe('Ringkasan pilihan penulis.');
    $post->excerpt = null;
    $post->body_format = 'markdown';
    $post->body = 'Berita **penting** dengan [tautan](https://example.com).';
    expect($post->excerpt)->toBe('Berita penting dengan tautan.');
    $post->body = str_repeat('Berita lengkap. ', 40);
    expect(mb_strlen($post->excerpt))->toBeLessThanOrEqual(201);
    $post->body = '';
    expect($post->excerpt)->toBeNull();
    $post->kind = 'card';
    $post->body = 'Card tetap opsional.';
    expect($post->excerpt)->toBeNull();
});
