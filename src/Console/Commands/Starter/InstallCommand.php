<?php

namespace Altekno\StarterKit\Console\Commands\Starter;

use Altekno\StarterKit\Installation\FreshLaravelChecker;
use Altekno\StarterKit\Installation\StarterEnvironmentManager;
use Altekno\StarterKit\Installation\StarterHostConnector;
use Altekno\StarterKit\Installation\StarterHostSnapshot;
use Altekno\StarterKit\Installation\StarterInstallState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class InstallCommand extends Command
{
    protected $signature = 'starterkit:install
        {--company= : Internal company/client name}
        {--email= : Superuser notification email}
        {--username= : Superuser username}
        {--app=keuangan : First app code/subdomain}
        {--app-name= : Human-readable first app name}
        {--skip-default-app : Install without creating the first app}';

    protected $description = 'Install Starterkit Larawire on a fresh Laravel project';

    public function handle(
        FreshLaravelChecker $checker,
        StarterEnvironmentManager $environment,
        StarterHostConnector $connector,
    ): int {
        if (! $this->confirmRisk()) {
            return $this->cancelled();
        }

        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            $this->components->error('File .env tidak ditemukan. Buat dan atur .env sebelum instalasi.');

            return self::FAILURE;
        }

        try {
            $appUrl = $environment->appUrl($envPath);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->confirmAppUrl($appUrl)) {
            return $this->cancelled();
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            $this->components->error('Koneksi database gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->confirmDatabase($environment->databaseSummary())) {
            return $this->cancelled();
        }

        try {
            $report = $checker->inspectDatabase();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $report->isFresh()) {
            $this->newLine();
            $this->components->error('Project ini bukan Laravel fresh yang didukung:');

            foreach ($report->findings as $finding) {
                $this->line('  - '.$finding);
            }

            $this->newLine();
            $this->components->warn(
                'Instalasi dihentikan. Silakan buat project Laravel fresh, lalu pasang package ini kembali.',
            );
            $this->line('Tidak ada file atau database yang diubah.');

            return self::FAILURE;
        }

        if ($report->migrationsHaveRun && ! $this->confirmMigratedDatabase()) {
            return $this->cancelled();
        }

        $snapshot = null;

        try {
            $snapshot = StarterHostSnapshot::capture();
            $connector->connect();

            if (! $this->runFinalizer($environment)) {
                throw new RuntimeException('Tahap final instalasi gagal.');
            }

            StarterInstallState::write('installed');
            $snapshot->discard();
        } catch (Throwable $exception) {
            if ($snapshot instanceof StarterHostSnapshot) {
                try {
                    $snapshot->restore();
                    $snapshot->discard();
                    $this->components->warn('Perubahan file instalasi telah dikembalikan.');
                } catch (Throwable $rollbackException) {
                    $this->components->error('Rollback file gagal: '.$rollbackException->getMessage());
                }
            }

            $this->components->error($exception->getMessage());
            $this->components->warn(
                'Jika migrate:fresh sudah dimulai, isi database mungkin telah berubah. '
                .'Periksa database sebelum mencoba kembali.',
            );

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Starterkit Larawire berhasil dipasang.');
        $this->line('APP URL: '.$appUrl);
        $this->line('Jalankan php artisan starter:sync setelah setiap update package.');

        return self::SUCCESS;
    }

    private function confirmRisk(): bool
    {
        $this->newLine();
        $this->components->warn('PERINGATAN KERAS INSTALASI STARTERKIT LARAWIRE');
        $this->line('Installer ini hanya untuk project Laravel fresh. Proses instalasi akan:');
        $this->line('  - mengubah bootstrap, provider, route, view, konfigurasi, dan environment project;');
        $this->line('  - memasang autentikasi, App/subdomain, role, menu, theme, dan aturan AGENTS AI;');
        $this->line('  - menjalankan migrate:fresh dan menghapus seluruh tabel serta data database target.');
        $this->newLine();

        return $this->confirm('Saya memahami seluruh perubahan dan ingin melanjutkan?', false);
    }

    private function confirmAppUrl(string $appUrl): bool
    {
        $scheme = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME));
        $host = (string) parse_url($appUrl, PHP_URL_HOST);

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            $this->components->error('APP_URL harus berupa URL HTTP/HTTPS lengkap dengan domain yang valid.');

            return false;
        }

        $this->newLine();
        $this->components->info('APP URL');
        $this->line($appUrl);
        $this->line('Nilai ini menjadi domain utama dan dasar subdomain seluruh App.');

        return $this->confirm('Apakah APP_URL tersebut sudah benar?', false);
    }

    /** @param array<string, string|null> $summary */
    private function confirmDatabase(array $summary): bool
    {
        $this->newLine();
        $this->components->info('DATABASE CONNECTION');

        foreach ($summary as $label => $value) {
            $this->line(str_pad($label, 10).': '.($value === null || $value === '' ? '(kosong)' : $value));
        }

        $this->line('Password tidak ditampilkan. Seluruh tabel dan data database ini akan dihapus.');

        return $this->confirm('Apakah koneksi database tersebut sudah benar?', false);
    }

    private function confirmMigratedDatabase(): bool
    {
        $this->newLine();
        $this->components->warn('Migration Laravel terdeteksi sudah pernah dijalankan.');
        $this->line('Source code masih menggunakan struktur Laravel fresh yang didukung.');
        $this->line('Jika dilanjutkan, seluruh tabel dan data akan dihapus dan dibuat ulang.');

        return $this->confirm('Lanjutkan instalasi pada database ini?', false);
    }

    private function runFinalizer(StarterEnvironmentManager $environment): bool
    {
        $command = [PHP_BINARY, base_path('artisan'), 'starterkit:finalize', '--ansi'];

        foreach (['company', 'email', 'username', 'app', 'app-name'] as $option) {
            $value = $this->option($option);

            if (is_string($value) && $value !== '') {
                $command[] = "--{$option}={$value}";
            }
        }

        if ($this->option('skip-default-app')) {
            $command[] = '--skip-default-app';
        }

        $process = new Process(
            $command,
            base_path(),
            $environment->processEnvironment(base_path('.env')),
            null,
            null,
        );
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful();
    }

    private function cancelled(): int
    {
        $this->newLine();
        $this->components->warn('Instalasi dibatalkan. Tidak ada file atau database yang diubah.');

        return self::FAILURE;
    }
}
