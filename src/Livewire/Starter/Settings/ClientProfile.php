<?php

namespace Aldhi88\StarterKit\Livewire\Starter\Settings;

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\ProfileService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts::app')]
class ClientProfile extends Component
{
    use WithFileUploads;

    private ClientInterface $clients;

    private ProfileService $profiles;

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

    public mixed $clientFaviconUpload = null;

    public function boot(
        ClientInterface $clients,
        ProfileService $profiles,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->clients = $clients;
        $this->profiles = $profiles;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(bool $embedded = false): void
    {
        $this->login();
        $this->embedded = $embedded;
        $this->fillFromClient($this->client());
    }

    public function save(): void
    {
        $login = $this->login();
        $client = $this->client();

        $validated = $this->validate([
            'clientForm.name' => ['required', 'string', 'max:255'],
            'clientForm.email' => ['nullable', 'email', 'max:255'],
            'clientForm.phone' => ['nullable', 'string', 'max:255'],
            'clientForm.pic_name' => ['nullable', 'string', 'max:255'],
            'clientPhotoUpload' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'dimensions:max_width=4096,max_height=4096',
                'max:2048',
            ],
            'clientFaviconUpload' => ['nullable', 'image', 'mimes:png', 'dimensions:ratio=1,min_width=32,min_height=32,max_width=512,max_height=512', 'max:512'],
        ], [], [
            'clientForm.name' => 'client name',
            'clientForm.email' => 'client email',
            'clientForm.phone' => 'phone',
            'clientForm.pic_name' => 'PIC name',
            'clientPhotoUpload' => 'logo upload',
            'clientFaviconUpload' => 'favicon',
        ])['clientForm'];

        $oldLogo = (string) $client->logo;
        $validated['logo'] = $oldLogo;

        $oldFavicon = (string) $client->favicon;
        $validated['favicon'] = $oldFavicon ?: null;

        try {
            if ($this->clientPhotoUpload instanceof TemporaryUploadedFile) {
                $validated['logo'] = 'storage/'.$this->clientPhotoUpload->store(
                    "starter/client-photos/{$client->id}",
                    'public'
                );
            }

            if ($this->clientFaviconUpload instanceof TemporaryUploadedFile) {
                $validated['favicon'] = 'storage/'.$this->clientFaviconUpload->store("starter/client-favicons/{$client->id}", 'public');
            }

            $updatedClient = $this->profiles->updateClientProfile($login, [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'pic_name' => $validated['pic_name'] ?? null,
                'logo' => $validated['logo'] ?? null,
                'favicon' => $validated['favicon'],
            ]);
        } catch (Throwable $exception) {
            if ($validated['logo'] !== $oldLogo) {
                $this->deleteStoredClientPhoto($validated['logo'], $client->id);
            }
            if ($validated['favicon'] !== ($oldFavicon ?: null) && $validated['favicon']) {
                $this->deleteStoredFavicon($validated['favicon'], $client->id);
            }
            throw $exception;
        }

        if ($oldLogo && $oldLogo !== (string) $updatedClient->logo) {
            $this->deleteStoredClientPhoto($oldLogo, $client->id);
        }

        if ($oldFavicon && $oldFavicon !== (string) $updatedClient->favicon) {
            $this->deleteStoredFavicon($oldFavicon, $client->id);
        }

        $this->clientPhotoUpload = null;
        $this->clientFaviconUpload = null;
        $this->clientPhotoReset = false;
        $this->fillFromClient($updatedClient);
        $this->dispatchClientBrandingUpdated($updatedClient);
        $this->dispatch('starter-toast', type: 'success', message: 'Profil perusahaan berhasil disimpan.');
    }

    public function resetClientPhoto(): void
    {
        $this->login();
        $client = $this->client();
        $oldLogo = (string) $client->logo;

        if ($oldLogo) {
            $updatedClient = $this->profiles->updateClientProfile($this->login(), [
                'name' => $client->name, 'email' => $client->email, 'phone' => $client->phone,
                'pic_name' => $client->pic_name,
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

    public function resetClientFavicon(): void
    {
        $login = $this->login();
        $client = $this->client();
        $oldFavicon = (string) $client->favicon;
        // Reset only the icon; keep saved profile and logo data independent.
        $updatedClient = $this->profiles->updateClientProfile($login, [
            'name' => $client->name, 'email' => $client->email, 'phone' => $client->phone,
            'pic_name' => $client->pic_name, 'logo' => $client->logo, 'favicon' => null,
        ]);
        $this->deleteStoredFavicon($oldFavicon, $client->id);
        $this->clientFaviconUpload = null;
        $this->resetValidation('clientFaviconUpload');
        $this->dispatchClientBrandingUpdated($updatedClient);
        $this->dispatch('starter-toast', type: 'success', message: 'Favicon berhasil dikembalikan ke default.');
    }

    public function render()
    {
        $this->login();
        $client = $this->client();

        return view(StarterTheme::viewName('starter.settings.client-profile'), [
            'client' => $client,
            'clientLogoPreviewUrl' => $this->clientLogoPreviewUrl($client),
            'clientFaviconPreviewUrl' => $this->faviconUrl($client),
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
            faviconUrl: $this->faviconUrl($client),
            clientName: $client->name,
        );
    }

    private function logoUrl(Client $client): ?string
    {
        $logo = trim((string) $client->logo);
        $ownedPrefix = "storage/starter/client-photos/{$client->id}/";

        if ($logo === '' || str_contains($logo, '..') || ! str_starts_with($logo, $ownedPrefix)) {
            return null;
        }

        return asset(ltrim($logo, '/'));
    }

    private function faviconUrl(Client $client): ?string
    {
        $favicon = (string) $client->favicon;

        return str_starts_with($favicon, "storage/starter/client-favicons/{$client->id}/") && ! str_contains($favicon, '..')
            ? asset($favicon) : null;
    }

    private function deleteStoredFavicon(string $favicon, int $clientId): void
    {
        if (str_starts_with($favicon, "storage/starter/client-favicons/{$clientId}/") && ! str_contains($favicon, '..')) {
            Storage::disk('public')->delete(substr($favicon, strlen('storage/')));
        }
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

        if (str_contains($logo, '..') || ! str_starts_with($logo, $ownedPrefix)) {
            return;
        }

        Storage::disk('public')->delete(str($logo)->after('storage/')->toString());
    }
}
