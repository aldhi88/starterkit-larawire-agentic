<?php

use Aldhi88\StarterKit\Contracts\Cms\CmsReadRepositoryInterface;
use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
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
use Aldhi88\StarterKit\Services\Cms\CmsReader;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185233_create_starter_app_metadata_tables.php', '2026_07_27_185235_create_starter_logs_table.php'] as $migration) {
        (require StarterPaths::path('database/migrations/starter/'.$migration))->up();
    }
    foreach (glob(StarterPaths::path('installer/templates/cms/*.php')) as $migration) {
        (require $migration)->up();
    }
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    app()->bind(CmsRepositoryInterface::class, CmsRepository::class);
    app()->bind(CmsReadRepositoryInterface::class, CmsReadRepository::class);
    app()->scoped(AuditLogService::class);
    foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
        foreach ([Content::class, Media::class] as $model) {
            Event::listen('eloquent.'.$event.': '.$model, fn ($record) => app(AuditLogService::class)->recordModelEvent($event, $record));
        }
    }
    $role = ClientRole::create(['code' => 'superuser', 'name' => 'Superuser', 'is_system' => true]);
    $this->richActor = ClientLogin::create(['client_role_id' => $role->id, 'status' => 'active', 'name' => 'Editor', 'username' => 'rich-editor', 'email' => 'rich-editor@example.test']);
    $this->actingAs($this->richActor);
    $app = StarterApp::create(['name' => 'CMS', 'subdomain' => 'cms']);
    foreach (['dashboard', 'posts', 'pages', 'cards', 'media', 'menus', 'comments'] as $module) {
        AppMod::create(['app_id' => $app->id, 'code' => $module, 'name' => $module]);
    }
    Route::get('/cms/media/{publicId}', fn () => 'media')->name('starter.cms.media');
    Storage::fake('local');
    $this->richManager = app(CmsManager::class);
    $this->richReader = app(CmsReader::class);
});

function cmsRichData(array $overrides = []): array
{
    return ['name' => 'Rich content', 'title' => 'Rich content', 'status' => 'draft', 'body_format' => 'html', ...$overrides];
}

it('keeps visual editor formatting as safe standalone HTML without Quill CSS', function (): void {
    $content = $this->richManager->save($this->richActor, CmsResource::Cards, cmsRichData(['body' => '<h2>Welcome</h2><p class="ql-align-center ql-indent-2"><strong>Bold</strong> <em>Italic</em> <u>Underline</u> <s>Strike</s><span class="ql-size-large" style="color:rgb(230,0,0);background-color:#ffff00;position:fixed">Color</span></p><ol><li data-list="bullet">First</li><li data-list="bullet">Second</li></ol><blockquote>Quotation</blockquote>']));
    $html = $this->richReader->renderBody($content);
    expect($html)->toContain('<h2>Welcome</h2>', '<strong>Bold</strong>', '<em>Italic</em>', '<u>Underline</u>', 'text-align:center', 'padding-left:4em', 'font-size:1.5em', 'color:rgb(230,0,0)', '<ul><li>First</li><li>Second</li></ul>', '<blockquote>Quotation</blockquote>')
        ->not->toContain('ql-', 'position:fixed', 'data-list');
});

it('sanitizes scripts links and CSS while preserving normal text', function (): void {
    $content = $this->richManager->save($this->richActor, CmsResource::Pages, cmsRichData(['body' => '<script>alert(1)</script><p onclick="evil()" style="background-image:url(javascript:evil());color:red">Text <a href="javascript:evil()" onmouseover="evil()">link</a><iframe src="https://evil.test"></iframe></p>']));
    $html = $this->richReader->renderBody($content);
    expect($html)->toContain('Text ', 'link', 'color:red')->not->toContain('<script', 'alert(1)', 'javascript:', 'onclick', 'onmouseover', 'background-image', '<iframe');
});

it('rejects malformed canonical media links and removes them when rendering stored content', function (): void {
    $body = '<p><a href="cms-media:not-a-public-id">Attachment</a></p>';
    expect(fn () => $this->richManager->save($this->richActor, CmsResource::Posts, cmsRichData(['body' => $body])))->toThrow(ValidationException::class);
    $content = new Content(['body' => $body, 'body_format' => 'html']);
    expect($this->richReader->renderBody($content))->toContain('Attachment')->not->toContain('cms-media:', 'href=');
});

