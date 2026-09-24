<?php

use Aldhi88\StarterKit\Contracts\Starter\AppModInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientRoleInterface;
use Aldhi88\StarterKit\Exceptions\Starter\TemporaryPasswordDeliveryException;
use Aldhi88\StarterKit\Mail\Starter\TemporaryPasswordMail;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\TemporaryPasswordMailService;
use Aldhi88\StarterKit\Services\Starter\UserManagementUserService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

function temporaryPasswordRole(string $code = 'staff', bool $system = false): ClientRole
{
    $role = new ClientRole;
    $role->forceFill([
        'id' => $system ? 1 : 2,
        'code' => $code,
        'name' => $system ? 'Superuser' : 'Staff',
        'is_system' => $system,
    ]);

    return $role;
}

function temporaryPasswordLogin(int $id, ClientRole $role, string $username, string $email): ClientLogin
{
    $login = new ClientLogin;
    $login->forceFill([
        'id' => $id,
        'client_role_id' => $role->id,
        'name' => str($username)->headline()->toString(),
        'username' => $username,
        'email' => $email,
        'status' => 'active',
        'must_change_password' => false,
        'failed_login_count' => 0,
        'auth_version' => 1,
    ]);
    $login->setRelation('role', $role);

    return $login;
}

function temporaryPasswordUserService(
    ClientLoginInterface $clientLogins,
    ClientRoleInterface $clientRoles,
    AuditLogService $auditLogs,
    TemporaryPasswordMailService $mailService,
): UserManagementUserService {
    return new UserManagementUserService(
        $clientLogins,
        $clientRoles,
        Mockery::mock(AppModInterface::class),
        $auditLogs,
        $mailService,
    );
}

beforeEach(function (): void {
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
    config()->set('app.name', 'Example App');
    config()->set('app.url', 'https://company.test');
    config()->set('app.domain', 'company.test');
    config()->set('mail.default', 'log');
    config()->set('mail.mailers.log', ['transport' => 'log']);
    config()->set('mail.mailers.array', ['transport' => 'array']);
});

it('queues an encrypted generated password after user creation without returning it to Livewire', function (): void {
    $mail = Mail::fake();
    $role = temporaryPasswordRole();
    $currentLogin = temporaryPasswordLogin(1, temporaryPasswordRole('superuser', true), 'superuser', 'admin@example.test');
    $createdLogin = temporaryPasswordLogin(2, $role, 'new-user', 'new-user@example.test');
    $createdPayload = null;

    $clientRoles = Mockery::mock(ClientRoleInterface::class);
    $clientRoles->shouldReceive('findBasicById')->once()->with(2)->andReturn($role);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('createUser')->once()->with(Mockery::on(function (array $payload) use (&$createdPayload): bool {
        $createdPayload = $payload;

        return is_string($payload['password'] ?? null)
            && strlen($payload['password']) === 16
            && ($payload['must_change_password'] ?? false) === true;
    }))->andReturn($createdLogin);

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('withinAction')->once()->with(
        'user.create',
        'Membuat user New User',
        Mockery::type(Closure::class),
    )->andReturnUsing(fn (string $key, string $label, Closure $callback): mixed => $callback());
    $auditLogs->shouldNotReceive('recordSecurityEvent');

    $service = temporaryPasswordUserService(
        $clientLogins,
        $clientRoles,
        $auditLogs,
        new TemporaryPasswordMailService($mail),
    );

    $result = $service->saveUser($currentLogin, null, [
        'name' => 'New User',
        'username' => 'new-user',
        'email' => 'new-user@example.test',
        'client_role_id' => 2,
        'status' => 'active',
    ]);

    expect($result)->toBe($createdLogin)
        ->and($createdPayload)->not->toBeNull();

    Mail::assertQueued(TemporaryPasswordMail::class, function (TemporaryPasswordMail $message) use ($createdPayload): bool {
        return $message->hasTo('new-user@example.test')
            && $message->appName === 'Example App'
            && $message->username === 'new-user'
            && $message->temporaryPassword === $createdPayload['password']
            && $message->passwordWasReset === false
            && $message->mailer === 'array';
    });
});

