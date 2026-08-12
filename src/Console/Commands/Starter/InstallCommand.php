<?php

namespace Aldhi88\StarterKit\Console\Commands\Starter;

use Aldhi88\StarterKit\Installation\FreshLaravelChecker;
use Aldhi88\StarterKit\Installation\StarterDatabaseProvisioner;
use Aldhi88\StarterKit\Installation\StarterDatabaseProvisioning;
use Aldhi88\StarterKit\Installation\StarterEnvironmentManager;
use Aldhi88\StarterKit\Installation\StarterHostConnector;
use Aldhi88\StarterKit\Installation\StarterHostSnapshot;
use Aldhi88\StarterKit\Installation\StarterInstallState;
use Aldhi88\StarterKit\Rules\Starter\StarterPasswordRules;
use Aldhi88\StarterKit\Support\Starter\StarterInternalRunContext;
use Aldhi88\StarterKit\Support\Starter\StarterThemeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class InstallCommand extends Command
{
    /** @var array<string, string|bool> */
    private array $installation = [];

    protected $signature = 'starter:install
        {--reset : Internal mode used only by starter:reset}';

    protected $description = 'Pasang Starterkit Larawire pada project Laravel fresh';

    public function handle(
        FreshLaravelChecker $checker,
        StarterEnvironmentManager $environment,
        StarterHostConnector $connector,
        StarterDatabaseProvisioner $databaseProvisioner,
        StarterInternalRunContext $internal,
    ): int {
        if ((bool) $this->option('reset') && ! $internal->allows('reset')) {
            $this->components->error('Mode reset internal tidak dapat dipanggil langsung. Gunakan starter:reset.');

            return self::FAILURE;
        }

        try {
            return $this->executeInstallation($checker, $environment, $connector, $databaseProvisioner);
        } finally {
            $this->installation['password'] = '';
        }
    }

    private function executeInstallation(
        FreshLaravelChecker $checker,
        StarterEnvironmentManager $environment,
        StarterHostConnector $connector,
        StarterDatabaseProvisioner $databaseProvisioner,
    ): int {
        $reset = (bool) $this->option('reset');

        if (app()->isProduction()) {
            $this->line('<fg=red;options=bold>INSTALASI DITOLAK DI PRODUCTION.</>');
            $this->line('Jalankan starter:install di local, commit project Laravel, lalu gunakan starter:deploy di production.');

            return self::FAILURE;
        }

        if (! $this->canRun($reset)) {
            return self::FAILURE;
        }

        if (! $this->confirmRisk($reset)) {
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

        if (! $this->resolvePresentation()) {
            return self::FAILURE;
        }

        if (! $this->resolveInstallationIdentity($appUrl)) {
            return self::FAILURE;
        }

        if (! $this->confirmDatabase($environment->databaseSummary())) {
            return $this->cancelled();
        }

        if (! $reset) {
            try {
                $sourceFindings = $checker->sourceFindings();
            } catch (Throwable $exception) {
                $this->components->error('Source Laravel tidak dapat diperiksa: '.$exception->getMessage());

                return self::FAILURE;
            }

            if ($sourceFindings !== []) {
                return $this->rejectNonFreshProject($sourceFindings);
            }
        }

        try {
            $databaseProvisioning = $databaseProvisioner->connectOrCreate();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($databaseProvisioning->created) {
            $this->components->info(
                "Database {$databaseProvisioning->database} belum tersedia dan berhasil dibuat otomatis.",
            );
        }

        $migrationsHaveRun = false;

        if (! $reset) {
            try {
                $report = $checker->inspectDatabase();
            } catch (Throwable $exception) {
                $this->rollbackCreatedDatabase($databaseProvisioner, $databaseProvisioning);
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            }

            if (! $report->isFresh()) {
                $this->rollbackCreatedDatabase($databaseProvisioner, $databaseProvisioning);

                return $this->rejectNonFreshProject($report->findings);
            }

            $migrationsHaveRun = $report->migrationsHaveRun;

            if ($migrationsHaveRun && ! $this->confirmMigratedDatabase()) {
                $this->rollbackCreatedDatabase($databaseProvisioner, $databaseProvisioning);

                return $this->cancelled();
            }
        }

        $snapshot = null;
        $databaseMutationStarted = false;

        try {
            $snapshot = StarterHostSnapshot::capture();

            if ($reset) {
                $this->clearInstalledApplication();
            }

            $company = (string) $this->installation['company'];
            $environment->setApplicationName(base_path('.env'), $company);
            $environment->setApplicationName(base_path('.env.example'), $company);
            $connector->connect(
                (string) $this->installation['theme'],
                (string) $this->installation['layout'],
            );

            if (! $this->runFinalizer($environment)) {
                $databaseMutationStarted = StarterInstallState::databaseMutationStarted();

                throw new RuntimeException('Tahap final instalasi gagal.');
            }

            StarterInstallState::write('installed');
            $snapshot->discard();
        } catch (Throwable $exception) {
            $filesRestored = false;

            if ($snapshot instanceof StarterHostSnapshot) {
                try {
                    $snapshot->restore();
                    $snapshot->discard();
                    $filesRestored = true;
                    $this->components->warn('Perubahan file instalasi telah dikembalikan.');
                } catch (Throwable $rollbackException) {
                    $this->components->error('Rollback file gagal: '.$rollbackException->getMessage());
                }
            }

            $this->components->error($exception->getMessage());

            if ($databaseProvisioning->created) {
                $this->rollbackCreatedDatabase($databaseProvisioner, $databaseProvisioning);
            } elseif ($reset && $databaseMutationStarted) {
                $this->components->error(
                    'Reset database telah dimulai dan data lama tidak dapat dipulihkan otomatis. '.
                    'Perbaiki error yang tampil, lalu jalankan starter:reset kembali.',
                );
            } elseif ($databaseMutationStarted && $filesRestored) {
                $this->restoreFreshDatabaseState($migrationsHaveRun);
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info($reset
            ? 'Starterkit Larawire berhasil di-reset dan dipasang ulang.'
            : 'Starterkit Larawire berhasil dipasang.');
        $this->line('APP URL: '.$appUrl);
        $this->line('Jalankan php artisan starter:sync setelah setiap update package.');

        return self::SUCCESS;
    }

    private function canRun(bool $reset): bool
    {
        $status = StarterInstallState::status();

        if ($reset && $status !== 'installed') {
            $this->components->error('starter:reset hanya dapat dijalankan setelah starterkit berhasil terpasang.');

            return false;
        }

        if (! $reset && $status !== null) {
            $this->components->error(
                'Starterkit sudah terpasang. Gunakan php artisan starter:reset untuk menginstal ulang.',
            );

            return false;
        }

        return true;
    }

    private function confirmRisk(bool $reset): bool
    {
        $this->newLine();

        if ($reset) {
            $this->line('<fg=red;options=bold>PERINGATAN KERAS RESET STARTERKIT LARAWIRE</>');
            $this->line('Reset akan menghapus seluruh instalasi aplikasi sebelumnya, termasuk:');
            $this->line('  - seluruh tabel dan data pada database target;');
            $this->line('  - seluruh source App pada config, route, PHP, view, migration, asset, bahasa, dan test;');
            $this->line('  - seluruh logo dan foto profil yang di-upload melalui starterkit; serta');
            $this->line('  - role, user, pengaturan, menu, dan log aktivitas yang tersimpan.');
            $this->line('Project kemudian dipasang ulang menggunakan wizard instalasi baru.');
        } else {
            $this->line('<fg=red;options=bold>PERINGATAN KERAS INSTALASI STARTERKIT LARAWIRE</>');
            $this->line('Installer ini hanya untuk project Laravel fresh. Proses instalasi akan:');
            $this->line('  - mengubah bootstrap, provider, route, view, konfigurasi, dan environment project;');
            $this->line('  - memasang autentikasi, App/subdomain, role, menu, theme, dan aturan AGENTS AI;');
            $this->line('  - menjalankan migrate:fresh dan menghapus seluruh tabel serta data database target.');
        }

        $this->newLine();

        $confirmed = $this->confirm(
            $reset
                ? 'Saya memahami seluruh data dan source App lama akan dihapus. Lanjutkan reset?'
                : 'Saya memahami seluruh perubahan dan ingin melanjutkan?',
            false,
        );

        if (! $confirmed || ! $reset) {
            return $confirmed;
        }

        return strtoupper(trim((string) $this->ask(
            'Ketik RESET untuk mengonfirmasi penghapusan permanen',
        ))) === 'RESET';
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

    private function resolveInstallationIdentity(string $appUrl): bool
    {
        $this->newLine();
        $this->components->info('WIZARD IDENTITAS APLIKASI');
        $this->line('Nama perusahaan/client boleh memakai spasi. Contoh: PT Maju Bersama');
        $this->line('Nama App boleh memakai spasi. Contoh: Human Resources');
        $this->line('Domain App adalah subdomain pendek tanpa spasi dan tanpa domain utama. Contoh: hr');

        $company = $this->resolveName(
            'Nama perusahaan/client (contoh: PT Maju Bersama)',
            'Nama perusahaan/client',
        );

        if ($company === null) {
            return false;
        }

        $this->installation['company'] = $company;

        $email = $this->resolveSuperuserEmail();
        $password = $this->resolveSuperuserPassword();

        if ($email === null || $password === null) {
            return false;
        }

        $this->installation['email'] = $email;
        $this->installation['password'] = $password;

        $appName = $this->resolveName(
            'Nama App pertama (contoh: Human Resources)',
            'Nama App',
        );

        if ($appName === null) {
            return false;
        }

        $app = $this->resolveAppDomain();

        if ($app === null) {
            return false;
        }

        $this->installation['app'] = $app;
        $this->installation['app_name'] = $appName;
        $this->showIdentitySummary($appUrl);

        return true;
    }

    private function resolvePresentation(): bool
    {
        $themes = StarterThemeRegistry::all();

        if ($themes === []) {
            $this->components->error('Tidak ada theme Starterkit yang terdaftar.');

            return false;
        }

        $labels = [];

        foreach ($themes as $key => $definition) {
            $labels[$key] = is_string($definition['label'] ?? null)
                ? $definition['label']
                : ucfirst((string) $key);
        }

        if (count($labels) === 1) {
            $theme = (string) array_key_first($labels);
            $this->line('Theme UI: '.$labels[$theme].' (satu-satunya theme yang terpasang)');
        } else {
            $selectedLabel = (string) $this->choice('Pilih theme UI', array_values($labels));
            $theme = (string) array_search($selectedLabel, $labels, true);
        }

        $layouts = array_keys((array) ($themes[$theme]['layouts'] ?? []));

        if ($layouts === []) {
            $this->components->error("Theme [{$theme}] tidak memiliki layout terdaftar.");

            return false;
        }

        $layout = count($layouts) === 1
            ? (string) $layouts[0]
            : (string) $this->choice('Pilih layout navigasi', $layouts, 'vertical');

        $this->installation['theme'] = $theme;
        $this->installation['layout'] = $layout;

        return true;
    }

    private function resolveSuperuserEmail(): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('Email Superuser wajib diisi melalui wizard interaktif.');

            return null;
        }

        while (true) {
            $email = strtolower(trim((string) $this->ask(
                'Email Superuser (contoh: admin@company.test)',
            )));

            if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 255) {
                return $email;
            }

            $this->components->error('Email Superuser tidak valid.');
        }
    }

    private function resolveSuperuserPassword(): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('Password Superuser wajib dimasukkan melalui prompt rahasia interaktif.');

            return null;
        }

        $this->line('Untuk development local, password boleh sederhana tetapi tetap wajib diisi dan dikonfirmasi.');

        while (true) {
            $password = (string) $this->secret('Password Superuser');
            $errors = Validator::make(['password' => $password], [
                'password' => StarterPasswordRules::localBootstrapRules(),
            ])->errors()->all();

            if ($errors !== []) {
                $this->components->error(implode(' ', $errors));

                continue;
            }

            if (! hash_equals($password, (string) $this->secret('Konfirmasi password Superuser'))) {
                $this->components->error('Konfirmasi password tidak sama.');

                continue;
            }

            return $password;
        }
    }

    private function resolveName(string $question, string $label): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->components->error("{$label} wajib diisi melalui wizard interaktif.");

            return null;
        }

        while (true) {
            $value = trim((string) $this->ask($question));

            if ($value !== '' && mb_strlen($value) <= 255) {
                return $value;
            }

            $this->components->error("{$label} wajib diisi dan maksimal 255 karakter.");
        }
    }

    private function resolveAppDomain(): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('Domain App wajib diisi melalui wizard interaktif.');

            return null;
        }

        while (true) {
            $app = strtolower(trim((string) $this->ask(
                'Domain/subdomain App tanpa spasi (contoh: hr)',
            )));

            if ($app === 'api') {
                $this->components->error('Domain [api] digunakan oleh API gateway. Pilih nama lain.');
            } elseif (preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $app) === 1) {
                return $app;
            } else {
                $this->components->error(
                    'Domain hanya boleh berisi huruf kecil, angka, atau tanda hubung di tengah; tanpa spasi dan titik.',
                );
            }

        }
    }

    private function showIdentitySummary(string $appUrl): void
    {
        $this->newLine();
        $this->components->info('RINGKASAN WIZARD');
        $this->line('Perusahaan/client : '.$this->installation['company']);
        $this->line('Username Superuser: superuser');
        $this->line('Email Superuser   : '.$this->installation['email']);
        $this->line('Theme / layout    : '.$this->installation['theme'].' / '.$this->installation['layout']);

        $host = (string) parse_url($appUrl, PHP_URL_HOST);
        $scheme = (string) parse_url($appUrl, PHP_URL_SCHEME);
        $port = parse_url($appUrl, PHP_URL_PORT);
        $appUrl = $scheme.'://'.$this->installation['app'].'.'.$host
            .(is_int($port) ? ':'.$port : '');

        $this->line('Nama App           : '.$this->installation['app_name']);
        $this->line('Domain App         : '.$this->installation['app']);
        $this->line('Alamat App         : '.$appUrl);
    }

    /** @param array<string, string|null> $summary */
    private function confirmDatabase(array $summary): bool
    {
        $this->newLine();
        $this->components->info('DATABASE CONNECTION');

        foreach ($summary as $label => $value) {
            $this->line(str_pad($label, 10).': '.($value === null || $value === '' ? '(kosong)' : $value));
        }

        $this->line('<fg=red;options=bold>DANGER: seluruh tabel dan data database ini akan dihapus.</>');
        $this->line('Password koneksi tidak ditampilkan.');
        $this->line('Jika database belum ada, installer akan mencoba membuatnya otomatis setelah konfirmasi.');

        return $this->confirm('Apakah koneksi database tersebut sudah benar?', false);
    }

    /** @param list<string> $findings */
    private function rejectNonFreshProject(array $findings): int
    {
        $this->newLine();
        $this->components->error('Project ini bukan Laravel fresh yang didukung:');

        foreach ($findings as $finding) {
            $this->line('  - '.$finding);
        }

        $this->newLine();
        $this->components->warn(
            'Instalasi dihentikan. Silakan buat project Laravel fresh, lalu pasang package ini kembali.',
        );
        $this->line('Tidak ada file atau database yang diubah.');

        return self::FAILURE;
    }

    private function rollbackCreatedDatabase(
        StarterDatabaseProvisioner $provisioner,
        StarterDatabaseProvisioning $provisioning,
    ): void {
        if (! $provisioning->created) {
            return;
        }

        try {
            $provisioner->rollback($provisioning);
            $this->components->warn('Database yang dibuat installer telah dihapus kembali.');
        } catch (Throwable $exception) {
            $this->components->error('Rollback database baru gagal: '.$exception->getMessage());
        }
    }

    private function confirmMigratedDatabase(): bool
    {
        $this->newLine();
        $this->line('<fg=red;options=bold>DANGER: migration Laravel terdeteksi sudah pernah dijalankan.</>');
        $this->line('Source code masih menggunakan struktur Laravel fresh yang didukung.');
        $this->line('Jika dilanjutkan, seluruh tabel dan data akan dihapus dan dibuat ulang.');

        return $this->confirm('Lanjutkan instalasi pada database ini?', false);
    }

    private function runFinalizer(StarterEnvironmentManager $environment): bool
    {
        $command = [PHP_BINARY, base_path('artisan'), 'starter:deploy', '--installing', '--ansi'];
        $payload = $this->installation;

        $process = new Process(
            $command,
            base_path(),
            $environment->processEnvironment(base_path('.env')),
            null,
            null,
        );
        $process->setInput(json_encode($payload, JSON_THROW_ON_ERROR));
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        $payload['password'] = '';

        return $process->isSuccessful();
    }

    private function clearInstalledApplication(): void
    {
        $directories = [
            config_path('apps'),
            base_path('routes/apps'),
            app_path('Apps'),
            resource_path('views/apps'),
            database_path('migrations/apps'),
            base_path('tests/Feature/Apps'),
            public_path('assets/apps'),
            storage_path('app/public/starter'),
            ...(glob(app_path('*/Apps')) ?: []),
            ...(glob(app_path('*/*/Apps')) ?: []),
            ...(glob(lang_path('*/apps')) ?: []),
        ];

        foreach (array_unique($directories) as $directory) {
            if (File::isDirectory($directory)) {
                File::deleteDirectory($directory);
            }
        }

        $this->components->info('Source App dan upload starterkit sebelumnya telah dibersihkan.');
    }

    private function restoreFreshDatabaseState(bool $migrationsHadRun): void
    {
        $artisanCommand = $migrationsHadRun ? 'migrate:fresh' : 'db:wipe';
        $process = new Process(
            [PHP_BINARY, base_path('artisan'), $artisanCommand, '--force', '--ansi'],
            base_path(),
            null,
            null,
            null,
        );
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if ($process->isSuccessful()) {
            $message = $migrationsHadRun
                ? 'Struktur database bawaan Laravel telah dibuat ulang setelah rollback.'
                : 'Database telah dikembalikan ke kondisi kosong setelah rollback.';
            $this->components->warn($message);

            return;
        }

        $this->components->error(
            'Rollback struktur database gagal. Periksa database sebelum mencoba kembali.',
        );
    }

    private function cancelled(): int
    {
        $this->newLine();
        $this->components->warn('Instalasi dibatalkan. Tidak ada file atau database yang diubah.');

        return self::FAILURE;
    }
}
