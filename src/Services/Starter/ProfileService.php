<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\ClientInterface;
use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Altekno\StarterKit\Models\Starter\Client;
use Altekno\StarterKit\Models\Starter\ClientLogin;
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
     * @param  array{name: string, email: string, profile_photo?: ?string}  $data
     */
    public function updateProfile(ClientLogin $login, array $data): ClientLogin
    {
        $updatedLogin = $this->clientLogins->updateUser($login, [
            'name' => trim($data['name']),
            'email' => str($data['email'])->lower()->trim()->toString(),
            'profile_photo' => $this->nullableTrim($data['profile_photo'] ?? null),
        ]);

        return $this->clientLogins->refreshWithRole($updatedLogin);
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

    private function ensureAdmin(ClientLogin $login): void
    {
        $login = $this->clientLogins->loadRole($login);
        abort_unless($login->role?->canManageSettings(), 403);
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
