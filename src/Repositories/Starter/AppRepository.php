<?php

namespace Altekno\StarterKit\Repositories\Starter;

use Altekno\StarterKit\Contracts\Starter\AppInterface;
use Altekno\StarterKit\Models\Starter\App;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AppRepository implements AppInterface
{
    public function findBySubdomain(string $subdomain): ?App
    {
        return App::query()
            ->where('subdomain', $subdomain)
            ->first();
    }

    public function countRegistered(): int
    {
        return App::query()->count();
    }

    public function allOrderedByName(): EloquentCollection
    {
        return App::query()
            ->orderBy('name')
            ->get();
    }

    public function whereHasModIds(array $modIds): EloquentCollection
    {
        return App::query()
            ->whereHas('mods', function ($query) use ($modIds): void {
                $query->whereIn('starter_app_mods.id', $modIds);
            })
            ->orderBy('name')
            ->get();
    }
}
