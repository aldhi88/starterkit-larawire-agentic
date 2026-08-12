<?php

namespace Aldhi88\StarterKit\Repositories\Starter;

use Aldhi88\StarterKit\Contracts\Starter\AppRouteInterface;
use Aldhi88\StarterKit\Models\Starter\AppRoute;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Support\Collection;

class AppRouteRepository implements AppRouteInterface
{
    public function viewerCanAccessNamedRoute(ClientLogin $viewer, string $routeName): bool
    {
        $role = $viewer->loadMissing('role')->role;

        return AppRoute::query()
            ->where('name', $routeName)
            ->when(! $role->hasFullAccess(), fn ($query) => $query->whereHas(
                'mod.roles',
                fn ($roleQuery) => $roleQuery->whereKey($role->id),
            ))
            ->exists();
    }

    public function nameForGetUriAndAppSubdomain(string $uri, string $appSubdomain): ?string
    {
        return AppRoute::query()
            ->where('method', 'GET')
            ->where('uri', $uri)
            ->whereHas('mod.app', function ($query) use ($appSubdomain): void {
                $query->where('subdomain', $appSubdomain);
            })
            ->value('name');
    }

    public function getRoutesForModIds(?array $modIds, ?string $appSubdomain = null): Collection
    {
        return AppRoute::query()
            ->with('mod.app')
            ->when($modIds !== null, fn ($query) => $query->whereIn('app_mod_id', $modIds))
            ->where('method', 'GET')
            ->when($appSubdomain, function ($query) use ($appSubdomain): void {
                $query->whereHas('mod.app', function ($query) use ($appSubdomain): void {
                    $query->where('subdomain', $appSubdomain);
                });
            })
            ->get();
    }
}