it('creates a user with an administrator supplied temporary password without sending credential email', function (): void {
    $role = temporaryPasswordRole();
    $currentLogin = temporaryPasswordLogin(1, temporaryPasswordRole('superuser', true), 'superuser', 'admin@example.test');
    $createdLogin = temporaryPasswordLogin(2, $role, 'dummy-email-user', 'dummy@example.test');

    $clientRoles = Mockery::mock(ClientRoleInterface::class);
    $clientRoles->shouldReceive('findBasicById')->once()->with(2)->andReturn($role);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('createUser')->once()->with(Mockery::on(fn (array $payload): bool => $payload['password'] === 'Manual123'
        && $payload['must_change_password'] === true
    ))->andReturn($createdLogin);

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('withinAction')->once()->with(
        'user.create',
        'Membuat user Dummy Email User',
        Mockery::type(Closure::class),
    )->andReturnUsing(fn (string $key, string $label, Closure $callback): mixed => $callback());
    $auditLogs->shouldNotReceive('recordSecurityEvent');

    $mailService = Mockery::mock(TemporaryPasswordMailService::class);
    $mailService->shouldNotReceive('queueForNewAccount');

    $result = temporaryPasswordUserService(
        $clientLogins,
        $clientRoles,
        $auditLogs,
        $mailService,
    )->saveUser($currentLogin, null, [
        'name' => 'Dummy Email User',
        'username' => 'dummy-email-user',
        'email' => 'dummy@example.test',
        'client_role_id' => 2,
        'status' => 'active',
    ], 'Manual123');

    expect($result)->toBe($createdLogin);
});

it('rejects an administrator supplied temporary password that does not meet policy', function (): void {
    $role = temporaryPasswordRole();
    $currentLogin = temporaryPasswordLogin(1, temporaryPasswordRole('superuser', true), 'superuser', 'admin@example.test');

    $clientRoles = Mockery::mock(ClientRoleInterface::class);
    $clientRoles->shouldReceive('findBasicById')->once()->with(2)->andReturn($role);

    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldNotReceive('createUser');

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldNotReceive('withinAction');

    $mailService = Mockery::mock(TemporaryPasswordMailService::class);
    $mailService->shouldNotReceive('queueForNewAccount');

    expect(fn () => temporaryPasswordUserService(
        $clientLogins,
        $clientRoles,
        $auditLogs,
        $mailService,
    )->saveUser($currentLogin, null, [
        'name' => 'Weak Password User',
        'username' => 'weak-password-user',
        'email' => 'dummy@example.test',
        'client_role_id' => 2,
        'status' => 'active',
    ], 'weak'))
        ->toThrow(ValidationException::class);
});

it('queues an encrypted temporary password after a superuser confirms reset', function (): void {
    $mail = Mail::fake();
    $role = temporaryPasswordRole();
    $currentLogin = temporaryPasswordLogin(1, temporaryPasswordRole('superuser', true), 'superuser', 'admin@example.test');
    $targetLogin = temporaryPasswordLogin(2, $role, 'existing-user', 'existing@example.test');
    $resetPayload = null;

    $clientRoles = Mockery::mock(ClientRoleInterface::class);
    $clientLogins = Mockery::mock(ClientLoginInterface::class);
    $clientLogins->shouldReceive('findForManagement')->once()->with(2)->andReturn($targetLogin);
    $clientLogins->shouldReceive('updateUser')->once()->with($targetLogin, Mockery::on(function (array $payload) use (&$resetPayload): bool {
        $resetPayload = $payload;

        return is_string($payload['password'] ?? null)
            && strlen($payload['password']) === 16
            && $payload['auth_version'] === 2
            && $payload['must_change_password'] === true;
    }))->andReturn($targetLogin);

    $auditLogs = Mockery::mock(AuditLogService::class);
    $auditLogs->shouldReceive('withinAction')->once()->with(
        'user.reset_password',
        'Reset password user Existing User',
        Mockery::type(Closure::class),
    )->andReturnUsing(fn (string $key, string $label, Closure $callback): mixed => $callback());
    $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
        'auth.password_reset_by_admin',
        'Password direset oleh administrator',
        $targetLogin,
        $currentLogin,
        ['must_change_password' => true, 'delivery' => 'queued_email'],
    );

    $service = temporaryPasswordUserService(
        $clientLogins,
        $clientRoles,
        $auditLogs,
        new TemporaryPasswordMailService($mail),
    );

    $service->resetPassword($currentLogin, 2);

    Mail::assertQueued(TemporaryPasswordMail::class, function (TemporaryPasswordMail $message) use ($resetPayload): bool {
        return $message->hasTo('existing@example.test')
            && $message->appName === 'Example App'
            && $message->temporaryPassword === $resetPayload['password']
            && $message->passwordWasReset === true
            && $message->mailer === 'array';
    });
});

