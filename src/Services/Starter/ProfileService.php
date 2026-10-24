<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Support\Starter\StarterNotificationSound;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function __construct(
        private readonly ClientInterface $clients,
        private readonly ClientLoginInterface $clientLogins,
        private readonly AuditLogService $auditLogs,
    ) {}

    /**
     * @param  array{name: string, username?: string, email: string, profile_photo?: ?string}  $data
     */
    public function updateProfile(ClientLogin $login, array $data): ClientLogin
    {
        return DB::transaction(function () use ($login, $data): ClientLogin {
            $lockedLogin = $this->clientLogins->findForAuthenticationWithLock((int) $login->getKey());
            abort_unless($lockedLogin instanceof ClientLogin, 404);

            $username = str($data['username'] ?? $lockedLogin->username)->lower()->trim()->toString();
            $oldUsername = $lockedLogin->username;
            $usernameChanged = $username !== $lockedLogin->username;

            if ($usernameChanged && ! $lockedLogin->canChangeOwnUsername()) {
                throw ValidationException::withMessages([
                    'accountForm.username' => $lockedLogin->role->isSuperuser()
                        ? 'Username Superuser tidak dapat diubah dari profil.'
                        : 'Kesempatan mengganti username sendiri sudah digunakan. Hubungi Superuser untuk perubahan berikutnya.',
                ]);
            }

            $updatedLogin = $this->clientLogins->updateUser($lockedLogin, [
                'name' => trim($data['name']),
                'username' => $username,
                'email' => str($data['email'])->lower()->trim()->toString(),
                'profile_photo' => $this->nullableTrim($data['profile_photo'] ?? null),
                ...($usernameChanged ? ['username_self_changed_at' => now()] : []),
            ]);

            if ($usernameChanged) {
                $this->auditLogs->recordSecurityEvent(
                    'auth.username_self_changed',
                    'Username diubah sendiri oleh user',
                    target: $updatedLogin,
                    actor: $updatedLogin,
                    metadata: [
                        'old_username' => $oldUsername,
                        'new_username' => $username,
                    ],
                );
            }

            return $this->clientLogins->refreshWithRole($updatedLogin);
        });
    }

    /**
     * @param  array{name: string, email?: ?string, phone?: ?string, pic_name?: ?string, logo?: ?string, favicon?: ?string}  $data
     */
    public function updateClientProfile(ClientLogin $login, array $data): Client
    {
        $this->ensureAdmin($login);

        return DB::transaction(fn (): Client => $this->clients->updateProfile($this->clients->current(), [
            'name' => trim($data['name']),
            'email' => $this->nullableTrim($data['email'] ?? null),
            'phone' => $this->nullableTrim($data['phone'] ?? null),
            'pic_name' => $this->nullableTrim($data['pic_name'] ?? null),
            'logo' => $this->nullableTrim($data['logo'] ?? null),
            ...(array_key_exists('favicon', $data) ? ['favicon' => $this->nullableTrim($data['favicon'])] : []),
        ]));
    }

    public function updateNotificationSound(ClientLogin $actor, string $sound): ClientLogin
    {
        if (! array_key_exists($sound, StarterNotificationSound::catalog()) && $sound !== 'custom') {
            throw ValidationException::withMessages(['sound' => 'Pilih suara notifikasi yang tersedia.']);
        }

        return $this->auditLogs->withinAction('profile.notification_sound.update', 'Mengubah preferensi audio notifikasi',
            function () use ($actor, $sound): ClientLogin {
                return DB::transaction(function () use ($actor, $sound): ClientLogin {
                    $login = $this->clientLogins->findForAuthenticationWithLock($actor->id);
                    abort_unless($login instanceof ClientLogin, 404);
                    if ($sound === 'custom' && (! StarterNotificationSound::isOwnedPath($login->id, $login->notification_sound_path)
                        || ! Storage::disk('local')->exists($login->notification_sound_path))) {
                        throw ValidationException::withMessages(['sound' => 'Unggah audio terlebih dahulu.']);
                    }
                    $updated = $this->clientLogins->updateUser($login, ['notification_sound' => $sound]);

                    return $this->clientLogins->refreshWithRole($updated);
                });
            });
    }

    public function uploadNotificationSound(ClientLogin $actor, UploadedFile $file): ClientLogin
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $extension = match ($mime) {
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave' => 'wav',
            default => null,
        };
        if ($extension === null || strtolower($file->getClientOriginalExtension()) !== $extension
            || $file->getSize() < 1 || $file->getSize() > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['soundUpload' => 'Pilih file MP3 atau WAV yang valid.']);
        }

        $path = $file->storeAs("starter/notification-sounds/{$actor->id}", Str::uuid().'.'.$extension, 'local');
        if (! is_string($path)) {
            throw ValidationException::withMessages(['soundUpload' => 'File audio gagal disimpan. Coba lagi.']);
        }

        $oldPath = null;
        try {
            $updated = $this->auditLogs->withinAction('profile.notification_sound.upload', 'Mengunggah audio notifikasi',
                function () use ($actor, $path, &$oldPath): ClientLogin {
                    return DB::transaction(function () use ($actor, $path, &$oldPath): ClientLogin {
                        $login = $this->clientLogins->findForAuthenticationWithLock($actor->id);
                        abort_unless($login instanceof ClientLogin, 404);
                        $oldPath = $login->notification_sound_path;

                        return $this->clientLogins->refreshWithRole($this->clientLogins->updateUser($login, [
                            'notification_sound' => 'custom',
                            'notification_sound_path' => $path,
                        ]));
                    });
                });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        if (StarterNotificationSound::isOwnedPath($actor->id, $oldPath) && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return $updated;
    }

    public function changePassword(ClientLogin $login, string $currentPassword, string $password): ClientLogin
    {
        if (! $login->password || ! Hash::check($currentPassword, $login->password)) {
            $this->auditLogs->recordSecurityEvent(
                'auth.password_change_failed',
                'Perubahan password gagal',
                target: $login,
                actor: $login,
                metadata: ['reason' => 'invalid_current_password'],
            );

            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $updatedLogin = $this->clientLogins->updateUser($login, [
            'password' => $password,
            'must_change_password' => false,
            'password_changed_at' => now(),
            'failed_login_count' => 0,
            'locked_until' => null,
            'remember_token' => Str::random(60),
            'auth_version' => max(1, (int) $login->auth_version) + 1,
        ]);

        $this->auditLogs->recordSecurityEvent(
            'auth.password_changed',
            'Password berhasil diubah',
            target: $updatedLogin,
            actor: $updatedLogin,
        );

        return $this->clientLogins->refreshWithRole($updatedLogin);
    }

    public function assertCurrentPassword(ClientLogin $login, string $currentPassword, string $field): void
    {
        if ($login->password && Hash::check($currentPassword, $login->password)) {
            return;
        }

        $this->auditLogs->recordSecurityEvent(
            'auth.two_factor_password_failed',
            'Konfirmasi password two-factor authentication gagal',
            target: $login,
            actor: $login,
            metadata: ['reason' => 'invalid_current_password'],
        );

        throw ValidationException::withMessages([
            $field => 'Password saat ini tidak sesuai.',
        ]);
    }

    /** @param list<string> $hashedRecoveryCodes */
    public function enableTwoFactorAuthentication(
        ClientLogin $login,
        string $secret,
        array $hashedRecoveryCodes,
    ): ClientLogin {
        $updatedLogin = $this->clientLogins->updateUser($login, [
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $hashedRecoveryCodes,
            'two_factor_confirmed_at' => now(),
            'remember_token' => Str::random(60),
            'auth_version' => max(1, (int) $login->auth_version) + 1,
        ]);

        $this->auditLogs->recordSecurityEvent(
            'auth.two_factor_enabled',
            'Two-factor authentication diaktifkan',
            target: $updatedLogin,
            actor: $updatedLogin,
        );

        return $this->clientLogins->refreshWithRole($updatedLogin);
    }

    public function disableTwoFactorAuthentication(ClientLogin $login): ClientLogin
    {
        $updatedLogin = $this->clientLogins->updateUser($login, [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'remember_token' => Str::random(60),
            'auth_version' => max(1, (int) $login->auth_version) + 1,
        ]);

        $this->auditLogs->recordSecurityEvent(
            'auth.two_factor_disabled',
            'Two-factor authentication dinonaktifkan',
            target: $updatedLogin,
            actor: $updatedLogin,
        );

        return $this->clientLogins->refreshWithRole($updatedLogin);
    }

    public function recordTwoFactorVerificationFailure(ClientLogin $login, string $action): void
    {
        $this->auditLogs->recordSecurityEvent(
            'auth.two_factor_verification_failed',
            'Verifikasi two-factor authentication gagal',
            target: $login,
            actor: $login,
            metadata: [
                'reason' => 'invalid_code',
                'action' => $action,
            ],
        );
    }

    private function ensureAdmin(ClientLogin $login): void
    {
        $login = $this->clientLogins->loadRole($login);
        abort_unless($login->role->canManageSettings(), 403);
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