it('tracks image and PDF body references and expands their URLs for the current host', function (): void {
    $image = $this->richManager->save($this->richActor, CmsResource::Media, ['name' => 'Photo', 'status' => 'active'], upload: UploadedFile::fake()->image('photo.png'));
    $pdf = $this->richManager->save($this->richActor, CmsResource::Media, ['name' => 'Guide', 'status' => 'active'], upload: UploadedFile::fake()->create('guide.pdf', 1, 'application/pdf'));
    $content = $this->richManager->save($this->richActor, CmsResource::Posts, cmsRichData(['body' => '<p><img src="'.$this->richReader->mediaUrl($image).'" alt="Photo"><a href="'.$this->richReader->mediaUrl($pdf).'">Guide PDF</a></p>']));
    expect($content->getAttribute('body'))->toContain('cms-media:'.$image->public_id, 'cms-media:'.$pdf->public_id)->not->toContain('localhost');
    expect(DB::table('cms_content_media')->where('content_id', $content->id)->pluck('created_by')->all())->toBe([$this->richActor->id, $this->richActor->id]);
    URL::forceRootUrl('https://production.example.test');
    URL::forceScheme('https');
    $html = $this->richReader->renderBody($content);
    expect($html)->toContain('https://production.example.test/cms/media/'.$image->public_id, 'https://production.example.test/cms/media/'.$pdf->public_id)->not->toContain('cms-media:');
    $editor = $this->richManager->editor($this->richActor, CmsResource::Posts, $content->id);
    expect($editor['data']['body_format'])->toBe('html')->and($editor['data']['body'])->toContain('https://production.example.test/cms/media/');
});

it('retains Quill image resize dimensions across post save editor reload and public render', function (int $width, int $height): void {
    $image = $this->richManager->save($this->richActor, CmsResource::Media, ['name' => 'Resized post image', 'status' => 'active'], upload: UploadedFile::fake()->image('resize.png'));
    $body = '<p><img src="'.$this->richReader->mediaUrl($image).'" alt="Resized image" height="'.$height.'" width="'.$width.'"></p>';
    $post = $this->richManager->save($this->richActor, CmsResource::Posts, cmsRichData(['body' => $body]));
    foreach ([$post->body, $this->richManager->editor($this->richActor, CmsResource::Posts, $post->id)['data']['body'], $this->richReader->renderBody($post->fresh())] as $html) {
        expect($html)->toContain('width="'.$width.'"', 'height="'.$height.'"', 'alt="Resized image"');
    }
    expect(DB::table('cms_content_media')->where('content_id', $post->id)->pluck('media_id')->all())->toBe([$image->id]);
})->with([[420, 236], [10000, 1]]);

it('discards malformed or excessive image resize dimensions while preserving valid media', function (string $dimension): void {
    $image = $this->richManager->save($this->richActor, CmsResource::Media, ['name' => 'Photo', 'status' => 'active'], upload: UploadedFile::fake()->image('photo.png'));
    $post = $this->richManager->save($this->richActor, CmsResource::Posts, cmsRichData(['body' => '<img src="'.$this->richReader->mediaUrl($image).'" width="'.$dimension.'" height="'.$dimension.'" style="width:100%;height:100vh">']));
    $html = $this->richReader->renderBody($post);
    expect($html)->toContain('/cms/media/'.$image->public_id)->not->toContain('width=', 'height=', 'style=');
})->with(['0', '-1', '10001', '100%', '120px', '1e3']);

it('protects body media references until content releases them and preserves actor ownership', function (): void {
    $image = $this->richManager->save($this->richActor, CmsResource::Media, ['name' => 'Photo', 'status' => 'active'], upload: UploadedFile::fake()->image('photo.png'));
    $content = $this->richManager->save($this->richActor, CmsResource::Cards, cmsRichData(['body' => '<img src="'.$this->richReader->mediaUrl($image).'">']));
    expect(fn () => $this->richManager->lifecycle($this->richActor, CmsResource::Media, 'archive', [$image->id]))->toThrow(ValidationException::class);
    expect(fn () => $this->richManager->save($this->richActor, CmsResource::Media, ['name' => 'Photo', 'status' => 'active'], $image->id, UploadedFile::fake()->create('new.pdf', 1, 'application/pdf')))->toThrow(ValidationException::class);
    $this->richManager->save($this->richActor, CmsResource::Cards, cmsRichData(['body' => '<p>No image now</p>']), $content->id);
    expect(DB::table('cms_content_media')->where('content_id', $content->id)->count())->toBe(0);
    expect($this->richManager->lifecycle($this->richActor, CmsResource::Media, 'archive', [$image->id]))->toBe(1);
});

