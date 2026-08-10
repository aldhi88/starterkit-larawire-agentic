<?php

namespace Altekno\StarterKit\Livewire\Starter\Auth;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuditLogService;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Altekno\StarterKit\Services\Starter\StarterConfigService;
use Altekno\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Layar Dikunci')]
class LockScreen extends Component
{
    public string $password = '';

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(
        StarterConfigService $configs,
        NavigationAuthorizedRedirectService $redirects
    ) {
        $login = $this->login();

        if (! $configs->boolean('security.lock_screen_enabled')) {
            $this->clearLockState();

            return $this->redirect(
                $redirects->firstAuthorizedUrl($login),
            );
        }

        $redirect = request()->query('redirect');

        if (blank($redirect) && url()->previous() !== url()->current()) {
            $redirect = url()->previous();
        }

        if (is_string($redirect) && StarterNavigation::isSafeRedirect($redirect)) {
            session()->put('starter.lock.intended', $redirect);
        }

        session()->put('starter.locked', true);

        return null;
    }

    public function unlock(
        StarterConfigService $configs,
        NavigationAuthorizedRedirectService $redirects,
        AuditLogService $auditLogs
    ) {
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
        $throttleKey = 'lock-screen|'.$login->getKey().'|'.request()->ip();
        $maxAttempts = max(1, min(20, $configs->integer('security.login_max_attempts')));
        $decaySeconds = max(30, min(3600, $configs->integer('security.login_decay_seconds')));

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->reset('password');
            $auditLogs->recordSecurityEvent(
                'auth.screen_unlock_blocked',
                'Unlock layar dibatasi sementara',
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
                'auth.screen_unlock_failed',
                'Unlock layar gagal',
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
        $intended = session()->pull('starter.lock.intended');
        $this->clearLockState();
        session()->regenerate();
        $auditLogs->recordSecurityEvent(
            'auth.screen_unlocked',
            'Layar berhasil dibuka',
            target: $login,
            actor: $login,
        );

        return $this->redirect($redirects->forLogin($login, null, $intended));
    }

    public function render()
    {
        return view('starter.auth.lock-screen', [
            'login' => $this->login(),
        ]);
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->current();
    }

    private function clearLockState(): void
    {
        session()->forget('starter.locked');
        session()->put('starter.last_activity_at', now()->timestamp);
    }
}
