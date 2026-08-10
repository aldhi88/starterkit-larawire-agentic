<?php

namespace Altekno\StarterKit\Http\Middleware\Starter;

use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StarterAdmin
{
    public function __construct(
        private readonly AuthenticatedLoginService $authenticatedLogins,
    ) {}

    /**
     * Allow roles with the settings capability to access starter management.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->authenticatedLogins->settingsManager($request->user());

        return $next($request);
    }
}
