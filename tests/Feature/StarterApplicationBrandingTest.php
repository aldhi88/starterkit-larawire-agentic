<?php

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Livewire\Starter\Settings\ClientProfile;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Starter\ClientRepository;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\ProfileService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('b', 32))]);
    app()->register(LivewireServiceProvider::class);
    (require StarterPaths::path('database/migrations/starter/2026_07_27_185232_create_starter_identity_tables.php'))->up();
    (require StarterPaths::path('database/migrations/starter/2026_10_16_000000_add_favicon_to_starter_clients.php'))->up();
    Client::create(['name' => 'Example', 'logo' => 'storage/starter/client-photos/1/legacy.png']);
    $actor = new ClientLogin;
    $actor->setRelation('role', new ClientRole(['code' => 'manager', 'can_manage_settings' => true]));
    $authentication = Mockery::mock(AuthenticatedLoginService::class);
    $authentication->shouldReceive('settingsManager')->andReturn($actor);
    app()->instance(AuthenticatedLoginService::class, $authentication);
    $logins = Mockery::mock(ClientLoginInterface::class);
    $logins->shouldReceive('loadRole')->andReturnUsing(fn ($login) => $login);
    app()->instance(ClientInterface::class, new ClientRepository);
    app()->instance(ProfileService::class, new ProfileService(new ClientRepository, $logins, Mockery::mock(AuditLogService::class)));
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
    Storage::fake('public');
});

function brandingSettings(string $theme = 'tabler')
{
    config(['starter.theme' => $theme]);
    View::addLocation(StarterPaths::path("resources/themes/{$theme}/views"));

    return Livewire::test(ClientProfile::class, ['embedded' => true]);
}

it('saves independent logo and favicon uploads and resets each without affecting the other', function (string $theme): void {
    Storage::disk('public')->put('starter/client-photos/1/legacy.png', 'legacy');
    $test = brandingSettings($theme)->assertSee('Logo Aplikasi')->assertSee('Favicon')
        ->set('clientPhotoUpload', UploadedFile::fake()->image('logo.png', 480, 120))
        ->set('clientFaviconUpload', UploadedFile::fake()->image('icon.png', 64, 64))
        ->call('save')->assertHasNoErrors()->assertDispatched('starter-client-branding-updated');
    if ($theme === 'vuexy') {
        $test->assertSee('data-starter-modal-open="#delete-client-favicon-modal"', false)
            ->assertSee('data-starter-modal-open="#delete-client-photo-modal"', false);
    }
    $client = Client::firstOrFail();
    $logo = $client->logo;
    $favicon = $client->favicon;
    expect($logo)->toStartWith('storage/starter/client-photos/1/')
        ->and($favicon)->toStartWith('storage/starter/client-favicons/1/')
        ->and($logo)->not->toBe($favicon);
    Storage::disk('public')->assertExists(substr($logo, 8));
    Storage::disk('public')->assertExists(substr($favicon, 8));
    Storage::disk('public')->assertMissing('starter/client-photos/1/legacy.png');

    $test->call('resetClientPhoto')->assertHasNoErrors();
    expect($client->fresh()->logo)->toBeNull()->and($client->fresh()->favicon)->toBe($favicon);
    $test->call('resetClientFavicon')->assertHasNoErrors();
    expect($client->fresh()->favicon)->toBeNull();
    Storage::disk('public')->assertMissing(substr($logo, 8));
    Storage::disk('public')->assertMissing(substr($favicon, 8));
})->with(['tabler', 'dashcode', 'vuexy']);

it('preserves the legacy logo when updating only the favicon and preserves the icon when updating profile text', function (): void {
    $test = brandingSettings()->set('clientFaviconUpload', UploadedFile::fake()->image('icon.png', 64, 64))
        ->call('save')->assertHasNoErrors();
    $client = Client::firstOrFail();
    expect($client->logo)->toBe('storage/starter/client-photos/1/legacy.png');
    $favicon = $client->favicon;
    $test->set('clientForm.name', 'Renamed')->call('save')->assertHasNoErrors();
    expect($client->fresh()->favicon)->toBe($favicon);
    $test->call('resetClientFavicon');
    expect($client->fresh()->logo)->toBe('storage/starter/client-photos/1/legacy.png');
});

it('rejects invalid favicon dimensions format and size before persistence', function (string $case): void {
    $file = match ($case) {
        'rectangular' => UploadedFile::fake()->image('icon.png', 64, 32),
        'small' => UploadedFile::fake()->image('icon.png', 16, 16),
        'large' => UploadedFile::fake()->image('icon.png', 600, 600),
        'format' => UploadedFile::fake()->image('icon.jpg', 64, 64),
        default => UploadedFile::fake()->image('icon.png', 64, 64)->size(513),
    };
    brandingSettings()->set('clientFaviconUpload', $file)->call('save')->assertHasErrors('clientFaviconUpload');
    expect(Client::firstOrFail()->favicon)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with(['rectangular', 'small', 'large', 'format', 'oversize']);

it('authorizes settings before accepting branding actions', function (): void {
    $authentication = Mockery::mock(AuthenticatedLoginService::class);
    $authentication->shouldReceive('settingsManager')->andThrow(new HttpException(403));
    app()->instance(AuthenticatedLoginService::class, $authentication);
    brandingSettings()->assertStatus(403);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('removes newly stored uploads when profile persistence fails', function (): void {
    $profiles = Mockery::mock(ProfileService::class);
    $profiles->shouldReceive('updateClientProfile')->andThrow(new RuntimeException('Persistence failed'));
    app()->instance(ProfileService::class, $profiles);
    $test = brandingSettings()
        ->set('clientPhotoUpload', UploadedFile::fake()->image('logo.png', 480, 120))
        ->set('clientFaviconUpload', UploadedFile::fake()->image('icon.png', 64, 64));
    expect(fn () => $test->call('save'))->toThrow(RuntimeException::class, 'Persistence failed');
    expect(Storage::disk('public')->allFiles())->toBe([])
        ->and(Client::firstOrFail()->logo)->toBe('storage/starter/client-photos/1/legacy.png')
        ->and(Client::firstOrFail()->favicon)->toBeNull();
});

it('uses the square favicon independently on all layouts and keeps branding image-only', function (): void {
    foreach (['tabler', 'dashcode', 'vuexy'] as $theme) {
        config(['starter.theme' => $theme]);
        $html = view('starter-shared::components.favicon', ['clientFaviconUrl' => '/icon.png', 'clientLogoUrl' => '/wide-logo.png'])->render();
        expect($html)->toContain('href="/icon.png"')->not->toContain('/wide-logo.png');
        $root = StarterPaths::path("resources/themes/{$theme}/views/starter");
        foreach ($theme === 'vuexy' ? ['templates/layouts/head', 'errors/layout'] : ['templates/layouts/app', 'templates/layouts/auth', 'templates/layouts/landing', 'errors/layout'] as $layout) {
            expect(file_get_contents($root.'/'.$layout.'.blade.php'))->toContain("@include('starter-shared::components.favicon')");
        }
    }
    $brand = file_get_contents(StarterPaths::path('resources/themes/vuexy/views/starter/templates/layouts/brand.blade.php'));
    expect($brand)->not->toContain('app-brand-text');
});

it('adds the favicon column idempotently while preserving an installed logo', function (): void {
    $migration = require StarterPaths::path('database/migrations/starter/2026_10_16_000000_add_favicon_to_starter_clients.php');
    $migration->up();
    expect(Client::firstOrFail()->logo)->toBe('storage/starter/client-photos/1/legacy.png');
});
