<?php

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
use Aldhi88\StarterKit\Services\Starter\LoginHumanChallengeService;
use Aldhi88\StarterKit\Services\Starter\LoginOtpMailService;
use Aldhi88\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Services\Starter\TwoFactorAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    config()->set('starter.auth.login_two_factor_enabled', true);
});

function starterSecurityRequest(string $ip = '127.0.0.31'): Store
{
    $request = Request::create('/auth/login', 'POST', server: ['REMOTE_ADDR' => $ip]);
    $session = new Store('starter-login-security-test', new ArraySessionHandler(120));
    $session->start();
    $request->setLaravelSession($session);
    app()->instance('request', $request);

    return $session;
}

it('implements interoperable six-digit TOTP and local QR provisioning', function (): void {
    config()->set('app.name', 'Example App');
    $service = new TwoFactorAuthenticationService;
    $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    expect($service->verifyCode($secret, '287082', 59))->toBeTrue()
        ->and($service->verifyCode($secret, '287083', 59))->toBeFalse()
        ->and($service->provisioningUri($secret, 'user@example.test'))
        ->toStartWith('otpauth://totp/Example%20App%3Auser%40example.test?')
        ->toContain('secret='.$secret)
        ->toContain('issuer=Example%20App')
        ->toContain('algorithm=SHA1')
        ->toContain('digits=6')
        ->toContain('period=30');

    $qrDataUri = $service->qrCodeDataUri($secret, 'user@example.test');
    $svg = base64_decode(str($qrDataUri)->after(',')->toString(), true);

    expect($qrDataUri)->toStartWith('data:image/svg+xml;base64,')
        ->and($svg)->toBeString()
        ->toContain('<svg')
        ->toContain('<path')
        ->not->toContain('otpauth://')
        ->not->toContain('user@example.test');
});

it('generates encrypted-storage-ready recovery codes that can only be consumed once', function (): void {
    $service = new TwoFactorAuthenticationService;
    $codes = $service->generateRecoveryCodes();
    $hashes = $service->hashRecoveryCodes($codes);
    $remaining = $service->consumeRecoveryCode(strtolower(str_replace('-', '', $codes[0])), $hashes);

    expect($codes)->toHaveCount(8)
        ->and(array_unique($codes))->toHaveCount(8)
        ->and($codes[0])->toMatch('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/')
        ->and($hashes)->toHaveCount(8)
        ->and($hashes[0])->not->toBe($codes[0])
        ->and($remaining)->toHaveCount(7)
        ->and($service->consumeRecoveryCode($codes[0], $remaining ?? []))->toBeNull();
});

it('issues a five-digit non-repeating human challenge without exposing text digits', function (): void {
    config()->set('starter.auth.login_human_challenge_enabled', true);
    $session = starterSecurityRequest();
    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('recordSecurityEvent');
    $service = new LoginHumanChallengeService($auditLogs);
    $generator = new ReflectionMethod($service, 'generateUniqueDigits');

    for ($attempt = 0; $attempt < 50; $attempt++) {
        $code = $generator->invoke($service);

        expect($code)->toMatch('/^[1-9]\d{4}$/')
            ->and(array_unique(str_split($code)))->toHaveCount(5);
    }

    $image = $service->issue();
    $svg = base64_decode(str($image)->after(',')->toString(), true);
    $challenge = $session->get('starter.login_human_challenge');

    expect($image)->toStartWith('data:image/svg+xml;base64,')
        ->and($svg)->toBeString()
        ->toContain('<rect')
        ->not->toContain('<text')
        ->and($challenge)->toBeArray()
        ->and($challenge)->toHaveKeys(['hash', 'expires_at'])
        ->not->toHaveKey('code');
});

