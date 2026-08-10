<?php

namespace Altekno\StarterKit\Http\Middleware\Starter;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StarterForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $login = $request->user();

        if ($login instanceof ClientLogin
            && $login->must_change_password
            && ! $request->routeIs('starter.profile.edit', 'auth.logout')) {
            return redirect()->route('starter.profile.edit', ['tab' => 'security']);
        }

        return $next($request);
    }
}
