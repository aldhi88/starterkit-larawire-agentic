<?php

namespace Altekno\StarterKit\Http\Middleware\Starter;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\StarterConfigService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StarterLockScreen
{
    public function __construct(private readonly StarterConfigService $configs) {}

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
            $request->session()->put('starter.last_activity_at', now()->timestamp);

            return $next($request);
        }

        if ($request->routeIs('starter.lock-screen', 'auth.logout')) {
            return $next($request);
        }

        if ((bool) $request->session()->get('starter.locked', false)) {
            return redirect()->route('starter.lock-screen');
        }

        $lastActivityAt = (int) $request->session()->get('starter.last_activity_at', now()->timestamp);
        $timeoutSeconds = max(60, min(86400, $this->configs->integer('security.lock_screen_timeout_minutes') * 60));

        if (now()->timestamp - $lastActivityAt >= $timeoutSeconds) {
            if ($request->isMethod('GET')) {
                $request->session()->put('starter.lock.intended', $request->fullUrl());
            }

            $request->session()->put('starter.locked', true);

            return redirect()->route('starter.lock-screen');
        }

        $request->session()->put('starter.last_activity_at', now()->timestamp);

        return $next($request);
    }
}
