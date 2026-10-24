<?php

use Aldhi88\StarterKit\Contracts\Starter\AppModInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientRoleInterface;
use Aldhi88\StarterKit\Http\Middleware\Starter\StarterAdmin;
use Aldhi88\StarterKit\Http\Middleware\Starter\StarterLogAccess;
use Aldhi88\StarterKit\Livewire\Starter\UserManagement\RoleForm;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Starter\AppModRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientRoleRepository;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\UserManagementRoleService;
use Aldhi88\StarterKit\Services\Starter\UserManagementUserService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('r', 32))]);
    app()->register(LivewireServiceProvider::class);
    foreach (['185232_create_starter_identity_tables', '185233_create_starter_app_metadata_tables', '185235_create_starter_logs_table'] as $name) {
        (require StarterPaths::path('database/migrations/starter/2026_07_27_'.$name.'.php'))->up();
    }
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    app()->bind(ClientRoleInterface::class, ClientRoleRepository::class);
    app()->bind(AppModInterface::class, AppModRepository::class);
    app()->scoped(AuditLogService::class);
    $this->managerRole = ClientRole::create(['code' => 'manager', 'name' => 'Manager', 'can_manage_settings' => true, 'can_view_logs' => false]);
    $this->logRole = ClientRole::create(['code' => 'auditor', 'name' => 'Auditor', 'can_view_logs' => true]);
    $this->manager = ClientLogin::create(['name' => 'Manager', 'username' => 'manager', 'email' => 'manager@example.test', 'client_role_id' => $this->managerRole->id]);
    $this->actingAs($this->manager);
});

it('keeps settings and log capabilities independent for all four combinations', function (bool $settings, bool $logs): void {
    $this->managerRole->update(['can_manage_settings' => $settings, 'can_view_logs' => $logs]);
    $this->manager->unsetRelation('role');
    Route::get('/settings-gate', fn () => 'settings')->middleware(StarterAdmin::class);
    Route::get('/logs-gate', fn () => 'logs')->middleware(StarterLogAccess::class);
    $this->get('/settings-gate')->assertStatus($settings ? 200 : 403);
    $this->get('/logs-gate')->assertStatus($logs ? 200 : 403);
})->with([[true, false], [false, true], [true, true], [false, false]]);

it('rejects granting log access on an existing or newly created role and audits the denial', function (bool $existing): void {
    $id = $existing ? $this->managerRole->id : null;
    try {
        app(UserManagementRoleService::class)->saveRole($this->manager, $id, [
            'code' => 'manager', 'name' => 'Manager', 'can_manage_settings' => true, 'can_view_logs' => true,
        ], []);
        $this->fail('The unauthorized grant must fail.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
    expect($this->managerRole->fresh()->can_view_logs)->toBeFalse()
        ->and(ClientRole::count())->toBe(2)
        ->and(ActivityLog::where('action_key', 'authorization.role_access_denied')->where('client_login_id', $this->manager->id)->count())->toBe(1);
})->with([true, false]);

it('allows ordinary settings role updates without enabling logs and lets a permitted manager grant logs', function (): void {
    $roles = app(UserManagementRoleService::class);
    $saved = $roles->saveRole($this->manager, $this->managerRole->id, ['code' => 'manager', 'name' => 'Updated', 'can_manage_settings' => true, 'can_view_logs' => false], []);
    expect($saved->canViewLogs())->toBeFalse();
    $this->managerRole->update(['can_view_logs' => true]);
    $this->manager->unsetRelation('role');
    $created = $roles->saveRole($this->manager, null, ['code' => 'delegate', 'name' => 'Delegate', 'can_manage_settings' => false, 'can_view_logs' => true], []);
    expect($created->canViewLogs())->toBeTrue()->and($created->canManageSettings())->toBeFalse();
});

it('rejects a forged Livewire grant even when its checkbox is disabled', function (string $theme): void {
    config(['starter.theme' => $theme]);
    View::addLocation(StarterPaths::path("resources/themes/{$theme}/views"));
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
    require StarterPaths::path('routes/starter/global.php');
    Livewire::test(RoleForm::class)
        ->assertSee('role-can-view-logs', false)
        ->set('roleForm.code', 'forged')->set('roleForm.name', 'Forged')
        ->set('roleForm.can_manage_settings', true)->set('roleForm.can_view_logs', true)
        ->call('save')->assertForbidden();
    expect(ClientRole::where('code', 'forged')->exists())->toBeFalse();
})->with(['tabler', 'vuexy', 'dashcode']);

it('blocks indirect elevation by role assignment and by taking over a privileged account', function (string $operation): void {
    $target = ClientLogin::create(['name' => 'Auditor', 'username' => 'auditor', 'email' => 'auditor@example.test', 'client_role_id' => $this->logRole->id]);
    $users = app(UserManagementUserService::class);
    try {
        match ($operation) {
            'self-assign' => $users->saveUser($this->manager, $this->manager->id, ['name' => 'Manager', 'username' => 'manager', 'email' => 'manager@example.test', 'client_role_id' => $this->logRole->id, 'status' => 'active']),
            'password-reset' => $users->resetPassword($this->manager, $target->id),
            'privileged-update' => $users->saveUser($this->manager, $target->id, ['name' => 'Auditor', 'username' => 'auditor', 'email' => 'changed@example.test', 'client_role_id' => $this->managerRole->id, 'status' => 'active']),
            'role-update' => app(UserManagementRoleService::class)->saveRole($this->manager, $this->logRole->id, ['code' => 'auditor', 'name' => 'Auditor', 'can_view_logs' => false], []),
            'archive' => $users->archiveUsers($this->manager, [$this->manager->id, $target->id]),
        };
        $this->fail('Indirect privilege elevation must fail.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
    expect($this->manager->fresh()->client_role_id)->toBe($this->managerRole->id)
        ->and($target->fresh()->email)->toBe('auditor@example.test')
        ->and($target->fresh()->trashed())->toBeFalse()
        ->and($this->logRole->fresh()->can_view_logs)->toBeTrue();
})->with(['self-assign', 'password-reset', 'privileged-update', 'role-update', 'archive']);

it('limits assignable roles without exposing the Superuser or roles beyond the actor authority', function (): void {
    ClientRole::create(['code' => 'superuser', 'name' => 'Superuser', 'is_system' => true]);
    expect(app(ClientRoleInterface::class)->allAssignableForViewer($this->manager)->pluck('code')->all())->toBe(['manager']);
});
