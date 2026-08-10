<?php

namespace Altekno\StarterKit\Livewire\Starter\UserManagement;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\UserManagementRoleService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Roles extends Component
{
    use WithPagination;

    private UserManagementRoleService $roleService;

    private AuthenticatedLoginService $authenticatedLogins;

    public bool $embedded = false;

    public string $search = '';

    public bool $roleUsersModalOpen = false;

    public string $roleUsersRoleName = '';

    /**
     * @var array<int, array{name: string, email: string}>
     */
    public array $roleUsers = [];

    public bool $roleAccessModalOpen = false;

    public string $roleAccessRoleName = '';

    public string $roleAccessRoleCode = '';

    public bool $roleAccessIsFull = false;

    public bool $roleAccessCanManageSettings = false;

    public bool $roleAccessCanViewLogs = false;

    public int $roleAccessAppCount = 0;

    public int $roleAccessModuleCount = 0;

    public function boot(
        UserManagementRoleService $roleService,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->roleService = $roleService;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    /**
     * @var array<int, array{name: string, modules: array<int, array{name: string, code: string, desc: string}>}>
     */
    public array $roleAccessApps = [];

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
    }

    public function updatedSearch(): void
    {
        $this->resetPage('rolesPage');
    }

    #[On('starter-role-users-request')]
    public function showRoleUsers(int $id): void
    {
        $role = $this->roles()->findRole($this->login(), $id);
        $roleUsers = $this->roles()->roleUsers($role);

        $this->roleUsersRoleName = $role->name;
        $this->roleUsers = $roleUsers
            ->map(fn (ClientLogin $login): array => [
                'name' => $login->name,
                'email' => $login->email,
            ])
            ->values()
            ->all();
        $this->roleUsersModalOpen = true;
    }

    public function closeRoleUsersModal(): void
    {
        $this->roleUsersModalOpen = false;
        $this->roleUsersRoleName = '';
        $this->roleUsers = [];
    }

    #[On('starter-role-access-request')]
    public function showRoleAccess(int $id): void
    {
        $role = $this->roles()->findRole($this->login(), $id);
        $modules = $role->isSuperuser()
            ? $this->roles()->availableModules()
            : $role->mods;

        $this->roleAccessRoleName = $role->name;
        $this->roleAccessRoleCode = $role->code;
        $this->roleAccessIsFull = $role->isSuperuser();
        $this->roleAccessCanManageSettings = $role->canManageSettings();
        $this->roleAccessCanViewLogs = $role->canViewLogs();
        $this->roleAccessModuleCount = $modules->count();
        $this->roleAccessApps = $modules
            ->groupBy(fn ($module): string => $module->app?->name ?? 'Tanpa App')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(function ($appModules, string $appName): array {
                return [
                    'name' => $appName,
                    'modules' => $appModules
                        ->sortBy(fn ($module): string => $module->name, SORT_NATURAL | SORT_FLAG_CASE)
                        ->map(fn ($module): array => [
                            'name' => $module->name,
                            'code' => $module->code,
                            'desc' => (string) $module->desc,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
        $this->roleAccessAppCount = count($this->roleAccessApps);
        $this->roleAccessModalOpen = true;
    }

    public function closeRoleAccessModal(): void
    {
        $this->roleAccessModalOpen = false;
        $this->roleAccessRoleName = '';
        $this->roleAccessRoleCode = '';
        $this->roleAccessIsFull = false;
        $this->roleAccessCanManageSettings = false;
        $this->roleAccessCanViewLogs = false;
        $this->roleAccessAppCount = 0;
        $this->roleAccessModuleCount = 0;
        $this->roleAccessApps = [];
    }

    public function render()
    {
        $login = $this->login();
        $roles = $this->roles()->paginateRoles($login, $this->search);
        $moduleStats = $this->roles()->moduleStats();

        return view('starter.user-management.roles', [
            'roles' => $roles,
            'roleCount' => $roles->total(),
            'totalAppCount' => $moduleStats['apps'],
            'totalModuleCount' => $moduleStats['modules'],
        ])->title('Manajemen Role');
    }

    private function roles(): UserManagementRoleService
    {
        return $this->roleService;
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->settingsManager();
    }
}
