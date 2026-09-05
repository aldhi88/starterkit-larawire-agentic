<?php

namespace Aldhi88\StarterKit\Console\Commands\Starter;

use Aldhi88\StarterKit\Installation\StarterDatabaseProvisioner;
use Aldhi88\StarterKit\Installation\StarterDatabaseProvisioning;
use Aldhi88\StarterKit\Installation\StarterInstallState;
use Aldhi88\StarterKit\Rules\Starter\StarterPasswordRules;
use Aldhi88\StarterKit\Services\Starter\StarterAppScaffolder;
use Aldhi88\StarterKit\Services\Starter\StarterAssetPublisher;
use Aldhi88\StarterKit\Services\Starter\StarterDeploymentService;
use Aldhi88\StarterKit\Services\Starter\StarterIdentityService;
use Aldhi88\StarterKit\Services\Starter\StarterSecurityValidator;
use Aldhi88\StarterKit\Support\Starter\StarterInternalRunContext;
use Aldhi88\StarterKit\Support\Starter\StarterRouteRegistrar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Pest\TestSuite;
use Symfony\Component\Process\Process;
use Throwable;

class DeployCommand extends Command
{
    /** @var array<string, string|bool> */
    private array $installation = [];

    protected $signature = 'starter:deploy
        {--force : Skip the production deployment confirmation}
        {--installing : Internal installation stage; reads its payload from STDIN}';

    protected $description = 'Validasi dan deploy Starterkit Larawire ke production';

    public function handle(
        StarterAppScaffolder $appScaffolder,
        StarterAssetPublisher $assets,
        StarterDeploymentService $deployment,
        StarterIdentityService $identities,
        StarterSecurityValidator $security,
        StarterDatabaseProvisioner $databases,
        StarterInternalRunContext $internal,
    ): int {
        if ($this->option('installing')) {
            if (app()->isProduction() || StarterInstallState::status() !== 'installing') {
                $this->components->error('Tahap internal instalasi tidak tersedia pada kondisi project saat ini.');

                return self::FAILURE;
            }

            try {
                return $this->install($appScaffolder, $assets, $identities, $security, $internal);
            } finally {
                $this->installation['password'] = '';
            }
        }

        $restarted = $this->restartAfterClearingBootCache();

        if ($restarted !== null) {
            return $restarted;
        }

        return $this->deploy($deployment, $identities, $security, $databases, $internal);
    }

    private function restartAfterClearingBootCache(): ?int
    {
        if (! app()->configurationIsCached() && ! app()->routesAreCached()) {
            return null;
        }

        $this->components->info('Cache bootstrap lama terdeteksi dan dibersihkan sebelum preflight.');

        if ($this->call('config:clear') !== self::SUCCESS
            || $this->call('route:clear') !== self::SUCCESS) {
            $this->components->error('Cache bootstrap lama tidak dapat dibersihkan.');

            return self::FAILURE;
        }

        $command = [PHP_BINARY, base_path('artisan'), 'starter:deploy', '--ansi'];

        if ($this->option('force')) {
            $command[] = '--force';
        }

        $process = new Process($command, base_path(), null, null, null);

        if ($this->input->isInteractive()) {
            $process->setInput(STDIN);
        }

        if ($this->input->isInteractive() && Process::isTtySupported()) {
            $process->setTty(true);
        }

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }

