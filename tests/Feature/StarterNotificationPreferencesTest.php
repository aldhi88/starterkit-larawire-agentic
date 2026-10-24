<?php

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Livewire\Starter\Settings\NotificationSettings;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientRepository;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\ProfileService;
use Aldhi88\StarterKit\Support\Starter\StarterNotificationSound;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;

beforeEach(function (): void {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('n', 32))]);
    app()->register(LivewireServiceProvider::class);
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185235_create_starter_logs_table.php', '2026_10_24_000000_add_notification_sound_to_starter_client_logins.php'] as $name) {
        (require StarterPaths::path('database/migrations/starter/'.$name))->up();
    }
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    app()->bind(ClientInterface::class, ClientRepository::class);
    app()->scoped(AuditLogService::class);
    // Exercise the same model-event audit boundary registered by StarterServiceProvider.
    Event::listen('eloquent.updated: '.ClientLogin::class, fn (ClientLogin $login) => app(AuditLogService::class)->recordModelEvent('updated', $login));
    $role = ClientRole::create(['code' => 'staff', 'name' => 'Staff']);
    $this->first = ClientLogin::create(['client_role_id' => $role->id, 'name' => 'First', 'username' => 'first', 'email' => 'first@example.test']);
    $this->second = ClientLogin::create(['client_role_id' => $role->id, 'name' => 'Second', 'username' => 'second', 'email' => 'second@example.test']);
    $this->actingAs($this->first);
    Route::get('/settings', fn () => 'settings')->name('starter.settings');
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
});

function notificationSettingsFor(string $theme)
{
    config(['starter.theme' => $theme]);
    View::addLocation(StarterPaths::path("resources/themes/{$theme}/views"));

    return Livewire::test(NotificationSettings::class);
}

it('lets an ordinary user save only their own sound and restores the selection on reload', function (string $theme): void {
    notificationSettingsFor($theme)->assertSet('sound', 'chime')->assertSee('Dengarkan Pratinjau')
        ->set('sound', 'bell')->call('save')->assertHasNoErrors()
        ->assertDispatched('starter-notification-sound-updated', sound: 'bell');
    expect($this->first->fresh()->notification_sound)->toBe('bell')
        ->and($this->second->fresh()->notification_sound)->toBeNull();
    $this->actingAs($this->first->fresh());
    notificationSettingsFor($theme)->assertSet('sound', 'bell');
    $this->actingAs($this->second->fresh());
    notificationSettingsFor($theme)->assertSet('sound', 'chime')->set('sound', 'gentle')->call('save')->assertHasNoErrors();
    expect($this->first->fresh()->notification_sound)->toBe('bell')
        ->and($this->second->fresh()->notification_sound)->toBe('gentle');
    expect(ActivityLog::where('action_key', 'profile.notification_sound.update')->pluck('client_login_id')->all())
        ->toBe([$this->first->id, $this->second->id]);
})->with(['tabler', 'vuexy', 'dashcode']);

it('rejects arbitrary sound keys paths and URLs without changing the saved preference', function (string $sound): void {
    notificationSettingsFor('tabler')->set('sound', $sound)->call('save')->assertHasErrors('sound');
    expect($this->first->fresh()->notification_sound)->toBeNull();
})->with(['invalid', '../../other.wav', 'https://example.test/audio.mp3', '']);

it('rolls back a preference update and its success audit when persistence fails', function (): void {
    Event::listen('eloquent.updated: '.ClientLogin::class, fn () => throw new RuntimeException('Failed persistence'));
    expect(fn () => app(ProfileService::class)->updateNotificationSound($this->first, 'bell'))->toThrow(RuntimeException::class);
    expect($this->first->fresh()->notification_sound)->toBeNull()
        ->and(ActivityLog::count())->toBe(0);
});

it('has stable defaults and an idempotent additive migration', function (): void {
    expect(StarterNotificationSound::normalize(null))->toBe('chime')
        ->and(StarterNotificationSound::normalize('old-key'))->toBe('chime');
    $this->first->update(['notification_sound' => 'bell']);
    $migration = require StarterPaths::path('database/migrations/starter/2026_10_24_000000_add_notification_sound_to_starter_client_logins.php');
    $migration->up();
    expect($this->first->fresh()->notification_sound)->toBe('bell');
});

it('provides a short two-note default for every new or unset user', function (): void {
    $default = StarterNotificationSound::catalog()[StarterNotificationSound::DEFAULT];
    $duration = array_sum(array_column($default['notes'], 1)) + .04 * (count($default['notes']) - 1);
    expect($default['notes'])->toHaveCount(2)
        ->and($duration)->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(3)
        ->and(StarterNotificationSound::normalize(null))->toBe(StarterNotificationSound::DEFAULT);
});
