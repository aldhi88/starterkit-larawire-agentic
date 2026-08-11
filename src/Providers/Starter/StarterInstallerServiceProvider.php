<?php

namespace Altekno\StarterKit\Providers\Starter;

use Altekno\StarterKit\Console\Commands\Starter\FinalizeInstallationCommand;
use Altekno\StarterKit\Console\Commands\Starter\InstallCommand;
use Altekno\StarterKit\Installation\StarterInstallState;
use Illuminate\Support\ServiceProvider;

class StarterInstallerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $status = StarterInstallState::status();

            if ($status === 'installing') {
                $this->commands([FinalizeInstallationCommand::class]);
            } elseif ($status === null) {
                $this->commands([InstallCommand::class]);
            }
        }
    }
}
