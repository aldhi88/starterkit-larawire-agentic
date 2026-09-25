<?php

namespace Aldhi88\StarterKit\Http\Middleware\Starter;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Support\Starter\StarterNavigation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StarterLockScreen
{
    public function __construct(
        private readonly StarterConfigService $configs,
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

        if (! $login instanceof ClientLogin) {
            return $next($request);
        }

        if (! $this->configs->boolean('security.lock_screen_enabled')) {
            $request->session()->forget(['starter.locked', 'starter.lock.intended']);

            if ($this->recordsUserActivity($request)) {
                $request->session()->put('starter.last_activity_at', now()->timestamp);
            }

            return $next($request);
        }

        if ($request->routeIs('starter.lock-screen', 'auth.logout')) {
            return $next($request);
        }

        if ((bool) $request->session()->get('starter.locked', false)) {
            return $this->lockedResponse($request);
        }

        $lastActivityAt = (int) $request->session()->get('starter.last_activity_at', now()->timestamp);
        $timeoutSeconds = max(60, min(86400, $this->configs->integer('security.lock_screen_timeout_minutes') * 60));

        if (now()->timestamp - $lastActivityAt >= $timeoutSeconds) {
            $request->session()->put('starter.locked', true);
            $this->auditLogs->recordSecurityEvent(
                'auth.screen_locked',
                'Layar dikunci otomatis',
                target: $login,
                actor: $login,
                metadata: [
                    'reason' => 'inactivity_timeout',
                    'timeout_seconds' => $timeoutSeconds,
                ],
            );

            return $this->lockedResponse($request, 'idle_timeout');
        }

        if ($this->recordsUserActivity($request)) {
            $request->session()->put('starter.last_activity_at', now()->timestamp);
        }

        return $next($request);
    }

    private function lockedResponse(Request $request, ?string $reason = null): Response
    {
        $returnUrl = StarterNavigation::safeRequestReturnUrl($request);
        $storedReturnUrl = $request->session()->get('starter.lock.intended');

        if ($returnUrl === null && is_string($storedReturnUrl) && StarterNavigation::isSafeRedirect($storedReturnUrl)) {
            $returnUrl = $storedReturnUrl;
        }

        $returnUrl ??= url('/');
        $request->session()->put('starter.lock.intended', $returnUrl);

        $parameters = ['redirect' => $returnUrl];

        if ($reason !== null) {
            $parameters['reason'] = $reason;
        }

        $lockUrl = route('starter.lock-screen', $parameters);

        if ($request->headers->get('X-Livewire') === '1') {
            return response()->json([
                'message' => 'Session locked.',
                'redirect' => $lockUrl,
                'lock_url' => $lockUrl,
                'return_url' => $returnUrl,
            ], 423)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return redirect()->to($lockUrl);
    }

    private function recordsUserActivity(Request $request): bool
    {
        return $request->headers->get('X-Livewire') !== '1'
            || $request->headers->get('X-Starter-Passive') !== '1';
    }
}
