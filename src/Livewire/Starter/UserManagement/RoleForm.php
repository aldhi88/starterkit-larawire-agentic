<?php

namespace Altekno\StarterKit\Livewire\Starter\UserManagement;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\UserManagementRoleService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class RoleForm extends Component
{
    private UserManagementRoleService $roleService;

    private AuthenticatedLoginService $authenticatedLogins;

    public ?int $roleId = null;

    public ?int $deleteRoleId = null;

    public string $deleteRoleName = '';

    public bool $deleteRoleModalOpen = false;

    /**
     * @var array{code: string, name: string, desc: string, can_manage_settings: bool, can_view_logs: bool, module_ids: array<int, string>, landing_menu_ids: array<int|string, string>}
     */
    public array $roleForm = [
        'code' => '',
        'name' => '',
        'desc' => '',
        'can_manage_settings' => false,
        'can_view_logs' => false,
        'module_ids' => [],
        'landing_menu_ids' => [],
    ];

    public function boot(
        UserManagementRoleService $roleService,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->roleService = $roleService;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(?int $roleId = null): void
    {
        $this->roleId = $roleId;

        if ($roleId === null) {
            return;
        }

        $role = $this->roles()->findRole($this->login(), $roleId);
        $this->roleForm = [
            'code' => $role->code,
            'name' => $role->name,
            'desc' => (string) $role->desc,
            'can_manage_settings' => $role->canManageSettings(),
            'can_view_logs' => $role->canViewLogs(),
            'module_ids' => $role->mods->pluck('id')->map(fn (int $id): string => (string) $id)->values()->all(),
            'landing_menu_ids' => $role->landings->mapWithKeys(fn ($landing): array => [
                $landing->app_id => (string) $landing->app_menu_id,
            ])->all(),
        ];
    }

    public function updatedRoleFormCode(string $value): void
    {
        $this->roleForm['code'] = Str::of($value)->lower()->slug('_')->toString();
    }

    public function updatedRoleFormModuleIds(): void
    {
        $this->roleForm['landing_menu_ids'] = $this->normalizedLandingMenuIds(
            $this->roleForm['module_ids'],
            $this->roleForm['landing_menu_ids'],
        );
    }

    public function save(): mixed
    {
        $this->roleForm['landing_menu_ids'] = $this->normalizedLandingMenuIds(
            $this->roleForm['module_ids'],
            $this->roleForm['landing_menu_ids'],
        );

        $validated = $this->validate([
            'roleForm.code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('starter_client_roles', 'code')
                    ->ignore($this->roleId),
            ],
            'roleForm.name' => ['required', 'string', 'max:255'],
            'roleForm.desc' => ['nullable', 'string', 'max:2000'],
            'roleForm.can_manage_settings' => ['boolean'],
            'roleForm.can_view_logs' => ['boolean'],
            'roleForm.module_ids' => ['array'],
            'roleForm.module_ids.*' => ['integer', 'exists:starter_app_mods,id'],
            'roleForm.landing_menu_ids' => ['array'],
            'roleForm.landing_menu_ids.*' => ['nullable', 'integer', 'exists:starter_app_menus,id'],
        ], [], [
            'roleForm.code' => 'kode',
            'roleForm.name' => 'nama',
            'roleForm.desc' => 'deskripsi',
            'roleForm.can_manage_settings' => 'akses pengaturan',
            'roleForm.can_view_logs' => 'akses log aktivitas',
            'roleForm.module_ids' => 'akses module',
            'roleForm.module_ids.*' => 'akses module',
            'roleForm.landing_menu_ids' => 'halaman awal',
            'roleForm.landing_menu_ids.*' => 'halaman awal',
        ])['roleForm'];

        try {
            $this->roles()->saveRole(
                $this->login(),
                $this->roleId,
                $validated,
                $validated['module_ids'],
                $validated['landing_menu_ids'] ?? [],
            );
        } catch (ValidationException $exception) {
            $this->dispatch('starter-toast', type: 'danger', message: $this->firstValidationMessage($exception));

            return null;
        }

        session()->flash('starter-toast', [
            'type' => 'success',
            'message' => 'Role berhasil disimpan.',
        ]);

        return $this->redirectRoute('starter.settings', ['section' => 'roles'], navigate: true);
    }

    public function prepareRoleDeletion(): void
    {
        if ($this->roleId === null) {
            return;
        }

        $role = $this->roles()->findRole($this->login(), $this->roleId);
        $this->deleteRoleId = $role->id;
        $this->deleteRoleName = $role->name;
        $this->deleteRoleModalOpen = true;
    }

    public function cancelRoleDeletion(): void
    {
        $this->deleteRoleId = null;
        $this->deleteRoleName = '';
        $this->deleteRoleModalOpen = false;
    }

    public function deleteSelectedRole(): mixed
    {
        if ($this->deleteRoleId === null) {
            return null;
        }

        try {
            $this->roles()->deleteRole($this->login(), $this->deleteRoleId);
        } catch (ValidationException $exception) {
            $this->dispatch('starter-toast', type: 'danger', message: $this->firstValidationMessage($exception));

            return null;
        }

        $this->deleteRoleId = null;
        $this->deleteRoleName = '';
        $this->deleteRoleModalOpen = false;
        session()->flash('starter-toast', [
            'type' => 'success',
            'message' => 'Role berhasil diarsipkan.',
        ]);

        return $this->redirectRoute('starter.settings', ['section' => 'roles'], navigate: true);
    }

    public function render()
    {
        $selectedRole = $this->roleId === null
            ? null
            : $this->roles()->findRole($this->login(), $this->roleId);
        $modules = $this->roles()->availableModules()
            ->groupBy(fn ($mod): string => $mod->app?->name ?? 'Tanpa App');

        return view('starter.user-management.role-form', [
            'selectedRole' => $selectedRole,
            'modules' => $modules,
        ])->title($this->roleId === null ? 'Tambah Role' : 'Edit Role');
    }

    private function roles(): UserManagementRoleService
    {
        return $this->roleService;
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->settingsManager();
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first() ?? 'Data tidak valid.';
    }

    /**
     * @param  array<int, int|string>  $moduleIds
     * @param  array<int|string, int|string|null>  $landingMenuIds
     * @return array<int, string>
     */
    private function normalizedLandingMenuIds(array $moduleIds, array $landingMenuIds): array
    {
        $selectedModuleIds = collect($moduleIds)
            ->map(fn (int|string $id): string => (string) $id)
            ->filter()
            ->values();

        if ($selectedModuleIds->isEmpty()) {
            return [];
        }

        $nextLandingMenuIds = [];

        $this->roles()
            ->availableModules()
            ->filter(fn ($module): bool => $selectedModuleIds->contains((string) $module->id))
            ->groupBy('app_id')
            ->each(function ($appModules, int|string $appId) use (&$nextLandingMenuIds, $landingMenuIds): void {
                $candidateMenuIds = $appModules
                    ->flatMap(fn ($module) => $module->menus)
                    ->pluck('id')
                    ->map(fn (int|string $id): string => (string) $id)
                    ->unique()
                    ->values();

                $currentMenuId = (string) ($landingMenuIds[$appId] ?? '');

                if ($candidateMenuIds->contains($currentMenuId)) {
                    $nextLandingMenuIds[$appId] = $currentMenuId;

                    return;
                }

                if ($candidateMenuIds->count() === 1) {
                    $nextLandingMenuIds[$appId] = $candidateMenuIds->first();
                }
            });

        return $nextLandingMenuIds;
    }
}
