<?php

namespace Aldhi88\StarterKit\Livewire\Starter\UserManagement;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\UserManagementUserService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts::app')]
class Users extends Component
{
    private UserManagementUserService $userService;

    private AuthenticatedLoginService $authenticatedLogins;

    public bool $embedded = false;

    public function boot(
        UserManagementUserService $userService,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->userService = $userService;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public ?int $passwordResetUserId = null;

    public string $passwordResetUserName = '';

    public string $passwordResetUserEmail = '';

    public bool $passwordResetModalOpen = false;

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
    }

    #[On('starter-user-reset-request')]
    public function preparePasswordReset(int $id): void
    {
        $login = $this->users()->findPasswordResetTarget($this->login(), $id);
        $this->passwordResetUserId = $login->id;
        $this->passwordResetUserName = $login->name;
        $this->passwordResetUserEmail = $login->email;
        $this->passwordResetModalOpen = true;
    }

    public function cancelPasswordReset(): void
    {
        $this->passwordResetUserId = null;
        $this->passwordResetUserName = '';
        $this->passwordResetUserEmail = '';
        $this->passwordResetModalOpen = false;
    }

    public function resetSelectedPassword(): void
    {
        if ($this->passwordResetUserId === null) {
            return;
        }

        $login = $this->users()->findPasswordResetTarget($this->login(), $this->passwordResetUserId);

        try {
            $this->users()->resetPassword($this->login(), $login->id);
        } catch (ValidationException $exception) {
            $this->dispatch(
                'starter-toast',
                type: 'danger',
                message: collect($exception->errors())->flatten()->first()
                    ?? 'Password tidak direset karena email gagal diproses oleh queue.',
            );

            return;
        }

        $this->passwordResetUserId = null;
        $this->passwordResetUserName = '';
        $this->passwordResetUserEmail = '';
        $this->passwordResetModalOpen = false;
        $this->dispatch(
            'starter-toast',
            type: 'success',
            message: 'Permintaan email password sementara baru berhasil diproses untuk '.$login->email.'.',
        );
    }

    public function render()
    {
        return view(StarterTheme::viewName('starter.user-management.users'), [
            'appCount' => $this->users()->appCount(),
        ])->title('Manajemen User');
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