it('disables and clears the human challenge through its global switch', function (): void {
    config()->set('starter.auth.login_human_challenge_enabled', false);
    $session = starterSecurityRequest('127.0.0.35');
    $session->put('starter.login_human_challenge', [
        'hash' => 'stale',
        'expires_at' => now()->addMinute()->timestamp,
    ]);
    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('recordSecurityEvent');
    $service = new LoginHumanChallengeService($auditLogs);

    expect($service->enabled())->toBeFalse()
        ->and($service->issue())->toBe('')
        ->and($session->has('starter.login_human_challenge'))->toBeFalse();

    $service->verify('not-required');
});

it('clears a pending authenticator challenge when its global switch is disabled', function (): void {
    config()->set('starter.auth.login_two_factor_enabled', false);
    $session = starterSecurityRequest('127.0.0.36');
    $session->put('starter.login_two_factor', [
        'login_id' => 36,
        'remember' => false,
        'redirect' => null,
        'otp_verified' => false,
        'expires_at' => now()->addMinute()->timestamp,
        'attempts' => 0,
    ]);
    $service = new AuthLoginService(
        Mockery::mock(ClientLoginInterface::class),
        Mockery::mock(NavigationAuthorizedRedirectService::class),
        Mockery::mock(ClientInterface::class),
        Mockery::mock(StarterConfigService::class),
        Mockery::mock(AuditLogService::class),
        Mockery::mock(LoginOtpMailService::class),
        new TwoFactorAuthenticationService,
    );

    expect($service->pendingTwoFactor())->toBeFalse()
        ->and($session->has('starter.login_two_factor'))->toBeFalse();
});

it('requires authenticator verification after valid credentials when the user enabled it', function (): void {
    config()->set('starter.auth.login_otp_enabled', false);
    $session = starterSecurityRequest('127.0.0.32');
    $twoFactor = new TwoFactorAuthenticationService;
    $secret = $twoFactor->generateSecret();
    $codeMethod = new ReflectionMethod($twoFactor, 'codeAtCounter');
    $currentCode = $codeMethod->invoke($twoFactor, $secret, intdiv(time(), 30));

    $role = new ClientRole;
    $role->forceFill(['id' => 2, 'code' => 'staff', 'name' => 'Staff', 'is_system' => false]);

    $login = new ClientLogin;
    $login->forceFill([
        'id' => 31,
        'client_role_id' => 2,
        'name' => 'Authenticator User',
        'username' => 'authenticator-user',
        'email' => 'authenticator@example.test',
        'password' => Hash::make('ValidPassword123'),
        'status' => 'active',
        'must_change_password' => false,
        'failed_login_count' => 0,
        'auth_version' => 2,
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at' => now(),
    ]);
    $login->setRelation('role', $role);

    $client = new Client;
    $client->forceFill(['account_status' => 'approved']);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findByUsername')->once()->with('authenticator-user')->andReturn($login);
    $clientLogins->shouldReceive('findForAuthentication')->once()->with(31)->andReturn($login);
    $clientLogins->shouldReceive('updateUser')->once()->with($login, Mockery::on(
        fn (array $values): bool => $values['failed_login_count'] === 0 && $values['locked_until'] === null,
    ))->andReturn($login);
    $clientLogins->shouldReceive('refreshWithRole')->once()->with($login)->andReturn($login);

    $clients = Mockery::mock(ClientInterface::class);
    $clients->shouldReceive('current')->twice()->andReturn($client);

    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('integer')->once()->with('security.login_max_attempts')->andReturn(5);
    $configs->shouldReceive('integer')->once()->with('security.login_decay_seconds')->andReturn(60);
    $configs->shouldNotReceive('boolean');

    $redirects = Mockery::mock(NavigationAuthorizedRedirectService::class);
    $redirects->shouldReceive('forLogin')->once()->with($login, '/target', null)->andReturn('/target');

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_two_factor_requested',
        'Verifikasi authenticator login diminta',
        $login,
    );
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_succeeded',
        'Login berhasil',
        $login,
        $login,
    );

    $otpMail = Mockery::mock(LoginOtpMailService::class);
    $otpMail->shouldNotReceive('send');
    Auth::shouldReceive('login')->once()->with($login, false);

    $service = new AuthLoginService(
        $clientLogins,
        $redirects,
        $clients,
        $configs,
        $auditLogs,
        $otpMail,
        $twoFactor,
    );

    expect($service->attempt(' Authenticator-User ', 'ValidPassword123', true, '/target'))->toBeNull()
        ->and($session->get('starter.login_two_factor.login_id'))->toBe(31)
        ->and($service->verifyTwoFactor($currentCode))->toBe('/target')
        ->and($session->has('starter.login_two_factor'))->toBeFalse()
        ->and($session->get(AuthLoginService::SESSION_TWO_FACTOR_VERIFIED_LOGIN_ID))->toBe(31);
});

