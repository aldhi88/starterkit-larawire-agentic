<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\ClientInterface;
use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthLoginService
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$iyXo7zsds4flChs1YKIM5.JJUzRpsY2ncVulkOwgEd6IdqQrRsMkO';

    private const SESSION_AUTH_VERSION = 'starter.auth_version';

    public function __construct(
        private readonly ClientLoginInterface $clientLogins,
        private readonly NavigationAuthorizedRedirectService $redirects,
        private readonly ClientInterface $clients,
        private readonly StarterConfigService $configs,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function attempt(string $username, string $password, bool $remember = false, ?string $redirect = null): string
    {
        $identifier = str($username)->lower()->trim()->toString();
        $ipAddress = (string) request()->ip();
        $accountThrottleKey = $this->accountThrottleKey($identifier, $ipAddress);
        $ipThrottleKey = $this->ipThrottleKey($ipAddress);
        $maxAttempts = max(1, min(20, $this->configs->integer('security.login_max_attempts')));
        $maxIpAttempts = min(100, $maxAttempts * 5);
        $decaySeconds = max(30, min(3600, $this->configs->integer('security.login_decay_seconds')));

        if (RateLimiter::tooManyAttempts($accountThrottleKey, $maxAttempts)
            || RateLimiter::tooManyAttempts($ipThrottleKey, $maxIpAttempts)) {
            $this->auditLogs->recordSecurityEvent(
                'auth.login_blocked',
                'Login dibatasi sementara',
                metadata: [
                    'reason' => 'rate_limited',
                    'scope' => RateLimiter::tooManyAttempts($ipThrottleKey, $maxIpAttempts)
                        ? 'ip'
                        : 'account',
                ],
            );

            throw ValidationException::withMessages([
                'form.identifier' => 'Terlalu banyak percobaan login. Coba lagi dalam '.max(
                    RateLimiter::availableIn($accountThrottleKey),
                    RateLimiter::availableIn($ipThrottleKey),
                ).' detik.',
            ]);
        }

        $login = $this->clientLogins->findByUsername($identifier);
        $passwordMatches = Hash::check($password, $login?->password ?: self::DUMMY_PASSWORD_HASH);

        if (! $login || ! $login->password || ! $passwordMatches) {
            RateLimiter::hit($accountThrottleKey, $decaySeconds);
            RateLimiter::hit($ipThrottleKey, $decaySeconds);
            $failedLoginCount = null;

            if ($login) {
                $failedLoginCount = $login->failed_login_count + 1;
                $this->clientLogins->updateUser($login, [
                    'failed_login_count' => $failedLoginCount,
                    'locked_until' => $failedLoginCount >= $maxAttempts
                        ? now()->addSeconds($decaySeconds)
                        : $login->locked_until,
                ]);
            }

            $this->auditLogs->recordSecurityEvent(
                $failedLoginCount !== null && $failedLoginCount >= $maxAttempts
                    ? 'auth.login_locked'
                    : 'auth.login_failed',
                $failedLoginCount !== null && $failedLoginCount >= $maxAttempts
                    ? 'Akun dikunci setelah login gagal'
                    : 'Percobaan login gagal',
                target: $login,
                metadata: array_filter([
                    'reason' => 'invalid_credentials',
                    'failed_login_count' => $failedLoginCount,
                ], fn (mixed $value): bool => $value !== null),
            );

            throw ValidationException::withMessages([
                'form.identifier' => __('auth.failed'),
            ]);
        }

        if ($this->clients->current()->account_status !== 'approved') {
            $this->auditLogs->recordSecurityEvent(
                'auth.login_blocked',
                'Login ditolak karena perusahaan tidak aktif',
                target: $login,
                metadata: ['reason' => 'company_inactive'],
            );

            throw ValidationException::withMessages([
                'form.identifier' => 'Perusahaan tidak aktif atau belum disetujui.',
            ]);
        }

        if (! $login->isActive()) {
            $this->auditLogs->recordSecurityEvent(
                'auth.login_blocked',
                'Login ditolak karena akun tidak aktif',
                target: $login,
                metadata: ['reason' => 'account_inactive'],
            );

            throw ValidationException::withMessages([
                'form.identifier' => 'Akun tidak aktif atau sedang dikunci. Hubungi administrator.',
            ]);
        }

        RateLimiter::clear($accountThrottleKey);

        Auth::login($login, $remember && $this->configs->boolean('security.remember_me_enabled'));

        $this->clientLogins->updateUser($login, [
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'failed_login_count' => 0,
            'locked_until' => null,
        ]);

        if (request()->hasSession()) {
            request()->session()->regenerate();
            request()->session()->forget(['starter.locked', 'starter.lock.intended']);
            request()->session()->put('starter.last_activity_at', now()->timestamp);
            request()->session()->put(self::SESSION_AUTH_VERSION, max(1, (int) $login->auth_version));
            request()->session()->passwordConfirmed();
        }

        $authenticatedLogin = $this->clientLogins->refreshWithRole($login);
        $this->auditLogs->recordSecurityEvent(
            'auth.login_succeeded',
            'Login berhasil',
            target: $authenticatedLogin,
            actor: $authenticatedLogin,
        );

        if ($authenticatedLogin->must_change_password) {
            return $this->redirects->firstAuthorizedUrl($authenticatedLogin);
        }

        session()->pull('url.intended');

        return $this->redirects->forLogin(
            $authenticatedLogin,
            $redirect,
            null,
        );
    }

    private function accountThrottleKey(string $identifier, string $ipAddress): string
    {
        return 'login-account:'.hash('sha256', $identifier.'|'.$ipAddress);
    }

    private function ipThrottleKey(string $ipAddress): string
    {
        return 'login-ip:'.hash('sha256', $ipAddress);
    }
}