it('rolls back user creation when credential email cannot be queued', function (): void {
    Schema::create('temporary_password_creation_probe', function ($table): void {
        $table->id();
        $table->string('marker');
    });

    try {
        $role = temporaryPasswordRole();
        $currentLogin = temporaryPasswordLogin(1, temporaryPasswordRole('superuser', true), 'superuser', 'admin@example.test');
        $createdLogin = temporaryPasswordLogin(2, $role, 'new-user', 'new-user@example.test');

        $clientRoles = Mockery::mock(ClientRoleInterface::class);
        $clientRoles->shouldReceive('findBasicById')->once()->with(2)->andReturn($role);

        $clientLogins = Mockery::mock(ClientLoginInterface::class);
        $clientLogins->shouldReceive('createUser')->once()->andReturnUsing(function () use ($createdLogin): ClientLogin {
            DB::table('temporary_password_creation_probe')->insert(['marker' => 'user-created']);

            return $createdLogin;
        });

        $mailService = Mockery::mock(TemporaryPasswordMailService::class);
        $mailService->shouldReceive('queueForNewAccount')->once()->andThrow(new TemporaryPasswordDeliveryException);

        $auditLogs = Mockery::mock(AuditLogService::class);
        $auditLogs->shouldReceive('withinAction')->once()->andReturnUsing(
            fn (string $key, string $label, Closure $callback): mixed => $callback(),
        );
        $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
            'auth.temporary_password_delivery_failed',
            'Pengiriman email password sementara gagal',
            $createdLogin,
            $currentLogin,
            ['operation' => 'user_created', 'delivery' => 'queued_email'],
        );

        $service = temporaryPasswordUserService($clientLogins, $clientRoles, $auditLogs, $mailService);

        expect(fn () => $service->saveUser($currentLogin, null, [
            'name' => 'New User',
            'username' => 'new-user',
            'email' => 'new-user@example.test',
            'client_role_id' => 2,
            'status' => 'active',
        ]))
            ->toThrow(ValidationException::class)
            ->and(DB::table('temporary_password_creation_probe')->count())->toBe(0);
    } finally {
        Schema::dropIfExists('temporary_password_creation_probe');
    }
});

