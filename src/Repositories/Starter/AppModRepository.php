<?php

namespace Aldhi88\StarterKit\Repositories\Starter;

use Aldhi88\StarterKit\Contracts\Starter\AppModInterface;
use Aldhi88\StarterKit\Models\Starter\App;
use Aldhi88\StarterKit\Models\Starter\AppMod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class AppModRepository implements AppModInterface
{
    public function allForUserAccessPreview(): Collection
    {
        return AppMod::query()
            ->with('app')
            ->orderBy('app_id')
            ->orderBy('name')
            ->get();
    }

    public function allForRoleAccessManagement(): Collection
    {
        return AppMod::query()
            ->with([
                'app',
                'menus' => function ($query): void {
                    $query
                        ->with('route')
                        ->whereNotNull('app_route_id')
                        ->orderBy('order');
                },
            ])
            ->orderBy('app_id')
            ->orderBy('name')
            ->get();
    }

    public function forIdsWithNavigableMenus(array $ids): Collection
    {
        return AppMod::query()
            ->with([
                'app',
                'menus' => function ($query): void {
                    $query
                        ->with('route')
                        ->whereNotNull('app_route_id')
                        ->orderBy('order');
                },
            ])
            ->whereIn('id', $ids)
            ->get();
    }

    public function accessStats(): array
    {
        $stats = AppMod::query()
            ->selectRaw('COUNT(*) as modules_count, COUNT(DISTINCT app_id) as apps_count')
            ->first();

        return [
            'apps' => (int) $stats->apps_count,
            'modules' => (int) $stats->modules_count,
        ];
    }

    public function forApp(App $app, array $with = [], array $onlyIds = []): EloquentCollection
    {
        $modules = AppMod::query()
            ->whereBelongsTo($app)
            ->with($with)
            ->when($onlyIds !== [], function ($query) use ($onlyIds): void {
                $query->whereIn('id', $onlyIds);
            })
            ->orderBy('id')
            ->get();

        $configuredOrder = array_flip(array_keys(
            config("apps.{$app->subdomain}.mods", [])
        ));

        return $modules
            ->sortBy(fn (AppMod $module): array => [
                $configuredOrder[$module->code] ?? PHP_INT_MAX,
                $module->id,
            ])
            ->values();
    }
}
