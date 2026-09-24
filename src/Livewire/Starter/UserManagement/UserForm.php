<?php

namespace Aldhi88\StarterKit\Livewire\Starter\UserManagement;

use Aldhi88\StarterKit\Models\Starter\AppMod;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Rules\Starter\StarterPasswordRules;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\UserManagementUserService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class UserForm extends Component
{
    private UserManagementUserService $userService;

    private AuthenticatedLoginService $authenticatedLogins;

    public ?int $userLoginId = null;

    /** @var array{name: string, username: string, email: string, role_id: string, status: string, use_manual_password: bool, password: string, password_confirmation: string} */
    public array $userForm = [
        'name' => '',
        'username' => '',
        'email' => '',
        'role_id' => '',
        'status' => 'active',
        'use_manual_password' => false,
        'password' => '',
        'password_confirmation' => '',
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
            'use_manual_password' => false,
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    public function save(): void
    {
        $creating = $this->userLoginId === null;
        $rules = [
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
        ];

        if ($creating) {
            $rules['userForm.use_manual_password'] = ['required', 'boolean'];

            if ($this->userForm['use_manual_password']) {
                $rules['userForm.password'] = [...StarterPasswordRules::rules(), 'same:userForm.password_confirmation'];
                $rules['userForm.password_confirmation'] = ['required', 'string', 'max:255'];
            }
        }

        $validated = $this->validate($rules, [], [
            'userForm.password' => 'password sementara',
            'userForm.password_confirmation' => 'konfirmasi password sementara',
        ])['userForm'];

        $useManualPassword = $creating && (bool) ($validated['use_manual_password'] ?? false);
        $login = $this->users()->saveUser($this->login(), $this->userLoginId, [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'client_role_id' => $validated['role_id'],
            'status' => $validated['status'],
        ], $useManualPassword ? $validated['password'] : null);

        $this->userLoginId = $login->id;
        $this->clearManualPassword();

        $this->dispatch(
            'starter-toast',
            type: 'success',
            message: $creating
                ? ($useManualPassword
                    ? 'User berhasil dibuat dengan password sementara dari admin. User wajib mengganti password saat login pertama.'
                    : 'User berhasil dibuat. Permintaan email password sementara berhasil diproses untuk '.$login->email.'.')
                : 'User berhasil disimpan.',
        );
    }

    public function updatedUserForm(mixed $value, string $key): void
    {
        if ($key === 'use_manual_password' && ! (bool) $value) {
            $this->clearManualPassword();
            $this->resetValidation(['userForm.password', 'userForm.password_confirmation']);
        }
    }

    public function render()
    {
        $roles = $this->users()->roles($this->login());
        $selectedRole = $roles->firstWhere('id', (int) $this->userForm['role_id']);
        $selectedRoleModules = match (true) {
            ! $selectedRole instanceof ClientRole => collect(),
            $selectedRole->isSuperuser() => $this->users()->availableModules(),
            default => $selectedRole->mods,
        };

        return view(StarterTheme::viewName('starter.user-management.user-form'), [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'selectedRoleModules' => $selectedRoleModules->groupBy(
                fn (AppMod $module): string => $module->app->name,
            ),
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

    private function clearManualPassword(): void
    {
        $this->userForm['use_manual_password'] = false;
        $this->userForm['password'] = '';
        $this->userForm['password_confirmation'] = '';
    }
}
