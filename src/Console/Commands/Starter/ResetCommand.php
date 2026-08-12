<?php

namespace Aldhi88\StarterKit\Console\Commands\Starter;

use Aldhi88\StarterKit\Installation\StarterInstallState;
use Aldhi88\StarterKit\Support\Starter\StarterInternalRunContext;
use Illuminate\Console\Application as ArtisanApplication;
use Illuminate\Console\Command;

class ResetCommand extends Command
{
    protected $signature = 'starter:reset';

    protected $description = 'Hapus instalasi lama dan jalankan kembali wizard instalasi';

    public function handle(StarterInternalRunContext $internal): int
    {
        if (app()->isProduction()) {
            $this->line('<fg=red;options=bold>RESET DITOLAK DI PRODUCTION.</>');
            $this->line('starter:reset hanya untuk development karena menghapus source App dan seluruh database.');

            return self::FAILURE;
        }

        if (StarterInstallState::status() !== 'installed') {
            $this->components->error('Starterkit belum selesai terpasang dan tidak dapat di-reset.');

            return self::FAILURE;
        }

        $application = $this->getApplication();

        if (! $application instanceof ArtisanApplication) {
            $this->components->error('Laravel Console Application tidak tersedia.');

            return self::FAILURE;
        }

        $application->resolve(InstallCommand::class);

        return $internal->run(
            'reset',
            fn (): int => $this->call('starter:install', ['--reset' => true]),
        );
    }
}