it('bypasses authenticator login without deleting enrollment when the global switch is disabled', function (): void {
    config()->set('starter.auth.login_otp_enabled', false);
    config()->set('starter.auth.login_two_factor_enabled', false);
    $session = starterSecurityRequest('127.0.0.34');
    $twoFactor = new TwoFactorAuthenticationService;

    $role = new ClientRole;
    $role->forceFill(['id' => 2, 'code' => 'staff', 'name' => 'Staff', 'is_system' => false]);

    $login = new ClientLogin;
    $login->forceFill([
        'id' => 34,
        'client_role_id' => 2,
        'name' => 'Globally Disabled Authenticator User',
        'username' => 'disabled-authenticator-user',
        'email' => 'disabled-authenticator@example.test',
        'password' => Hash::make('ValidPassword123'),
        'status' => 'active',
        'must_change_password' => false,
        'failed_login_count' => 0,
        'auth_version' => 2,
        'two_factor_secret' => $twoFactor->generateSecret(),
        'two_factor_recovery_codes' => ['stored-recovery-code-hash'],
        'two_factor_confirmed_at' => now(),
    ]);
    $login->setRelation('role', $role);

    $client = new Client;
    $client->forceFill(['account_status' => 'approved']);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findByUsername')->once()->with('disabled-authenticator-user')->andReturn($login);
    $clientLogins->shouldReceive('updateUser')->once()->with($login, Mockery::on(
        fn (array $values): bool => $values['failed_login_count'] === 0 && $values['locked_until'] === null,
    ))->andReturn($login);
    $clientLogins->shouldReceive('refreshWithRole')->once()->with($login)->andReturn($login);

    $clients = Mockery::mock(ClientInterface::class);
    $clients->shouldReceive('current')->once()->andReturn($client);

    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('integer')->once()->with('security.login_max_attempts')->andReturn(5);
    $configs->shouldReceive('integer')->once()->with('security.login_decay_seconds')->andReturn(60);
    $configs->shouldReceive('boolean')->once()->with('security.remember_me_enabled')->andReturn(true);

    $redirects = Mockery::mock(NavigationAuthorizedRedirectService::class);
    $redirects->shouldReceive('forLogin')->once()->with($login, '/target', null)->andReturn('/target');

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_succeeded',
        'Login berhasil',
        $login,
        $login,
    );

    $otpMail = Mockery::mock(LoginOtpMailService::class);
    $otpMail->shouldNotReceive('send');
    Auth::shouldReceive('login')->once()->with($login, true);

    $service = new AuthLoginService(
        $clientLogins,
        $redirects,
        $clients,
        $configs,
        $auditLogs,
        $otpMail,
        $twoFactor,
    );

    expect($twoFactor->enabled())->toBeFalse()
        ->and($service->attempt('disabled-authenticator-user', 'ValidPassword123', true, '/target'))->toBe('/target')
        ->and($session->has('starter.login_two_factor'))->toBeFalse()
        ->and($login->hasTwoFactorAuthenticationEnabled())->toBeTrue();
});

