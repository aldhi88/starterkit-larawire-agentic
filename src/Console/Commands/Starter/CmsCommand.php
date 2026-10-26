<?php

namespace Aldhi88\StarterKit\Console\Commands\Starter;

use Aldhi88\StarterKit\Installation\StarterInstallState;
use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Starter\StarterCmsInstaller;
use Aldhi88\StarterKit\Services\Starter\StarterDeploymentService;
use Aldhi88\StarterKit\Support\Starter\StarterInternalRunContext;
use Aldhi88\StarterKit\Support\Starter\StarterRouteRegistrar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class CmsCommand extends Command
{
    protected $signature = 'starter:cms
        {--dry-run : Show the optional CMS source plan without changing files or data}
        {--force : Skip the CMS activation confirmation}';

    protected $description = 'Tambahkan App CMS opsional tanpa menghapus App atau data yang sudah ada';

    public function handle(
        StarterCmsInstaller $installer,
        CmsManager $cms,
        StarterDeploymentService $deployment,
        StarterInternalRunContext $internal,
    ): int {
        if (app()->isProduction()) {
            $this->components->error('Tambahkan CMS di local, lalu gunakan starter:deploy di production.');

            return self::FAILURE;
        }

        if (StarterInstallState::status() !== 'installed') {
            $this->components->error('Pasang starterkit terlebih dahulu melalui starter:install.');

            return self::FAILURE;
        }

        $created = [];

        try {
            $plan = $installer->plan();
            $this->table(['Source CMS', 'Tindakan'], array_map(
                fn (string $path): array => [str_replace(base_path().'/', '', $path), is_file($path) ? 'Pertahankan' : 'Buat'],
                array_keys($plan),
            ));

            if ($this->option('dry-run')) {
                return self::SUCCESS;
            }

            if (! $this->option('force') && ! $this->confirm('Tambahkan App CMS dan jalankan migration tambahannya?', false)) {
                return self::FAILURE;
            }

            $created = $installer->prepare();
            StarterRouteRegistrar::register('cms');

            if ($deployment->prepare($this) !== self::SUCCESS) {
                throw new RuntimeException('Persiapan atau migration CMS gagal.');
            }

            if ($this->call('starter:sync', ['subdomain' => 'cms', '--dry-run' => true]) !== self::SUCCESS) {
                throw new RuntimeException('Rencana sinkronisasi CMS tidak valid.');
            }

            DB::transaction(function () use ($cms, $internal): void {
                $cms->installDefaults();

                $result = $internal->run('app', fn (): int => $this->call('starter:sync', [
                    'subdomain' => 'cms', '--force' => true, '--prepared' => true,
                ]));

                if ($result !== self::SUCCESS) {
                    throw new RuntimeException('Sinkronisasi CMS gagal.');
                }
            });
        } catch (Throwable $exception) {
            File::delete($created);
            $this->components->error($exception->getMessage());
            $this->line('Source CMS yang baru dibuat telah dikembalikan. Data aplikasi tidak di-reset; migration tambahan yang sudah berjalan dipertahankan agar aman untuk dicoba kembali.');

            return self::FAILURE;
        }

        $this->components->info('App CMS siap. Buka daftar aplikasi untuk mengakses CMS.');

        return self::SUCCESS;
    }
}
