<?php

namespace Aldhi88\StarterKit\Livewire\Starter\Settings;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\ProfileService;
use Aldhi88\StarterKit\Support\Starter\StarterNotificationSound;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
class NotificationSettings extends Component
{
    use WithFileUploads;

    public bool $embedded = false;

    public string $sound = StarterNotificationSound::DEFAULT;

    public mixed $soundUpload = null;

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
        $login = $this->logins->current();
        $this->sound = StarterNotificationSound::normalize($login->notification_sound, $this->hasCustomSound($login));
    }

    public function save(): void
    {
        $login = $this->logins->current();
        $this->validate(['sound' => ['required', 'string', Rule::in([...array_keys(StarterNotificationSound::catalog()), 'custom'])]], [], [
            'sound' => 'jenis suara',
        ]);
        $this->logins->replaceCurrent($this->profiles->updateNotificationSound($login, $this->sound));
        $this->dispatch('starter-notification-sound-updated', sound: $this->sound,
            customUrl: $this->sound === 'custom' ? route('starter.notifications.audio', absolute: false) : '');
        $this->dispatch('starter-toast', type: 'success', message: 'Preferensi audio notifikasi berhasil disimpan.');
    }

    public function saveCustomSound(): void
    {
        $this->validate(['soundUpload' => ['required', 'file', 'mimes:mp3,wav', 'max:5120']], [], [
            'soundUpload' => 'audio pribadi',
        ]);
        if (! $this->soundUpload instanceof TemporaryUploadedFile) {
            throw ValidationException::withMessages(['soundUpload' => 'Pilih file MP3 atau WAV yang valid.']);
        }
        $this->logins->replaceCurrent($this->profiles->uploadNotificationSound($this->logins->current(), $this->soundUpload));
        $this->soundUpload = null;
        $this->sound = 'custom';
        $this->dispatch('starter-notification-sound-updated', sound: 'custom', customUrl: route('starter.notifications.audio', absolute: false));
        $this->dispatch('starter-toast', type: 'success', message: 'Audio pribadi berhasil diunggah dan dipilih.');
    }

    public function render()
    {
        $login = $this->logins->current();
        $hasCustomSound = $this->hasCustomSound($login);

        return view(StarterTheme::viewName('starter.settings.notification-settings'), [
            'sounds' => StarterNotificationSound::catalog(),
            'hasCustomSound' => $hasCustomSound,
            'customAudioUrl' => $hasCustomSound ? route('starter.notifications.audio', absolute: false) : '',
            'canManageSettings' => $login->role->canManageSettings(),
        ])->title('Notifikasi');
    }

    private function hasCustomSound(ClientLogin $login): bool
    {
        return StarterNotificationSound::isOwnedPath($login->id, $login->notification_sound_path)
            && Storage::disk('local')->exists($login->notification_sound_path);
    }
}
