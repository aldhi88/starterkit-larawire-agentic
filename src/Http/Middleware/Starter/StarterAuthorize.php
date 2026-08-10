<?php

namespace Altekno\StarterKit\Http\Middleware\Starter;

use Altekno\StarterKit\Contracts\Starter\AppRouteInterface;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StarterAuthorize
{
    public function __construct(
        private readonly AppRouteInterface $appRoutes,
    ) {}

    /**
     * Authorize access to starter page routes synced into app_routes.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $login = $request->user();
        $routeName = $request->route()?->getName();

        if ($routeName && str_starts_with($routeName, 'starter.')) {
            return $next($request);
        }

        if (! $login instanceof ClientLogin || ! $routeName) {
            abort(403);
        }

        if (! $this->appRoutes->viewerCanAccessNamedRoute($login, $routeName)) {
            abort(403);
        }

        return $next($request);
    }
}
