<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\AppInterface;
use Altekno\StarterKit\Contracts\Starter\AppModInterface;
use Altekno\StarterKit\Contracts\Starter\ClientInterface;
use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Altekno\StarterKit\Contracts\Starter\ClientRoleInterface;
use Altekno\StarterKit\Models\Starter\App;
use Altekno\StarterKit\Models\Starter\AppMenu;
use Altekno\StarterKit\Models\Starter\AppMod;
use Altekno\StarterKit\Models\Starter\Client;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class StarterContextService
{
    public function __construct(
        private readonly AppInterface $apps,
        private readonly AppModInterface $appMods,
        private readonly ClientRoleInterface $clientRoles,
        private readonly ClientInterface $clients,
        private readonly ClientLoginInterface $clientLogins,
        private readonly StarterConfigService $configs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $attribute = 'starter.context.data';

        if (request()->attributes->has($attribute)) {
            return request()->attributes->get($attribute);
        }

        $data = $this->build();
        request()->attributes->set($attribute, $data);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $login = auth()->user();
        $login = $login instanceof ClientLogin ? $login : null;

        $login = $login ? $this->clientLogins->loadRole($login) : null;
        $client = $login ? $this->clients->current() : null;
        $modIds = $this->authorizedModIds($login);

        $accessibleApps = $this->accessibleApps($login, $modIds);
        $currentApp = $this->resolveCurrentApp($accessibleApps);
        $currentAppKey = $currentApp?->subdomain ?? 'landing';
        $sidebarMods = $this->sidebarMods($login, $currentApp, $modIds);
        $sidebarPayload = $this->sidebarPayload($sidebarMods);

        $lockScreenEnabled = $this->configs->boolean('security.lock_screen_enabled');
        $lockScreenTimeoutSeconds = max(
            60,
            min(86400, $this->configs->integer('security.lock_screen_timeout_minutes') * 60),
        );

        return [
            'login' => $login,
            'loginName' => $login?->name,
            'loginEmail' => $login?->email,
            'loginAvatarUrl' => $this->avatarUrl($login),
            'loginRoleName' => $login?->role?->name,
            'clientName' => $client?->name,
            'clientLogoUrl' => $this->clientLogoUrl($client),
            'currentApp' => $currentApp,
            'currentAppKey' => $currentAppKey,
            'currentAppName' => $currentApp?->name,
            'currentAppIcon' => $this->normalizeIcon($currentApp?->icon, 'apps'),
            'currentDashboardUrl' => $this->dashboardUrl($currentAppKey),
            'currentProfileUrl' => $this->profileUrl(),
            'appOptions' => $this->appOptions($accessibleApps, $currentApp),
            'sidebarMods' => $sidebarPayload,
            'accessibleAppCount' => $accessibleApps->count(),
            'sidebarModCount' => $sidebarMods->count(),
            'lockScreenEnabled' => $lockScreenEnabled,
            'lockScreenTimeoutSeconds' => $lockScreenTimeoutSeconds,
            'lockScreenUrl' => route('starter.lock-screen'),
            'sessionActivityUrl' => route('starter.session.activity'),
        ];
    }

    private function requestedAppKey(): ?string
    {
        $routeName = request()->route()?->getName();

        if ($routeName && str_contains($routeName, '.') && ! str_starts_with($routeName, 'default-livewire.')) {
            $routeAppKey = explode('.', $routeName)[0];

            if ($routeAppKey !== 'starter') {
                return $routeAppKey;
            }
        }

        $host = request()->getHost();
        $domain = config('app.domain');

        if ($domain && $host !== $domain && str_ends_with($host, '.'.$domain)) {
            return str($host)->before('.'.$domain)->toString();
        }

        return null;
    }

    /**
     * Keep the sidebar attached to an authorized app on global starter pages.
     *
     * @param  EloquentCollection<int, App>  $accessibleApps
     */
    private function resolveCurrentApp(EloquentCollection $accessibleApps): ?App
    {
        $requestedAppKey = $this->requestedAppKey();
        $requestedApp = $requestedAppKey
            ? $accessibleApps->firstWhere('subdomain', $requestedAppKey)
            : null;

        if ($requestedApp instanceof App) {
            $this->rememberCurrentApp($requestedApp);

            return $requestedApp;
        }

        $rememberedAppKey = request()->hasSession()
            ? request()->session()->get('starter.current_app_key')
            : null;
        $rememberedApp = filled($rememberedAppKey)
            ? $accessibleApps->firstWhere('subdomain', $rememberedAppKey)
            : null;

        if ($rememberedApp instanceof App) {
            return $rememberedApp;
        }

        $fallbackApp = $accessibleApps->first();

        if ($fallbackApp instanceof App) {
            $this->rememberCurrentApp($fallbackApp);
        }

        return $fallbackApp;
    }

    private function rememberCurrentApp(App $app): void
    {
        if (request()->hasSession()) {
            request()->session()->put('starter.current_app_key', $app->subdomain);
        }
    }

    /**
     * @return EloquentCollection<int, App>
     */
    private function accessibleApps(?ClientLogin $login, ?Collection $modIds): EloquentCollection
    {
        if (! $login) {
            return new EloquentCollection;
        }

        if ($login->role?->hasFullAccess()) {
            return $this->apps->allOrderedByName();
        }

        if (! $modIds || $modIds->isEmpty()) {
            return new EloquentCollection;
        }

        return $this->apps->whereHasModIds($modIds->all());
    }

    /**
     * @return EloquentCollection<int, AppMod>
     */
    private function sidebarMods(?ClientLogin $login, ?App $currentApp, ?Collection $modIds): EloquentCollection
    {
        if (! $login || ! $currentApp) {
            return new EloquentCollection;
        }

        $with = [
            'menus' => function ($query): void {
                $query
                    ->whereNull('parent_id')
                    ->with(['route', 'childrenRecursive.route'])
                    ->orderBy('order');
            },
        ];

        if ($login->role?->hasFullAccess()) {
            return $this->appMods->forApp($currentApp, $with);
        }

        return ! $modIds || $modIds->isEmpty()
            ? new EloquentCollection
            : $this->appMods->forApp($currentApp, $with, $modIds->all());
    }

    /**
     * Null means the authenticated role has full access.
     *
     * @return Collection<int, int>|null
     */
    private function authorizedModIds(?ClientLogin $login): ?Collection
    {
        if (! $login) {
            return null;
        }

        if (! $login->role) {
            return collect();
        }

        if ($login->role->hasFullAccess()) {
            return null;
        }

        return $this->clientRoles->modIds($login->role);
    }

    /**
     * @param  EloquentCollection<int, App>  $apps
     * @return Collection<int, array<string, mixed>>
     */
    private function appOptions(EloquentCollection $apps, ?App $currentApp): Collection
    {
        return $apps
            ->map(fn (App $app): array => [
                'name' => $app->name,
                'subdomain' => $app->subdomain,
                'icon' => $this->normalizeIcon($app->icon, 'apps'),
                'url' => $this->appUrl($app),
                'active' => $currentApp?->is($app) ?? false,
            ])
            ->values();
    }

    /**
     * @param  EloquentCollection<int, AppMod>  $mods
     * @return Collection<int, array<string, mixed>>
     */
    private function sidebarPayload(EloquentCollection $mods): Collection
    {
        return $mods
            ->map(fn (AppMod $mod): array => [
                'name' => $mod->name,
                'menus' => $mod->menus
                    ->map(fn (AppMenu $menu): array => $this->menuPayload($menu))
                    ->values(),
                'menuLabels' => $mod->menus->pluck('label')->join(', '),
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function menuPayload(AppMenu $menu): array
    {
        $children = $menu->childrenRecursive;
        $childPayload = $children
            ->map(fn (AppMenu $child): array => $this->menuPayload($child))
            ->values();
        $isActive = $this->isCurrentUrl($this->menuUrl($menu));
        $isExpanded = $isActive || $childPayload->contains(fn (array $child): bool => $child['active'] || $child['expanded']);

        return [
            'label' => $menu->label,
            'icon' => $this->normalizeIcon($menu->icon, 'circle'),
            'url' => $this->menuUrl($menu),
            'children' => $childPayload,
            'hasChildren' => $children->isNotEmpty(),
            'active' => $isActive,
            'expanded' => $isExpanded,
        ];
    }

    private function isCurrentUrl(?string $url): bool
    {
        if (! $url || $url === '#') {
            return false;
        }

        return rtrim($url, '/') === rtrim(url()->current(), '/');
    }

    private function normalizeIcon(?string $icon, string $fallback): string
    {
        return match ($icon) {
            'ri-global-line' => 'world',
            'ri-apps-line', 'ri-apps-2-line' => 'apps',
            'ri-dashboard-line' => 'layout-dashboard',
            'ri-folder-line' => 'folder',
            'ri-user-settings-line', 'user-management' => 'users-group',
            null, '' => $fallback,
            default => str($icon)
                ->replaceStart('ri-', '')
                ->replaceEnd('-line', '')
                ->toString(),
        };
    }

    public function avatarUrl(?ClientLogin $login): string
    {
        $photo = trim((string) $login?->profile_photo);
        $ownedPrefix = $login ? "storage/starter/profile-photos/{$login->id}/" : null;

        if ($photo === 'assets/starter/images/avatar.png') {
            return asset($photo);
        }

        if ($photo === '' || ! $ownedPrefix || ! str_starts_with($photo, $ownedPrefix)) {
            return asset('assets/starter/images/avatar.png');
        }

        return asset(ltrim($photo, '/'));
    }

    private function clientLogoUrl(?Client $client): ?string
    {
        $logo = trim((string) $client?->logo);
        $ownedPrefix = $client ? "storage/starter/client-photos/{$client->id}/" : null;

        if ($logo === '' || ! $ownedPrefix || ! str_starts_with($logo, $ownedPrefix)) {
            return null;
        }

        return asset(ltrim($logo, '/'));
    }

    private function dashboardUrl(string $appKey): string
    {
        $routeName = $appKey.'.anchor';

        return Route::has($routeName) ? route($routeName) : url('/');
    }

    private function profileUrl(): string
    {
        return $this->routeUrl('starter.profile.edit', url('/profile/edit'));
    }

    private function appUrl(App $app): string
    {
        $routeName = $app->subdomain.'.anchor';

        if (Route::has($routeName)) {
            return route($routeName);
        }

        $host = $app->subdomain.'.'.config('app.domain');

        return request()->getScheme().'://'.$host;
    }

    private function menuUrl(AppMenu $menu): string
    {
        $routeName = $menu->route?->name;

        return $routeName ? $this->routeUrl($routeName) : '#';
    }

    private function routeUrl(string $routeName, string $fallback = '#'): string
    {
        return Route::has($routeName) ? route($routeName) : $fallback;
    }
}