    private function deploy(
        StarterDeploymentService $deployment,
        StarterIdentityService $identities,
        StarterSecurityValidator $security,
        StarterDatabaseProvisioner $databases,
        StarterInternalRunContext $internal,
    ): int {
        $this->newLine();
        $this->components->info('DEPLOYMENT PRODUCTION STARTERKIT LARAWIRE');
        $this->line('Command ini memvalidasi environment, koneksi database, keamanan, migration, asset, registry App, dan cache production.');
        $this->line('<fg=red;options=bold>DANGER: migration production dapat mengubah struktur database. Pastikan backup tersedia sebelum melanjutkan.</>');
        $this->newLine();
        $this->line('APP_ENV : '.(string) config('app.env'));
        $this->line('APP_URL : '.(string) config('app.url'));
        $this->line('Theme UI: '.(string) config('starter.theme').' / '.(string) config('starter.layout'));
        $this->line('Database: '.(string) config('database.default').' / '.(string) config('database.connections.'.config('database.default').'.database'));
        $this->line('Source template vendor tidak diperlukan di production; deployment memakai aset runtime dari repository aplikasi.');

        if (! $security->validate($this, production: true)) {
            $this->line('<fg=red>Deployment dihentikan sebelum file atau database diubah.</>');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Environment production valid. Lanjutkan deployment?', false)) {
            $this->components->warn('Deployment dibatalkan tanpa perubahan.');

            return self::FAILURE;
        }

        $provisioning = null;
        $credentials = null;

        try {
            $provisioning = $databases->connectOrCreate();

            if ($provisioning->created) {
                $this->line('<fg=yellow>Database production belum ada dan berhasil dibuat.</>');
            }

            $needsIdentity = ! $this->identityExists($identities);
            $credentials = $needsIdentity ? $this->promptProductionIdentity() : null;

            if ($needsIdentity && $credentials === null) {
                throw new \RuntimeException('Kredensial Superuser production tidak lengkap.');
            }

            if ($deployment->prepare($this, production: true) !== self::SUCCESS) {
                throw new \RuntimeException('Persiapan deployment gagal.');
            }

            if ($credentials !== null && ! $identities->initialized()) {
                $identities->create(
                    (string) config('app.name'),
                    $credentials['email'],
                    $credentials['password'],
                );
                $credentials['password'] = '';
                $this->components->info('Superuser production berhasil dibuat dengan username: superuser');
            }

            $syncResult = $internal->run('deploy', fn (): int => $this->call('starter:sync', [
                '--force' => true,
                '--prepared' => true,
                '--deploying' => true,
            ]));

            if ($syncResult !== self::SUCCESS) {
                throw new \RuntimeException('Sinkronisasi registry App gagal.');
            }

            if (! $security->validate($this, production: true)) {
                throw new \RuntimeException('Validasi akhir production gagal.');
            }
        } catch (Throwable $exception) {
            if ($provisioning instanceof StarterDatabaseProvisioning && $provisioning->created) {
                try {
                    $databases->rollback($provisioning);
                    $this->line('<fg=red>Database baru dihapus kembali karena deployment gagal.</>');
                } catch (Throwable $rollback) {
                    $this->components->error('Rollback database baru gagal: '.$rollback->getMessage());
                }
            }

            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if (is_array($credentials)) {
                $credentials['password'] = '';
            }
        }

        $this->newLine();
        $this->components->info('Deployment production selesai dan seluruh validasi lulus.');

        return self::SUCCESS;
    }

    private function identityExists(StarterIdentityService $identities): bool
    {
        return Schema::hasTable('starter_clients')
            && Schema::hasTable('starter_client_logins')
            && $identities->initialized();
    }

    /** @return array{email: string, password: string}|null */
    private function promptProductionIdentity(): ?array
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('Database baru memerlukan prompt interaktif untuk membuat Superuser pertama.');