it('rejects image data external URLs missing media and PDF images', function (string $type): void {
    $image = $this->richManager->save($this->richActor, CmsResource::Media, ['name' => 'Photo', 'status' => 'active'], upload: UploadedFile::fake()->image('photo.png'));
    $pdf = $this->richManager->save($this->richActor, CmsResource::Media, ['name' => 'PDF', 'status' => 'active'], upload: UploadedFile::fake()->create('guide.pdf', 1, 'application/pdf'));
    $src = match ($type) {
        'data' => 'data:image/png;base64,AAAA',
        'external' => 'https://evil.example.test/cms/media/'.$image->public_id,
        'missing' => '/cms/media/01AAAAAAAAAAAAAAAAAAAAAAAA',
        'pdf' => $this->richReader->mediaUrl($pdf),
        'inactive' => $this->richReader->mediaUrl($image),
    };
    if ($type === 'inactive') {
        $image->update(['status' => 'inactive']);
    }
    expect(fn () => $this->richManager->save($this->richActor, CmsResource::Cards, cmsRichData(['body' => '<img src="'.$src.'">'])))->toThrow(ValidationException::class);
    expect(Content::count())->toBe(0);
})->with(['data', 'external', 'missing', 'pdf', 'inactive']);

it('preserves Markdown content through the forward migration and converts only editor saves', function (): void {
    $migration = require StarterPaths::path('installer/templates/cms/2026_10_25_000001_add_cms_rich_content.php');
    $migration->down();
    DB::table('cms_contents')->insert(['kind' => 'card', 'name' => 'Legacy', 'body' => '**Existing text**', 'status' => 'draft', 'position' => 0]);
    $migration->up();
    $content = Content::firstOrFail();
    expect($content->getAttribute('body'))->toBe('**Existing text**')->and($content->getAttribute('body_format'))->toBe('markdown');
    expect($this->richReader->renderBody($content))->toContain('<strong>Existing text</strong>');
    $editor = $this->richManager->editor($this->richActor, CmsResource::Cards, $content->id);
    expect($editor['data']['body'])->toContain('<strong>Existing text</strong>')->and($editor['data']['body_format'])->toBe('html');
    $this->richManager->save($this->richActor, CmsResource::Cards, $editor['data'], $content->id);
    expect($content->fresh()->getAttribute('body_format'))->toBe('html');
});

it('uploads a bounded media batch with per-file errors and shared successful audit grouping', function (): void {
    $result = $this->richManager->uploadMediaBatch($this->richActor, [UploadedFile::fake()->image('photo.png'), UploadedFile::fake()->create('guide.pdf', 1, 'application/pdf'), UploadedFile::fake()->create('bad.txt', 1, 'text/plain')]);
    expect($result['uploaded'])->toHaveCount(2)->and($result['failed'])->toHaveCount(1)->and(Media::count())->toBe(2);
    foreach ($result['uploaded'] as $file) {
        expect($file)->toHaveKeys(['id', 'name', 'url', 'mime_type', 'archived'])->not->toHaveKey('path');
    }
    expect(Media::pluck('created_by')->all())->toBe([$this->richActor->id, $this->richActor->id]);
    expect(ActivityLog::where('action_key', 'cms.media.upload_batch')->where('event', 'created')->pluck('action_id')->unique()->count())->toBe(1);
    expect(fn () => $this->richManager->uploadMediaBatch($this->richActor, array_fill(0, 21, UploadedFile::fake()->image('photo.png'))))->toThrow(ValidationException::class);
    expect(Media::count())->toBe(2);
});

