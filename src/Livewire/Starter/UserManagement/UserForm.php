<?php

namespace Altekno\StarterKit\Livewire\Starter\UserManagement;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\UserManagementUserService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class UserForm extends Component
{
    private UserManagementUserService $userService;

    private AuthenticatedLoginService $authenticatedLogins;

    public ?int $userLoginId = null;

    public ?string $temporaryPassword = null;

    public ?string $temporaryPasswordUsername = null;

    /** @var array{name: string, username: string, email: string, role_id: string, status: string} */
    public array $userForm = [
        'name' => '',
        'username' => '',
        'email' => '',
        'role_id' => '',
        'status' => 'active',
    ];

    public function boot(
        UserManagementUserService $userService,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->userService = $userService;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(?int $userLoginId = null): void
    {
        if ($userLoginId === null) {
            return;
        }

        $login = $this->users()->findUser($this->login(), $userLoginId);

        $this->userLoginId = $login->id;
        $this->userForm = [
            'name' => $login->name,
            'username' => $login->username,
            'email' => $login->email,
            'role_id' => (string) $login->client_role_id,
            'status' => $login->status,
        ];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'userForm.name' => ['required', 'string', 'max:255'],
            'userForm.username' => [
                'required', 'string', 'min:3', 'max:255', 'alpha_dash:ascii',
                Rule::unique('starter_client_logins', 'username')->ignore($this->userLoginId),
            ],
            'userForm.email' => [
                'required', 'email', 'max:255',
                Rule::unique('starter_client_logins', 'email')->ignore($this->userLoginId),
            ],
            'userForm.role_id' => [
                'required', 'integer',
                Rule::exists('starter_client_roles', 'id'),
            ],
            'userForm.status' => ['required', Rule::in(['active', 'inactive', 'locked'])],
        ])['userForm'];

        $temporaryPassword = $this->userLoginId === null ? Str::password(16) : null;
        $login = $this->users()->saveUser($this->login(), $this->userLoginId, [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'client_role_id' => $validated['role_id'],
            'status' => $validated['status'],
            'password' => $temporaryPassword,
        ]);

        $this->userLoginId = $login->id;

        if ($temporaryPassword !== null) {
            $this->temporaryPasswordUsername = $login->username;
            $this->temporaryPassword = $temporaryPassword;
        }

        $this->dispatch('starter-toast', type: 'success', message: 'User berhasil disimpan.');
    }

    public function dismissTemporaryPassword(): void
    {
        $this->temporaryPassword = null;
        $this->temporaryPasswordUsername = null;
    }

    public function render()
    {
        $roles = $this->users()->roles($this->login());
        $selectedRole = $roles->firstWhere('id', (int) $this->userForm['role_id']);
        $selectedRoleModules = $selectedRole?->isSuperuser()
            ? $this->users()->availableModules()
            : ($selectedRole?->mods ?? collect());

        return view('starter.user-management.user-form', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'selectedRoleModules' => $selectedRoleModules->groupBy(fn ($module): string => $module->app?->name ?? 'Tanpa App'),
        ])->title($this->userLoginId === null ? 'Tambah User' : 'Edit User');
    }

    private function users(): UserManagementUserService
    {
        return $this->userService;
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->settingsManager();
    }
}
