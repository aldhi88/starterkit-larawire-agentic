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

    public function boot(): void
    {
        if (! $this->app->runningInConsole()
            || ! in_array('package:discover', $_SERVER['argv'] ?? [], true)) {
            return;
        }

        $message = PHP_EOL
            ."\033[36;1mStarterkit Larawire siap dipasang.\033[0m".PHP_EOL
            .'Atur APP_URL dan koneksi database, lalu jalankan php artisan starter:install.'.PHP_EOL
            .'Wizard akan memilih theme dan mengunduh aset terverifikasi dari GitHub secara otomatis.'.PHP_EOL;

        fwrite(STDERR, $message);
    }
}
