<?php

use Aldhi88\StarterKit\Contracts\Starter\AppModInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientRoleInterface;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\TemporaryPasswordMailService;
use Aldhi88\StarterKit\Services\Starter\UserManagementUserService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
});

function adminTwoFactorRole(int $id, string $code, bool $system): ClientRole
{
    $role = new ClientRole;
    $role->forceFill([
        'id' => $id,
        'code' => $code,
        'name' => str($code)->headline()->toString(),
        'is_system' => $system,
        'can_manage_settings' => $system,
    ]);

    return $role;
}

function adminTwoFactorLogin(int $id, ClientRole $role, bool $enabled): ClientLogin
{
    $login = new ClientLogin;
    $login->forceFill([
        'id' => $id,
        'client_role_id' => $role->id,
        'name' => 'Recovery User '.$id,
        'username' => 'recovery-user-'.$id,
        'email' => 'recovery-'.$id.'@example.test',
        'status' => 'active',
        'auth_version' => 4,
        'two_factor_secret' => $enabled ? 'JBSWY3DPEHPK3PXP' : null,
        'two_factor_recovery_codes' => $enabled ? ['hashed-recovery-code'] : null,
        'two_factor_confirmed_at' => $enabled ? now() : null,
    ]);
    $login->setRelation('role', $role);

    return $login;
}

function adminTwoFactorUserService(
    ClientLoginInterface $clientLogins,
    AuditLogService $auditLogs,
): UserManagementUserService {
    return new UserManagementUserService(
        $clientLogins,
        Mockery::mock(ClientRoleInterface::class),
        Mockery::mock(AppModInterface::class),
        $auditLogs,
        Mockery::mock(TemporaryPasswordMailService::class),
    );
}

it('lets a settings manager reset authenticator enrollment for a non-superuser', function (): void {
    $currentLogin = adminTwoFactorLogin(1, adminTwoFactorRole(1, 'superuser', true), false);
    $target = adminTwoFactorLogin(2, adminTwoFactorRole(2, 'staff', false), true);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findForManagement')->once()->with(2)->andReturn($target);
    $clientLogins->shouldReceive('updateUser')->once()->with($target, Mockery::on(
        fn (array $payload): bool => $payload['two_factor_secret'] === null
            && $payload['two_factor_recovery_codes'] === null
            && $payload['two_factor_confirmed_at'] === null
            && is_string($payload['remember_token'])
            && strlen($payload['remember_token']) === 60
            && $payload['auth_version'] === 5,
    ))->andReturnUsing(function (ClientLogin $login, array $payload): ClientLogin {
        $login->forceFill($payload);

        return $login;
    });

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('withinAction')->once()->with(
        'user.reset_two_factor',
        'Mereset authenticator user Recovery User 2',
        Mockery::type(Closure::class),
    )->andReturnUsing(fn (string $key, string $label, Closure $callback): mixed => $callback());
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.two_factor_reset_by_admin',
        'Authenticator direset oleh administrator',
        $target,
        $currentLogin,
        ['reason' => 'account_recovery'],
    );

    $result = adminTwoFactorUserService($clientLogins, $auditLogs)
        ->resetTwoFactorAuthentication($currentLogin, 2);

    expect($result)->toBe($target)
        ->and($result->hasTwoFactorAuthenticationEnabled())->toBeFalse()
        ->and($result->two_factor_recovery_codes)->toBeNull()
        ->and($result->auth_version)->toBe(5);
});

it('rejects authenticator reset when enrollment is already inactive', function (): void {
    $currentLogin = adminTwoFactorLogin(1, adminTwoFactorRole(1, 'superuser', true), false);
    $target = adminTwoFactorLogin(2, adminTwoFactorRole(2, 'staff', false), false);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findForManagement')->once()->with(2)->andReturn($target);
    $clientLogins->shouldNotReceive('updateUser');

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('withinAction');
    $auditLogs->shouldNotReceive('recordSecurityEvent');

    expect(fn () => adminTwoFactorUserService($clientLogins, $auditLogs)
        ->resetTwoFactorAuthentication($currentLogin, 2))
        ->toThrow(ValidationException::class);
});

it('exposes authenticator recovery only for enrolled non-superusers in every theme', function (): void {
    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $rowActions = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/user-management/powergrid/users-row-actions.blade.php"));
        $users = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/user-management/users.blade.php"));

        expect($rowActions)
            ->toContain('$row->hasTwoFactorAuthenticationEnabled()')
            ->toContain('starter-user-authenticator-reset-request')
            ->toContain('Reset authenticator')
            ->and($users)
            ->toContain("'confirmAction' => 'resetSelectedAuthenticator'")
            ->toContain("'cancelAction' => 'cancelAuthenticatorReset'")
            ->toContain('seluruh kode pemulihan')
            ->toContain('Seluruh sesi lamanya akan berakhir');
    }
});