it('allows a content editor to use active media and upload through the editor without granting media management', function (): void {
    $role = ClientRole::create(['code' => 'writer', 'name' => 'Writer']);
    $role->mods()->attach(AppMod::where('code', 'posts')->firstOrFail()->id);
    $writer = ClientLogin::create(['client_role_id' => $role->id, 'status' => 'active', 'name' => 'Writer', 'username' => 'rich-writer', 'email' => 'writer@example.test']);
    $this->actingAs($writer);
    expect(fn () => $this->richManager->uploadMediaBatch($writer, [UploadedFile::fake()->image('photo.png')]))->toThrow(HttpException::class);
    $result = $this->richManager->uploadMediaBatch($writer, [UploadedFile::fake()->image('photo.png')], forEditor: true);
    expect($result['uploaded'])->toHaveCount(1)->and($this->richManager->mediaLibrary($writer, forEditor: true)->total())->toBe(1);
    expect(fn () => $this->richManager->mediaLibrary($writer, archive: 'archived', forEditor: true))->toThrow(ValidationException::class);
    expect(fn () => $this->richManager->save($writer, CmsResource::Media, ['name' => 'No permission', 'status' => 'inactive'], $result['uploaded'][0]['id']))->toThrow(HttpException::class);
});

it('uses identical filename MIME and archive scopes for library pagination and bulk actions', function (): void {
    $this->richManager->uploadMediaBatch($this->richActor, [UploadedFile::fake()->image('shared-name.png'), UploadedFile::fake()->create('shared-name.pdf', 1, 'application/pdf')]);
    $pdf = Media::where('mime_type', 'application/pdf')->firstOrFail();
    $pdf->update(['name' => 'Renamed display label']);
    $page = $this->richManager->mediaLibrary($this->richActor, 'shared-name', 'pdf');
    expect($page->total())->toBe(1)->and($page->items()[0]['id'])->toBe($pdf->id);
    $filters = ['search' => 'shared-name', 'media_type' => 'pdf'];
    expect($this->richManager->countFiltered($this->richActor, CmsResource::Media, $filters))->toBe(1);
    $this->richManager->lifecycleFiltered($this->richActor, CmsResource::Media, 'archive', $filters, expectedCount: 1);
    $archived = $this->richManager->mediaLibrary($this->richActor, 'shared-name', 'pdf', 'archived');
    expect($archived->items()[0]['archived'])->toBeTrue()->and($archived->items()[0]['url'])->toBe('')
        ->and($this->richManager->mediaLibrary($this->richActor, type: 'image')->total())->toBe(1);
});

it('shows only active nonarchived media in the editor picker', function (): void {
    $this->richManager->uploadMediaBatch($this->richActor, [UploadedFile::fake()->image('active.png'), UploadedFile::fake()->image('inactive.png'), UploadedFile::fake()->image('archived.png')]);
    Media::where('name', 'inactive')->firstOrFail()->update(['status' => 'inactive']);
    Media::where('name', 'archived')->firstOrFail()->delete();
    expect($this->richManager->mediaLibrary($this->richActor, forEditor: true)->pluck('name')->all())->toBe(['active'])
        ->and($this->richManager->mediaLibrary($this->richActor)->total())->toBe(2);
    expect($this->richManager->mediaOptions($this->richActor)->pluck('name')->all())->toBe(['active']);
});

it('creates categories by name and preserves existing mapping and metadata on rename', function (): void {
    $category = $this->richManager->saveCategoryName($this->richActor, CmsResource::CardCategories, 'Client Logos');
    $second = $this->richManager->saveCategoryName($this->richActor, CmsResource::CardCategories, 'Client Logos');
    expect($category->code)->toBe('client-logos')->and($second->code)->toBe('client-logos-2');
    $category->update(['status' => 'inactive', 'position' => 8, 'description' => 'Keep this']);
    $renamed = $this->richManager->saveCategoryName($this->richActor, CmsResource::CardCategories, 'Our Partners', $category->id);
    expect($renamed->name)->toBe('Our Partners')->and($renamed->code)->toBe('client-logos')->and($renamed->status)->toBe('inactive')->and($renamed->position)->toBe(8)->and($renamed->description)->toBe('Keep this');
    expect(fn () => $this->richManager->saveCategoryName($this->richActor, CmsResource::CardCategories, ''))->toThrow(ValidationException::class);
    expect(ActivityLog::where('action_key', 'cms.card_categories.failed')->where('client_login_id', $this->richActor->id)->exists())->toBeTrue();
});
