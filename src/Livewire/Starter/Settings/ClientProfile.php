<?php

namespace Altekno\StarterKit\Livewire\Starter\Settings;

use Altekno\StarterKit\Contracts\Starter\ClientInterface;
use Altekno\StarterKit\Models\Starter\Client;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\ProfileService;
use Altekno\StarterKit\Services\Starter\StarterConfigService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
class ClientProfile extends Component
{
    use WithFileUploads;

    private ClientInterface $clients;

    private ProfileService $profiles;

    private StarterConfigService $configs;

    private AuthenticatedLoginService $authenticatedLogins;

    public bool $embedded = false;

    /** @var array{name: string, email: string, phone: string, pic_name: string} */
    public array $clientForm = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'pic_name' => '',
    ];

    public mixed $clientPhotoUpload = null;

    public bool $clientPhotoReset = false;

    public function boot(
        ClientInterface $clients,
        ProfileService $profiles,
        StarterConfigService $configs,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->clients = $clients;
        $this->profiles = $profiles;
        $this->configs = $configs;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->fillFromClient($this->client());
    }

    public function save(): void
    {
        $client = $this->client();

        $validated = $this->validate([
            'clientForm.name' => ['required', 'string', 'max:255'],
            'clientForm.email' => ['nullable', 'email', 'max:255'],
            'clientForm.phone' => ['nullable', 'string', 'max:255'],
            'clientForm.pic_name' => ['nullable', 'string', 'max:255'],
            'clientPhotoUpload' => [
                'nullable',
                'image',
                'dimensions:max_width=4096,max_height=4096',
                'max:2048',
            ],
        ], [], [
            'clientForm.name' => 'client name',
            'clientForm.email' => 'client email',
            'clientForm.phone' => 'phone',
            'clientForm.pic_name' => 'PIC name',
            'clientPhotoUpload' => 'logo upload',
        ])['clientForm'];

        $oldLogo = (string) $client->logo;
        $validated['logo'] = $oldLogo;

        if ($this->clientPhotoUpload instanceof TemporaryUploadedFile) {
            $validated['logo'] = 'storage/'.$this->clientPhotoUpload->store(
                "starter/client-photos/{$client->id}",
                'public'
            );
        }

        $updatedClient = $this->profiles->updateClientProfile($this->login(), [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'pic_name' => $validated['pic_name'] ?? null,
            'logo' => $validated['logo'] ?? null,
        ]);

        if ($oldLogo && $oldLogo !== (string) $updatedClient->logo) {
            $this->deleteStoredClientPhoto($oldLogo, $client->id);
        }

        $this->clientPhotoUpload = null;
        $this->clientPhotoReset = false;
        $this->fillFromClient($updatedClient);
        $this->dispatchClientBrandingUpdated($updatedClient);
        $this->dispatch('starter-toast', type: 'success', message: 'Profil perusahaan berhasil disimpan.');
    }

    public function resetClientPhoto(): void
    {
        $client = $this->client();
        $oldLogo = (string) $client->logo;

        if ($oldLogo) {
            $updatedClient = $this->profiles->updateClientProfile($this->login(), [
                ...$this->clientForm,
                'logo' => null,
            ]);

            $this->deleteStoredClientPhoto($oldLogo, $client->id);
            $this->fillFromClient($updatedClient);
            $this->dispatchClientBrandingUpdated($updatedClient);
            $this->dispatch('starter-toast', type: 'success', message: 'Logo berhasil dikembalikan ke default.');
        }

        $this->clientPhotoUpload = null;
        $this->clientPhotoReset = true;
        $this->resetValidation('clientPhotoUpload');
    }

    public function render()
    {
        $client = $this->client();

        return view('starter.settings.client-profile', [
            'client' => $client,
            'clientLogoPreviewUrl' => $this->clientLogoPreviewUrl($client),
            'clientInitials' => $this->clientInitials(),
        ])->title('Profil Perusahaan');
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->settingsManager();
    }

    private function client(): Client
    {
        return $this->clients->current();
    }

    private function fillFromClient(Client $client): void
    {
        $this->clientForm = [
            'name' => (string) $client->name,
            'email' => (string) $client->email,
            'phone' => (string) $client->phone,
            'pic_name' => (string) $client->pic_name,
        ];
    }

    private function clientLogoPreviewUrl(Client $client): ?string
    {
        if ($this->clientPhotoUpload instanceof TemporaryUploadedFile) {
            return $this->clientPhotoUpload->temporaryUrl();
        }

        if ($this->clientPhotoReset) {
            return null;
        }

        return $this->logoUrl($client);
    }

    private function dispatchClientBrandingUpdated(Client $client): void
    {
        $this->dispatch(
            'starter-client-branding-updated',
            logoUrl: $this->logoUrl($client),
            clientName: $client->name,
        );
    }

    private function logoUrl(Client $client): ?string
    {
        $logo = trim((string) $client->logo);
        $ownedPrefix = "storage/starter/client-photos/{$client->id}/";

        if ($logo === '' || ! str_starts_with($logo, $ownedPrefix)) {
            return null;
        }

        return asset(ltrim($logo, '/'));
    }

    private function clientInitials(): string
    {
        return str($this->clientForm['name'] ?: 'Client')
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => str($part)->substr(0, 1)->upper()->toString())
            ->implode('');
    }

    private function deleteStoredClientPhoto(string $logo, int $clientId): void
    {
        $ownedPrefix = "storage/starter/client-photos/{$clientId}/";

        if (! str_starts_with($logo, $ownedPrefix)) {
            return;
        }

        Storage::disk('public')->delete(str($logo)->after('storage/')->toString());
    }
}
