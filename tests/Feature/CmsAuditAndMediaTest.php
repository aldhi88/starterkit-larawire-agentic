<?php

use Aldhi88\StarterKit\Contracts\Cms\CmsReadRepositoryInterface;
use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Contracts\Starter\AppRouteInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Http\Controllers\Cms\CmsMediaController;
use Aldhi88\StarterKit\Http\Middleware\Starter\StarterAuthorize;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Cms\CmsReadRepository;
use Aldhi88\StarterKit\Repositories\Cms\CmsRepository;
use Aldhi88\StarterKit\Repositories\Starter\AppRouteRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    foreach (['185232_create_starter_identity_tables', '185233_create_starter_app_metadata_tables', '185235_create_starter_logs_table'] as $name) {
        (require StarterPaths::path('database/migrations/starter/2026_07_27_'.$name.'.php'))->up();
    }
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000000_create_cms_tables.php'))->up();
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000001_add_cms_rich_content.php'))->up();
    (require StarterPaths::path('installer/templates/cms/2026_10_25_000002_add_post_category_hierarchy.php'))->up();
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    app()->bind(CmsReadRepositoryInterface::class, CmsReadRepository::class);
    app()->bind(CmsRepositoryInterface::class, CmsRepository::class);
    app()->scoped(AuditLogService::class);
    Storage::fake('local');
    $this->cmsPublicId = (string) Str::ulid();
    $this->cmsMedia = new Media([
        'public_id' => $this->cmsPublicId,
        'name' => 'CMS document',
        'path' => 'cms/media/'.$this->cmsPublicId.'.pdf',
        'original_name' => 'cms-guide.pdf',
        'mime_type' => 'application/pdf',
        'size' => 16,
        'status' => 'active',
    ]);
    $this->cmsMedia->save();
    Storage::disk('local')->put($this->cmsMedia->path, '%PDF-1.4 CMS test');
    Route::get('/cms-file/{publicId}', CmsMediaController::class)->name('starter.cms.media');
});

it('streams active public CMS media without exposing its private storage path', function (): void {
    $this->get('/cms-file/'.$this->cmsPublicId)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy', 'sandbox')
        ->assertStreamedContent('%PDF-1.4 CMS test');
    $this->get('/cms-file/not-a-public-id')->assertNotFound();
});

it('rejects archived inactive missing and unsafe media paths', function (string $case): void {
    match ($case) {
        'archived' => $this->cmsMedia->delete(),
        'inactive' => $this->cmsMedia->update(['status' => 'inactive']),
        'missing' => Storage::disk('local')->delete($this->cmsMedia->path),
        'path' => $this->cmsMedia->update(['path' => '../cms-guide.pdf']),
        'mime' => $this->cmsMedia->update(['mime_type' => 'text/html']),
    };
    $this->get('/cms-file/'.$this->cmsPublicId)->assertNotFound();
})->with(['archived', 'inactive', 'missing', 'path', 'mime']);

it('keeps CMS model audit events while redacting content paths and personal text', function (): void {
    $role = ClientRole::create(['code' => 'cms-auditor', 'name' => 'CMS Auditor']);
    $actor = ClientLogin::create(['name' => 'CMS Actor', 'username' => 'cms-actor', 'email' => 'cms-actor@example.test', 'client_role_id' => $role->id]);
    $this->actingAs($actor);
    $this->cmsMedia->setAttribute('caption', 'Confidential original caption');
    $this->cmsMedia->save();
    app(AuditLogService::class)->recordModelEvent('created', $this->cmsMedia);
    $entry = ActivityLog::query()->latest('id')->firstOrFail();
    $serialized = json_encode($entry->new_values);
    expect($entry->client_login_id)->toBe($actor->id)
        ->and($entry->table_name)->toBe('cms_media')
        ->and($serialized)->toContain('[REDACTED]')
        ->not->toContain('Confidential original caption')
        ->not->toContain('cms/media/')
        ->not->toContain('cms-guide.pdf');
    $this->get('/cms-file/'.$this->cmsPublicId)->assertOk();
    expect(ActivityLog::query()->where('action_key', 'cms.media.download')->where('client_login_id', $actor->id)->exists())->toBeTrue();
});

it('audits a denied CMS route before any component or mutation can run', function (): void {
    app()->bind(AppRouteInterface::class, AppRouteRepository::class);
    $role = ClientRole::create(['code' => 'without-cms', 'name' => 'Without CMS']);
    $actor = ClientLogin::create(['name' => 'Restricted', 'username' => 'cms-restricted', 'email' => 'cms-restricted@example.test', 'client_role_id' => $role->id]);
    $this->actingAs($actor);
    Route::get('/cms-denied', fn () => 'must never render')
        ->middleware(StarterAuthorize::class)->name('cms.posts.index');
    $this->get('/cms-denied')->assertForbidden();
    expect(ActivityLog::query()->where('action_key', 'authorization.module_access_denied')
        ->where('client_login_id', $actor->id)->where('metadata->outcome', 'denied')->exists())->toBeTrue();
});
