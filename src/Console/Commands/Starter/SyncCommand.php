<?php

namespace Altekno\StarterKit\Console\Commands\Starter;

use Altekno\StarterKit\Models\Starter\App;
use Altekno\StarterKit\Models\Starter\AppMenu;
use Altekno\StarterKit\Models\Starter\AppMod;
use Altekno\StarterKit\Models\Starter\AppRoute;
use Altekno\StarterKit\Services\Starter\StarterDeploymentService;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'starter:sync
        {subdomain? : Sync only one registered subdomain}
        {--force : Run without confirmation}
        {--dry-run : Validate and show changes without writing to the database}
        {--prepared : Internal flag when deployment preparation was already completed by starter:setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply migrations, assets, and starter app registry updates';

    /**
     * Execute the console command.
     */
    public function handle(StarterDeploymentService $deployment): int
    {
        if (! $this->option('dry-run')
            && ! $this->option('prepared')
            && $deployment->prepare($this) !== self::SUCCESS) {
            return self::FAILURE;
        }

        foreach ($this->subdomains() as $subdomain) {
            $plan = $this->buildPlan($subdomain);

            if ($this->option('dry-run')) {
                $this->table(['App', 'Change', 'Modules', 'Routes', 'Menus'], [
                    [$subdomain, 'create', $plan['create']['modules'], $plan['create']['routes'], $plan['create']['menus']],
                    [$subdomain, 'delete', $plan['delete']['modules'], $plan['delete']['routes'], $plan['delete']['menus']],
                ]);

                continue;
            }

            if (! $this->confirmDeletes($subdomain, $plan)) {
                return self::FAILURE;
            }

            if (! $this->confirmCreates($subdomain, $plan)) {
                return self::FAILURE;
            }

            $this->applyPlan($subdomain, $plan);
            $this->info("Synced app: {$subdomain}");
        }

        if (! $this->option('dry-run')
            && $deployment->finish($this) !== self::SUCCESS) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Get the subdomains that should be synced.
     *
     * @return array<int, string>
     */
    private function subdomains(): array
    {
        $subdomain = $this->argument('subdomain');

        return $subdomain ? [$subdomain] : $this->discoverSubdomains();
    }

    /**
     * Discover app configs from config/apps/*.php.
     *
     * @return array<int, string>
     */
    private function discoverSubdomains(): array
    {
        return collect(glob(config_path('apps/*.php')) ?: [])
            ->map(function (string $path): string {
                return pathinfo($path, PATHINFO_FILENAME);
            })
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Build one sync plan by comparing config/routes directly with database rows.
     *
     * @return array<string, mixed>
     */
    private function buildPlan(string $subdomain): array
    {
        $this->validateSubdomain($subdomain);
        $config = $this->appConfig($subdomain);
        $sourceRouteNames = $this->sourceRouteNames($subdomain);
        $this->validateConfig($subdomain, $config, $sourceRouteNames);

        $app = App::query()->where('subdomain', $subdomain)->first();
        $configuredModCodes = collect($config['mods'] ?? [])->keys();
        $configuredMenuKeys = $this->configuredMenuKeys($config['mods'] ?? []);
        $routeNames = $this->routeNames($configuredModCodes, $sourceRouteNames);

        $existingMods = $app
            ? AppMod::query()->where('app_id', $app->id)->get()
            : collect();

        $existingModIds = $existingMods->pluck('id');
        $staleModIds = $existingMods->whereNotIn('code', $configuredModCodes)->pluck('id');
        $keptModIds = $existingMods->whereIn('code', $configuredModCodes)->pluck('id');

        $existingRoutes = AppRoute::query()
            ->whereIn('app_mod_id', $keptModIds)
            ->get();

        $staleRouteIds = $existingRoutes->whereNotIn('name', $routeNames)->pluck('id')
            ->merge(AppRoute::query()->whereIn('app_mod_id', $staleModIds)->pluck('id'));

        $existingMenuKeys = $this->existingMenuKeys($existingMods);
        $deleteMenuCount = $existingMenuKeys->diff($configuredMenuKeys)->count();
        $createModuleCount = $configuredModCodes->diff($existingMods->pluck('code'))->count();
        $createRouteCount = $routeNames->diff($existingRoutes->pluck('name'))->count();
        $createMenuCount = $configuredMenuKeys->diff($existingMenuKeys)->count();

        return [
            'config' => $config,
            'configured_mod_codes' => $configuredModCodes,
            'configured_menu_keys' => $configuredMenuKeys,
            'route_names' => $routeNames,
            'delete' => [
                'modules' => $staleModIds->count(),
                'routes' => $staleRouteIds->unique()->count(),
                'menus' => $deleteMenuCount,
            ],
            'create' => [
                'modules' => $createModuleCount,
                'routes' => $createRouteCount,
                'menus' => $createMenuCount,
            ],
        ];
    }

    /**
     * Ask for confirmation when the sync will delete database rows.
     *
     * @param  array<string, mixed>  $plan
     */
    private function confirmDeletes(string $subdomain, array $plan): bool
    {
        $delete = $plan['delete'];

        if ($this->option('force') || array_sum($delete) === 0) {
            return true;
        }

        $this->warn("App {$subdomain}: {$delete['modules']} module, {$delete['routes']} route, and {$delete['menus']} menu will be deleted.");

        return $this->confirm('Lanjutkan?', false);
    }

    /**
     * Ask for confirmation when the sync will create database rows.
     *
     * @param  array<string, mixed>  $plan
     */
    private function confirmCreates(string $subdomain, array $plan): bool
    {
        $create = $plan['create'];

        if ($this->option('force') || array_sum($create) === 0) {
            return true;
        }

        $this->info("App {$subdomain}: {$create['modules']} new module, {$create['routes']} route, and {$create['menus']} menu will be added.");

        return $this->confirm('Lanjutkan?', true);
    }

    /**
     * Apply one validated sync plan.
     *
     * @param  array<string, mixed>  $plan
     */
    private function applyPlan(string $subdomain, array $plan): void
    {
        DB::transaction(function () use ($subdomain, $plan): void {
            $config = $plan['config'];
            $app = $this->upsertApp($subdomain, $config);
            $mods = $this->syncMods($app, $config['mods'] ?? []);
            $routes = $this->syncRoutes($subdomain, $mods, $config['mods'] ?? []);

            $this->pruneRoutes($mods, $routes);
            $this->syncMenus($mods, $routes, $config['mods'] ?? []);
            $this->pruneMenus($mods, $plan['configured_menu_keys']);
            $this->pruneMods($app, $mods);
            $this->syncAdminDashboardLandings($app, $subdomain);
        });
    }

    /**
     * Load one app config file.
     *
     * @return array<string, mixed>
     */
    private function appConfig(string $subdomain): array
    {
        $this->validateSubdomain($subdomain);

        $configPath = config_path("apps/{$subdomain}.php");

        if (! file_exists($configPath)) {
            $this->fail("Config not found: {$configPath}");
        }

        if (! file_exists(base_path("routes/apps/{$subdomain}.php"))) {
            $this->fail('Route file not found: '.base_path("routes/apps/{$subdomain}.php"));
        }

        return require $configPath;
    }

    /**
     * Validate one app config filename as a subdomain.
     */
    private function validateSubdomain(string $subdomain): void
    {
        if ($subdomain === 'api') {
            $this->fail('App code [api] is reserved for the shared API gateway.');
        }

        if (preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $subdomain) !== 1) {
            $this->fail("Invalid app config filename [{$subdomain}]. Use lowercase letters, numbers, and hyphens only.");
        }
    }

    /**
     * Validate one app config payload.
     *
     * @param  array<string, mixed>  $config
     */
    private function validateConfig(string $subdomain, array $config, Collection $sourceRouteNames): void
    {
        if (empty($config['mods']) || ! is_array($config['mods'])) {
            $this->fail("App [{$subdomain}] must define at least one module in the [mods] array.");
        }

        if (empty($config['mods']['dashboard']) || ! is_array($config['mods']['dashboard'])) {
            $this->fail("App [{$subdomain}] must define a [dashboard] module.");
        }

        if (! $this->hasLandingMenu($config['mods']['dashboard']['menus'] ?? [], "{$subdomain}.dashboard")) {
            $this->fail("App [{$subdomain}] dashboard module must include a default page menu for [{$subdomain}.dashboard].");
        }

        foreach ($config['mods'] as $modCode => $modConfig) {
            if (! is_string($modCode) || preg_match('/^[a-z0-9][a-z0-9_-]*$/', $modCode) !== 1) {
                $this->fail("Invalid module code [{$modCode}] in app [{$subdomain}]. Use lowercase letters, numbers, underscore, or hyphen.");
            }

            if (! is_array($modConfig) || blank($modConfig['name'] ?? null)) {
                $this->fail("Module [{$subdomain}.{$modCode}] must define a name.");
            }

            $this->validateMenus($subdomain, $modCode, $modConfig['menus'] ?? [], $sourceRouteNames);
        }

        $sourceRouteNames
            ->filter(fn (string $name): bool => $name !== "{$subdomain}.anchor")
            ->each(function (string $name) use ($subdomain, $config): void {
                $modCode = explode('.', $name)[1] ?? '';

                if (! array_key_exists($modCode, $config['mods'])) {
                    $this->fail("Route [{$name}] is not owned by a configured module [{$subdomain}.{$modCode}].");
                }
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $menus
     */
    private function validateMenus(
        string $subdomain,
        string $modCode,
        array $menus,
        Collection $sourceRouteNames,
    ): void {
        foreach ($menus as $menu) {
            if (! is_array($menu) || blank($menu['label'] ?? null)) {
                $this->fail("Every menu in module [{$subdomain}.{$modCode}] must define a label.");
            }

            $routeName = $menu['route'] ?? null;

            if ($routeName !== null) {
                if (! is_string($routeName) || ! $sourceRouteNames->contains($routeName)) {
                    $this->fail("Menu [{$menu['label']}] references missing route [{$routeName}].");
                }

                $expectedPrefix = "{$subdomain}.{$modCode}";

                if ($routeName !== $expectedPrefix && ! str_starts_with($routeName, $expectedPrefix.'.')) {
                    $this->fail("Menu route [{$routeName}] must belong to module [{$subdomain}.{$modCode}].");
                }
            }

            if (($menu['landing'] ?? false) === true && $routeName === null) {
                $this->fail("Landing menu [{$menu['label']}] must define a route.");
            }

            $this->validateMenus($subdomain, $modCode, $menu['children'] ?? [], $sourceRouteNames);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $menus
     */
    private function hasLandingMenu(array $menus, string $routeName): bool
    {
        return collect($menus)->contains(function (array $menu) use ($routeName): bool {
            if (($menu['route'] ?? null) === $routeName && ($menu['landing'] ?? false) === true) {
                return true;
            }

            return $this->hasLandingMenu($menu['children'] ?? [], $routeName);
        });
    }

    /**
     * Get configured menu keys including children.
     *
     * @param  array<string, array<string, mixed>>  $configuredMods
     * @return Collection<int, string>
     */
    private function configuredMenuKeys(array $configuredMods): Collection
    {
        return collect($configuredMods)
            ->flatMap(function (array $modConfig, string $modCode): Collection {
                return $this->collectConfiguredMenuKeys($modCode, $modConfig['menus'] ?? []);
            })
            ->values();
    }

    /**
     * Collect configured menu keys recursively.
     *
     * @param  array<int, array<string, mixed>>  $menus
     * @return Collection<int, string>
     */
    private function collectConfiguredMenuKeys(string $modCode, array $menus, string $parentPath = ''): Collection
    {
        return collect($menus)->flatMap(function (array $menu) use ($modCode, $parentPath): Collection {
            $path = $parentPath === ''
                ? $menu['label']
                : $parentPath.'>'.$menu['label'];

            return collect(["{$modCode}|{$path}"])
                ->merge($this->collectConfiguredMenuKeys($modCode, $menu['children'] ?? [], $path));
        });
    }

    /**
     * Get existing menu keys for the given modules.
     *
     * @param  Collection<int, AppMod>  $mods
     * @return Collection<int, string>
     */
    private function existingMenuKeys(Collection $mods): Collection
    {
        return $this->existingMenuKeyMap($mods)->values();
    }

    /**
     * Get existing menu keys mapped by menu id.
     *
     * @param  Collection<int, AppMod>  $mods
     * @return Collection<int, string>
     */
    private function existingMenuKeyMap(Collection $mods): Collection
    {
        $menus = AppMenu::query()
            ->whereIn('app_mod_id', $mods->pluck('id'))
            ->orderBy('order')
            ->get();

        return $this->collectExistingMenuKeyMap($menus, $mods->keyBy('id'));
    }

    /**
     * Collect existing menu keys recursively.
     *
     * @param  Collection<int, AppMenu>  $menus
     * @param  Collection<int, AppMod>  $modsById
     * @return Collection<int, string>
     */
    private function collectExistingMenuKeyMap(Collection $menus, Collection $modsById, ?int $parentId = null, string $parentPath = ''): Collection
    {
        return $menus
            ->where('parent_id', $parentId)
            ->reduce(function (Collection $keys, AppMenu $menu) use ($menus, $modsById, $parentPath): Collection {
                $modCode = $modsById->get($menu->app_mod_id)?->code;

                if (! $modCode) {
                    return $keys;
                }

                $path = $parentPath === ''
                    ? $menu->label
                    : $parentPath.'>'.$menu->label;

                $keys->put($menu->id, "{$modCode}|{$path}");

                return $keys->union($this->collectExistingMenuKeyMap($menus, $modsById, $menu->id, $path));
            }, collect());
    }

    /**
     * Get all named routes that belong to this app and a configured module.
     *
     * @param  Collection<int, string>  $configuredModCodes
     * @return Collection<int, string>
     */
    private function routeNames(Collection $configuredModCodes, Collection $sourceRouteNames): Collection
    {
        return $sourceRouteNames
            ->filter(function (string $routeName) use ($configuredModCodes): bool {
                $modCode = explode('.', $routeName)[1] ?? 'dashboard';

                return $configuredModCodes->contains($modCode);
            })
            ->values();
    }

    /**
     * Load the current App route source independently from any route cache that
     * was active when Artisan booted.
     *
     * @return Collection<int, string>
     */
    private function sourceRouteNames(string $subdomain): Collection
    {
        $activeRouter = Route::getFacadeRoot();
        $sourceRouter = new Router(app('events'), app());

        Route::swap($sourceRouter);

        try {
            $sourceRouter
                ->middleware('web')
                ->domain($subdomain.'.'.config('app.domain'))
                ->group(base_path("routes/apps/{$subdomain}.php"));

            return collect($sourceRouter->getRoutes())
                ->map(fn ($route): string => (string) $route->getName())
                ->filter(fn (string $name): bool => str_starts_with($name, "{$subdomain}."))
                ->unique()
                ->values();
        } finally {
            Route::swap($activeRouter);
        }
    }

    /**
     * Create or update the app row.
     *
     * @param  array<string, mixed>  $config
     */
    private function upsertApp(string $subdomain, array $config): App
    {
        return App::query()->updateOrCreate([
            'subdomain' => $subdomain,
        ], [
            'name' => $config['name'],
            'desc' => $config['desc'] ?? null,
            'icon' => $config['icon'] ?? null,
        ]);
    }

    /**
     * Sync module config rows.
     *
     * @param  array<string, array<string, mixed>>  $configuredMods
     * @return Collection<string, AppMod>
     */
    private function syncMods(App $app, array $configuredMods): Collection
    {
        return collect($configuredMods)->mapWithKeys(function (array $modConfig, string $code) use ($app): array {
            $mod = AppMod::query()->updateOrCreate([
                'app_id' => $app->id,
                'code' => $code,
            ], [
                'name' => $modConfig['name'],
                'desc' => $modConfig['desc'] ?? null,
            ]);

            return [$code => $mod];
        });
    }

    /**
     * Sync named routes with the app subdomain prefix.
     *
     * @param  Collection<string, AppMod>  $mods
     * @return Collection<string, AppRoute>
     */
    private function syncRoutes(string $subdomain, Collection $mods, array $configuredMods): Collection
    {
        return collect(Route::getRoutes())
            ->filter(function ($route) use ($subdomain): bool {
                return str_starts_with((string) $route->getName(), "{$subdomain}.");
            })
            ->mapWithKeys(function ($route) use ($mods): array {
                $name = (string) $route->getName();
                $modCode = explode('.', $name)[1] ?? 'dashboard';
                $method = collect($route->methods())->reject(function (string $method): bool {
                    return $method === 'HEAD';
                })->first();

                if (! $method || ! $mods->has($modCode)) {
                    return [];
                }

                $appRoute = AppRoute::query()->updateOrCreate([
                    'app_mod_id' => $mods[$modCode]->id,
                    'name' => $name,
                ], [
                    'uri' => $route->uri(),
                    'method' => $method,
                ]);

                return [$name => $appRoute];
            });
    }

    private function syncAdminDashboardLandings(App $app, string $subdomain): void
    {
        $dashboardMenu = AppMenu::query()
            ->where('is_landing_candidate', true)
            ->whereHas('mod', function ($query) use ($app): void {
                $query->where('app_id', $app->id);
            })
            ->whereHas('route', function ($query) use ($subdomain): void {
                $query->where('name', "{$subdomain}.dashboard");
            })
            ->first();

        if (! $dashboardMenu instanceof AppMenu) {
            return;
        }

        DB::table('pivot_client_roles_app_landings')
            ->where('app_id', $app->id)
            ->whereIn('client_role_id', function ($query): void {
                $query
                    ->select('id')
                    ->from('starter_client_roles')
                    ->where('code', 'superuser');
            })
            ->delete();

        $now = now();
        $adminRoleIds = DB::table('starter_client_roles')->where('code', 'superuser')->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            DB::table('pivot_client_roles_app_landings')->updateOrInsert([
                'client_role_id' => $roleId,
                'app_id' => $app->id,
            ], [
                'app_menu_id' => $dashboardMenu->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Delete route rows no longer present in code.
     *
     * @param  Collection<string, AppMod>  $mods
     * @param  Collection<string, AppRoute>  $routes
     */
    private function pruneRoutes(Collection $mods, Collection $routes): void
    {
        $modIds = $mods->pluck('id');
        $routeNames = $routes->keys();
        $staleRouteIds = AppRoute::query()
            ->whereIn('app_mod_id', $modIds)
            ->whereNotIn('name', $routeNames)
            ->pluck('id');

        $this->deleteMenus(
            AppMenu::query()->whereIn('app_route_id', $staleRouteIds)->pluck('id')
        );

        AppRoute::query()
            ->whereIn('app_mod_id', $modIds)
            ->whereNotIn('name', $routeNames)
            ->delete();
    }

    /**
     * Rebuild menus from config.
     *
     * @param  Collection<string, AppMod>  $mods
     * @param  Collection<string, AppRoute>  $routes
     * @param  array<string, array<string, mixed>>  $configuredMods
     */
    private function syncMenus(Collection $mods, Collection $routes, array $configuredMods): void
    {
        foreach ($configuredMods as $modCode => $modConfig) {
            foreach ($modConfig['menus'] ?? [] as $order => $menuConfig) {
                $this->syncMenu($mods[$modCode], $routes, $menuConfig, $order + 1);
            }
        }
    }

    /**
     * Create or update one menu row and all of its children.
     *
     * @param  Collection<string, AppRoute>  $routes
     * @param  array<string, mixed>  $menuConfig
     */
    private function syncMenu(AppMod $mod, Collection $routes, array $menuConfig, int $order, ?AppMenu $parent = null): AppMenu
    {
        $routeName = $menuConfig['route'] ?? null;

        $menu = AppMenu::query()->updateOrCreate([
            'app_mod_id' => $mod->id,
            'parent_id' => $parent?->id,
            'label' => $menuConfig['label'],
        ], [
            'icon' => $parent ? null : ($menuConfig['icon'] ?? null),
            'order' => $order,
            'is_landing_candidate' => $routeName !== null && ($menuConfig['landing'] ?? false) === true,
            'app_route_id' => $routeName ? $routes->get($routeName)?->id : null,
        ]);

        foreach ($menuConfig['children'] ?? [] as $childOrder => $childConfig) {
            $this->syncMenu($mod, $routes, $childConfig, $childOrder + 1, $menu);
        }

        return $menu;
    }

    /**
     * Delete menu rows no longer present in config.
     *
     * @param  Collection<string, AppMod>  $mods
     * @param  Collection<int, string>  $configuredMenuKeys
     */
    private function pruneMenus(Collection $mods, Collection $configuredMenuKeys): void
    {
        $staleMenuIds = $this->existingMenuKeyMap($mods)
            ->filter(function (string $key) use ($configuredMenuKeys): bool {
                return ! $configuredMenuKeys->contains($key);
            })
            ->keys();

        $this->deleteMenus($staleMenuIds);
    }

    /**
     * Delete menus and their children.
     *
     * @param  Collection<int, int>  $menuIds
     */
    private function deleteMenus(Collection $menuIds): void
    {
        $menuIds = $menuIds->filter()->unique()->values();

        if ($menuIds->isEmpty()) {
            return;
        }

        $this->deleteMenus(AppMenu::query()->whereIn('parent_id', $menuIds)->pluck('id'));
        AppMenu::query()->whereIn('id', $menuIds)->delete();
    }

    /**
     * Delete module rows no longer present in config.
     *
     * @param  Collection<string, AppMod>  $mods
     */
    private function pruneMods(App $app, Collection $mods): void
    {
        $keptModIds = $mods->pluck('id');
        $staleModIds = AppMod::query()
            ->where('app_id', $app->id)
            ->whereNotIn('id', $keptModIds)
            ->pluck('id');

        if ($staleModIds->isEmpty()) {
            return;
        }

        $this->deleteMenus(AppMenu::query()->whereIn('app_mod_id', $staleModIds)->pluck('id'));
        AppRoute::query()->whereIn('app_mod_id', $staleModIds)->delete();
        DB::table('pivot_client_roles_app_mods')->whereIn('app_mod_id', $staleModIds)->delete();
        AppMod::query()->whereIn('id', $staleModIds)->delete();
    }
}
