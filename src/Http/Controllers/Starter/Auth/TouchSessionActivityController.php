<?php

namespace Altekno\StarterKit\Http\Controllers\Starter\Auth;

use Altekno\StarterKit\Services\Starter\StarterConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TouchSessionActivityController extends Controller
{
    public function __construct(private readonly StarterConfigService $configs) {}

    public function __invoke(Request $request): JsonResponse|Response
    {
        if (! $this->configs->boolean('security.lock_screen_enabled')) {
            $request->session()->forget(['starter.locked', 'starter.lock.intended']);
            $request->session()->put('starter.last_activity_at', now()->timestamp);

            return response()->noContent();
        }

        if ((bool) $request->session()->get('starter.locked', false)) {
            return response()->json([
                'redirect' => route('starter.lock-screen'),
            ], 423);
        }

        $timeoutSeconds = max(60, min(86400, $this->configs->integer('security.lock_screen_timeout_minutes') * 60));
        $lastActivityAt = (int) $request->session()->get('starter.last_activity_at', now()->timestamp);

        if (now()->timestamp - $lastActivityAt >= $timeoutSeconds) {
            $request->session()->put('starter.locked', true);

            return response()->json([
                'redirect' => route('starter.lock-screen'),
            ], 423);
        }

        $request->session()->put('starter.last_activity_at', now()->timestamp);

        return response()->noContent();
    }
}
