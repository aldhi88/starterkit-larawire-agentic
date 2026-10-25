<?php

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Http\Controllers\Starter\NotificationSoundController;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;

beforeEach(function (): void {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('n', 32))]);
    app()->register(LivewireServiceProvider::class);
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185235_create_starter_logs_table.php', '2026_10_24_000000_add_notification_sound_to_starter_client_logins.php', '2026_10_24_000001_add_notification_sound_path_to_starter_client_logins.php'] as $name) {
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
    Route::get('/settings/roles/{roleId}/edit', fn () => 'edit')->name('starter.settings.roles.edit');
    Route::middleware('auth:web')->get('/settings/notifications/audio', NotificationSoundController::class)
        ->name('starter.notifications.audio');
    View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
    Storage::fake('local');
});

function notificationWav(): UploadedFile
{
    $content = 'RIFF'.pack('V', 36).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 22050, 44100, 2, 16).'data'.pack('V', 0);

    return UploadedFile::fake()->createWithContent('suara.wav', $content);
}

function notificationSettingsFor(string $theme)
{
    config(['starter.theme' => $theme]);
    View::addLocation(StarterPaths::path("resources/themes/{$theme}/views"));

    return Livewire::test(NotificationSettings::class);
}

it('provides one settings menu entry with an authorized destination in every theme', function (string $theme, bool $manager, bool $logs): void {
    $this->first->role->update(['can_manage_settings' => $manager, 'can_view_logs' => $logs]);
    $this->first->unsetRelation('role');
    View::addLocation(StarterPaths::path("resources/themes/{$theme}/views"));
    Route::get('/settings/notifications', fn () => 'notifications')->name('starter.notifications');
    Route::get('/activity-logs', fn () => 'logs')->name('starter.logs.index');
    Route::post('/logout', fn () => 'logout')->name('auth.logout');

    $html = View::make('starter.templates.layouts.account-menu', [
        'login' => $this->first,
        'loginName' => $this->first->name,
        'loginAvatarUrl' => '/avatar.png',
        'currentProfileUrl' => '/profile/edit',
    ])->render();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $links = (new DOMXPath($document))->query('//a');
    $settings = [];
    $labels = [];
    foreach ($links as $link) {
        $label = trim($link->textContent);
        $labels[] = $label;
        if ($label === 'Pengaturan') {
            $settings[] = $link->getAttribute('href');
        }
    }

    expect($settings)->toBe([route($manager ? 'starter.settings' : 'starter.notifications')])
        ->and($labels)->not->toContain('Notifikasi')
        ->and(in_array('Log Aktivitas', $labels, true))->toBe($logs);
})->with(['tabler', 'vuexy', 'dashcode'])->with([[false, false], [false, true], [true, false], [true, true]]);

it('lets an ordinary user save only their own sound and restores the selection on reload', function (string $theme): void {
    notificationSettingsFor($theme)->assertSet('sound', 'chime')->assertSee('Dengarkan')
        ->assertSee('Unggah audio pribadi')->assertDontSee('Unggah dan Gunakan')->assertDontSee('Gunakan Default')
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

it('provides a clear three-note default for every new or unset user', function (): void {
    $default = StarterNotificationSound::catalog()[StarterNotificationSound::DEFAULT];
    $duration = array_sum(array_column($default['notes'], 1)) + .04 * (count($default['notes']) - 1);
    expect($default['notes'])->toHaveCount(3)
        ->and($duration)->toBeGreaterThanOrEqual(1.5)->toBeLessThanOrEqual(2)
        ->and(StarterNotificationSound::normalize(null))->toBe(StarterNotificationSound::DEFAULT);
});

it('lets each user upload, play and replace only their own WAV sound', function (string $theme): void {
    notificationSettingsFor($theme)->set('soundUpload', notificationWav())->call('saveCustomSound')->assertHasNoErrors()
        ->assertSet('sound', 'custom')->assertSee('Audio unggahan saya');
    $firstPath = $this->first->fresh()->notification_sound_path;
    expect($this->first->fresh()->notification_sound)->toBe('custom')
        ->and($this->second->fresh()->notification_sound_path)->toBeNull();
    Storage::disk('local')->assertExists($firstPath);
    $this->get('/settings/notifications/audio')->assertOk()->assertHeader('content-type', 'audio/wav');

    notificationSettingsFor($theme)->set('sound', 'bell')->call('save')->assertHasNoErrors();
    notificationSettingsFor($theme)->set('sound', 'custom')->call('save')->assertHasNoErrors();
    notificationSettingsFor($theme)->set('soundUpload', notificationWav())->call('saveCustomSound')->assertHasNoErrors();
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($this->first->fresh()->notification_sound_path);

    $this->actingAs($this->second)->get('/settings/notifications/audio')->assertNotFound();
    notificationSettingsFor($theme)->set('sound', 'custom')->call('save')->assertHasErrors('sound');
})->with(['tabler', 'vuexy', 'dashcode']);

it('rejects invalid upload types and removes a newly stored file when persistence fails', function (): void {
    notificationSettingsFor('tabler')->set('soundUpload', UploadedFile::fake()->createWithContent('bad.mp3', 'not audio'))
        ->call('saveCustomSound')->assertHasErrors('soundUpload');
    expect(Storage::disk('local')->allFiles())->toBe([]);

    Event::listen('eloquent.updated: '.ClientLogin::class, fn () => throw new RuntimeException('Failed persistence'));
    expect(fn () => app(ProfileService::class)->uploadNotificationSound($this->first, notificationWav()))
        ->toThrow(RuntimeException::class);
    expect(Storage::disk('local')->allFiles())->toBe([])
        ->and($this->first->fresh()->notification_sound_path)->toBeNull();
});

it('uses a readable field name when audio upload is missing', function (string $theme): void {
    notificationSettingsFor($theme)->call('saveCustomSound')
        ->assertHasErrors(['soundUpload' => 'required'])
        ->assertSee('audio pribadi');
})->with(['tabler', 'vuexy', 'dashcode']);

it('renders role row actions and a real notification bell in every theme', function (string $theme): void {
    View::addLocation(StarterPaths::path("resources/themes/{$theme}/views"));
    $role = $this->first->role;
    $actions = file_get_contents(StarterPaths::path(
        "resources/themes/{$theme}/views/starter/user-management/powergrid/roles-row-actions.blade.php",
    ));
    $html = Blade::render($actions, ['row' => $role, 'canManage' => true]);
    expect($html)->toContain('Aksi')->toContain('Edit')->toContain('Arsipkan');
    $role->delete();
    $archivedHtml = Blade::render($actions, ['row' => $role, 'canManage' => true]);
    expect($archivedHtml)->toContain('Pulihkan')->toContain('Hapus permanen');

    $bell = View::make('starter.templates.layouts.icon', ['name' => 'bell'])->render();
    expect($bell)->toContain(match ($theme) {
        'vuexy' => 'tabler-bell',
        'dashcode' => 'starter-icon-bell',
        default => 'icon-tabler-bell',
    });
})->with(['tabler', 'vuexy', 'dashcode']);

it('saves a new personal sound through the same preference submit action in every theme', function (string $theme): void {
    notificationSettingsFor($theme)->set('sound', 'upload')->call('save')
        ->assertHasErrors(['soundUpload' => 'required']);
    notificationSettingsFor($theme)->set('sound', 'upload')->set('soundUpload', notificationWav())->call('save')
        ->assertHasNoErrors()->assertSet('sound', 'custom')->assertSet('soundUpload', null);
    expect($this->first->fresh()->notification_sound)->toBe('custom');
})->with(['tabler', 'vuexy', 'dashcode']);
