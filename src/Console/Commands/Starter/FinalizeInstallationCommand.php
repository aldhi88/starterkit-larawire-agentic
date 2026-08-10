<?php

namespace Altekno\StarterKit\Console\Commands\Starter;

use Altekno\StarterKit\Services\Starter\StarterAppScaffolder;
use Altekno\StarterKit\Services\Starter\StarterAssetPublisher;
use Altekno\StarterKit\Support\Starter\StarterRouteRegistrar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pest\TestSuite;
use Throwable;

class FinalizeInstallationCommand extends Command
{
    protected $signature = 'starterkit:finalize
        {--company= : Internal company/client name}
        {--email= : Superuser notification email}
        {--username= : Superuser username}
        {--app=keuangan : First app code/subdomain}
        {--app-name= : Human-readable first app name}
        {--skip-default-app : Install without creating the first app}';

    protected $description = 'Internal second stage of the Starterkit Larawire installer';

    protected $hidden = true;

    public function handle(
        StarterAppScaffolder $appScaffolder,
        StarterAssetPublisher $assets,
    ): int {
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

        if ($this->call('migrate:fresh', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $setupOptions = array_filter([
            '--company' => $this->option('company'),
            '--email' => $this->option('email'),
            '--username' => $this->option('username'),
        ], fn (mixed $value): bool => is_string($value) && $value !== '');

        if ($this->call('starter:setup', $setupOptions) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('starter:security-check') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Starterkit installation completed successfully.');

        return self::SUCCESS;
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

    private function installDefaultApp(StarterAppScaffolder $scaffolder): int
    {
        if ((glob(config_path('apps/*.php')) ?: []) !== []) {
            $this->components->info('Project app registry already exists; the first app was not created.');

            return self::SUCCESS;
        }

        if ($this->option('skip-default-app')) {
            $this->components->info(
                'First app skipped. The default landing page contains an app setup guide.',
            );

            return self::SUCCESS;
        }

        $app = strtolower(trim((string) $this->option('app')));

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

        $name = trim((string) ($this->option('app-name') ?: Str::headline($app)));

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
@php($hasStarterApps = \Altekno\StarterKit\Support\Starter\StarterAppRegistry::keys() !== [])

<main class="page min-vh-100 bg-light">
    <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl">
            <div class="navbar-brand navbar-brand-autodark">
                {{ config('app.name') }}
            </div>

            <div class="navbar-nav flex-row order-md-last">
                <a href="{{ \Altekno\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}" class="btn btn-primary">
                    Login
                </a>
            </div>
        </div>
    </header>

    @if ($hasStarterApps)
        <section class="page-wrapper">
            <div class="container-tight py-6">
                <div class="text-center">
                    <h1 class="display-5 fw-bold mb-3">Ini landing page</h1>
                    <p class="text-secondary mb-4">{{ config('app.name') }}</p>

                    <a href="{{ \Altekno\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}" class="btn btn-primary">
                        Masuk ke aplikasi
                    </a>
                </div>
            </div>
        </section>
    @else
        <section class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-3 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Panduan awal project</div>
                            <h1 class="page-title">Starterkit siap, tetapi belum ada App</h1>
                            <p class="text-secondary mt-2 mb-0">
                                Halaman ini membantu developer memahami struktur navigasi sebelum membuat fitur pertama.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">
                    <div class="alert alert-info" role="alert">
                        Project tetap dapat membuka landing, login, profil, pengaturan, user, role, dan log aktivitas tanpa App.
                        Dashboard bisnis baru tersedia setelah App pertama dibuat.
                    </div>

                    <div class="row row-cards mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <article class="card h-100">
                                <div class="card-body">
                                    <span class="badge bg-primary-lt mb-3">1</span>
                                    <h2 class="card-title">App</h2>
                                    <p class="text-secondary mb-0">
                                        Area fitur tingkat atas. Setiap App memiliki kode, subdomain, module, route, dan menu sendiri.
                                    </p>
                                </div>
                            </article>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <article class="card h-100">
                                <div class="card-body">
                                    <span class="badge bg-primary-lt mb-3">2</span>
                                    <h2 class="card-title">Module</h2>
                                    <p class="text-secondary mb-0">
                                        Kelompok kemampuan di dalam App sekaligus unit pemberian hak akses kepada role.
                                    </p>
                                </div>
                            </article>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <article class="card h-100">
                                <div class="card-body">
                                    <span class="badge bg-primary-lt mb-3">3</span>
                                    <h2 class="card-title">Menu</h2>
                                    <p class="text-secondary mb-0">
                                        Navigasi utama milik module. Menu dapat langsung menuju route atau menjadi kelompok navigasi.
                                    </p>
                                </div>
                            </article>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <article class="card h-100">
                                <div class="card-body">
                                    <span class="badge bg-primary-lt mb-3">4</span>
                                    <h2 class="card-title">Submenu</h2>
                                    <p class="text-secondary mb-0">
                                        Menu anak untuk memisahkan halaman dalam satu kelompok tanpa membuat module baru.
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="row row-cards">
                        <div class="col-lg-5">
                            <article class="card h-100">
                                <div class="card-header">
                                    <h2 class="card-title">Contoh struktur</h2>
                                </div>
                                <div class="card-body">
                                    <ul class="steps steps-vertical mb-0">
                                        <li class="step-item">
                                            <div class="h4 m-0">App: layanan</div>
                                            <div class="text-secondary">Subdomain: layanan.example.com</div>
                                        </li>
                                        <li class="step-item">
                                            <div class="h4 m-0">Module: pengaduan</div>
                                            <div class="text-secondary">Batas hak akses untuk fitur pengaduan.</div>
                                        </li>
                                        <li class="step-item">
                                            <div class="h4 m-0">Menu: Pengaduan</div>
                                            <div class="text-secondary">Kelompok navigasi pada sidebar.</div>
                                        </li>
                                        <li class="step-item">
                                            <div class="h4 m-0">Submenu: Daftar dan Buat Aduan</div>
                                            <div class="text-secondary">Masing-masing terhubung ke named route.</div>
                                        </li>
                                    </ul>
                                </div>
                            </article>
                        </div>

                        <div class="col-lg-7">
                            <article class="card h-100">
                                <div class="card-header">
                                    <h2 class="card-title">Membuat App pertama</h2>
                                </div>
                                <div class="card-body">
                                    <p class="text-secondary">
                                        Jalankan dari root Laravel. Gunakan <code>--no-sync</code> agar source dapat diperiksa sebelum diterapkan ke database.
                                    </p>
                                    <pre class="bg-dark text-white rounded p-3 overflow-auto"><code>php artisan starter:make-app layanan --name="Layanan" --no-sync</code></pre>

                                    <p class="fw-semibold mt-4 mb-2">Generator menyiapkan:</p>
                                    <div class="list-group list-group-flush mb-4">
                                        <div class="list-group-item px-0"><code>config/apps/layanan.php</code> — definisi App, module, dan menu.</div>
                                        <div class="list-group-item px-0"><code>routes/apps/layanan.php</code> — route pada subdomain App.</div>
                                        <div class="list-group-item px-0"><code>routes/apps/layanan.api.php</code> — endpoint App pada gateway API opsional.</div>
                                        <div class="list-group-item px-0"><code>app/Livewire/Apps/Layanan/</code> — komponen server.</div>
                                        <div class="list-group-item px-0"><code>resources/views/apps/layanan/</code> — tampilan App.</div>
                                        <div class="list-group-item px-0"><code>tests/Feature/Apps/Layanan/</code> — test awal.</div>
                                    </div>

                                    <p class="text-secondary mb-2">
                                        Setelah source sesuai kebutuhan, periksa perubahan registry lalu terapkan:
                                    </p>
                                    <pre class="bg-dark text-white rounded p-3 overflow-auto mb-0"><code>php artisan starter:sync layanan --dry-run
php artisan starter:sync layanan --force</code></pre>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                            <div class="flex-fill">
                                <h2 class="card-title mb-1">Single App atau Multi App?</h2>
                                <p class="text-secondary mb-0">
                                    Keduanya didukung. Satu instalasi berbagi login dan pengaturan global, sedangkan setiap App memisahkan subdomain, module, route, menu, dan code fiturnya.
                                </p>
                            </div>
                            <a href="{{ \Altekno\StarterKit\Support\Starter\StarterNavigation::authLoginUrl() }}" class="btn btn-primary">
                                Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
</main>
BLADE.PHP_EOL;
    }
}
