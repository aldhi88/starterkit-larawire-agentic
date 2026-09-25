<?php

namespace Aldhi88\StarterKit\Http\Controllers\Starter\Auth;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TouchSessionActivityController extends Controller
{
    public function __construct(
        private readonly StarterConfigService $configs,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function __invoke(Request $request): JsonResponse|Response
    {
        if (! $this->configs->boolean('security.lock_screen_enabled')) {
            $request->session()->forget(['starter.locked', 'starter.lock.intended']);
            $request->session()->put('starter.last_activity_at', now()->timestamp);

            return response()->noContent();
        }

        if ((bool) $request->session()->get('starter.locked', false)) {
            return $this->lockedResponse($request);
        }

        $timeoutSeconds = max(60, min(86400, $this->configs->integer('security.lock_screen_timeout_minutes') * 60));
        $lastActivityAt = (int) $request->session()->get('starter.last_activity_at', now()->timestamp);

        if (now()->timestamp - $lastActivityAt >= $timeoutSeconds) {
            $request->session()->put('starter.locked', true);

            $login = $request->user();

            if ($login instanceof ClientLogin) {
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
            }

            return $this->lockedResponse($request, 'idle_timeout');
        }

        $request->session()->put('starter.last_activity_at', now()->timestamp);

        return response()->noContent();
    }

    private function lockedResponse(Request $request, ?string $reason = null): JsonResponse
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

        return response()->json([
            'message' => 'Session locked.',
            'redirect' => $lockUrl,
            'lock_url' => $lockUrl,
            'return_url' => $returnUrl,
        ], 423)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
