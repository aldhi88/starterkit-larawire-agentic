<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Pest\TestSuite;
use RuntimeException;
use Throwable;

class StarterAppScaffolder
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @return list<string>
     */
    public function create(string $subdomain, string $name, ?string $description = null, string $icon = 'apps'): array
    {
        if ($subdomain === 'api') {
            throw new RuntimeException('Subdomain [api] is reserved for the shared API gateway.');
        }

        $className = Str::studly($subdomain);
        $files = $this->files($subdomain, $className, $name, $description, $icon);
        $collisions = array_keys(array_filter($files, fn (string $contents, string $path): bool => $this->files->exists($path), ARRAY_FILTER_USE_BOTH));

        if ($collisions !== []) {
            throw new RuntimeException('Scaffold target already exists: '.implode(', ', $collisions));
        }

        $created = [];

        try {
            foreach ($files as $path => $contents) {
                $this->files->ensureDirectoryExists(dirname($path));
                $this->files->put($path, $contents);
                $created[] = $path;
            }
        } catch (Throwable $exception) {
            $this->files->delete($created);

            throw $exception;
        }

        return $created;
    }

    /**
     * @return array<string, string>
     */
    private function files(string $subdomain, string $className, string $name, ?string $description, string $icon): array
    {
        $description ??= "Aplikasi internal {$name}.";
        $viewName = "apps.{$subdomain}.dashboard.{$subdomain}-dashboard-index";
        $appConfigKey = "apps.{$subdomain}";
        $config = var_export([
            'name' => $name,
            'desc' => $description,
            'icon' => $icon,
            'mods' => [
                'dashboard' => [
                    'name' => 'Dashboard',
                    'desc' => 'Ringkasan aplikasi.',
                    'menus' => [[
                        'label' => 'Contoh Menu',
                        'icon' => 'layout-dashboard',
                        'children' => [
                            [
                                'label' => 'Contoh Submenu 1',
                                'route' => "{$subdomain}.dashboard",
                                'landing' => true,
                            ],
                            [
                                'label' => 'Contoh Submenu 2',
                                'route' => "{$subdomain}.dashboard.submenu-two",
                            ],
                        ],
                    ]],
                ],
            ],
        ], true);

        return [
            config_path("apps/{$subdomain}.php") => "<?php\n\nreturn {$config};\n",
            base_path("routes/apps/{$subdomain}.php") => $this->routeContents($subdomain, $className),
            base_path("routes/apps/{$subdomain}.api.php") => $this->apiRouteContents($subdomain),
            app_path("Livewire/Apps/{$className}/Dashboard/{$className}DashboardIndex.php") => <<<PHP
<?php

namespace App\Livewire\Apps\\{$className}\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class {$className}DashboardIndex extends Component
{
    public function render()
    {
        \$section = request()->routeIs('{$subdomain}.dashboard.submenu-two')
            ? 'Contoh Submenu 2'
            : 'Contoh Submenu 1';

        return view('{$viewName}', compact('section'))
            ->title((string) config('{$appConfigKey}.name').' - '.\$section);
    }
}
PHP.PHP_EOL,
            resource_path("views/apps/{$subdomain}/dashboard/{$subdomain}-dashboard-index.blade.php") => <<<BLADE
<div>
    <div class="page-header d-print-none mt-0 mb-3" aria-label="Header halaman">
        <div class="page-pretitle">{{ config('{$appConfigKey}.name') }}</div>
        <h2 class="page-title">{{ \$section }}</h2>
    </div>
    <div class="card"><div class="card-body">{{ config('{$appConfigKey}.desc') }}</div></div>
</div>
BLADE.PHP_EOL,
            base_path("tests/Feature/Apps/{$className}/{$className}DashboardTest.php") => $this->testContents($subdomain, $className),
        ];
    }

    private function testContents(string $subdomain, string $className): string
    {
        if (class_exists(TestSuite::class)) {
            return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

it('registers the {$subdomain} dashboard route', function () {
    expect(Route::has('{$subdomain}.dashboard'))->toBeTrue()
        ->and(Route::has('{$subdomain}.dashboard.submenu-two'))->toBeTrue();
});

it('ships an isolated API route for the {$subdomain} app', function () {
    expect(base_path('routes/apps/{$subdomain}.api.php'))->toBeFile();

    if (config('starter.api.enabled')) {
        expect(Route::has('api.{$subdomain}.index'))->toBeTrue();
    }
});

it('uses the standard page header spacing', function () {
    expect(file_get_contents(resource_path('views/apps/{$subdomain}/dashboard/{$subdomain}-dashboard-index.blade.php')))
        ->toContain('page-header d-print-none mt-0 mb-3');
});

it('ships an explicit example menu structure', function () {
    \$menus = config('apps.{$subdomain}.mods.dashboard.menus');

    expect(\$menus)->toHaveCount(1)
        ->and(\$menus[0]['label'])->toBe('Contoh Menu')
        ->and(\$menus[0]['children'])->toHaveCount(2)
        ->and(\$menus[0]['children'][0]['label'])->toBe('Contoh Submenu 1')
        ->and(\$menus[0]['children'][1]['label'])->toBe('Contoh Submenu 2');
});
PHP.PHP_EOL;
        }

        return <<<PHP
<?php

namespace Tests\Feature\Apps;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class {$className}DashboardTest extends TestCase
{
    public function test_it_registers_the_dashboard_routes(): void
    {
        \$this->assertTrue(Route::has('{$subdomain}.dashboard'));
        \$this->assertTrue(Route::has('{$subdomain}.dashboard.submenu-two'));
    }

    public function test_it_ships_an_isolated_api_route(): void
    {
        \$this->assertFileExists(base_path('routes/apps/{$subdomain}.api.php'));

        if (config('starter.api.enabled')) {
            \$this->assertTrue(Route::has('api.{$subdomain}.index'));
        }
    }

    public function test_it_uses_the_standard_page_header_spacing(): void
    {
        \$this->assertStringContainsString(
            'page-header d-print-none mt-0 mb-3',
            (string) file_get_contents(resource_path('views/apps/{$subdomain}/dashboard/{$subdomain}-dashboard-index.blade.php')),
        );
    }

    public function test_it_ships_an_explicit_example_menu_structure(): void
    {
        \$menus = config('apps.{$subdomain}.mods.dashboard.menus');

        \$this->assertCount(1, \$menus);
        \$this->assertSame('Contoh Menu', \$menus[0]['label']);
        \$this->assertCount(2, \$menus[0]['children']);
        \$this->assertSame('Contoh Submenu 1', \$menus[0]['children'][0]['label']);
        \$this->assertSame('Contoh Submenu 2', \$menus[0]['children'][1]['label']);
    }
}
PHP.PHP_EOL;
    }

    private function routeContents(string $subdomain, string $className): string
    {
        return <<<PHP
<?php

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Aldhi88\StarterKit\Support\Starter\StarterNavigation;
use App\Livewire\Apps\\{$className}\Dashboard\\{$className}DashboardIndex;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    \$redirect = request()->query('redirect', url('/'));

    return redirect(StarterNavigation::authLoginUrl(StarterNavigation::isSafeRedirect(\$redirect) ? \$redirect : url('/')));
});

Route::name('{$subdomain}.')->group(function () {
    Route::middleware(['auth:web', 'starter.active', 'starter.password-change', 'starter.lock'])->group(function () {
        Route::get('/', function (NavigationAuthorizedRedirectService \$redirects) {
            \$login = auth()->user();

            return \$login instanceof ClientLogin
                ? redirect(\$redirects->forAppAnchor(\$login, '{$subdomain}'))
                : redirect()->route('auth.login');
        })->name('anchor');

        Route::middleware('starter.authorize')->group(function () {
            Route::livewire('/dashboard/index', {$className}DashboardIndex::class)->name('dashboard');
            Route::livewire('/dashboard/submenu-2', {$className}DashboardIndex::class)->name('dashboard.submenu-two');
        });
    });
});
PHP.PHP_EOL;
    }

    private function apiRouteContents(string $subdomain): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'app' => config('apps.{$subdomain}.name'),
    'status' => 'ready',
]))->name('index');
PHP.PHP_EOL;
    }
}
