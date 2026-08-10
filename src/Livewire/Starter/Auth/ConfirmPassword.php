<?php

namespace Altekno\StarterKit\Livewire\Starter\Auth;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuditLogService;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Altekno\StarterKit\Services\Starter\StarterConfigService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Konfirmasi Password')]
class ConfirmPassword extends Component
{
    public string $password = '';

    private NavigationAuthorizedRedirectService $redirects;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(
        NavigationAuthorizedRedirectService $redirects,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->redirects = $redirects;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function confirm(
        StarterConfigService $configs,
        NavigationAuthorizedRedirectService $redirects,
        AuditLogService $auditLogs,
    ): mixed {
        try {
            $this->validate([
                'password' => ['required', 'string', 'max:1024'],
            ], [], [
                'password' => 'password',
            ]);
        } catch (ValidationException $exception) {
            $this->reset('password');

            throw $exception;
        }

        $login = $this->login();
        $throttleKey = 'confirm-password|'.$login->getKey().'|'.request()->ip();
        $maxAttempts = max(1, min(20, $configs->integer('security.login_max_attempts')));
        $decaySeconds = max(30, min(3600, $configs->integer('security.login_decay_seconds')));

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->reset('password');
            $auditLogs->recordSecurityEvent(
                'auth.password_confirmation_blocked',
                'Konfirmasi password dibatasi sementara',
                target: $login,
                actor: $login,
                metadata: ['reason' => 'rate_limited'],
            );

            throw ValidationException::withMessages([
                'password' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        if (! $login->password || ! Hash::check($this->password, $login->password)) {
            RateLimiter::hit($throttleKey, $decaySeconds);
            $this->reset('password');
            $auditLogs->recordSecurityEvent(
                'auth.password_confirmation_failed',
                'Konfirmasi password gagal',
                target: $login,
                actor: $login,
                metadata: ['reason' => 'invalid_password'],
            );

            throw ValidationException::withMessages([
                'password' => 'Password tidak sesuai.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $this->reset('password');
        session()->passwordConfirmed();
        $intended = session()->pull('url.intended');

        $auditLogs->recordSecurityEvent(
            'auth.password_confirmation_succeeded',
            'Konfirmasi password berhasil',
            target: $login,
            actor: $login,
        );

        return $this->redirect($redirects->forLogin($login, null, $intended));
    }

    public function render()
    {
        return view('starter.auth.confirm-password', [
            'cancelUrl' => $this->redirects->firstAuthorizedUrl($this->login()),
        ]);
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->current();
    }
}
