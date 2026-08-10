<?php

namespace Altekno\StarterKit\Contracts\Starter;

use Altekno\StarterKit\Models\Starter\AppRoute;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Support\Collection;

interface AppRouteInterface
{
    public function viewerCanAccessNamedRoute(ClientLogin $viewer, string $routeName): bool;

    public function nameForGetUriAndAppSubdomain(string $uri, string $appSubdomain): ?string;

    /**
     * @param  array<int, int>|null  $modIds
     * @return Collection<int, AppRoute>
     */
    public function getRoutesForModIds(?array $modIds, ?string $appSubdomain = null): Collection;
}
