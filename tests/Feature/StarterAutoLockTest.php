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
        ->toContain("document.addEventListener('visibilitychange'")
        ->toContain("['focus', 'pageshow'].forEach")
        ->toContain('elapsed >= this.autoLockConfigValue.timeoutMilliseconds')
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

it('returns json lock details for an expired livewire request without touching passive activity', function (): void {
    Carbon::setTestNow('2026-09-25 10:00:00');
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);
    Route::get('/lock-screen-json-test', fn () => null)->name('starter.lock-screen');

    $lastActivityAt = now()->subMinutes(15)->timestamp;
    $request = autoLockRequest('https://hr.company.test/livewire-4.4.0/update', $lastActivityAt);
    $request->headers->set('X-Livewire', '1');
    $request->headers->set('X-Starter-Passive', '1');
    $request->headers->set('X-Starter-Page-Url', 'https://hr.company.test/reports?month=9');
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

    $response = (new StarterLockScreen($configs, $auditLogs))
        ->handle($request, fn () => response('should-not-run'));
    $payload = $response->getData(true);

    expect($response->getStatusCode())->toBe(423)
        ->and($payload['lock_url'])->toBe($payload['redirect'])
        ->and($payload['return_url'])->toBe('https://hr.company.test/reports?month=9')
        ->and($payload['redirect'])->toContain('/lock-screen-json-test')
        ->and($payload['redirect'])->toContain('reason=idle_timeout')
        ->and($request->session()->get('starter.last_activity_at'))->toBe($lastActivityAt)
        ->and($request->session()->get('starter.locked'))->toBeTrue();

    Carbon::setTestNow();
});

it('keeps full page redirects for an expired ordinary page request', function (): void {
    Carbon::setTestNow('2026-09-25 10:00:00');
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);
    Route::get('/lock-screen-page-test', fn () => null)->name('starter.lock-screen');

    $request = autoLockRequest('https://company.test/settings', now()->subMinutes(15)->timestamp);
    $request->setMethod('GET');
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

    $response = (new StarterLockScreen($configs, $auditLogs))
        ->handle($request, fn () => response('should-not-run'));

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('/lock-screen-page-test')
        ->and($response->headers->get('Location'))->toContain(urlencode('https://company.test/settings'));

    Carbon::setTestNow();
});

it('does not let passive livewire polling refresh server activity', function (): void {
    Carbon::setTestNow('2026-09-25 10:00:00');
    $lastActivityAt = now()->subMinutes(5)->timestamp;
    $request = autoLockRequest('/livewire-4.4.0/update', $lastActivityAt);
    $request->headers->set('X-Livewire', '1');
    $request->headers->set('X-Starter-Passive', '1');
    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('boolean')->once()->with('security.lock_screen_enabled')->andReturnTrue();
    $configs->shouldReceive('integer')->once()->with('security.lock_screen_timeout_minutes')->andReturn(15);
    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('recordSecurityEvent');

    $response = (new StarterLockScreen($configs, $auditLogs))
        ->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200)
        ->and($request->session()->get('starter.last_activity_at'))->toBe($lastActivityAt);

    Carbon::setTestNow();
});

it('refreshes server activity for a normal livewire user action', function (): void {
    Carbon::setTestNow('2026-09-25 10:00:00');
    $request = autoLockRequest('/livewire-4.4.0/update', now()->subMinutes(5)->timestamp);
    $request->headers->set('X-Livewire', '1');
    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('boolean')->once()->with('security.lock_screen_enabled')->andReturnTrue();
    $configs->shouldReceive('integer')->once()->with('security.lock_screen_timeout_minutes')->andReturn(15);
    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('recordSecurityEvent');

    $response = (new StarterLockScreen($configs, $auditLogs))
        ->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200)
        ->and($request->session()->get('starter.last_activity_at'))->toBe(now()->timestamp);

    Carbon::setTestNow();
});

it('serializes passive polling and heartbeat after the timeout', function (): void {
    Carbon::setTestNow('2026-09-25 10:00:00');
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);
    Route::get('/lock-screen-race-test', fn () => null)->name('starter.lock-screen');

    $request = autoLockRequest('https://company.test/livewire-4.4.0/update', now()->subMinutes(15)->timestamp);
    $request->headers->set('X-Livewire', '1');
    $request->headers->set('X-Starter-Passive', '1');
    $request->headers->set('referer', 'https://company.test/settings');
    $login = $request->user();
    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('boolean')->twice()->with('security.lock_screen_enabled')->andReturnTrue();
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

    $pollResponse = (new StarterLockScreen($configs, $auditLogs))
        ->handle($request, fn () => response('should-not-run'));
    $heartbeatResponse = (new TouchSessionActivityController($configs, $auditLogs))($request);

    expect($pollResponse->getStatusCode())->toBe(423)
        ->and($heartbeatResponse->getStatusCode())->toBe(423)
        ->and($request->session()->get('starter.locked'))->toBeTrue();

    Carbon::setTestNow();
});

it('guards livewire html failures and concurrent requests with one top level redirect across themes', function (): void {
    $runtime = file_get_contents(StarterPaths::path('public/assets/starter/js/starter-runtime.js'));

    expect($runtime)
        ->toContain('isAuthenticationHtmlResponse(response, body)')
        ->toContain("String(body || '').includes('data-starter-session-expired')")
        ->toContain("document.getElementById('livewire-error')")
        ->toContain('prepareForTerminalNavigation(sourceRequest = null)')
        ->toContain('this.terminalNavigationStarted = true')
        ->toContain('request.cancel?.()')
        ->toContain('onRedirect(({ url, preventDefault }) =>')
        ->toContain('preventDefault();')
        ->toContain("request.options.headers['X-Starter-Passive'] = '1'")
        ->toContain("request.options.headers['X-Starter-Page-Url'] = window.location.href")
        ->toContain("call?.metadata?.type === 'poll'")
        ->toContain("root?.hasAttribute?.('data-starter-passive')")
        ->toContain('this.prepareForTerminalNavigation();')
        ->not->toContain('showHtmlModal(');

    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $layout = file_get_contents(StarterPaths::path(
            'resources/themes/'.$theme.'/views/starter/templates/layouts/app.blade.php',
        ));
        $scriptsPath = StarterPaths::path(
            'resources/themes/'.$theme.'/views/starter/templates/layouts/scripts.blade.php',
        );
        $sessionExpired = file_get_contents(StarterPaths::path(
            'resources/themes/'.$theme.'/views/starter/templates/session-expired.blade.php',
        ));

        expect($layout.(is_file($scriptsPath) ? file_get_contents($scriptsPath) : ''))
            ->toContain('assets/starter/js/starter-runtime.js')
            ->and($sessionExpired)->toContain('data-starter-session-expired');
    }
});
