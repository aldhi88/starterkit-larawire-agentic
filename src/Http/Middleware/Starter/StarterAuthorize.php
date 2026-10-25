<?php

namespace Aldhi88\StarterKit\Http\Middleware\Starter;

use Aldhi88\StarterKit\Contracts\Starter\AppRouteInterface;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StarterAuthorize
{
    public function __construct(
        private readonly AppRouteInterface $appRoutes,
        private readonly AuditLogService $audit,
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
            $this->audit->recordSecurityEvent(
                'authorization.module_access_denied',
                'Akses module ditolak',
                actor: $login,
                metadata: ['outcome' => 'denied', 'route_name' => $routeName],
            );
            abort(403);
        }

        return $next($request);
    }
}
