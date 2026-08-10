<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Support\Facades\DB;

class SecuritySettingsService
{
    public function __construct(
        private readonly StarterConfigService $configs,
        private readonly ClientLoginInterface $users,
        private readonly AuditLogService $auditLogs,
    ) {}

    /**
     * @param  array{
     *     remember_me_enabled: bool,
     *     lock_screen_enabled: bool,
     *     lock_screen_timeout_minutes: int,
     *     login_max_attempts: int,
     *     login_decay_seconds: int
     * }  $values
     */
    public function update(ClientLogin $actor, array $values): void
    {
        abort_unless($actor->role?->canManageSettings() ?? false, 403);

        $this->auditLogs->withinAction(
            'config.security.update',
            'Mengubah konfigurasi keamanan',
            function () use ($values): void {
                DB::transaction(function () use ($values): void {
                    $this->configs->update([
                        'security.remember_me_enabled' => (bool) $values['remember_me_enabled'],
                        'security.lock_screen_enabled' => (bool) $values['lock_screen_enabled'],
                        'security.lock_screen_timeout_minutes' => (int) $values['lock_screen_timeout_minutes'],
                        'security.login_max_attempts' => (int) $values['login_max_attempts'],
                        'security.login_decay_seconds' => (int) $values['login_decay_seconds'],
                    ]);

                    if (! $values['remember_me_enabled']) {
                        $this->auditRevokedRememberTokens($this->users->revokeRememberTokens());
                    }
                });
            },
        );
    }

    private function auditRevokedRememberTokens(int $revokedTokens): void
    {
        if ($revokedTokens === 0) {
            return;
        }

        $this->auditLogs->recordManual(
            'updated',
            ClientLogin::class,
            'bulk-remember-token',
            newValues: ['remember_token' => '[REDACTED]'],
            tableName: 'starter_client_logins',
            auditableLabel: 'Session remember me',
            metadata: ['revoked_user_count' => $revokedTokens],
        );
    }
}
