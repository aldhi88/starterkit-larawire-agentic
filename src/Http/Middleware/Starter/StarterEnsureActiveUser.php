<?php

namespace Altekno\StarterKit\Http\Middleware\Starter;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StarterEnsureActiveUser
{
    private const SESSION_AUTH_VERSION = 'starter.auth_version';

    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $login = $request->user();

        if ($login instanceof ClientLogin && $this->credentialsChanged($request, $login)) {
            $this->auditLogs->recordSecurityEvent(
                'auth.session_revoked',
                'Session dihentikan karena kredensial berubah',
                target: $login,
                actor: $login,
                metadata: ['reason' => 'credentials_changed'],
            );

            return $this->terminateSession(
                $request,
                'Sesi Anda telah berakhir karena password berubah. Silakan login kembali.',
            );
        }

        if ($login instanceof ClientLogin && ! $login->isActive()) {
            $this->auditLogs->recordSecurityEvent(
                'auth.session_revoked',
                'Session dihentikan karena akun tidak aktif',
                target: $login,
                actor: $login,
                metadata: ['reason' => 'account_inactive'],
            );

            return $this->terminateSession(
                $request,
                'Akun tidak aktif atau sedang dikunci. Hubungi administrator.',
            );
        }

        return $next($request);
    }

    private function credentialsChanged(Request $request, ClientLogin $login): bool
    {
        $currentVersion = max(1, (int) $login->auth_version);
        $sessionVersion = $request->session()->get(self::SESSION_AUTH_VERSION);

        if ($sessionVersion === null) {
            if ($currentVersion !== 1) {
                return true;
            }

            $request->session()->put(self::SESSION_AUTH_VERSION, 1);

            return false;
        }

        return (int) $sessionVersion !== $currentVersion;
    }

    private function terminateSession(Request $request, string $message): Response
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login')->withErrors([
            'form.identifier' => $message,
        ]);
    }
}
