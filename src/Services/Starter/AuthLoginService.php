<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthLoginService
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$iyXo7zsds4flChs1YKIM5.JJUzRpsY2ncVulkOwgEd6IdqQrRsMkO';

    private const SESSION_AUTH_VERSION = 'starter.auth_version';

    private const SESSION_OTP_CHALLENGE = 'starter.login_otp';

    public const SESSION_OTP_VERIFIED_LOGIN_ID = 'starter.login_otp_verified_login_id';

    private const SESSION_TWO_FACTOR_CHALLENGE = 'starter.login_two_factor';

    public const SESSION_TWO_FACTOR_VERIFIED_LOGIN_ID = 'starter.login_two_factor_verified_login_id';

    private const OTP_EXPIRES_SECONDS = 300;

    private const OTP_MAX_ATTEMPTS = 5;

    private const OTP_RESEND_SECONDS = 60;

    private const OTP_MAX_SENDS = 3;

    public function __construct(
        private readonly ClientLoginInterface $clientLogins,
        private readonly NavigationAuthorizedRedirectService $redirects,
        private readonly ClientInterface $clients,
        private readonly StarterConfigService $configs,
        private readonly AuditLogService $auditLogs,
        private readonly LoginOtpMailService $otpMail,
        private readonly TwoFactorAuthenticationService $twoFactor,
    ) {}

    public function attempt(string $username, string $password, bool $remember = false, ?string $redirect = null): ?string
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

        $this->assertLoginAllowed($login, 'form.identifier');
        RateLimiter::clear($accountThrottleKey);

        if ((bool) config('starter.auth.login_otp_enabled', false)) {
            $this->startOtpChallenge($login, $remember, $redirect);

            return null;
        }

        if ($this->twoFactor->enabled() && $login->hasTwoFactorAuthenticationEnabled()) {
            $this->startTwoFactorChallenge($login, $remember, $redirect, false);

            return null;
        }

        return $this->completeAuthentication($login, $remember, $redirect, false, false);
    }

    public function verifyOtp(string $code): ?string
    {
        $challenge = $this->otpChallenge();

        if ($challenge === null || ! (bool) config('starter.auth.login_otp_enabled', false)) {
            $this->clearOtpChallenge();

            throw ValidationException::withMessages([
                'otpForm.code' => 'Sesi verifikasi tidak tersedia. Silakan login kembali.',
            ]);
        }

        $login = $this->clientLogins->findForAuthentication($challenge['login_id']);

        if (! $login || $challenge['expires_at'] < now()->timestamp) {
            $this->clearOtpChallenge();

            $this->auditLogs->recordSecurityEvent(
                'auth.login_otp_expired',
                'Kode OTP login kedaluwarsa',
                target: $login,
                metadata: ['reason' => 'expired'],
            );

            throw ValidationException::withMessages([
                'otpForm.code' => 'Kode OTP sudah kedaluwarsa. Silakan login kembali.',
            ]);
        }

        $this->assertLoginAllowed($login, 'otpForm.code');

        if (! preg_match('/^\d{6}$/', $code) || ! Hash::check($code, $challenge['otp_hash'])) {
            $attempts = $challenge['attempts'] + 1;
            $blocked = $attempts >= self::OTP_MAX_ATTEMPTS;

            if ($blocked) {
                $this->clearOtpChallenge();
            } else {
                $challenge['attempts'] = $attempts;
                request()->session()->put(self::SESSION_OTP_CHALLENGE, $challenge);
            }

            $this->auditLogs->recordSecurityEvent(
                $blocked ? 'auth.login_otp_blocked' : 'auth.login_otp_failed',
                $blocked ? 'Verifikasi OTP login dibatasi' : 'Verifikasi OTP login gagal',
                target: $login,
                metadata: [
                    'reason' => 'invalid_code',
                    'attempts' => $attempts,
                ],
            );

            throw ValidationException::withMessages([
                'otpForm.code' => $blocked
                    ? 'Terlalu banyak kode yang salah. Silakan login kembali.'
                    : 'Kode OTP tidak valid.',
            ]);
        }

        $this->clearOtpChallenge();

        if ($this->twoFactor->enabled() && $login->hasTwoFactorAuthenticationEnabled()) {
            $this->startTwoFactorChallenge(
                $login,
                $challenge['remember'],
                $challenge['redirect'],
                true,
            );

            return null;
        }

        return $this->completeAuthentication(
            $login,
            $challenge['remember'],
            $challenge['redirect'],
            true,
            false,
        );
    }

    public function verifyTwoFactor(string $code): string
    {
        if (! $this->twoFactor->enabled()) {
            $this->clearTwoFactorChallenge();

            throw ValidationException::withMessages([
                'authenticatorForm.code' => 'Fitur authenticator sedang dinonaktifkan. Silakan login kembali.',
            ]);
        }

        $challenge = $this->twoFactorChallenge();

        if ($challenge === null) {
            $this->clearTwoFactorChallenge();

            throw ValidationException::withMessages([
                'authenticatorForm.code' => 'Sesi authenticator tidak tersedia. Silakan login kembali.',
            ]);
        }

        $login = $this->clientLogins->findForAuthentication($challenge['login_id']);

        if (! $login || ! $login->hasTwoFactorAuthenticationEnabled() || $challenge['expires_at'] < now()->timestamp) {
            $this->clearTwoFactorChallenge();
            $this->auditLogs->recordSecurityEvent(
                'auth.login_two_factor_expired',
                'Verifikasi authenticator login kedaluwarsa',
                target: $login,
                metadata: ['reason' => 'expired_or_disabled'],
            );

            throw ValidationException::withMessages([
                'authenticatorForm.code' => 'Sesi authenticator sudah berakhir. Silakan login kembali.',
            ]);
        }

        $this->assertLoginAllowed($login, 'authenticatorForm.code');
        $normalizedCode = strtoupper(trim($code));
        $verified = $this->twoFactor->verifyCode((string) $login->two_factor_secret, $normalizedCode);

        if (! $verified) {
            $recoveryLogin = DB::transaction(function () use ($login, $normalizedCode): ?ClientLogin {
                $lockedLogin = $this->clientLogins->findForAuthenticationWithLock((int) $login->getKey());

                if (! $lockedLogin || ! is_array($lockedLogin->two_factor_recovery_codes)) {
                    return null;
                }

                $remainingCodes = $this->twoFactor->consumeRecoveryCode(
                    $normalizedCode,
                    $lockedLogin->two_factor_recovery_codes,
                );

                if ($remainingCodes === null) {
                    return null;
                }

                $updatedLogin = $this->clientLogins->updateUser($lockedLogin, [
                    'two_factor_recovery_codes' => $remainingCodes,
                ]);
                $this->auditLogs->recordSecurityEvent(
                    'auth.login_two_factor_recovery_used',
                    'Kode pemulihan digunakan untuk login',
                    target: $updatedLogin,
                );

                return $updatedLogin;
            });

            if ($recoveryLogin !== null) {
                $login = $recoveryLogin;
                $verified = true;
            }
        }

        if (! $verified) {
            $attempts = $challenge['attempts'] + 1;
            $blocked = $attempts >= self::OTP_MAX_ATTEMPTS;

            if ($blocked) {
                $this->clearTwoFactorChallenge();
            } else {
                $challenge['attempts'] = $attempts;
                request()->session()->put(self::SESSION_TWO_FACTOR_CHALLENGE, $challenge);
            }

            $this->auditLogs->recordSecurityEvent(
                $blocked ? 'auth.login_two_factor_blocked' : 'auth.login_two_factor_failed',
                $blocked ? 'Verifikasi authenticator login dibatasi' : 'Verifikasi authenticator login gagal',
                target: $login,
                metadata: [
                    'reason' => 'invalid_code',
                    'attempts' => $attempts,
                ],
            );

            throw ValidationException::withMessages([
                'authenticatorForm.code' => $blocked
                    ? 'Terlalu banyak kode yang salah. Silakan login kembali.'
                    : 'Kode authenticator atau kode pemulihan tidak valid.',
            ]);
        }

        $this->clearTwoFactorChallenge();

        return $this->completeAuthentication(
            $login,
            $challenge['remember'],
            $challenge['redirect'],
            $challenge['otp_verified'],
            true,
        );
    }

    public function resendOtp(): void
    {
        $challenge = $this->otpChallenge();

        if ($challenge === null || $challenge['expires_at'] < now()->timestamp) {
            $this->clearOtpChallenge();

            throw ValidationException::withMessages([
                'otpForm.code' => 'Sesi verifikasi sudah berakhir. Silakan login kembali.',
            ]);
        }

        $waitSeconds = $challenge['resend_available_at'] - now()->timestamp;

        if ($waitSeconds > 0) {
            throw ValidationException::withMessages([
                'otpForm.code' => "Tunggu {$waitSeconds} detik sebelum mengirim ulang kode.",
            ]);
        }

        $login = $this->clientLogins->findForAuthentication($challenge['login_id']);

        if (! $login) {
            $this->clearOtpChallenge();

            throw ValidationException::withMessages([
                'otpForm.code' => 'Akun tidak tersedia. Silakan login kembali.',
            ]);
        }

        $this->assertLoginAllowed($login, 'otpForm.code');
        $this->deliverOtp($login, $challenge['remember'], $challenge['redirect'], 'resent');
    }

    /** @return array{masked_email: string, resend_available_at: int}|null */
    public function pendingOtp(): ?array
    {
        if (! (bool) config('starter.auth.login_otp_enabled', false)) {
            $this->clearOtpChallenge();

            return null;
        }

        $challenge = $this->otpChallenge();

        if ($challenge === null || $challenge['expires_at'] < now()->timestamp) {
            $this->clearOtpChallenge();

            return null;
        }

        return [
            'masked_email' => $challenge['masked_email'],
            'resend_available_at' => $challenge['resend_available_at'],
        ];
    }

    public function cancelOtp(): void
    {
        $this->clearOtpChallenge();
    }

    public function pendingTwoFactor(): bool
    {
        if (! $this->twoFactor->enabled()) {
            $this->clearTwoFactorChallenge();

            return false;
        }

        $challenge = $this->twoFactorChallenge();

        if ($challenge === null || $challenge['expires_at'] < now()->timestamp) {
            $this->clearTwoFactorChallenge();

            return false;
        }

        return true;
    }

    public function cancelTwoFactor(): void
    {
        $this->clearOtpChallenge();
        $this->clearTwoFactorChallenge();
    }

    private function startOtpChallenge(ClientLogin $login, bool $remember, ?string $redirect): void
    {
        if (! request()->hasSession()) {
            throw ValidationException::withMessages([
                'form.identifier' => 'Sesi login tidak tersedia. Muat ulang halaman lalu coba kembali.',
            ]);
        }

        $this->deliverOtp($login, $remember, $redirect, 'requested');
    }

    private function startTwoFactorChallenge(
        ClientLogin $login,
        bool $remember,
        ?string $redirect,
        bool $otpVerified,
    ): void {
        if (! request()->hasSession()) {
            throw ValidationException::withMessages([
                'form.identifier' => 'Sesi login tidak tersedia. Muat ulang halaman lalu coba kembali.',
            ]);
        }

        request()->session()->put(self::SESSION_TWO_FACTOR_CHALLENGE, [
            'login_id' => (int) $login->getKey(),
            'remember' => $remember,
            'redirect' => filled($redirect) ? $redirect : null,
            'otp_verified' => $otpVerified,
            'expires_at' => now()->addSeconds(self::OTP_EXPIRES_SECONDS)->timestamp,
            'attempts' => 0,
        ]);

        $this->auditLogs->recordSecurityEvent(
            'auth.login_two_factor_requested',
            'Verifikasi authenticator login diminta',
            target: $login,
        );
    }

    private function deliverOtp(ClientLogin $login, bool $remember, ?string $redirect, string $event): void
    {
        $sendThrottleKey = $this->otpSendThrottleKey($login->getKey(), (string) request()->ip());

        if (RateLimiter::tooManyAttempts($sendThrottleKey, self::OTP_MAX_SENDS)) {
            $this->auditLogs->recordSecurityEvent(
                'auth.login_otp_blocked',
                'Pengiriman OTP login dibatasi',
                target: $login,
                metadata: ['reason' => 'send_rate_limited'],
            );

            throw ValidationException::withMessages([
                $event === 'requested' ? 'form.identifier' : 'otpForm.code' => 'Terlalu banyak permintaan kode OTP. Coba lagi dalam '.RateLimiter::availableIn($sendThrottleKey).' detik.',
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        RateLimiter::hit($sendThrottleKey, self::OTP_EXPIRES_SECONDS);

        try {
            $this->otpMail->send($login, $code, intdiv(self::OTP_EXPIRES_SECONDS, 60));
        } catch (Throwable) {
            $this->auditLogs->recordSecurityEvent(
                'auth.login_otp_delivery_failed',
                'Pengiriman OTP login gagal',
                target: $login,
                metadata: ['reason' => 'mail_delivery_failed'],
            );

            throw ValidationException::withMessages([
                $event === 'requested' ? 'form.identifier' : 'otpForm.code' => 'Kode OTP gagal dikirim. Periksa konfigurasi email atau coba kembali.',
            ]);
        }

        request()->session()->put(self::SESSION_OTP_CHALLENGE, [
            'login_id' => (int) $login->getKey(),
            'otp_hash' => Hash::make($code),
            'remember' => $remember,
            'redirect' => filled($redirect) ? $redirect : null,
            'masked_email' => $this->maskEmail($login->email),
            'expires_at' => now()->addSeconds(self::OTP_EXPIRES_SECONDS)->timestamp,
            'attempts' => 0,
            'resend_available_at' => now()->addSeconds(self::OTP_RESEND_SECONDS)->timestamp,
        ]);

        $this->auditLogs->recordSecurityEvent(
            $event === 'requested' ? 'auth.login_otp_requested' : 'auth.login_otp_resent',
            $event === 'requested' ? 'Kode OTP login dikirim' : 'Kode OTP login dikirim ulang',
            target: $login,
        );
    }

    private function completeAuthentication(
        ClientLogin $login,
        bool $remember,
        ?string $redirect,
        bool $otpVerified,
        bool $twoFactorVerified,
    ): string {
        Auth::login(
            $login,
            ! $otpVerified
                && ! $twoFactorVerified
                && $remember
                && $this->configs->boolean('security.remember_me_enabled'),
        );

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

            if ($otpVerified) {
                request()->session()->put(self::SESSION_OTP_VERIFIED_LOGIN_ID, (int) $login->getKey());
            } else {
                request()->session()->forget(self::SESSION_OTP_VERIFIED_LOGIN_ID);
            }

            if ($twoFactorVerified) {
                request()->session()->put(self::SESSION_TWO_FACTOR_VERIFIED_LOGIN_ID, (int) $login->getKey());
            } else {
                request()->session()->forget(self::SESSION_TWO_FACTOR_VERIFIED_LOGIN_ID);
            }

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
            return route('starter.profile.edit', ['tab' => 'security']);
        }

        session()->pull('url.intended');

        return $this->redirects->forLogin(
            $authenticatedLogin,
            $redirect,
            null,
        );
    }

    private function assertLoginAllowed(ClientLogin $login, string $field): void
    {
        if ($this->clients->current()->account_status !== 'approved') {
            $this->auditLogs->recordSecurityEvent(
                'auth.login_blocked',
                'Login ditolak karena perusahaan tidak aktif',
                target: $login,
                metadata: ['reason' => 'company_inactive'],
            );

            throw ValidationException::withMessages([
                $field => 'Perusahaan tidak aktif atau belum disetujui.',
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
                $field => 'Akun tidak aktif atau sedang dikunci. Hubungi administrator.',
            ]);
        }
    }

    /**
     * @return array{
     *     login_id: int,
     *     otp_hash: string,
     *     remember: bool,
     *     redirect: string|null,
     *     masked_email: string,
     *     expires_at: int,
     *     attempts: int,
     *     resend_available_at: int
     * }|null
     */
    private function otpChallenge(): ?array
    {
        if (! request()->hasSession()) {
            return null;
        }

        $challenge = request()->session()->get(self::SESSION_OTP_CHALLENGE);

        if (! is_array($challenge)
            || ! is_int($challenge['login_id'] ?? null)
            || ! is_string($challenge['otp_hash'] ?? null)
            || ! is_bool($challenge['remember'] ?? null)
            || (! is_string($challenge['redirect'] ?? null) && ($challenge['redirect'] ?? null) !== null)
            || ! is_string($challenge['masked_email'] ?? null)
            || ! is_int($challenge['expires_at'] ?? null)
            || ! is_int($challenge['attempts'] ?? null)
            || ! is_int($challenge['resend_available_at'] ?? null)) {
            return null;
        }

        return $challenge;
    }

    private function clearOtpChallenge(): void
    {
        if (request()->hasSession()) {
            request()->session()->forget(self::SESSION_OTP_CHALLENGE);
        }
    }

    /**
     * @return array{
     *     login_id: int,
     *     remember: bool,
     *     redirect: string|null,
     *     otp_verified: bool,
     *     expires_at: int,
     *     attempts: int
     * }|null
     */
    private function twoFactorChallenge(): ?array
    {
        if (! request()->hasSession()) {
            return null;
        }

        $challenge = request()->session()->get(self::SESSION_TWO_FACTOR_CHALLENGE);

        if (! is_array($challenge)
            || ! is_int($challenge['login_id'] ?? null)
            || ! is_bool($challenge['remember'] ?? null)
            || (! is_string($challenge['redirect'] ?? null) && ($challenge['redirect'] ?? null) !== null)
            || ! is_bool($challenge['otp_verified'] ?? null)
            || ! is_int($challenge['expires_at'] ?? null)
            || ! is_int($challenge['attempts'] ?? null)) {
            return null;
        }

        return $challenge;
    }

    private function clearTwoFactorChallenge(): void
    {
        if (request()->hasSession()) {
            request()->session()->forget(self::SESSION_TWO_FACTOR_CHALLENGE);
        }
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($local === '' || $domain === '') {
            return 'email akun Anda';
        }

        return mb_substr($local, 0, 1).str_repeat('*', max(3, min(8, mb_strlen($local) - 1))).'@'.$domain;
    }

    private function accountThrottleKey(string $identifier, string $ipAddress): string
    {
        return 'login-account:'.hash('sha256', $identifier.'|'.$ipAddress);
    }

    private function ipThrottleKey(string $ipAddress): string
    {
        return 'login-ip:'.hash('sha256', $ipAddress);
    }

    private function otpSendThrottleKey(int|string $loginId, string $ipAddress): string
    {
        return 'login-otp-send:'.hash('sha256', $loginId.'|'.$ipAddress);
    }
}
