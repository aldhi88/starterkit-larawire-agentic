<?php

namespace Aldhi88\StarterKit\Console\Commands\Starter;

use Aldhi88\StarterKit\Services\Starter\StarterViewOverrideService;
use Illuminate\Console\Command;
use Throwable;

class ViewsPublishCommand extends Command
{
    protected $signature = 'starter:views-publish
        {view?* : Relative view paths to publish}
        {--all : Publish every starter view for the active theme}
        {--force : Replace existing custom files after creating a backup}';

    protected $description = 'Publish view theme aktif ke area override project';

    public function handle(StarterViewOverrideService $views): int
    {
        if (app()->isProduction()) {
            $this->components->error('starter:views-publish hanya boleh dijalankan di local development.');

            return self::FAILURE;
        }

        try {
            /** @var list<string> $requested */
            $requested = $this->argument('view');

            if ($requested === [] && ! $this->option('all')) {
                if (! $this->input->isInteractive()) {
                    $this->components->error('Mode non-interaktif memerlukan path view atau opsi --all.');

                    return self::FAILURE;
                }

                $requested = $this->choice(
                    'Pilih view yang akan dijadikan milik project',
                    $views->sourcePaths(),
                    null,
                    null,
                    true,
                );

                if ($requested === []) {
                    $this->components->warn('Tidak ada view yang dipilih.');

                    return self::SUCCESS;
                }
            }

            $result = $views->publish($requested, (bool) $this->option('force'));

            $this->components->info("View override siap: {$result['created']} dibuat, {$result['replaced']} diganti, {$result['preserved']} custom dipertahankan.");

            if ($result['backup'] !== null) {
                $this->line('Backup: '.$result['backup']);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
