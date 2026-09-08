<?php

namespace Aldhi88\StarterKit\Console\Commands\Starter;

use Aldhi88\StarterKit\Services\Starter\StarterViewOverrideService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Console\Command;
use Throwable;

class ViewsStatusCommand extends Command
{
    protected $signature = 'starter:views-status';

    protected $description = 'Tampilkan status view override terhadap versi package aktif';

    public function handle(StarterViewOverrideService $views): int
    {
        try {
            if (! $views->hasOverrides()) {
                $this->components->info('Belum ada view override untuk theme '.StarterTheme::key().'.');
                $this->line('Jalankan php artisan starter:views-publish untuk mulai melakukan custom view.');

                return self::SUCCESS;
            }

            $statuses = array_filter(
                $views->statuses(),
                fn (array $item): bool => $item['status'] !== 'package-only',
            );
            $this->table(['View', 'Status', 'Project SHA', 'Package SHA'], array_map(
                fn (array $item): array => [
                    $item['path'],
                    $item['status'],
                    $this->shortHash($item['project_sha256']),
                    $this->shortHash($item['package_sha256']),
                ],
                array_values($statuses),
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function shortHash(?string $hash): string
    {
        return $hash === null ? '-' : substr($hash, 0, 12);
    }
}
