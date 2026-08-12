<?php

namespace Aldhi88\StarterKit\Console\Commands\Starter;

use Aldhi88\StarterKit\Services\Starter\StarterAppScaffolder;
use Aldhi88\StarterKit\Services\Starter\StarterDeploymentService;
use Aldhi88\StarterKit\Support\Starter\StarterInternalRunContext;
use Aldhi88\StarterKit\Support\Starter\StarterRouteRegistrar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class AppCommand extends Command
{
    protected $signature = 'starter:app';

    protected $description = 'Buat App dan subdomain baru melalui wizard';

    public function handle(
        StarterAppScaffolder $scaffolder,
        StarterDeploymentService $deployment,
        StarterInternalRunContext $internal,
    ): int {
        if (app()->isProduction()) {
            $this->components->error('starter:app hanya boleh dijalankan di lingkungan development.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('BUAT APP BARU');
        $this->line('Nama App boleh memakai spasi. Contoh: Human Resources');
        $this->line('Subdomain hanya nama pendek tanpa titik/spasi. Contoh: hr');

        $name = $this->askRequired('Nama App');
        $subdomain = $this->askSubdomain();
        $url = $this->appUrl($subdomain);

        $this->newLine();
        $this->line('Nama App   : '.$name);
        $this->line('Subdomain  : '.$subdomain);
        $this->line('Alamat App : '.$url);

        if (! $this->confirm('Buat App tersebut?', true)) {
            $this->components->warn('Pembuatan App dibatalkan.');

            return self::FAILURE;
        }

        $created = [];

        try {
            if ($deployment->prepare($this) !== self::SUCCESS) {
                throw new \RuntimeException('Persiapan App gagal.');
            }

            DB::beginTransaction();
            $created = $scaffolder->create($subdomain, $name);
            StarterRouteRegistrar::register($subdomain);

            $syncResult = $internal->run('app', fn (): int => $this->call('starter:sync', [
                'subdomain' => $subdomain,
                '--force' => true,
                '--prepared' => true,
            ]));

            if ($syncResult !== self::SUCCESS) {
                throw new \RuntimeException('Sinkronisasi metadata App gagal.');
            }

            DB::commit();
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            File::delete($created);
            $this->removeEmptyParents($created);
            $this->components->error($exception->getMessage());
            $this->line('<fg=red>Semua file App yang baru dibuat telah di-rollback.</>');

            return self::FAILURE;
        }

        $this->components->info("App [{$name}] siap di {$url}");

        return self::SUCCESS;
    }

    private function askRequired(string $label): string
    {
        do {
            $value = trim((string) $this->ask($label.' (contoh: Human Resources)'));

            if ($value === '' || mb_strlen($value) > 255) {
                $this->components->error("{$label} wajib diisi dan maksimal 255 karakter.");
            }
        } while ($value === '' || mb_strlen($value) > 255);

        return $value;
    }

    private function askSubdomain(): string
    {
        while (true) {
            $value = strtolower(trim((string) $this->ask('Subdomain (contoh: hr)')));

            if ($value === 'api') {
                $this->components->error('Subdomain [api] digunakan oleh API gateway.');
            } elseif (preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $value) === 1) {
                return $value;
            } else {
                $this->components->error('Gunakan huruf kecil, angka, atau tanda hubung di tengah; tanpa titik dan spasi.');
            }
        }
    }

    private function appUrl(string $subdomain): string
    {
        $base = (string) config('app.url');
        $scheme = (string) parse_url($base, PHP_URL_SCHEME);
        $host = (string) parse_url($base, PHP_URL_HOST);
        $port = parse_url($base, PHP_URL_PORT);

        return $scheme.'://'.$subdomain.'.'.$host.(is_int($port) ? ':'.$port : '');
    }

    /** @param list<string> $created */
    private function removeEmptyParents(array $created): void
    {
        foreach (array_unique(array_map('dirname', $created)) as $directory) {
            while (str_starts_with($directory, base_path())
                && $directory !== base_path()
                && File::isDirectory($directory)
                && File::files($directory) === []
                && File::directories($directory) === []) {
                File::deleteDirectory($directory);
                $directory = dirname($directory);
            }
        }
    }
}
