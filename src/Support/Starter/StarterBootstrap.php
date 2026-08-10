<?php

namespace Altekno\StarterKit\Support\Starter;

use Altekno\StarterKit\Http\Middleware\Starter\StarterAdmin;
use Altekno\StarterKit\Http\Middleware\Starter\StarterAuthorize;
use Altekno\StarterKit\Http\Middleware\Starter\StarterEnsureActiveUser;
use Altekno\StarterKit\Http\Middleware\Starter\StarterForcePasswordChange;
use Altekno\StarterKit\Http\Middleware\Starter\StarterLockScreen;
use Altekno\StarterKit\Http\Middleware\Starter\StarterLogAccess;
use Altekno\StarterKit\Http\Middleware\Starter\StarterSecurityHeaders;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class StarterBootstrap
{
    public static function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(StarterPaths::path('routes/starter/global.php'));

        Route::middleware('web')
            ->domain(config('app.domain'))
            ->group(StarterPaths::path('routes/starter/web.php'));

        StarterRouteRegistrar::registerAll();
    }

    /**
     * @param  array<string, class-string>  $additionalAliases
     */
    public static function configureMiddleware(Middleware $middleware, array $additionalAliases = []): void
    {
        $middleware->trustHosts();
        $middleware->append(StarterSecurityHeaders::class);

        $middleware->alias([
            'starter.admin' => StarterAdmin::class,
            'starter.authorize' => StarterAuthorize::class,
            'starter.active' => StarterEnsureActiveUser::class,
            'starter.password-change' => StarterForcePasswordChange::class,
            'starter.logs' => StarterLogAccess::class,
            'starter.lock' => StarterLockScreen::class,
            ...$additionalAliases,
        ]);

        $middleware->redirectGuestsTo(fn ($request) => StarterNavigation::authLoginUrl($request->fullUrl()));
        $middleware->redirectUsersTo(function ($request): string {
            $login = $request->user();

            return $login instanceof ClientLogin
                ? app(NavigationAuthorizedRedirectService::class)->forLogin($login, $request->query('redirect'))
                : url('/');
        });
    }

    public static function configureExceptions(Exceptions $exceptions): void
    {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            $loginUrl = StarterNavigation::authLoginUrl($request->fullUrl());

            if ($request->headers->get('X-Livewire-Navigate') === '1') {
                return response()
                    ->view('starter.templates.session-expired', ['loginUrl' => $loginUrl])
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            }

            if ($request->headers->get('X-Livewire') === '1') {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'redirect' => $loginUrl,
                ], 401);
            }

            return null;
        });
    }
}
