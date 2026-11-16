<?php

use Aldhi88\StarterKit\Console\Commands\Starter\CmsCommand;
use Aldhi88\StarterKit\Installation\StarterInstallState;
use Aldhi88\StarterKit\Services\Starter\StarterAppScaffolder;
use Aldhi88\StarterKit\Services\Starter\StarterCmsInstaller;
use Aldhi88\StarterKit\Services\Starter\StarterDeploymentService;
use Aldhi88\StarterKit\Support\Cms\CmsAppDefinition;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    $this->cmsOriginalBase = app()->basePath();
    $this->cmsFixture = dirname(__DIR__, 3).'/.agent-tmp/cms-install-tests-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->cmsFixture);
    app()->setBasePath($this->cmsFixture);
    config(['apps.cms' => null, 'app.domain' => 'company.test']);
    view()->addNamespace('starter-shared', __DIR__.'/../../resources/views');
});

afterEach(function (): void {
    app()->setBasePath($this->cmsOriginalBase);
    File::deleteDirectory($this->cmsFixture);
});

it('leaves the ordinary installation untouched until CMS is explicitly prepared', function (): void {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    $installer = app(StarterCmsInstaller::class);
    $plan = $installer->plan();

    expect(StarterCmsInstaller::enabled())->toBeFalse()
        ->and($plan)->toHaveCount(7)
        ->and(is_file(config_path('apps/cms.php')))->toBeFalse()
        ->and(is_file(base_path('routes/apps/cms.php')))->toBeFalse()
        ->and(is_dir(database_path('migrations/apps/cms')))->toBeFalse()
        ->and(Schema::hasTable('cms_contents'))->toBeFalse()
        ->and(Schema::hasTable('cms_categories'))->toBeFalse()
        ->and(trim(view('starter-shared::cms.runtime-assets')->render()))->toBe('');
});

it('publishes only the optional CMS App metadata route shim and host migration', function (): void {
    $installer = app(StarterCmsInstaller::class);
    $created = $installer->prepare();
    $definition = require config_path('apps/cms.php');

    expect($created)->toHaveCount(7)
        ->and(StarterCmsInstaller::enabled())->toBeTrue()
        ->and(view('starter-shared::cms.runtime-assets')->render())->toContain('cms-workspace.js', 'cms-workspace.css')
        ->and(array_keys($definition['mods']))->toBe(['dashboard', 'posts', 'pages', 'cards', 'media', 'menus', 'comments'])
        ->and($definition['mods']['posts']['menus'][0]['children'])->toHaveCount(3)
        ->and($definition['mods']['dashboard']['menus'][0]['children'][0]['route'])->toBe('cms.dashboard')
        ->and(File::get(base_path('routes/apps/cms.php')))->toContain("StarterPaths::path('routes/cms.php')")
        ->and(File::glob(database_path('migrations/apps/cms/*.php')))->toHaveCount(5)
        ->and(is_dir(app_path('Livewire/Apps/Cms')))->toBeFalse()
        ->and(app(Migrator::class)->paths())->toContain(database_path('migrations/apps/cms'));
});

it('preserves an installed CMS definition and never duplicates its source', function (): void {
    $installer = app(StarterCmsInstaller::class);
    $installer->prepare();
    $path = config_path('apps/cms.php');
    $source = str_replace("'name' => 'CMS'", "'name' => 'Company CMS'", File::get($path));
    File::put($path, $source);

    expect($installer->prepare())->toBe([])
        ->and(File::get($path))->toBe($source)
        ->and(config('apps.cms.name'))->toBe('Company CMS');
});

it('rejects a conflicting CMS App before writing any other file', function (): void {
    File::ensureDirectoryExists(config_path('apps'));
    File::put(config_path('apps/cms.php'), "<?php return ['name' => 'Existing App'];");

    expect(fn () => app(StarterCmsInstaller::class)->prepare())->toThrow(RuntimeException::class, 'Target CMS sudah digunakan')
        ->and(is_file(base_path('routes/apps/cms.php')))->toBeFalse()
        ->and(is_dir(database_path('migrations/apps/cms')))->toBeFalse();
});

it('leaves a pre-existing CMS API route on an existing host untouched', function (): void {
    $api = base_path('routes/apps/cms.api.php');
    File::ensureDirectoryExists(dirname($api));
    File::put($api, '<?php // Host API');

    expect(fn () => app(StarterCmsInstaller::class)->prepare())->toThrow(RuntimeException::class, 'routes/apps/cms.api.php')
        ->and(File::get($api))->toBe('<?php // Host API')
        ->and(is_file(config_path('apps/cms.php')))->toBeFalse();
});

