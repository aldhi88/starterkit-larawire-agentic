<?php

namespace Altekno\StarterKit\Support\Starter;

use Illuminate\Support\Facades\Route;

class StarterRouteRegistrar
{
    public static function registerAll(): void
    {
        foreach (StarterAppRegistry::keys() as $appKey) {
            self::register($appKey);
        }
    }

    public static function register(string $appKey): void
    {
        Route::middleware('web')
            ->domain($appKey.'.'.config('app.domain'))
            ->group(base_path("routes/apps/{$appKey}.php"));

        self::registerApi($appKey);

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    public static function registerApi(string $appKey): void
    {
        $routePath = base_path("routes/apps/{$appKey}.api.php");

        if (! config('starter.api.enabled') || ! is_file($routePath)) {
            return;
        }

        Route::middleware(['api', 'throttle:60,1'])
            ->domain(config('starter.api.domain'))
            ->prefix($appKey)
            ->name("api.{$appKey}.")
            ->group($routePath);
    }
}
