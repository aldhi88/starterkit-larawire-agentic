<?php

namespace Altekno\StarterKit\Livewire\Starter\Profile;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Rules\Starter\StarterPasswordRules;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Altekno\StarterKit\Services\Starter\ProfileService;
use Altekno\StarterKit\Services\Starter\StarterConfigService;
use Altekno\StarterKit\Services\Starter\StarterContextService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
class EditMyProfile extends Component
{
    use WithFileUploads;

    private const DEFAULT_PROFILE_PHOTO = 'assets/starter/images/avatar.png';

    private ProfileService $profiles;

    private StarterConfigService $configs;

    private StarterContextService $context;

    private NavigationAuthorizedRedirectService $redirects;

    private AuthenticatedLoginService $authenticatedLogins;

    public string $activeTab = 'account-details';

    /** @var array{name: string, email: string} */
    public array $accountForm = [
        'name' => '',
        'email' => '',
    ];

    public mixed $profilePhotoUpload = null;

    public bool $profilePhotoReset = false;

    /**
     * @var array{current_password: string, password: string, password_confirmation: string}
     */
    public array $passwordForm = [
        'current_password' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public function boot(
        ProfileService $profiles,
        StarterConfigService $configs,
        StarterContextService $context,
        NavigationAuthorizedRedirectService $redirects,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->profiles = $profiles;
        $this->configs = $configs;
        $this->context = $context;
        $this->redirects = $redirects;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(): void
    {
        $login = $this->login();
        $this->activeTab = $login->must_change_password || request()->query('tab') === 'security'
            ? 'security'
            : 'account-details';
        $this->fillFromLogin($login);
    }

    public function saveAccount(): void
    {
        $this->activeTab = 'account-details';
        $login = $this->login();

        $validated = $this->validate([
            'accountForm.name' => ['required', 'string', 'max:255'],
            'accountForm.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('starter_client_logins', 'email')->ignore($login->id),
            ],
            'profilePhotoUpload' => [
                'nullable',
                'image',
                'dimensions:max_width=4096,max_height=4096',
                'max:2048',
            ],
        ], [], [
            'accountForm.name' => 'display name',
            'accountForm.email' => 'email login',
            'profilePhotoUpload' => 'profile photo upload',
        ])['accountForm'];

        $oldProfilePhoto = (string) $login->profile_photo;
        $validated['profile_photo'] = $oldProfilePhoto;

        if ($this->profilePhotoUpload instanceof TemporaryUploadedFile) {
            $validated['profile_photo'] = 'storage/'.$this->profilePhotoUpload->store(
                "starter/profile-photos/{$login->id}",
                'public'
            );
        }

        $updatedLogin = $this->profiles->updateProfile($login, $validated);

        if ($oldProfilePhoto && $oldProfilePhoto !== (string) $updatedLogin->profile_photo) {
            $this->deleteStoredProfilePhoto($oldProfilePhoto, $login->id);
        }

        $this->profilePhotoUpload = null;
        $this->profilePhotoReset = false;
        $this->fillFromLogin($updatedLogin);
        $this->dispatch('starter-account-updated',
            avatarUrl: $this->context->avatarUrl($updatedLogin),
            name: $updatedLogin->name,
            roleName: $updatedLogin->role?->name ?? 'Role',
        );

        $this->dispatch('starter-toast', type: 'success', message: 'Profil berhasil disimpan.');
    }

    public function resetProfilePhoto(): void
    {
        $this->activeTab = 'account-details';
        $login = $this->login();
        $oldProfilePhoto = (string) $login->profile_photo;

        if ($oldProfilePhoto) {
            $updatedLogin = $this->profiles->updateProfile($login, [
                'name' => $login->name,
                'email' => $login->email,
                'profile_photo' => self::DEFAULT_PROFILE_PHOTO,
            ]);

            $this->deleteStoredProfilePhoto($oldProfilePhoto, $login->id);

            $this->dispatch('starter-account-updated',
                avatarUrl: $this->context->avatarUrl($updatedLogin),
                name: $updatedLogin->name,
                roleName: $updatedLogin->role?->name ?? 'Role',
            );

            $this->dispatch('starter-toast', type: 'success', message: 'Foto profil berhasil dikembalikan ke default.');
        }

        $this->profilePhotoUpload = null;
        $this->profilePhotoReset = true;
        $this->resetValidation('profilePhotoUpload');
    }

    public function changePassword(): mixed
    {
        $this->activeTab = 'security';
        $login = $this->login();
        $passwordChangeWasRequired = $login->must_change_password;

        try {
            $validated = $this->validate([
                'passwordForm.current_password' => ['required', 'string', 'max:1024'],
                'passwordForm.password' => [...StarterPasswordRules::rules(), 'same:passwordForm.password_confirmation'],
                'passwordForm.password_confirmation' => ['required', 'string', 'max:255'],
            ], [], [
                'passwordForm.current_password' => 'password saat ini',
                'passwordForm.password' => 'password baru',
                'passwordForm.password_confirmation' => 'konfirmasi password',
            ])['passwordForm'];
        } catch (ValidationException $exception) {
            $this->reset('passwordForm');

            throw $exception;
        }

        try {
            $updatedLogin = $this->profiles->changePassword(
                $login,
                $validated['current_password'],
                $validated['password'],
            );
        } catch (ValidationException $exception) {
            $this->reset('passwordForm');

            foreach ($exception->errors()['current_password'] ?? [] as $message) {
                $this->addError('passwordForm.current_password', $message);
            }

            $this->dispatch('starter-toast', type: 'danger', message: $this->firstValidationMessage($exception));

            return null;
        }

        session()->put('starter.auth_version', $updatedLogin->auth_version);
        session()->regenerate();
        session()->passwordConfirmed();
        $this->reset('passwordForm');

        if ($passwordChangeWasRequired) {
            session()->flash('starter-toast', [
                'type' => 'success',
                'message' => 'Password berhasil diubah. Anda sudah dapat menggunakan aplikasi.',
            ]);

            return $this->redirect(
                $this->redirects->firstAuthorizedUrl($updatedLogin),
            );
        }

        $this->dispatch('starter-toast', type: 'success', message: 'Password berhasil diubah.');

        return null;
    }

    public function render()
    {
        $login = $this->login();

        return view('starter.profile.edit-my-profile', [
            'login' => $login,
            'loginAvatarUrl' => $this->context->avatarUrl($login),
            'profilePhotoPreviewUrl' => $this->profilePhotoPreviewUrl($login),
        ])->title('Edit Profil Saya');
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->current();
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first() ?? 'Data tidak valid.';
    }

    private function fillFromLogin(ClientLogin $login): void
    {
        $this->profilePhotoReset = false;

        $this->accountForm = [
            'name' => (string) $login->name,
            'email' => (string) $login->email,
        ];
    }

    private function profilePhotoPreviewUrl(ClientLogin $login): string
    {
        if ($this->profilePhotoUpload instanceof TemporaryUploadedFile) {
            return $this->profilePhotoUpload->temporaryUrl();
        }

        if ($this->profilePhotoReset) {
            return asset(self::DEFAULT_PROFILE_PHOTO);
        }

        return $this->context->avatarUrl($login);
    }

    private function deleteStoredProfilePhoto(string $profilePhoto, int $loginId): void
    {
        $ownedPrefix = "storage/starter/profile-photos/{$loginId}/";

        if (! str_starts_with($profilePhoto, $ownedPrefix)) {
            return;
        }

        Storage::disk('public')->delete(str($profilePhoto)->after('storage/')->toString());
    }
}
