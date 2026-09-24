<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
     * @param  array{name: string, email?: ?string, phone?: ?string, pic_name?: ?string, logo?: ?string}  $data
     */
    public function updateClientProfile(ClientLogin $login, array $data): Client
    {
        $this->ensureAdmin($login);

        return $this->clients->updateProfile($this->clients->current(), [
            'name' => trim($data['name']),
            'email' => $this->nullableTrim($data['email'] ?? null),
            'phone' => $this->nullableTrim($data['phone'] ?? null),
            'pic_name' => $this->nullableTrim($data['pic_name'] ?? null),
            'logo' => $this->nullableTrim($data['logo'] ?? null),
        ]);
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
