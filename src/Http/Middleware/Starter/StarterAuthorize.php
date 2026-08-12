<?php

namespace Aldhi88\StarterKit\Http\Middleware\Starter;

use Aldhi88\StarterKit\Contracts\Starter\AppRouteInterface;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
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
