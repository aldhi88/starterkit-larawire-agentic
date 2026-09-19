<?php

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
use Aldhi88\StarterKit\Services\Starter\LoginOtpMailService;
use Aldhi88\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

it('redirects a user with a temporary password directly to profile security', function (): void {
    Route::get('/profile/edit', fn () => null)->name('starter.profile.edit');

    $request = Request::create('/auth/login', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
    $session = new Store('starter-auth-login-test', new ArraySessionHandler(120));
    $session->start();
    $request->setLaravelSession($session);
    $this->app->instance('request', $request);

    $role = new ClientRole;
    $role->forceFill([
        'id' => 2,
        'code' => 'staff',
        'name' => 'Staff',
        'is_system' => false,
    ]);

    $login = new ClientLogin;
    $login->forceFill([
        'id' => 2,
        'client_role_id' => 2,
        'name' => 'Staff User',
        'username' => 'staff-user',
        'email' => 'staff@example.test',
        'password' => Hash::make('Temporary123'),
        'status' => 'active',
        'must_change_password' => true,
        'failed_login_count' => 0,
        'auth_version' => 1,
    ]);
    $login->setRelation('role', $role);

    $client = new Client;
    $client->forceFill(['account_status' => 'approved']);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findByUsername')->once()->with('staff-user')->andReturn($login);
    $clientLogins->shouldReceive('updateUser')->once()->with($login, Mockery::on(
        fn (array $data): bool => $data['failed_login_count'] === 0 && $data['locked_until'] === null,
    ))->andReturn($login);
    $clientLogins->shouldReceive('refreshWithRole')->once()->with($login)->andReturn($login);

    $clients = Mockery::mock(ClientInterface::class);
    $clients->shouldReceive('current')->once()->andReturn($client);

    $configs = Mockery::mock(StarterConfigService::class);
    $configs->shouldReceive('integer')->once()->with('security.login_max_attempts')->andReturn(5);
    $configs->shouldReceive('integer')->once()->with('security.login_decay_seconds')->andReturn(60);
    $configs->shouldNotReceive('boolean');

    $redirects = Mockery::mock(NavigationAuthorizedRedirectService::class);
    $redirects->shouldNotReceive('firstAuthorizedUrl');
    $redirects->shouldNotReceive('forLogin');

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.login_succeeded',
        'Login berhasil',
        $login,
        $login,
    );

    Auth::shouldReceive('login')->once()->with($login, false);

    $otpMail = Mockery::mock(LoginOtpMailService::class);
    $otpMail->shouldNotReceive('send');

    $target = (new AuthLoginService(
        $clientLogins,
        $redirects,
        $clients,
        $configs,
        $auditLogs,
        $otpMail,
    ))->attempt(' Staff-User ', 'Temporary123');

    expect($target)->toBe(route('starter.profile.edit', ['tab' => 'security']))
        ->and(parse_url($target, PHP_URL_HOST))->toBe('localhost')
        ->and($session->get('starter.auth_version'))->toBe(1)
        ->and($session->has(AuthLoginService::SESSION_OTP_VERIFIED_LOGIN_ID))->toBeFalse();
});
