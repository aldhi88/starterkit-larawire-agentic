<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\AppRouteInterface;
use Altekno\StarterKit\Contracts\Starter\ClientRoleInterface;
use Altekno\StarterKit\Models\Starter\AppRoute;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Support\Starter\StarterAppRegistry;
use Altekno\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Throwable;

class NavigationAuthorizedRedirectService
{
    public function __construct(
        private readonly AppRouteInterface $appRoutes,
        private readonly ClientRoleInterface $clientRoles,
        private readonly Router $router,
    ) {}

    public function forLogin(ClientLogin $login, ?string $redirect = null, ?string $intended = null): string
    {
        if ($this->canVisitUrl($login, $redirect)) {
            return (string) $redirect;
        }

        if ($this->canVisitUrl($login, $intended)) {
            return (string) $intended;
        }

        return $this->firstAuthorizedUrl($login);
    }

    public function forAppAnchor(ClientLogin $login, string $appKey): string
    {
        $landingMenu = $login->role
            ? $this->clientRoles->landingMenuForApp($login->role, $appKey)
            : null;
        $landingRoute = $landingMenu?->route;

        if ($landingRoute instanceof AppRoute && $this->canAccessRouteName($login, $landingRoute->name)) {
            return route($landingRoute->name);
        }

        $dashboardRoute = "{$appKey}.dashboard";

        if ($this->canAccessRouteName($login, $dashboardRoute)) {
            return route($dashboardRoute);
        }

        $appRoute = $this->firstAuthorizedRoute($login, $appKey);

        if ($appRoute instanceof AppRoute && Route::has($appRoute->name)) {
            return route($appRoute->name);
        }

        return $this->firstAuthorizedUrl($login);
    }

    public function firstAuthorizedUrl(ClientLogin $login): string
    {
        $route = $this->firstAuthorizedRoute($login);

        if ($route instanceof AppRoute && Route::has($route->name)) {
            return route($route->name);
        }

        if ($login->role?->hasFullAccess()) {
            foreach (StarterAppRegistry::keys() as $appKey) {
                if (Route::has("{$appKey}.dashboard")) {
                    return route("{$appKey}.dashboard");
                }
            }
        }

        return Route::has('starter.profile.edit')
            ? route('starter.profile.edit')
            : url('/');
    }

    public function canVisitUrl(ClientLogin $login, ?string $url): bool
    {
        if (! StarterNavigation::isSafeRedirect($url)) {
            return false;
        }

        $routeName = $this->routeNameFromUrl((string) $url);

        return $routeName !== null && $this->canAccessRouteName($login, $routeName);
    }

    private function canAccessRouteName(ClientLogin $login, string $routeName): bool
    {
        if (! Route::has($routeName)) {
            return false;
        }

        if ($routeName === 'starter.profile.edit') {
            return true;
        }

        if ($routeName === 'starter.logs.index') {
            return $login->role?->canViewLogs() ?? false;
        }

        if ($routeName === 'starter.client-profile' || $routeName === 'starter.settings') {
            return $login->role?->canManageSettings() ?? false;
        }

        if (str_starts_with($routeName, 'starter.user-management.')
            || str_starts_with($routeName, 'starter.settings.')) {
            return $login->role?->canManageSettings() ?? false;
        }

        if (str_starts_with($routeName, 'admin.')) {
            return $login->role?->isAdmin() ?? false;
        }

        if (str_ends_with($routeName, '.anchor')) {
            $dashboardRoute = str($routeName)->replaceEnd('.anchor', '.dashboard')->toString();

            return $this->canAccessRouteName($login, $dashboardRoute);
        }

        return $this->appRoutes->viewerCanAccessNamedRoute($login, $routeName);
    }

    private function routeNameFromUrl(string $url): ?string
    {
        try {
            $routeName = $this->router->getRoutes()
                ->match(Request::create($url, 'GET'))
                ->getName();

            if ($routeName) {
                return $routeName;
            }
        } catch (Throwable) {
            // Fall back to the synced route table for local/dev URL edge cases.
        }

        $host = parse_url($url, PHP_URL_HOST);
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $domain = config('app.domain');

        if (! $host || $host === config('app.domain')) {
            return null;
        }

        $appKey = str((string) $host)->before('.'.$domain)->toString();

        if ($host === $domain) {
            $starterRoute = match ($path) {
                'profile/edit' => 'starter.profile.edit',
                'settings' => 'starter.settings',
                'client-profile' => 'starter.client-profile',
                'user-management/roles' => 'starter.user-management.roles',
                'user-management/users' => 'starter.user-management.users',
                default => null,
            };

            if ($starterRoute) {
                return $starterRoute;
            }
        }

        if ($path === '') {
            $anchorRoute = "{$appKey}.anchor";

            return Route::has($anchorRoute) ? $anchorRoute : null;
        }

        return $this->appRoutes->nameForGetUriAndAppSubdomain($path, $appKey);
    }

    private function firstAuthorizedRoute(ClientLogin $login, ?string $appKey = null): ?AppRoute
    {
        $hasFullAccess = $login->role?->hasFullAccess() ?? false;

        $modIds = $login->role
            ? $this->clientRoles->modIds($login->role)
            : collect();

        if (! $hasFullAccess && $modIds->isEmpty()) {
            return null;
        }

        return $this->appRoutes
            ->getRoutesForModIds($hasFullAccess ? null : $modIds->all(), $appKey)
            ->filter(fn (AppRoute $route): bool => Route::has($route->name))
            ->sortBy(fn (AppRoute $route): array => [
                $this->routeScore($route->name),
                $route->mod?->app?->name ?? '',
                $route->mod?->name ?? '',
                $route->name,
            ])
            ->first();
    }

    private function routeScore(string $routeName): int
    {
        return match (true) {
            str_ends_with($routeName, '.dashboard') => 0,
            str_ends_with($routeName, '.index') => 1,
            str_ends_with($routeName, '.create') => 2,
            default => 3,
        };
    }
}
