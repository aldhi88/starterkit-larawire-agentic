<?php

namespace Aldhi88\StarterKit\Providers\Starter;

use Aldhi88\StarterKit\Console\Commands\Starter\InstallCommand;
use Aldhi88\StarterKit\Installation\StarterInstallState;
use Aldhi88\StarterKit\Services\Starter\StarterAssetPublisher;
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

    public function boot(): void
    {
        if (! $this->app->runningInConsole()
            || ! in_array('package:discover', $_SERVER['argv'] ?? [], true)) {
            return;
        }

        $message = PHP_EOL
            ."\033[33;1mStarterkit Larawire: source template belum ikut package Composer.\033[0m".PHP_EOL
            .'Download: '.StarterAssetPublisher::TEMPLATE_SOURCE_URL.PHP_EOL
            .'Salin theme yang akan dipakai ke theme-intake/<theme>/ sebelum menjalankan starter:install.'.PHP_EOL
            .'DashCode hanya boleh digunakan oleh pemilik lisensi vendor yang sah.'.PHP_EOL;

        fwrite(STDERR, $message);
    }
}
