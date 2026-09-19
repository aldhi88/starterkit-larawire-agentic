<?php

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Mail\Starter\LoginOtpMail;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
use Aldhi88\StarterKit\Services\Starter\LoginOtpMailService;
use Aldhi88\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Services\Starter\StarterContextService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
    View::addNamespace('starter-mail', [
        resource_path('views/vendor/starter/mail'),
        StarterPaths::path('resources/views/mail'),
    ]);
});

function starterLoginOtpRequest(string $ip = '127.0.0.20'): Store
{
    $request = Request::create('/auth/login', 'POST', server: ['REMOTE_ADDR' => $ip]);
    $session = new Store('starter-login-otp-test', new ArraySessionHandler(120));
    $session->start();
    $request->setLaravelSession($session);
    app()->instance('request', $request);

    return $session;
}

function starterLoginOtpUser(int $id, string $username): ClientLogin
{
    $role = new ClientRole;
    $role->forceFill([
        'id' => 2,
        'code' => 'staff',
        'name' => 'Staff',
        'is_system' => false,
    ]);

    $login = new ClientLogin;
    $login->forceFill([
        'id' => $id,
        'client_role_id' => 2,
        'name' => 'OTP User',
        'username' => $username,
        'email' => $username.'@example.test',
        'password' => Hash::make('ValidPassword123'),
        'status' => 'active',
        'must_change_password' => false,
        'failed_login_count' => 0,
        'auth_version' => 1,
    ]);
    $login->setRelation('role', $role);

    return $login;
}

function starterLoginOtpClient(): Client
{
    $client = new Client;
    $client->forceFill(['account_status' => 'approved']);

    return $client;
}

it('queues the OTP through the configured queue driver', function (string $queueDriver): void {
    config()->set('app.name', 'Example App');
    config()->set('queue.default', $queueDriver);
    config()->set('mail.default', 'array');
    config()->set('mail.mailers.array', ['transport' => 'array']);
    $mail = Mail::fake();
    $login = starterLoginOtpUser(19, 'mail-otp-user');
    $context = Mockery::mock(StarterContextService::class);
    $context->shouldReceive('brandData')->once()->andReturn([
        'clientName' => 'Example Company',
        'clientLogoUrl' => 'https://company.test/logo.png',
    ]);

    (new LoginOtpMailService($mail, $context))->send($login, '123456', 5);

    Mail::assertQueued(LoginOtpMail::class, function (LoginOtpMail $message): bool {
        return $message->hasTo('mail-otp-user@example.test')
            && $message->appName === 'Example App'
            && $message->brandName === 'Example Company'
            && $message->brandLogoUrl === 'https://company.test/logo.png'
            && $message->otpCode === '123456'
            && $message->expiresInMinutes === 5
            && $message->mailer === 'array';
    });
    Mail::assertNotSent(LoginOtpMail::class);
})->with(['sync', 'database']);

it('falls back to the active theme logo when the Laravel host has no company logo', function (string $theme, string $logoPath): void {
    config()->set('app.name', 'Example App');
    config()->set('app.url', 'https://company.test');
    config()->set('starter.theme', $theme);
    config()->set('mail.default', 'array');
    config()->set('mail.mailers.array', ['transport' => 'array']);
    $mail = Mail::fake();
    $login = starterLoginOtpUser(29, 'theme-logo-user');
    $context = Mockery::mock(StarterContextService::class);
    $context->shouldReceive('brandData')->once()->andReturn([
        'clientName' => 'Example Company',
        'clientLogoUrl' => null,
    ]);

    (new LoginOtpMailService($mail, $context))->send($login, '123456', 5);

    Mail::assertQueued(LoginOtpMail::class, fn (LoginOtpMail $message): bool => $message->brandLogoUrl === asset($logoPath));
})->with([
    ['tabler', 'assets/tabler/static/logo-small.svg'],
    ['dashcode', 'assets/dashcode/images/logo/logo.svg'],
    ['vuexy', 'assets/vuexy/img/branding/vuexy-mark.svg'],
]);

it('renders a neutral branded OTP email through host-overridable views', function (): void {
    $mail = new LoginOtpMail(
        appName: 'Example App',
        brandName: 'Example Company',
        brandLogoUrl: 'https://company.test/logo.png',
        recipientName: 'Example User',
        otpCode: '123456',
        expiresInMinutes: 5,
    );

    $html = $mail->render();
    $hints = View::getFinder()->getHints();

    expect($mail->envelope()->subject)->toBe('Kode OTP login - Example Company')
        ->and($mail)->toBeInstanceOf(ShouldQueueAfterCommit::class)
        ->and($mail)->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and($mail->content()->view)->toBe('starter-mail::login-otp')
        ->and($mail->content()->text)->toBe('starter-mail::login-otp-text')
        ->and($html)->toContain('Example Company')
        ->toContain('https://company.test/logo.png')
        ->toContain('Verifikasi login Anda')
        ->toContain('Jangan berikan kode ini kepada siapa pun.')
        ->toContain('user-select:all;">123456</td>')
        ->and(substr_count($html, 'font-family:Courier New'))->toBe(1)
        ->and($hints['starter-mail'][0])->toBe(resource_path('views/vendor/starter/mail'))
        ->and($hints['starter-mail'][1])->toBe(StarterPaths::path('resources/views/mail'));
});

