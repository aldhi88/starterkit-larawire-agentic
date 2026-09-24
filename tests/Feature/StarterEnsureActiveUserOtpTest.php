<?php

use Aldhi88\StarterKit\Http\Middleware\Starter\StarterEnsureActiveUser;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

function starterActiveOtpRequest(ClientLogin $login): Request
{
    $request = Request::create('/dashboard');
    $session = new Store('starter-active-otp-test', new ArraySessionHandler(120));
    $session->start();
    $request->setLaravelSession($session);
    $request->setUserResolver(fn (): ClientLogin => $login);

    return $request;
}

function starterActiveOtpLogin(int $id): ClientLogin
{
    $login = new ClientLogin;
    $login->forceFill([
        'id' => $id,
        'status' => 'active',
        'auth_version' => 1,
    ]);

    return $login;
}

it('allows an enrolled authenticator session without proof while the global switch is disabled', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    config()->set('starter.auth.login_otp_enabled', false);
    config()->set('starter.auth.login_two_factor_enabled', false);
    $login = starterActiveOtpLogin(70);
    $login->forceFill([
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_confirmed_at' => now(),
    ]);
    $request = starterActiveOtpRequest($login);

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('recordSecurityEvent');

    $response = (new StarterEnsureActiveUser($auditLogs))->handle(
        $request,
        fn () => response('allowed'),
    );

    expect($response->getContent())->toBe('allowed')
        ->and($login->hasTwoFactorAuthenticationEnabled())->toBeTrue();
});

it('allows an OTP-protected session only when its proof belongs to the authenticated login', function (): void {
    config()->set('starter.auth.login_otp_enabled', true);
    $login = starterActiveOtpLogin(71);
    $request = starterActiveOtpRequest($login);
    $request->session()->put(AuthLoginService::SESSION_OTP_VERIFIED_LOGIN_ID, 71);

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('recordSecurityEvent');

    $response = (new StarterEnsureActiveUser($auditLogs))->handle(
        $request,
        fn () => response('allowed'),
    );

    expect($response->getContent())->toBe('allowed')
        ->and($request->session()->get('starter.auth_version'))->toBe(1);
});

it('revokes an authenticated session without OTP proof when OTP is enabled', function (): void {
    config()->set('starter.auth.login_otp_enabled', true);
    Route::get('/auth/login', fn () => null)->name('auth.login');
    $login = starterActiveOtpLogin(72);
    $request = starterActiveOtpRequest($login);

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.session_revoked',
        'Session dihentikan karena verifikasi OTP tidak tersedia',
        $login,
        $login,
        ['reason' => 'otp_verification_missing'],
    );
    Auth::shouldReceive('logout')->once();

    $response = (new StarterEnsureActiveUser($auditLogs))->handle(
        $request,
        fn () => response('must not pass'),
    );

    expect($response->isRedirect(route('auth.login')))->toBeTrue();
});
