<?php

use Aldhi88\StarterKit\Http\Controllers\Starter\Auth\TouchSessionActivityController;
use Aldhi88\StarterKit\Http\Middleware\Starter\StarterLockScreen;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

function autoLockRequest(string $uri, int $lastActivityAt): Request
{
    $request = Request::create($uri, 'POST');
    $session = new Store('auto-lock-test', new ArraySessionHandler(120));
    $session->start();
    $session->put('starter.last_activity_at', $lastActivityAt);
    $request->setLaravelSession($session);

    $login = new ClientLogin;
    $login->forceFill([
        'id' => 1,
        'name' => 'Test User',
        'username' => 'test-user',
    ]);
    $request->setUserResolver(fn (): ClientLogin => $login);

    return $request;
}

it('keeps browser and server activity synchronized before auto locking', function (): void {
    $runtime = file_get_contents(StarterPaths::path('public/assets/starter/js/starter-runtime.js'));

    expect($runtime)
        ->toContain('sessionTouchIntervalMilliseconds: Math.min(60000, Math.max(15000, timeoutSeconds * 1000 / 3))')
        ->toContain('window.localStorage.setItem(key, String(timestamp))')
        ->toContain('Math.min(timestamp, Date.now())')
        ->toContain("window.addEventListener('storage'")
        ->toContain('() => this.reconcileAutoLock()')
        ->toContain('const status = await this.touchSessionActivity()')
        ->toContain('const controller = new AbortController()')
        ->toContain("lockUrl.searchParams.set('reason', 'idle_timeout')")
        ->toContain('window.StarterTemplate.init(true)')
        ->toContain("document.addEventListener('DOMContentLoaded', () => window.StarterTemplate.init(true))")
        ->toContain('window.StarterTemplate.init(! event.persisted)')
        ->not->toContain('() => this.performAutoLock(),');
});

it('keeps session heartbeats on the current root or app origin', function (): void {
    $context = file_get_contents(StarterPaths::path('src/Services/Starter/StarterContextService.php'));
    $runtime = file_get_contents(StarterPaths::path('public/assets/starter/js/starter-runtime.js'));

    expect($context)
        ->toContain("'sessionActivityUrl' => route('starter.session.activity', absolute: false)")
        ->and($runtime)
        ->toContain("credentials: 'same-origin'");
});

it('scopes shared browser activity to the authenticated session in every theme', function (): void {
    $context = file_get_contents(StarterPaths::path('src/Services/Starter/StarterContextService.php'));

    expect($context)
        ->toContain("'sessionActivityScope' => \$login")
        ->toContain("hash_hmac('sha256', request()->session()->getId(), (string) config('app.key'))");

    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $layout = file_get_contents(StarterPaths::path(
            'resources/themes/'.$theme.'/views/starter/templates/layouts/app.blade.php',
        ));

        expect($layout)->toContain(
            '<meta name="starter-session-activity-scope" content="{{ $sessionActivityScope }}">',
        );
    }

    $vuexyScripts = file_get_contents(StarterPaths::path(
        'resources/themes/vuexy/views/starter/templates/layouts/scripts.blade.php',
    ));

    expect($vuexyScripts)
        ->toContain("assets/starter/js/starter-runtime.js') }}?v={{ filemtime(public_path('assets/starter/js/starter-runtime.js')) }}");
});

it('records automatic and manual screen locks for diagnosis', function (): void {
    $middleware = file_get_contents(StarterPaths::path('src/Http/Middleware/Starter/StarterLockScreen.php'));
    $controller = file_get_contents(StarterPaths::path(
        'src/Http/Controllers/Starter/Auth/TouchSessionActivityController.php',
    ));
    $component = file_get_contents(StarterPaths::path('src/Livewire/Starter/Auth/LockScreen.php'));

    expect($middleware)
        ->toContain("'auth.screen_locked'")
        ->toContain("'reason' => 'inactivity_timeout'")
        ->and($controller)
        ->toContain("'auth.screen_locked'")
        ->toContain("'reason' => 'inactivity_timeout'")
        ->and($component)
        ->toContain("request()->boolean('manual') => 'manual'")
        ->toContain("request()->query('reason') === 'idle_timeout' => 'inactivity_timeout'")
        ->toContain("'auth.screen_locked'");
});

it('lets the session activity endpoint refresh an active session', function (): void {
    Carbon::setTestNow('2026-09-07 01:00:00');
    $request = autoLockRequest('/session/activity', now()->subMinutes(14)->timestamp);
    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('boolean')->once()->with('security.lock_screen_enabled')->andReturnTrue();
    $configs->shouldReceive('integer')->once()->with('security.lock_screen_timeout_minutes')->andReturn(15);
    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('recordSecurityEvent');

    $response = (new TouchSessionActivityController($configs, $auditLogs))($request);

    expect($response->getStatusCode())->toBe(204)
        ->and($request->session()->get('starter.last_activity_at'))->toBe(now()->timestamp)
        ->and($request->session()->get('starter.locked'))->toBeNull();

    Carbon::setTestNow();
});

it('locks and audits an expired activity heartbeat', function (): void {
    Carbon::setTestNow('2026-09-07 01:00:00');
    $request = autoLockRequest('/session/activity', now()->subMinutes(15)->timestamp);
    $login = $request->user();
    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('boolean')->once()->with('security.lock_screen_enabled')->andReturnTrue();
    $configs->shouldReceive('integer')->once()->with('security.lock_screen_timeout_minutes')->andReturn(15);
    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.screen_locked',
        'Layar dikunci otomatis',
        $login,
        $login,
        [
            'reason' => 'inactivity_timeout',
            'timeout_seconds' => 900,
        ],
    );
    Route::get('/lock-screen-test', fn () => null)->name('starter.lock-screen');

    $response = (new TouchSessionActivityController($configs, $auditLogs))($request);

    expect($response->getStatusCode())->toBe(423)
        ->and($response->getData(true))->toHaveKey('redirect')
        ->and($request->session()->get('starter.locked'))->toBeTrue();

    Carbon::setTestNow();
});

it('refreshes navigation activity through lock middleware before the timeout', function (): void {
    Carbon::setTestNow('2026-09-07 01:00:00');
    $request = autoLockRequest('/settings', now()->subMinutes(14)->timestamp);
    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('boolean')->once()->with('security.lock_screen_enabled')->andReturnTrue();
    $configs->shouldReceive('integer')->once()->with('security.lock_screen_timeout_minutes')->andReturn(15);
    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('recordSecurityEvent');
    $middleware = new StarterLockScreen($configs, $auditLogs);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200)
        ->and($request->session()->get('starter.last_activity_at'))->toBe(now()->timestamp);

    Carbon::setTestNow();
});
