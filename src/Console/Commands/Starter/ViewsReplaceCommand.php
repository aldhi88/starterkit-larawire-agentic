<?php

namespace Aldhi88\StarterKit\Console\Commands\Starter;

use Aldhi88\StarterKit\Services\Starter\StarterViewOverrideService;
use Illuminate\Console\Command;
use Throwable;

class ViewsReplaceCommand extends Command
{
    protected $signature = 'starter:views-replace
        {view?* : Relative view paths to replace with package defaults}
        {--dry-run : Show replacements without writing files}
        {--force : Skip interactive selection and confirmation}';

    protected $description = 'Ganti view override project dengan default package aktif';

    public function handle(StarterViewOverrideService $views): int
    {
        if (app()->isProduction() && ! $this->option('dry-run')) {
            $this->components->error('starter:views-replace hanya boleh menulis file di local development.');

            return self::FAILURE;
        }

        try {
            if (! $views->hasOverrides()) {
                $this->components->info('Belum ada view override. Tidak ada file yang diganti.');

                return self::SUCCESS;
            }

            /** @var list<string> $requested */
            $requested = $this->argument('view');

            if (! $this->option('dry-run') && ! $this->option('force') && ! $this->input->isInteractive()) {
                $this->components->error('Mode non-interaktif memerlukan opsi --force.');

                return self::FAILURE;
            }

            if ($requested === [] && ! $this->option('dry-run') && ! $this->option('force')) {
                $scope = $this->choice('View override mana yang akan dikembalikan ke default package?', [
                    'all' => 'Semua view override',
                    'select' => 'Pilih per file',
                    'cancel' => 'Batal',
                ], 'cancel');

                if ($scope === 'cancel') {
                    $this->components->warn('Penggantian dibatalkan.');

                    return self::SUCCESS;
                }

                if ($scope === 'select') {
                    $requested = $this->choice(
                        'Pilih view yang akan diganti',
                        $views->availablePaths(),
                        null,
                        null,
                        true,
                    );
                }
            }

            $plan = $views->replacementPlan($requested);

            if ($plan === []) {
                $this->components->info('Semua view override yang dipilih sudah sama dengan package aktif.');

                return self::SUCCESS;
            }

            $this->table(['View', 'Aksi'], array_map(
                fn (string $path, string $action): array => [$path, $action],
                array_keys($plan),
                array_values($plan),
            ));

            if ($this->option('dry-run')) {
                $this->components->info('Dry-run selesai. Tidak ada file yang diubah.');

                return self::SUCCESS;
            }

            if (! $this->option('force') && ! $this->confirm('Backup lalu ganti view yang tercantum?', false)) {
                $this->components->warn('Penggantian dibatalkan.');

                return self::SUCCESS;
            }

            $result = $views->replace($requested);
            $this->components->info("View override diperbarui: {$result['created']} dibuat, {$result['replaced']} diganti, {$result['deleted']} dihapus.");

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
