<?php

namespace Aldhi88\StarterKit\Providers\Starter;

use Aldhi88\StarterKit\Console\Commands\Starter\InstallCommand;
use Aldhi88\StarterKit\Installation\StarterInstallState;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\ServiceProvider;

class StarterInstallerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(StarterPaths::path('config/starter.php'), 'starter');

        if ($this->app->runningInConsole()) {
            if (StarterInstallState::status() === null) {
                $this->commands([InstallCommand::class]);
            }
        }
    }
}
