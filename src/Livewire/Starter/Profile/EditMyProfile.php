<?php

namespace Aldhi88\StarterKit\Livewire\Starter\Profile;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Rules\Starter\StarterPasswordRules;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
use Aldhi88\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Aldhi88\StarterKit\Services\Starter\ProfileService;
use Aldhi88\StarterKit\Services\Starter\StarterContextService;
use Aldhi88\StarterKit\Services\Starter\TwoFactorAuthenticationService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Support\Facades\Crypt;
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

    private const SESSION_TWO_FACTOR_SETUP_SECRET = 'starter.profile_two_factor_setup_secret';

    private ProfileService $profiles;

    private StarterContextService $context;

    private NavigationAuthorizedRedirectService $redirects;

    private AuthenticatedLoginService $authenticatedLogins;

    private TwoFactorAuthenticationService $twoFactor;

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

    /** @var array{password: string, code: string} */
    public array $twoFactorForm = [
        'password' => '',
        'code' => '',
    ];

    /** @var array{password: string, code: string} */
    public array $twoFactorDisableForm = [
        'password' => '',
        'code' => '',
    ];

    public bool $twoFactorSetupActive = false;

    public string $twoFactorSetupKey = '';

    public string $twoFactorQrCode = '';

    /** @var list<string> */
    public array $twoFactorRecoveryCodes = [];

    public function boot(
        ProfileService $profiles,
        StarterContextService $context,
        NavigationAuthorizedRedirectService $redirects,
        AuthenticatedLoginService $authenticatedLogins,
        TwoFactorAuthenticationService $twoFactor,
    ): void {
        $this->profiles = $profiles;
        $this->context = $context;
        $this->redirects = $redirects;
        $this->authenticatedLogins = $authenticatedLogins;
        $this->twoFactor = $twoFactor;
    }

    public function mount(): void
    {
        $login = $this->login();
        $this->activeTab = $login->must_change_password || request()->query('tab') === 'security'
            ? 'security'
            : 'account-details';
        $this->fillFromLogin($login);
        $this->restorePendingTwoFactorSetup($login);
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
            roleName: $updatedLogin->role->name,
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
                roleName: $updatedLogin->role->name,
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

    public function generatePassword(): void
    {
        $this->activeTab = 'security';
        $password = StarterPasswordRules::generate();
        $this->passwordForm['password'] = $password;
        $this->passwordForm['password_confirmation'] = $password;
        $this->resetValidation([
            'passwordForm.password',
            'passwordForm.password_confirmation',
        ]);

        $encodedPassword = json_encode($password, JSON_THROW_ON_ERROR);
        $this->js("window.StarterTemplate.fillGeneratedPassword({ password: {$encodedPassword} })");

        $this->dispatch(
            'starter-toast',
            type: 'success',
            message: 'Password aman berhasil dibuat dan konfirmasi telah diisi otomatis.',
        );
    }

    public function startTwoFactorSetup(): void
    {
        $this->activeTab = 'security';
        $this->assertTwoFactorFeatureEnabled('twoFactorForm.password');
        $login = $this->login();

        try {
            $validated = $this->validate([
                'twoFactorForm.password' => ['required', 'string', 'max:1024'],
            ], [], [
                'twoFactorForm.password' => 'password saat ini',
            ]);

            $this->profiles->assertCurrentPassword(
                $login,
                $validated['twoFactorForm']['password'],
                'twoFactorForm.password',
            );
        } catch (ValidationException $exception) {
            $this->twoFactorForm['password'] = '';

            throw $exception;
        }

        $secret = $this->twoFactor->generateSecret();
        session()->put(self::SESSION_TWO_FACTOR_SETUP_SECRET, Crypt::encryptString($secret));
        $this->twoFactorSetupActive = true;
        $this->twoFactorSetupKey = $this->twoFactor->formattedSecret($secret);
        $this->twoFactorQrCode = $this->twoFactor->qrCodeDataUri($secret, $login->email);
        $this->twoFactorRecoveryCodes = [];
        $this->twoFactorForm = ['password' => '', 'code' => ''];
        $this->resetValidation(['twoFactorForm.password', 'twoFactorForm.code']);
    }

    public function confirmTwoFactorSetup(): void
    {
        $this->activeTab = 'security';
        $this->assertTwoFactorFeatureEnabled('twoFactorForm.code');
        $login = $this->login();
        $validated = $this->validate([
            'twoFactorForm.code' => ['required', 'digits:6'],
        ], [], [
            'twoFactorForm.code' => 'kode authenticator',
        ]);
        $secret = $this->pendingTwoFactorSecret();

        if ($secret === null) {
            $this->cancelTwoFactorSetup();
            throw ValidationException::withMessages([
                'twoFactorForm.code' => 'Sesi aktivasi sudah berakhir. Mulai kembali aktivasi authenticator.',
            ]);
        }

        if (! $this->twoFactor->verifyCode($secret, $validated['twoFactorForm']['code'])) {
            $this->twoFactorForm['code'] = '';
            $this->profiles->recordTwoFactorVerificationFailure($login, 'enable');

            throw ValidationException::withMessages([
                'twoFactorForm.code' => 'Kode authenticator tidak valid. Pastikan waktu perangkat Anda akurat.',
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $updatedLogin = $this->profiles->enableTwoFactorAuthentication(
            $login,
            $secret,
            $this->twoFactor->hashRecoveryCodes($recoveryCodes),
        );

        session()->forget(self::SESSION_TWO_FACTOR_SETUP_SECRET);
        session()->put('starter.auth_version', $updatedLogin->auth_version);
        session()->put(AuthLoginService::SESSION_TWO_FACTOR_VERIFIED_LOGIN_ID, (int) $updatedLogin->getKey());
        session()->regenerate();
        session()->passwordConfirmed();

        $this->twoFactorSetupActive = false;
        $this->twoFactorSetupKey = '';
        $this->twoFactorQrCode = '';
        $this->twoFactorRecoveryCodes = $recoveryCodes;
        $this->twoFactorForm = ['password' => '', 'code' => ''];
        $this->dispatch('starter-toast', type: 'success', message: 'Two-factor authentication berhasil diaktifkan.');
    }

    public function cancelTwoFactorSetup(): void
    {
        session()->forget(self::SESSION_TWO_FACTOR_SETUP_SECRET);
        $this->twoFactorSetupActive = false;
        $this->twoFactorSetupKey = '';
        $this->twoFactorQrCode = '';
        $this->twoFactorForm = ['password' => '', 'code' => ''];
        $this->resetValidation(['twoFactorForm.password', 'twoFactorForm.code']);
    }

    public function disableTwoFactorAuthentication(): void
    {
        $this->activeTab = 'security';
        $this->assertTwoFactorFeatureEnabled('twoFactorDisableForm.password');
        $login = $this->login();
        try {
            $validated = $this->validate([
                'twoFactorDisableForm.password' => ['required', 'string', 'max:1024'],
                'twoFactorDisableForm.code' => ['required', 'digits:6'],
            ], [], [
                'twoFactorDisableForm.password' => 'password saat ini',
                'twoFactorDisableForm.code' => 'kode authenticator',
            ])['twoFactorDisableForm'];

            $this->profiles->assertCurrentPassword(
                $login,
                $validated['password'],
                'twoFactorDisableForm.password',
            );
        } catch (ValidationException $exception) {
            $this->twoFactorDisableForm = ['password' => '', 'code' => ''];

            throw $exception;
        }

        if (! $this->twoFactor->verifyCode((string) $login->two_factor_secret, $validated['code'])) {
            $this->twoFactorDisableForm['code'] = '';
            $this->profiles->recordTwoFactorVerificationFailure($login, 'disable');

            throw ValidationException::withMessages([
                'twoFactorDisableForm.code' => 'Kode authenticator tidak valid.',
            ]);
        }

        $updatedLogin = $this->profiles->disableTwoFactorAuthentication($login);
        session()->put('starter.auth_version', $updatedLogin->auth_version);
        session()->forget(AuthLoginService::SESSION_TWO_FACTOR_VERIFIED_LOGIN_ID);
        session()->regenerate();
        session()->passwordConfirmed();

        $this->twoFactorDisableForm = ['password' => '', 'code' => ''];
        $this->twoFactorRecoveryCodes = [];
        $this->dispatch('starter-toast', type: 'success', message: 'Two-factor authentication berhasil dinonaktifkan.');
    }

    public function render()
    {
        $login = $this->login();

        return view(StarterTheme::viewName('starter.profile.edit-my-profile'), [
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

    private function restorePendingTwoFactorSetup(ClientLogin $login): void
    {
        if (! $this->twoFactor->enabled()) {
            session()->forget(self::SESSION_TWO_FACTOR_SETUP_SECRET);

            return;
        }

        $secret = $this->pendingTwoFactorSecret();

        if ($secret === null || $login->hasTwoFactorAuthenticationEnabled()) {
            session()->forget(self::SESSION_TWO_FACTOR_SETUP_SECRET);

            return;
        }

        $this->twoFactorSetupActive = true;
        $this->twoFactorSetupKey = $this->twoFactor->formattedSecret($secret);
        $this->twoFactorQrCode = $this->twoFactor->qrCodeDataUri($secret, $login->email);
    }

    private function pendingTwoFactorSecret(): ?string
    {
        $encrypted = session()->get(self::SESSION_TWO_FACTOR_SETUP_SECRET);

        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            session()->forget(self::SESSION_TWO_FACTOR_SETUP_SECRET);

            return null;
        }
    }

    private function assertTwoFactorFeatureEnabled(string $field): void
    {
        if ($this->twoFactor->enabled()) {
            return;
        }

        session()->forget(self::SESSION_TWO_FACTOR_SETUP_SECRET);
        $this->twoFactorSetupActive = false;
        $this->twoFactorSetupKey = '';
        $this->twoFactorQrCode = '';

        throw ValidationException::withMessages([
            $field => 'Two-factor authentication sedang dinonaktifkan oleh administrator.',
        ]);
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