it('allows a Laravel host to select custom OTP mail views', function (): void {
    config()->set('starter.auth.login_otp_mail_view', 'mail.custom-login-otp');
    config()->set('starter.auth.login_otp_mail_text_view', 'mail.custom-login-otp-text');

    $mail = new LoginOtpMail(
        appName: 'Example App',
        brandName: 'Example Company',
        brandLogoUrl: null,
        recipientName: 'Example User',
        otpCode: '123456',
        expiresInMinutes: 5,
    );

    expect($mail->content()->view)->toBe('mail.custom-login-otp')
        ->and($mail->content()->text)->toBe('mail.custom-login-otp-text');
});

it('uses the shared six-slot OTP control in every login theme', function (): void {
    $control = file_get_contents(StarterPaths::path('resources/views/components/otp-code-input.blade.php'));

    expect($control)
        ->toContain("\$wire.entangle('otpForm.code')")
        ->toContain('autocomplete="one-time-code"')
        ->toContain("replace(/\D/g, '').slice(0, 6)")
        ->toContain('x-on:paste.prevent')
        ->toContain("'is-active': String(code ?? '').length ===")
        ->toContain('@keyframes starter-otp-caret')
        ->and(substr_count($control, 'starter-otp-control-cell'))->toBeGreaterThanOrEqual(2);

    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $login = file_get_contents(StarterPaths::path(
            "resources/themes/{$theme}/views/starter/auth/login.blade.php",
        ));

        expect($login)
            ->toContain("@include('starter-shared::components.otp-code-input')")
            ->toContain('id="login-otp-error"')
            ->not->toContain('placeholder="000000"');
    }
});

it('requires an emailed OTP before creating the authenticated session', function (): void {
    config()->set('starter.auth.login_otp_enabled', true);
    $session = starterLoginOtpRequest();
    $login = starterLoginOtpUser(20, 'otp-user');
    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findByUsername')->once()->with('otp-user')->andReturn($login);
    $clientLogins->shouldReceive('findForAuthentication')->once()->with(20)->andReturn($login);
    $clientLogins->shouldReceive('updateUser')->once()->with($login, Mockery::on(
        fn (array $values): bool => $values['failed_login_count'] === 0 && $values['locked_until'] === null,
    ))->andReturn($login);
    $clientLogins->shouldReceive('refreshWithRole')->once()->with($login)->andReturn($login);

    $clients = Mockery::mock(ClientInterface::class);
    $clients->shouldReceive('current')->twice()->andReturn(starterLoginOtpClient());

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
        'auth.login_succeeded',
        'Login berhasil',
        $login,
        $login,
    );

    $sentCode = null;
    $otpMail = Mockery::mock(LoginOtpMailService::class);
    $otpMail->shouldReceive('send')->once()->with($login, Mockery::on(function (string $code) use (&$sentCode): bool {
        $sentCode = $code;

        return preg_match('/^\d{6}$/', $code) === 1;
    }), 5);

    Auth::shouldReceive('login')->once()->with($login, false);

    $service = new AuthLoginService($clientLogins, $redirects, $clients, $configs, $auditLogs, $otpMail);

    expect($service->attempt(' OTP-User ', 'ValidPassword123', true, '/target'))->toBeNull();

    $challenge = $session->get('starter.login_otp');

    expect($challenge)->toBeArray()
        ->and($challenge['login_id'])->toBe(20)
        ->and($challenge['masked_email'])->toBe('o*******@example.test')
        ->and($challenge['otp_hash'])->not->toBe($sentCode)
        ->and(Hash::check((string) $sentCode, $challenge['otp_hash']))->toBeTrue()
        ->and($service->verifyOtp((string) $sentCode))->toBe('/target')
        ->and($session->has('starter.login_otp'))->toBeFalse()
        ->and($session->get('starter.auth_version'))->toBe(1)
        ->and($session->get(AuthLoginService::SESSION_OTP_VERIFIED_LOGIN_ID))->toBe(20);
});

it('rejects an invalid OTP without authenticating the user', function (): void {
    config()->set('starter.auth.login_otp_enabled', true);
    $session = starterLoginOtpRequest('127.0.0.21');
    $login = starterLoginOtpUser(21, 'wrong-otp-user');

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findByUsername')->once()->andReturn($login);
    $clientLogins->shouldReceive('findForAuthentication')->once()->with(21)->andReturn($login);
    $clientLogins->shouldNotReceive('updateUser');
    $clientLogins->shouldNotReceive('refreshWithRole');

    $clients = Mockery::mock(ClientInterface::class);
    $clients->shouldReceive('current')->twice()->andReturn(starterLoginOtpClient());

    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('integer')->once()->with('security.login_max_attempts')->andReturn(5);
    $configs->shouldReceive('integer')->once()->with('security.login_decay_seconds')->andReturn(60);
    $configs->shouldNotReceive('boolean');

    $redirects = Mockery::mock(NavigationAuthorizedRedirectService::class);
    $redirects->shouldNotReceive('forLogin');

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_otp_requested',
        'Kode OTP login dikirim',
        $login,
    );
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_otp_failed',
        'Verifikasi OTP login gagal',
        $login,
        null,
        ['reason' => 'invalid_code', 'attempts' => 1],
    );

    $otpMail = Mockery::mock(LoginOtpMailService::class);
    $otpMail->shouldReceive('send')->once();
    Auth::shouldReceive('login')->never();

    $service = new AuthLoginService($clientLogins, $redirects, $clients, $configs, $auditLogs, $otpMail);
    $service->attempt('wrong-otp-user', 'ValidPassword123');

    expect(fn (): string => $service->verifyOtp('999999'))
        ->toThrow(ValidationException::class)
        ->and($session->get('starter.login_otp.attempts'))->toBe(1);
});
