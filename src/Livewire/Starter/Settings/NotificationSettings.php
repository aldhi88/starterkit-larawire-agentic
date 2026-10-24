<?php

namespace Aldhi88\StarterKit\Livewire\Starter\Settings;

use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\ProfileService;
use Aldhi88\StarterKit\Support\Starter\StarterNotificationSound;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class NotificationSettings extends Component
{
    public bool $embedded = false;

    public string $sound = StarterNotificationSound::DEFAULT;

    private AuthenticatedLoginService $logins;

    private ProfileService $profiles;

    public function boot(AuthenticatedLoginService $logins, ProfileService $profiles): void
    {
        $this->logins = $logins;
        $this->profiles = $profiles;
    }

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->sound = StarterNotificationSound::normalize($this->logins->current()->notification_sound);
    }

    public function save(): void
    {
        $login = $this->logins->current();
        $this->validate(['sound' => ['required', 'string', Rule::in(array_keys(StarterNotificationSound::catalog()))]]);
        $this->profiles->updateNotificationSound($login, $this->sound);
        $this->dispatch('starter-notification-sound-updated', sound: $this->sound);
        $this->dispatch('starter-toast', type: 'success', message: 'Preferensi audio notifikasi berhasil disimpan.');
    }

    public function render()
    {
        $login = $this->logins->current();

        return view(StarterTheme::viewName('starter.settings.notification-settings'), [
            'sounds' => StarterNotificationSound::catalog(),
            'canManageSettings' => $login->role->canManageSettings(),
        ])->title('Notifikasi');
    }
}
