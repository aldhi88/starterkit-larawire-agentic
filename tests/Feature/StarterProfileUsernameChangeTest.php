<?php

use Aldhi88\StarterKit\Contracts\Starter\AppModInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientRoleInterface;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\ProfileService;
use Aldhi88\StarterKit\Services\Starter\TemporaryPasswordMailService;
use Aldhi88\StarterKit\Services\Starter\UserManagementUserService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Validation\ValidationException;

function usernameChangeRole(string $code, bool $system): ClientRole
{
    $role = new ClientRole;
    $role->forceFill([
        'id' => $system ? 1 : 2,
        'code' => $code,
        'name' => $system ? 'Superuser' : 'Staff',
        'is_system' => $system,
        'can_manage_settings' => $system,
    ]);

    return $role;
}

function usernameChangeLogin(int $id, ClientRole $role, string $username): ClientLogin
{
    $login = new ClientLogin;
    $login->forceFill([
        'id' => $id,
        'client_role_id' => $role->id,
        'name' => 'Username Change User',
        'username' => $username,
        'email' => $username.'@example.test',
        'profile_photo' => null,
        'status' => 'active',
        'username_self_changed_at' => null,
    ]);
    $login->setRelation('role', $role);

    return $login;
}

it('allows a non-superuser to change their own username exactly once', function (): void {
    $login = usernameChangeLogin(21, usernameChangeRole('staff', false), 'first-username');
    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $auditLogs = Mockery::mock(AuditLogService::class);

    $clientLogins->shouldReceive('findForAuthenticationWithLock')->twice()->with(21)->andReturn($login);
    $clientLogins->shouldReceive('updateUser')->once()->with($login, Mockery::on(
        fn (array $payload): bool => $payload['username'] === 'chosen-username'
            && $payload['username_self_changed_at'] !== null,
    ))->andReturnUsing(function (ClientLogin $target, array $payload): ClientLogin {
        $target->forceFill($payload);

        return $target;
    });
    $clientLogins->shouldReceive('refreshWithRole')->once()->with($login)->andReturn($login);
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.username_self_changed',
        'Username diubah sendiri oleh user',
        $login,
        $login,
        Mockery::on(fn (array $metadata): bool => $metadata === [
            'old_username' => 'first-username',
            'new_username' => 'chosen-username',
        ]),
    );

    $service = new ProfileService(
        Mockery::mock(ClientInterface::class),
        $clientLogins,
        $auditLogs,
    );

    $updated = $service->updateProfile($login, [
        'name' => 'Updated Name',
        'username' => 'Chosen-Username',
        'email' => 'updated@example.test',
    ]);

    expect($updated->username)->toBe('chosen-username')
        ->and($updated->username_self_changed_at)->not->toBeNull()
        ->and($updated->canChangeOwnUsername())->toBeFalse();

    try {
        $service->updateProfile($updated, [
            'name' => 'Updated Again',
            'username' => 'another-username',
            'email' => 'again@example.test',
        ]);

        $this->fail('The second self-service username change should be rejected.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('accountForm.username')
            ->and($exception->errors()['accountForm.username'][0])->toContain('sudah digunakan');
    }
});

it('keeps superuser management changes unlimited without resetting the self-service marker', function (): void {
    $superuser = usernameChangeLogin(1, usernameChangeRole('superuser', true), 'superuser');
    $targetRole = usernameChangeRole('staff', false);
    $target = usernameChangeLogin(22, $targetRole, 'self-changed');
    $selfChangedAt = now()->subDay();
    $target->forceFill(['username_self_changed_at' => $selfChangedAt]);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientRoles = Mockery::mock(ClientRoleInterface::class);
    $auditLogs = Mockery::mock(AuditLogService::class);

    $clientRoles->shouldReceive('findBasicById')->twice()->with(2)->andReturn($targetRole);
    $clientLogins->shouldReceive('findForManagement')->twice()->with(22)->andReturn($target);
    $clientLogins->shouldReceive('updateUser')->twice()->with($target, Mockery::on(
        fn (array $payload): bool => ! array_key_exists('username_self_changed_at', $payload),
    ))->andReturnUsing(function (ClientLogin $login, array $payload): ClientLogin {
        $login->forceFill($payload);

        return $login;
    });
    $auditLogs->shouldReceive('withinAction')->twice()->andReturnUsing(
        fn (string $key, string $label, Closure $callback): ClientLogin => $callback(),
    );

    $service = new UserManagementUserService(
        $clientLogins,
        $clientRoles,
        Mockery::mock(AppModInterface::class),
        $auditLogs,
        Mockery::mock(TemporaryPasswordMailService::class),
    );

    foreach (['admin-change-one', 'admin-change-two'] as $username) {
        $service->saveUser($superuser, 22, [
            'name' => $target->name,
            'username' => $username,
            'email' => $target->email,
            'client_role_id' => 2,
            'status' => 'active',
        ]);
    }

    expect($target->username)->toBe('admin-change-two')
        ->and($target->username_self_changed_at?->timestamp)->toBe($selfChangedAt->timestamp);
});

it('renders the one-time username control in every profile theme and ships its marker migration', function (): void {
    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $profile = file_get_contents(StarterPaths::path(
            "resources/themes/{$theme}/views/starter/profile/edit-my-profile.blade.php",
        ));

        expect($profile)
            ->toContain('wire:model.defer="accountForm.username"')
            ->toContain('wire:key="profile-username-{{ $canChangeOwnUsername ? \'editable\' : \'locked\' }}"')
            ->toContain('@readonly(! $canChangeOwnUsername)')
            ->toContain('Username hanya dapat Anda ganti satu kali.')
            ->toContain('Hubungi Superuser untuk perubahan berikutnya.');
    }

    $migration = file_get_contents(StarterPaths::path(
        'database/migrations/starter/2026_09_24_000000_add_username_self_changed_at_to_starter_client_logins.php',
    ));

    expect($migration)->toContain("timestamp('username_self_changed_at')->nullable()")
        ->toContain("dropColumn('username_self_changed_at')");
});