it('rolls back a password reset when credential email cannot be queued', function (): void {
    Schema::create('temporary_password_mutation_probe', function ($table): void {
        $table->id();
        $table->string('marker');
    });

    try {
        $role = temporaryPasswordRole();
        $currentLogin = temporaryPasswordLogin(1, temporaryPasswordRole('superuser', true), 'superuser', 'admin@example.test');
        $targetLogin = temporaryPasswordLogin(2, $role, 'existing-user', 'existing@example.test');

        $clientRoles = Mockery::mock(ClientRoleInterface::class);
        $clientLogins = Mockery::mock(ClientLoginInterface::class);
        $clientLogins->shouldReceive('findForManagement')->once()->with(2)->andReturn($targetLogin);
        $clientLogins->shouldReceive('updateUser')->once()->andReturnUsing(function (ClientLogin $login): ClientLogin {
            DB::table('temporary_password_mutation_probe')->insert(['marker' => 'password-changed']);

            return $login;
        });

        $mailService = Mockery::mock(TemporaryPasswordMailService::class);
        $mailService->shouldReceive('queueForReset')->once()->andThrow(new TemporaryPasswordDeliveryException);

        $auditLogs = Mockery::mock(AuditLogService::class);
        $auditLogs->shouldReceive('withinAction')->once()->andReturnUsing(
            fn (string $key, string $label, Closure $callback): mixed => $callback(),
        );
        $auditLogs->shouldReceive('recordSecurityEvent')->once()->with(
            'auth.temporary_password_delivery_failed',
            'Pengiriman email password sementara gagal',
            $targetLogin,
            $currentLogin,
            ['operation' => 'password_reset', 'delivery' => 'queued_email'],
        );

        $service = temporaryPasswordUserService($clientLogins, $clientRoles, $auditLogs, $mailService);

        expect(fn () => $service->resetPassword($currentLogin, 2))
            ->toThrow(ValidationException::class)
            ->and(DB::table('temporary_password_mutation_probe')->count())->toBe(0);
    } finally {
        Schema::dropIfExists('temporary_password_mutation_probe');
    }
});

it('keeps generated temporary passwords out of Livewire and provides manual password controls in every theme', function (): void {
    $livewire = file_get_contents(StarterPaths::path('src/Livewire/Starter/UserManagement/UserForm.php'))
        .file_get_contents(StarterPaths::path('src/Livewire/Starter/UserManagement/Users.php'));

    expect($livewire)
        ->not->toContain('temporaryPassword')
        ->not->toContain('temporaryPasswordUsername');

    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $views = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/user-management/user-form.blade.php"))
            .file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/user-management/users.blade.php"));
        $profileView = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/profile/edit-my-profile.blade.php"));

        expect($views)
            ->not->toContain('$temporaryPassword')
            ->not->toContain('data-temporary-credentials-alert')
            ->toContain('wire:model.live="userForm.use_manual_password"')
            ->toContain('wire:model.defer="userForm.password"')
            ->toContain('wire:model.defer="userForm.password_confirmation"')
            ->toContain('Email kredensial tidak akan dikirim.')
            ->toContain('User tetap wajib mengganti password saat login pertama.');

        expect($profileView)
            ->toContain('melalui email atau dari administrator')
            ->not->toContain('password sementara yang dikirim ke email Anda');
    }
});

it('disables reset confirmation and shows a queue loader in every theme', function (): void {
    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        $usersView = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/user-management/users.blade.php"));
        $modalView = file_get_contents(StarterPaths::path("resources/themes/{$theme}/views/starter/templates/components/alert-modal.blade.php"));

        expect($usersView)
            ->toContain("'loadingText' => 'Memproses email...'")
            ->toContain("'confirmAction' => 'resetSelectedPassword'")
            ->and($modalView)
            ->toContain('wire:loading.attr="disabled"')
            ->toContain('wire:loading.remove')
            ->toContain('wire:target="{{ $loadingTarget }}"')
            ->toContain('{{ $loadingText }}');
    }
});

it('brands html and text email from the configured application name', function (): void {
    $mail = new TemporaryPasswordMail(
        appName: 'Example App',
        recipientName: 'Example User',
        username: 'example-user',
        temporaryPassword: 'Temporary123',
        loginUrl: 'https://company.test/auth/login',
        passwordWasReset: true,
    );

    expect($mail->envelope()->subject)->toBe('Password sementara baru - Example App')
        ->and($mail)->toBeInstanceOf(ShouldQueueAfterCommit::class)
        ->and($mail)->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and($mail->content()->view)->toBe('starter-shared::mail.temporary-password')
        ->and($mail->content()->text)->toBe('starter-shared::mail.temporary-password-text')
        ->and($mail->render())->toContain('Example App')
        ->and(file_get_contents(StarterPaths::path('resources/views/mail/temporary-password.blade.php')))
        ->not->toContain('starter.templates')
        ->not->toContain('logo')
        ->toContain('$appName');
});