it('enforces email OTP before authenticator when both login factors are enabled', function (): void {
    config()->set('starter.auth.login_otp_enabled', true);
    $session = starterSecurityRequest('127.0.0.33');
    $twoFactor = new TwoFactorAuthenticationService;
    $secret = $twoFactor->generateSecret();
    $codeMethod = new ReflectionMethod($twoFactor, 'codeAtCounter');
    $currentCode = $codeMethod->invoke($twoFactor, $secret, intdiv(time(), 30));

    $role = new ClientRole;
    $role->forceFill(['id' => 2, 'code' => 'staff', 'name' => 'Staff', 'is_system' => false]);

    $login = new ClientLogin;
    $login->forceFill([
        'id' => 32,
        'client_role_id' => 2,
        'name' => 'Layered Security User',
        'username' => 'layered-security-user',
        'email' => 'layered@example.test',
        'password' => Hash::make('ValidPassword123'),
        'status' => 'active',
        'must_change_password' => false,
        'failed_login_count' => 0,
        'auth_version' => 2,
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at' => now(),
    ]);
    $login->setRelation('role', $role);

    $client = new Client;
    $client->forceFill(['account_status' => 'approved']);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findByUsername')->once()->with('layered-security-user')->andReturn($login);
    $clientLogins->shouldReceive('findForAuthentication')->twice()->with(32)->andReturn($login);
    $clientLogins->shouldReceive('updateUser')->once()->with($login, Mockery::on(
        fn (array $values): bool => $values['failed_login_count'] === 0 && $values['locked_until'] === null,
    ))->andReturn($login);
    $clientLogins->shouldReceive('refreshWithRole')->once()->with($login)->andReturn($login);

    $clients = Mockery::mock(ClientInterface::class);
    $clients->shouldReceive('current')->times(3)->andReturn($client);

    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('integer')->once()->with('security.login_max_attempts')->andReturn(5);
    $configs->shouldReceive('integer')->once()->with('security.login_decay_seconds')->andReturn(60);
    $configs->shouldNotReceive('boolean');

    $redirects = Mockery::mock(NavigationAuthorizedRedirectService::class);
    $redirects->shouldReceive('forLogin')->once()->with($login, '/target', null)->andReturn('/target');

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_otp_requested',
        'Kode OTP login dikirim',
        $login,
    );
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_two_factor_requested',
        'Verifikasi authenticator login diminta',
        $login,
    );
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_succeeded',
        'Login berhasil',
        $login,
        $login,
    );

    $sentOtp = null;
    $otpMail = Mockery::mock(LoginOtpMailService::class);
    $otpMail->shouldReceive('send')->once()->with(
        $login,
        Mockery::on(function (string $code) use (&$sentOtp): bool {
            $sentOtp = $code;

            return preg_match('/^\d{6}$/', $code) === 1;
        }),
        5,
    );
    Auth::shouldReceive('login')->once()->with($login, false);

    $service = new AuthLoginService(
        $clientLogins,
        $redirects,
        $clients,
        $configs,
        $auditLogs,
        $otpMail,
        $twoFactor,
    );

    expect($service->attempt('layered-security-user', 'ValidPassword123', true, '/target'))->toBeNull()
        ->and($session->has('starter.login_otp'))->toBeTrue()
        ->and($session->has('starter.login_two_factor'))->toBeFalse()
        ->and($service->verifyOtp((string) $sentOtp))->toBeNull()
        ->and($session->has('starter.login_otp'))->toBeFalse()
        ->and($session->get('starter.login_two_factor.otp_verified'))->toBeTrue()
        ->and($service->verifyTwoFactor($currentCode))->toBe('/target')
        ->and($session->get(AuthLoginService::SESSION_OTP_VERIFIED_LOGIN_ID))->toBe(32)
        ->and($session->get(AuthLoginService::SESSION_TWO_FACTOR_VERIFIED_LOGIN_ID))->toBe(32);
});
