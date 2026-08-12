<?php

namespace Aldhi88\StarterKit\Providers\Starter;

use Aldhi88\StarterKit\Console\Commands\Starter\AppCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\DeployCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\ResetCommand;
use Aldhi88\StarterKit\Console\Commands\Starter\SyncCommand;
use Aldhi88\StarterKit\Contracts\Starter\ActivityLogInterface;
use Aldhi88\StarterKit\Contracts\Starter\AppInterface;
use Aldhi88\StarterKit\Contracts\Starter\AppModInterface;
use Aldhi88\StarterKit\Contracts\Starter\AppRouteInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientRoleInterface;
use Aldhi88\StarterKit\Contracts\Starter\StarterConfigInterface;
use Aldhi88\StarterKit\Http\Middleware\Starter\StarterAuthorize;
use Aldhi88\StarterKit\Http\Middleware\Starter\StarterEnsureActiveUser;
use Aldhi88\StarterKit\Http\Middleware\Starter\StarterForcePasswordChange;
use Aldhi88\StarterKit\Http\Middleware\Starter\StarterLockScreen;
use Aldhi88\StarterKit\Installation\StarterInstallState;
use Aldhi88\StarterKit\Livewire\Starter\Auth\ConfirmPassword;
use Aldhi88\StarterKit\Livewire\Starter\Auth\LockScreen as LockScreenComponent;
use Aldhi88\StarterKit\Livewire\Starter\Auth\Login;
use Aldhi88\StarterKit\Livewire\Starter\Logs\ActivityLogIndex;
use Aldhi88\StarterKit\Livewire\Starter\Logs\ActivityLogsTable;
use Aldhi88\StarterKit\Livewire\Starter\Profile\EditMyProfile;
use Aldhi88\StarterKit\Livewire\Starter\Settings\ClientProfile;
use Aldhi88\StarterKit\Livewire\Starter\Settings\SecuritySettings;
use Aldhi88\StarterKit\Livewire\Starter\Settings\SettingsIndex;
use Aldhi88\StarterKit\Livewire\Starter\UserManagement\RoleForm;
use Aldhi88\StarterKit\Livewire\Starter\UserManagement\Roles;
use Aldhi88\StarterKit\Livewire\Starter\UserManagement\RolesTable;
use Aldhi88\StarterKit\Livewire\Starter\UserManagement\UserForm;
use Aldhi88\StarterKit\Livewire\Starter\UserManagement\Users;
use Aldhi88\StarterKit\Livewire\Starter\UserManagement\UsersTable;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Repositories\Starter\ActivityLogRepository;
use Aldhi88\StarterKit\Repositories\Starter\AppModRepository;
use Aldhi88\StarterKit\Repositories\Starter\AppRepository;
use Aldhi88\StarterKit\Repositories\Starter\AppRouteRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientRoleRepository;
use Aldhi88\StarterKit\Repositories\Starter\StarterConfigRepository;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\SecuritySettingsService;
use Aldhi88\StarterKit\Services\Starter\SettingsOverviewService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Services\Starter\StarterContextService;
use Aldhi88\StarterKit\Services\Starter\UserManagementRoleService;
use Aldhi88\StarterKit\Services\Starter\UserManagementUserService;
use Aldhi88\StarterKit\Support\Starter\StarterInternalRunContext;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\Scramble;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class StarterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! StarterInstallState::runtimeEnabled()) {
            return;
        }

        $this->mergeConfigFrom(StarterPaths::path('config/starter.php'), 'starter');
        $this->app->singleton(StarterInternalRunContext::class);
        $this->configurePowerGrid();
        $this->configureEmbeddedApplication();
        $this->registerViewPaths();
        $this->prepareApiDocumentation();

        $this->app->bind(AppInterface::class, AppRepository::class);
        $this->app->bind(AppModInterface::class, AppModRepository::class);
        $this->app->bind(AppRouteInterface::class, AppRouteRepository::class);
        $this->app->scoped(ActivityLogInterface::class, ActivityLogRepository::class);
        $this->app->bind(ClientInterface::class, ClientRepository::class);
        $this->app->bind(ClientLoginInterface::class, ClientLoginRepository::class);
        $this->app->bind(ClientRoleInterface::class, ClientRoleRepository::class);
        $this->app->bind(StarterConfigInterface::class, StarterConfigRepository::class);
        $this->app->scoped(AuditLogService::class);
        $this->app->scoped(AuthenticatedLoginService::class);
        $this->app->scoped(SecuritySettingsService::class);
        $this->app->scoped(SettingsOverviewService::class);
        $this->app->scoped(StarterConfigService::class);
        $this->app->scoped(StarterContextService::class);
        $this->app->scoped(UserManagementRoleService::class);
        $this->app->scoped(UserManagementUserService::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                AppCommand::class,
                DeployCommand::class,
                ResetCommand::class,
                SyncCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! StarterInstallState::runtimeEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(StarterPaths::path('database/migrations/starter'));
        $this->loadAppMigrations();
        $this->registerLivewireComponents();
        $this->configureApiDocumentation();
        $this->configurePowerGridTranslations();
        View::addNamespace('errors', StarterTheme::viewPath('starter/errors'));

        Number::useLocale(str_replace('_', '-', (string) config('app.locale')));
        $strictDevelopment = $this->app->isLocal() || $this->app->runningUnitTests();
        Model::preventLazyLoading($strictDevelopment);
        Model::preventSilentlyDiscardingAttributes($strictDevelopment);

        Event::listen([
            'eloquent.created: *',
            'eloquent.updated: *',
            'eloquent.deleted: *',
            'eloquent.restored: *',
        ], function (string $eventName, array $models): void {
            $event = str($eventName)->after('eloquent.')->before(':')->value();
            $model = $models[0] ?? null;

            if ($model instanceof Model) {
                app(AuditLogService::class)->recordModelEvent($event, $model);
            }
        });

        View::composer([
            'layouts::app',
            'starter.templates.layouts.app',
            'apps.*',
        ], function ($view): void {
            $view->with(app(StarterContextService::class)->data());
        });

        Livewire::addPersistentMiddleware([
            StarterEnsureActiveUser::class,
            StarterForcePasswordChange::class,
            StarterLockScreen::class,
            StarterAuthorize::class,
            RequirePassword::class,
        ]);
    }

    private function registerLivewireComponents(): void
    {
        $components = [
            'starter.auth.confirm-password' => ConfirmPassword::class,
            'starter.auth.lock-screen' => LockScreenComponent::class,
            'starter.auth.login' => Login::class,
            'starter.logs.activity-log-index' => ActivityLogIndex::class,
            'starter.logs.activity-logs-table' => ActivityLogsTable::class,
            'starter.profile.edit-my-profile' => EditMyProfile::class,
            'starter.settings.client-profile' => ClientProfile::class,
            'starter.settings.security-settings' => SecuritySettings::class,
            'starter.settings.settings-index' => SettingsIndex::class,
            'starter.user-management.role-form' => RoleForm::class,
            'starter.user-management.roles' => Roles::class,
            'starter.user-management.roles-table' => RolesTable::class,
            'starter.user-management.user-form' => UserForm::class,
            'starter.user-management.users' => Users::class,
            'starter.user-management.users-table' => UsersTable::class,
        ];

        foreach ($components as $name => $component) {
            Livewire::component($name, $component);
        }
    }

    private function registerViewPaths(): void
    {
        $viewPaths = (array) $this->app['config']->get('view.paths', [resource_path('views')]);
        $starterViewPaths = [
            StarterTheme::viewPath('starter'),
            StarterTheme::viewPath(),
        ];

        $this->app['config']->set('view.paths', array_values(array_unique([
            ...$viewPaths,
            ...$starterViewPaths,
        ])));

        View::addNamespace('starter-shared', StarterPaths::path('resources/views'));
    }

    private function loadAppMigrations(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $appsMigrationPath = database_path('migrations/apps');

        if (! File::isDirectory($appsMigrationPath)) {
            return;
        }

        $paths = collect(File::directories($appsMigrationPath))
            ->filter(fn (string $path): bool => preg_match(
                '/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/',
                basename($path),
            ) === 1)
            ->sort()
            ->values()
            ->all();

        if ($paths !== []) {
            $this->loadMigrationsFrom($paths);
        }
    }

    private function configureEmbeddedApplication(): void
    {
        if (! StarterPaths::isEmbedded()) {
            return;
        }

        $config = $this->app['config'];
        $starterViews = StarterTheme::viewPath('starter');

        $config->set('app.domain', $config->get('starter.domain'));

        if ($config->get('starter.connector.configure_auth', true)) {
            $config->set('auth', require StarterPaths::path('config/auth.php'));
        }

        if ($config->get('starter.connector.configure_shared_session', true)) {
            $domain = trim((string) $config->get('starter.domain'), '.');
            $config->set('session.domain', in_array($domain, ['', 'localhost', '127.0.0.1'], true)
                ? null
                : ".{$domain}");
        }

        $componentLocations = (array) $config->get('livewire.component_locations', []);
        array_unshift($componentLocations, "{$starterViews}/templates/components");

        $config->set('livewire.component_locations', array_values(array_unique($componentLocations)));
        $config->set('livewire.component_namespaces.layouts', "{$starterViews}/templates/layouts");
        $config->set('livewire.component_layout', 'layouts::app');
        $config->set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:10240']);
    }

    private function configurePowerGrid(): void
    {
        $config = $this->app['config'];
        $config->set('livewire-powergrid.theme', StarterTheme::powerGridTheme());
        $config->set('livewire-powergrid.filter', 'inline');
        $config->set('livewire-powergrid.persist_driver', 'session');
    }

    private function configurePowerGridTranslations(): void
    {
        Lang::addLines([
            'datatable.buttons.clear_all_filters' => 'Hapus semua',
            'datatable.labels.action' => 'Aksi',
            'datatable.labels.results_per_page' => 'Data per halaman',
            'datatable.labels.clear_filter' => 'Hapus filter',
            'datatable.labels.no_data' => 'Tidak ada data yang ditemukan',
            'datatable.labels.all' => 'Semua',
            'datatable.labels.selected' => 'Dipilih',
            'datatable.labels.filtered' => 'Tersaring',
            'datatable.placeholders.search' => 'Cari...',
            'datatable.placeholders.select' => 'Pilih periode',
            'datatable.pagination.showing' => 'Menampilkan',
            'datatable.pagination.to' => 'sampai',
            'datatable.pagination.of' => 'dari',
            'datatable.pagination.results' => 'data',
            'datatable.boolean_filter.all' => 'Semua',
            'datatable.multi_select.select' => 'Pilih',
            'datatable.multi_select.all' => 'Semua',
            'datatable.select.select' => 'Pilih',
            'datatable.select.all' => 'Semua',
            'datatable.input_text_options.is' => 'Sama dengan',
            'datatable.input_text_options.is_not' => 'Tidak sama dengan',
            'datatable.input_text_options.contains' => 'Mengandung',
            'datatable.input_text_options.contains_not' => 'Tidak mengandung',
            'datatable.input_text_options.starts_with' => 'Dimulai dengan',
            'datatable.input_text_options.ends_with' => 'Diakhiri dengan',
            'datatable.input_text_options.is_null' => 'Tidak ada',
            'datatable.input_text_options.is_not_null' => 'Ada',
            'datatable.input_text_options.is_blank' => 'Kosong',
            'datatable.input_text_options.is_not_blank' => 'Tidak kosong',
            'datatable.input_text_options.is_empty' => 'Kosong atau tidak ada',
            'datatable.input_text_options.is_not_empty' => 'Tidak kosong dan ada',
        ], (string) config('app.locale'), 'livewire-powergrid');
    }

    private function prepareApiDocumentation(): void
    {
        if (! class_exists(Scramble::class)) {
            if (config('starter.api.enabled')) {
                throw new \RuntimeException(
                    'STARTER_API_ENABLED membutuhkan package dedoc/scramble. '
                    .'Jalankan composer require dedoc/scramble.',
                );
            }

            return;
        }

        Scramble::ignoreDefaultRoutes();

        $config = $this->app['config'];
        $config->set('scramble.api_domain', $config->get('starter.api.domain'));
        $config->set('scramble.api_path', '');
        $config->set('scramble.middleware', [
            'web',
            RestrictedDocsAccess::class,
        ]);
        $config->set('scramble.info.title', $config->get('app.name').' API');
    }

    private function configureApiDocumentation(): void
    {
        if (! class_exists(Scramble::class) || ! config('starter.api.enabled')) {
            return;
        }

        Gate::define('viewApiDocs', function (?ClientLogin $login): bool {
            if ($this->app->environment(['local', 'development'])) {
                return true;
            }

            return $login?->role?->isSuperuser() ?? false;
        });

        $apiDomain = (string) config('starter.api.domain');

        Scramble::configure()
            ->routes(fn (Route $route): bool => $route->getDomain() === $apiDomain
                && in_array('api', $route->gatherMiddleware(), true))
            ->expose(
                ui: fn (Router $router, mixed $action): Route => $router
                    ->domain($apiDomain)
                    ->get('/', $action)
                    ->name('api.docs'),
                document: fn (Router $router, mixed $action): Route => $router
                    ->domain($apiDomain)
                    ->get('/openapi.json', $action)
                    ->name('api.openapi'),
            );
    }
}