            return null;
        }

        $this->newLine();
        $this->line('<fg=yellow;options=bold>DATABASE BARU: buat Superuser production pertama.</>');
        $this->line('<fg=red;options=bold>Password production wajib minimal 10 karakter dengan huruf besar, huruf kecil, dan angka.</>');
        $email = strtolower(trim((string) $this->ask('Email Superuser')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error('Email Superuser tidak valid.');

            return null;
        }

        while (true) {
            $password = (string) $this->secret('Password Superuser');
            $errors = Validator::make(['password' => $password], [
                'password' => StarterPasswordRules::rules(),
            ])->errors()->all();

            if ($errors !== []) {
                $this->components->error(implode(' ', $errors));

                continue;
            }

            if (! hash_equals($password, (string) $this->secret('Konfirmasi password Superuser'))) {
                $this->components->error('Konfirmasi password tidak sama.');

                continue;
            }

            return compact('email', 'password');
        }
    }

    private function readInstallationPayload(): bool
    {
        try {
            $payload = json_decode((string) stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $payload = null;
        }

        if (! is_array($payload)) {
            $this->components->error('Payload instalasi internal tidak valid.');

            return false;
        }

        foreach (['company', 'app', 'app_name', 'email', 'password', 'theme', 'layout'] as $key) {
            if (! array_key_exists($key, $payload)) {
                $this->components->error("Payload instalasi tidak memiliki field [{$key}].");

                return false;
            }
        }

        $this->installation = [
            'company' => trim((string) $payload['company']),
            'app' => strtolower(trim((string) $payload['app'])),
            'app_name' => trim((string) $payload['app_name']),
            'email' => strtolower(trim((string) $payload['email'])),
            'password' => (string) $payload['password'],
            'theme' => strtolower(trim((string) $payload['theme'])),
            'layout' => strtolower(trim((string) $payload['layout'])),
        ];

        return $this->installation['company'] !== ''
            && $this->installation['email'] !== ''
            && $this->installation['password'] !== '';
    }

    private function install(
        StarterAppScaffolder $appScaffolder,
        StarterAssetPublisher $assets,
        StarterIdentityService $identities,
        StarterSecurityValidator $security,
        StarterInternalRunContext $internal,
    ): int {
        if (! $this->readInstallationPayload()) {
            return self::FAILURE;
        }

        if (! $assets->publish($this)) {
            return self::FAILURE;
        }

        if ($this->installLocale() !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->installTestBootstrap() !== self::SUCCESS
            || $this->installLanding() !== self::SUCCESS
            || $this->installDefaultApp($appScaffolder) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->components->info('Memverifikasi keamanan dan menjalankan seluruh test aplikasi.');

        if (! $security->validate($this) || ! $this->applicationTestsPass()) {
            $this->components->error(
                'Verifikasi instalasi gagal. Database target belum di-reset.',
            );

            return self::FAILURE;
        }

        StarterInstallState::write('database-mutating');

        if ($this->call('migrate:fresh', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        try {
            $identities->create(
                $this->installation['company'],
                $this->installation['email'],
                $this->installation['password'],
                strongPassword: false,
            );
        } catch (ValidationException $exception) {
            $this->components->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }

        $this->installation['password'] = '';

        $syncResult = $internal->run('install', fn (): int => $this->call('starter:sync', [
            '--force' => true,
            '--prepared' => true,
        ]));

        if ($syncResult !== self::SUCCESS) {
            return self::FAILURE;
        }

        StarterInstallState::write('verifying');

        if (! $security->validate($this)) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Starterkit installation completed successfully.');

        return self::SUCCESS;
    }

    /** @phpstan-impure */
    private function applicationTestsPass(): bool
    {
        $process = new Process(
            [PHP_BINARY, base_path('artisan'), 'test', '--without-tty'],
            base_path(),
            null,
            null,
            null,
        );
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful();
    }

    private function installLocale(): int
    {
        $locale = (string) config('app.locale', 'id');

        if ($this->localeIsInstalled($locale)) {
            $this->components->info("Locale [{$locale}] is already installed.");

            return self::SUCCESS;
        }

        try {
            $this->components->task(
                "Installing locale [{$locale}]",
                fn (): bool => $this->callSilently('lang:add', ['locales' => [$locale]]) === self::SUCCESS,
            );
        } catch (Throwable $exception) {
            $this->components->error(
                "Unable to install locale [{$locale}]: {$exception->getMessage()}",
            );

            return self::FAILURE;
        }

        return $this->localeIsInstalled($locale)
            ? self::SUCCESS
            : self::FAILURE;
    }

    /** @phpstan-impure */
    private function localeIsInstalled(string $locale): bool
    {
        return File::exists(lang_path("{$locale}/validation.php"))
            && File::exists(lang_path("{$locale}.json"));
    }

    private function installLanding(): int
    {
        $routePath = base_path('routes/web.php');
        $componentPath = app_path('Livewire/Landing/LandingIndex.php');
        $viewPath = resource_path('views/landing/index.blade.php');
        $routeContents = File::exists($routePath) ? File::get($routePath) : "<?php\n";

        if (str_contains($routeContents, 'LandingIndex::class')
            || File::exists($componentPath)
            || File::exists($viewPath)) {
            $this->components->info('Landing project already exists and was not changed.');

            return self::SUCCESS;
        }

        $defaultWelcomePattern = <<<'REGEX'
~Route::get\(\s*['"]\/['"]\s*,\s*function\s*\(\)\s*\{\s*return\s+view\(\s*['"]welcome['"]\s*\)\s*;\s*\}\s*\)\s*;~
REGEX;
        $defaultViewPattern = '~Route::view\(\s*[\'"]\/[\'"]\s*,\s*[\'"]welcome[\'"]\s*\)\s*;~';

        if (preg_match($defaultWelcomePattern, $routeContents) === 1
            || preg_match($defaultViewPattern, $routeContents) === 1) {
            $routeContents = $this->landingRouteContents();
        } elseif ($this->declaresRootRoute($routeContents)) {
            $this->components->warn(
                'Existing root landing route detected. Starterkit did not overwrite it.',
            );

            return self::SUCCESS;
        } else {
            $routeContents = $this->appendLandingRoute($routeContents);
        }

        File::ensureDirectoryExists(dirname($componentPath));
        File::ensureDirectoryExists(dirname($viewPath));
        File::put($componentPath, $this->landingComponentContents());
        File::put($viewPath, $this->landingViewContents());
        File::put($routePath, $routeContents);

        $this->components->info('Minimal project landing page created.');

        return self::SUCCESS;
    }

    private function installTestBootstrap(): int
    {
        if ($this->installBaseTestCase() !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! class_exists(TestSuite::class)) {
            $this->components->info('Pest is not installed; generated App tests will use PHPUnit.');

            return self::SUCCESS;
        }

        $path = base_path('tests/Pest.php');

        if (File::exists($path)) {
            $this->components->info('Pest project bootstrap already exists.');

            return self::SUCCESS;
        }

        if (! File::exists(base_path('tests/TestCase.php'))) {
            $this->components->error(
                'tests/TestCase.php is required before creating the Pest bootstrap.',
            );

            return self::FAILURE;
        }

        File::put($path, <<<'PHP'
<?php

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');
PHP.PHP_EOL);

        $this->components->info('Pest project bootstrap created.');

        return self::SUCCESS;
    }

    private function installBaseTestCase(): int
    {
        $path = base_path('tests/TestCase.php');

        if (! File::exists($path)) {
            $this->components->error('tests/TestCase.php is required for installer verification.');

            return self::FAILURE;
        }

        $contents = File::get($path);

        if (str_contains($contents, 'use LazilyRefreshDatabase;')) {
            return self::SUCCESS;
        }

        $baseImport = 'use Illuminate\\Foundation\\Testing\\TestCase as BaseTestCase;';
        $lazyImport = 'use Illuminate\\Foundation\\Testing\\LazilyRefreshDatabase;';

        if (! str_contains($contents, $baseImport)
            || preg_match('/abstract\s+class\s+TestCase\s+extends\s+BaseTestCase\s*\{/', $contents) !== 1) {
            $this->components->error(
                'Struktur tests/TestCase.php tidak didukung untuk verifikasi otomatis.',
            );

            return self::FAILURE;
        }

        $contents = str_replace($baseImport, $lazyImport.PHP_EOL.$baseImport, $contents);
        $contents = preg_replace(
            '/abstract\s+class\s+TestCase\s+extends\s+BaseTestCase\s*\{/',
            "abstract class TestCase extends BaseTestCase\n{\n    use LazilyRefreshDatabase;",
            $contents,
            1,
        );

        if (! is_string($contents)
            || File::put($path, $contents) === false) {
            $this->components->error('tests/TestCase.php tidak dapat disiapkan.');

            return self::FAILURE;
        }

        $this->components->info('Database testing otomatis disiapkan melalui tests/TestCase.php.');

        return self::SUCCESS;
    }

    private function installDefaultApp(StarterAppScaffolder $scaffolder): int
    {
        if ((glob(config_path('apps/*.php')) ?: []) !== []) {
            $this->components->info('Project app registry already exists; the first app was not created.');

            return self::SUCCESS;
        }

        $app = $this->installation['app'];

        if (preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $app) !== 1) {
            $this->components->error(
                'First app code must contain lowercase letters, numbers, or internal hyphens only.',
            );

            return self::FAILURE;
        }

        if ($app === 'api') {
            $this->components->error('First app code [api] is reserved for the shared API gateway.');

            return self::FAILURE;
        }

        $name = $this->installation['app_name'] ?: Str::headline($app);

        if ($name === '' || mb_strlen($name) > 255) {
            $this->components->error('First app name must contain between 1 and 255 characters.');

            return self::FAILURE;
        }

        try {
            $created = $scaffolder->create(
                $app,
                $name,
                null,
                'apps',
            );
        } catch (Throwable $exception) {
            $this->components->error("Unable to create first app: {$exception->getMessage()}");

            return self::FAILURE;
        }

        StarterRouteRegistrar::register($app);

        foreach ($created as $path) {
            $this->line('Created '.str_replace(base_path().'/', '', $path));
        }

        $this->components->info(
            "First app [{$app}] created with dashboard module and Contoh Menu structure.",
        );

        return self::SUCCESS;
    }

    private function declaresRootRoute(string $contents): bool
    {
        return preg_match(
            '~Route::(?:get|post|put|patch|delete|match|any|view|livewire)\(\s*[\'"]\/[\'"]~',
            $contents,
        ) === 1;
    }

    private function appendLandingRoute(string $contents): string
    {
        $uses = [
            'use App\\Livewire\\Landing\\LandingIndex;',
            'use Illuminate\\Support\\Facades\\Route;',
        ];

        foreach (array_reverse($uses) as $use) {
            if (! str_contains($contents, $use)) {
                $contents = preg_replace(
                    '/<\?php\s*/',
                    "<?php\n\n{$use}\n",
                    $contents,
                    1,
                ) ?? $contents;
            }
        }

        return rtrim($contents).PHP_EOL.PHP_EOL
            ."Route::livewire('/', LandingIndex::class)->name('landing');".PHP_EOL;
    }

    private function landingRouteContents(): string
    {
        return <<<'PHP'
<?php

use App\Livewire\Landing\LandingIndex;
use Illuminate\Support\Facades\Route;

Route::livewire('/', LandingIndex::class)->name('landing');
PHP.PHP_EOL;
    }

    private function landingComponentContents(): string
    {
        return <<<'PHP'
<?php

namespace App\Livewire\Landing;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::landing')]
class LandingIndex extends Component
{
    public function render()
    {
        return view('landing.index')
            ->title((string) config('app.name'));
    }
}
PHP.PHP_EOL;
    }

    private function landingViewContents(): string
    {
        return <<<'BLADE'
@include('starter.templates.landing')
BLADE.PHP_EOL;
    }
}
