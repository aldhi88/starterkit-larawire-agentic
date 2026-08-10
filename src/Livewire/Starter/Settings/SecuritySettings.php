<?php

namespace Altekno\StarterKit\Livewire\Starter\Settings;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\SecuritySettingsService;
use Altekno\StarterKit\Services\Starter\StarterConfigService;
use Livewire\Component;

class SecuritySettings extends Component
{
    public bool $embedded = false;

    private AuthenticatedLoginService $authenticatedLogins;

    /**
     * @var array{
     *     remember_me_enabled: bool,
     *     lock_screen_enabled: bool,
     *     lock_screen_timeout_minutes: int,
     *     login_max_attempts: int,
     *     login_decay_seconds: int
     * }
     */
    public array $securityForm = [
        'remember_me_enabled' => true,
        'lock_screen_enabled' => true,
        'lock_screen_timeout_minutes' => 15,
        'login_max_attempts' => 5,
        'login_decay_seconds' => 60,
    ];

    public function boot(AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(StarterConfigService $configs): void
    {
        $this->authorizeSettings();
        $this->securityForm = [
            'remember_me_enabled' => $configs->boolean('security.remember_me_enabled'),
            'lock_screen_enabled' => $configs->boolean('security.lock_screen_enabled'),
            'lock_screen_timeout_minutes' => $configs->integer('security.lock_screen_timeout_minutes'),
            'login_max_attempts' => $configs->integer('security.login_max_attempts'),
            'login_decay_seconds' => $configs->integer('security.login_decay_seconds'),
        ];
    }

    public function save(SecuritySettingsService $securitySettings): void
    {
        $login = $this->authorizeSettings();

        $validated = $this->validate([
            'securityForm.remember_me_enabled' => ['boolean'],
            'securityForm.lock_screen_enabled' => ['boolean'],
            'securityForm.lock_screen_timeout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'securityForm.login_max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'securityForm.login_decay_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
        ], [], [
            'securityForm.remember_me_enabled' => 'remember me',
            'securityForm.lock_screen_enabled' => 'lock screen',
            'securityForm.lock_screen_timeout_minutes' => 'waktu lock screen',
            'securityForm.login_max_attempts' => 'batas percobaan login',
            'securityForm.login_decay_seconds' => 'durasi pembatasan login',
        ])['securityForm'];

        $securitySettings->update($login, $validated);

        $this->dispatch('starter-toast', type: 'success', message: 'Konfigurasi keamanan berhasil disimpan.');
    }

    public function render()
    {
        $this->authorizeSettings();

        return view('starter.settings.security-settings');
    }

    private function authorizeSettings(): ClientLogin
    {
        return $this->authenticatedLogins->settingsManager();
    }
}