it('rolls back new source files when optional CMS publication fails', function (): void {
    $files = Mockery::mock(Filesystem::class)->makePartial();
    $files->shouldReceive('put')->with(base_path('routes/apps/cms.php'), Mockery::type('string'))->once()->andReturn(false);
    $installer = new StarterCmsInstaller($files, app(Migrator::class));

    expect(fn () => $installer->prepare())->toThrow(RuntimeException::class, 'Tidak dapat menulis source CMS')
        ->and(is_file(config_path('apps/cms.php')))->toBeFalse()
        ->and(is_file(base_path('routes/apps/cms.php')))->toBeFalse();
});

it('keeps every nested CMS menu route inside its owning module', function (): void {
    $definition = CmsAppDefinition::configuration();

    foreach ($definition['mods'] as $module => $data) {
        $menus = $data['menus'];

        while ($menus !== []) {
            $menu = array_shift($menus);
            expect($menu['visible'])->toBeTrue();

            if (isset($menu['route'])) {
                expect($menu['route'] === 'cms.'.$module || str_starts_with($menu['route'], 'cms.'.$module.'.'))->toBeTrue();
            }

            array_push($menus, ...($menu['children'] ?? []));
        }
    }
});

it('reserves the CMS subdomain for the optional installer', function (): void {
    expect(fn () => app(StarterAppScaffolder::class)->create('cms', 'Custom CMS'))
        ->toThrow(RuntimeException::class, 'reserved');
});

it('keeps activation dry runs free of file and database mutations', function (): void {
    File::ensureDirectoryExists(config_path());
    StarterInstallState::write('installed');
    app()->instance(StarterDeploymentService::class, Mockery::mock(StarterDeploymentService::class));
    $command = new CmsCommand;
    $command->setLaravel(app());
    $tester = new CommandTester($command);

    expect($tester->execute(['--dry-run' => true]))->toBe(0)
        ->and(is_file(config_path('apps/cms.php')))->toBeFalse()
        ->and(is_dir(database_path('migrations/apps/cms')))->toBeFalse();
});

it('detects an occupied CMS table before publishing or migrating on an existing host', function (): void {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    File::ensureDirectoryExists(config_path());
    StarterInstallState::write('installed');
    Schema::create('cms_media', fn (Blueprint $table) => $table->string('legacy_data'));
    DB::table('cms_media')->insert(['legacy_data' => 'keep']);
    $command = new CmsCommand;
    $command->setLaravel(app());
    $tester = new CommandTester($command);

    expect($tester->execute(['--force' => true]))->toBe(1)
        ->and($tester->getDisplay())->toContain('Tabel [cms_media] sudah ada')
        ->and(DB::table('cms_media')->value('legacy_data'))->toBe('keep')
        ->and(Schema::hasTable('cms_categories'))->toBeFalse()
        ->and(is_file(config_path('apps/cms.php')))->toBeFalse();
});

it('removes only new CMS source when activation preflight fails', function (): void {
    File::ensureDirectoryExists(config_path());
    StarterInstallState::write('installed');
    app()->register(LivewireServiceProvider::class);
    $deployment = Mockery::mock(StarterDeploymentService::class);
    $deployment->shouldReceive('prepare')
        ->with(Mockery::type(CmsCommand::class), false, array_keys(app(StarterCmsInstaller::class)->migrations()))
        ->once()->andReturn(1);
    app()->instance(StarterDeploymentService::class, $deployment);
    $command = new CmsCommand;
    $command->setLaravel(app());
    $tester = new CommandTester($command);

    expect($tester->execute(['--force' => true]))->toBe(1)
        ->and(is_file(config_path('apps/cms.php')))->toBeFalse()
        ->and(is_file(base_path('routes/apps/cms.php')))->toBeFalse()
        ->and(File::glob(database_path('migrations/apps/cms/*.php')))->toBe([])
        ->and(StarterInstallState::status())->toBe('installed');
});

it('registers CMS resource routes and keeps public helpers on the root domain', function (): void {
    app()->register(LivewireServiceProvider::class);
    app(StarterCmsInstaller::class)->prepare();
    Route::domain('cms.company.test')->middleware('web')->group(base_path('routes/apps/cms.php'));
    Route::getRoutes()->refreshNameLookups();

    foreach (CmsResource::cases() as $resource) {
        $route = Route::getRoutes()->getByName($resource->routePrefix().'.index');
        expect($route)->not->toBeNull()
            ->and($route->getDomain())->toBe('cms.company.test')
            ->and($route->defaults['resource'])->toBe($resource->value)
            ->and($route->gatherMiddleware())->toContain('auth:web', 'starter.active', 'starter.password-change', 'starter.lock', 'starter.authorize');
    }

    expect(Route::getRoutes()->getByName('starter.cms.documentation')->getDomain())->toBe('company.test')
        ->and(Route::getRoutes()->getByName('starter.cms.media')->getDomain())->toBe('company.test')
        ->and(Route::getRoutes()->getByName('cms.public.media'))->toBeNull()
        ->and(Route::has('cms.comments.create'))->toBeFalse();
});
