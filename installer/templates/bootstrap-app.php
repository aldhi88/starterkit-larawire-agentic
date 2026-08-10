<?php

use Altekno\StarterKit\Support\Starter\StarterBootstrap;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function (): void {
            StarterBootstrap::registerRoutes();

            Route::middleware('web')
                ->domain(config('app.domain'))
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        StarterBootstrap::configureMiddleware($middleware);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        StarterBootstrap::configureExceptions($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
