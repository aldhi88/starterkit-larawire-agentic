<?php

namespace Altekno\StarterKit\Repositories\Starter;

use Altekno\StarterKit\Contracts\Starter\ActivityLogInterface;
use Altekno\StarterKit\Models\Starter\ActivityLog;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Models\Starter\ClientRole;
use Altekno\StarterKit\Support\Starter\ActivityLogFilters;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ActivityLogRepository implements ActivityLogInterface
{
    /** @var list<string>|null */
    private ?array $protectedRoleIds = null;

    /** @var list<string>|null */
    private ?array $protectedUserIds = null;

    public function tableQueryForViewer(ClientLogin $viewer): Builder
    {
        $actions = $this->viewerQuery($viewer)
            ->selectRaw('MAX(id) as id')
            ->selectRaw('action_id')
            ->selectRaw('MAX(created_at) as created_at')
            ->selectRaw('MAX(action_label) as action_label')
            ->selectRaw('MAX(action_key) as action_key')
            ->selectRaw('MAX(actor_name) as actor_name')
            ->selectRaw('MAX(actor_username) as actor_username')
            ->selectRaw('MAX(actor_role) as actor_role')
            ->selectRaw('MAX(app_key) as app_key')
            ->selectRaw('MAX(route_name) as route_name')
            ->selectRaw('MAX(ip_address) as ip_address')
            ->selectRaw('COUNT(*) as changes_count')
            ->selectRaw('COUNT(DISTINCT table_name) as tables_count')
            ->groupBy('action_id');

        $activity = new ActivityLog;
        $activity->setTable('starter_activity_log_actions');

        return $activity->newQuery()->fromSub($actions, 'starter_activity_log_actions');
    }

    public function paginateActionsForViewer(
        ClientLogin $viewer,
        ActivityLogFilters $filters,
        int $perPage,
        string $pageName,
    ): LengthAwarePaginator {
        return $this->filteredQuery($viewer, $filters)
            ->select('action_id')
            ->selectRaw('MAX(created_at) as last_activity_at')
            ->selectRaw('COUNT(*) as changes_count')
            ->selectRaw('COUNT(DISTINCT table_name) as tables_count')
            ->groupBy('action_id')
            ->orderByDesc('last_activity_at')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function entriesGroupedByActionForViewer(ClientLogin $viewer, Collection $actionIds): Collection
    {
        if ($actionIds->isEmpty()) {
            return collect();
        }

        return $this->viewerQuery($viewer)
            ->whereIn('action_id', $actionIds->all())
            ->orderBy('sequence')
            ->orderBy('id')
            ->get()
            ->groupBy('action_id');
    }

    public function entriesForActionForViewer(ClientLogin $viewer, string $actionId): Collection
    {
        return $this->viewerQuery($viewer)
            ->where('action_id', $actionId)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();
    }

    public function actionExistsForViewer(ClientLogin $viewer, string $actionId): bool
    {
        return $this->viewerQuery($viewer)->where('action_id', $actionId)->exists();
    }

    public function metricsForViewer(ClientLogin $viewer): array
    {
        $today = CarbonImmutable::today();
        $metrics = $this->viewerQuery($viewer)
            ->selectRaw('COUNT(*) as total_changes')
            ->selectRaw(
                'SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today_changes',
                [$today, $today->addDay()],
            )
            ->selectRaw('COUNT(DISTINCT client_login_id) as active_actor_count')
            ->first();

        return [
            'total_changes' => (int) ($metrics?->total_changes ?? 0),
            'today_changes' => (int) ($metrics?->today_changes ?? 0),
            'active_actor_count' => (int) ($metrics?->active_actor_count ?? 0),
        ];
    }

    public function filterOptionsForViewer(ClientLogin $viewer): array
    {
        $isSuperuser = $viewer->role?->isSuperuser() ?? false;
        $actors = ClientLogin::query()
            ->when(! $isSuperuser, fn (Builder $query): Builder => $query->whereHas(
                'role',
                fn (Builder $roleQuery): Builder => $roleQuery
                    ->where('is_system', false)
                    ->where('code', '!=', 'superuser'),
            ))
            ->orderBy('name')
            ->get(['id', 'client_role_id', 'name', 'username']);
        $visibility = $isSuperuser ? 'superuser' : 'delegated';
        $cachedOptions = Cache::remember(
            ActivityLog::FILTER_OPTIONS_CACHE_KEY.'.'.$visibility,
            now()->addMinutes(10),
            function () use ($viewer, $isSuperuser): array {
                $base = $this->viewerQuery($viewer);
                $roleQuery = clone $base;

                if (! $isSuperuser) {
                    $roleQuery->where('actor_is_superuser', false);
                }

                return [
                    'roles' => $this->distinctOptions($roleQuery, 'actor_role')->all(),
                    'apps' => $this->distinctOptions(clone $base, 'app_key')->all(),
                    'tables' => $this->distinctOptions(clone $base, 'table_name')->all(),
                    'routes' => $this->distinctOptions(clone $base, 'route_name')->all(),
                ];
            },
        );

        return [
            'actors' => $actors,
            'roles' => collect($cachedOptions['roles']),
            'apps' => collect($cachedOptions['apps']),
            'tables' => collect($cachedOptions['tables']),
            'routes' => collect($cachedOptions['routes']),
        ];
    }

    private function filteredQuery(ClientLogin $viewer, ActivityLogFilters $filters): Builder
    {
        $query = $this->viewerQuery($viewer);

        if ($filters->search !== '') {
            $query->where(function (Builder $searchQuery) use ($filters, $viewer): void {
                $term = '%'.$filters->search.'%';
                $searchQuery
                    ->where('action_label', 'like', $term)
                    ->orWhere('action_key', 'like', $term)
                    ->orWhere('action_id', 'like', $term)
                    ->orWhere('auditable_label', 'like', $term)
                    ->orWhere('auditable_id', 'like', $term)
                    ->orWhere('table_name', 'like', $term)
                    ->orWhere('route_name', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhere(function (Builder $actorQuery) use ($term, $viewer): void {
                        if (! $viewer->role?->isSuperuser()) {
                            $actorQuery->where('actor_is_superuser', false);
                        }

                        $actorQuery->where(function (Builder $identityQuery) use ($term): void {
                            $identityQuery
                                ->where('actor_name', 'like', $term)
                                ->orWhere('actor_username', 'like', $term);
                        });
                    });
            });
        }

        return $query
            ->when($filters->dateFrom, fn (Builder $dateQuery): Builder => $dateQuery->where('created_at', '>=', $filters->dateFrom->startOfDay()))
            ->when($filters->dateTo, fn (Builder $dateQuery): Builder => $dateQuery->where('created_at', '<', $filters->dateTo->addDay()->startOfDay()))
            ->when($filters->event !== '', fn (Builder $eventQuery): Builder => $eventQuery->where('event', $filters->event))
            ->when($filters->actorId, fn (Builder $actorQuery): Builder => $actorQuery->where('client_login_id', $filters->actorId))
            ->when($filters->role !== '', fn (Builder $roleQuery): Builder => $roleQuery->where('actor_role', $filters->role))
            ->when($filters->app !== '', fn (Builder $appQuery): Builder => $appQuery->where('app_key', $filters->app))
            ->when($filters->table !== '', fn (Builder $tableQuery): Builder => $tableQuery->where('table_name', $filters->table))
            ->when($filters->route !== '', fn (Builder $routeQuery): Builder => $routeQuery->where('route_name', $filters->route))
            ->when($filters->ipPrefix !== '', fn (Builder $ipQuery): Builder => $ipQuery->where('ip_address', 'like', $filters->ipPrefix.'%'))
            ->when($filters->actionPrefix !== '', fn (Builder $actionQuery): Builder => $actionQuery->where('action_id', 'like', $filters->actionPrefix.'%'));
    }

    /**
     * @return Collection<int, string>
     */
    private function distinctOptions(Builder $query, string $column): Collection
    {
        return $query
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->values();
    }

    private function viewerQuery(ClientLogin $viewer): Builder
    {
        $query = ActivityLog::query();

        if ($viewer->role?->isSuperuser()) {
            return $query;
        }

        $protectedRoleIds = $this->protectedRoleIds ??= ClientRole::query()
            ->where(function (Builder $roleQuery): void {
                $roleQuery->where('is_system', true)->orWhere('code', 'superuser');
            })
            ->pluck('id')
            ->map(fn (int|string $id): string => (string) $id)
            ->all();
        $protectedUserIds = $this->protectedUserIds ??= ClientLogin::query()
            ->whereIn('client_role_id', $protectedRoleIds)
            ->pluck('id')
            ->map(fn (int|string $id): string => (string) $id)
            ->all();

        if ($protectedRoleIds === [] && $protectedUserIds === []) {
            return $query;
        }

        return $query->whereNot(function (Builder $protectedQuery) use ($protectedRoleIds, $protectedUserIds): void {
            $protectedQuery
                ->when($protectedRoleIds !== [], function (Builder $roleQuery) use ($protectedRoleIds): void {
                    $roleQuery->where(function (Builder $targetQuery) use ($protectedRoleIds): void {
                        $targetQuery
                            ->where('auditable_type', ClientRole::class)
                            ->whereIn('auditable_id', $protectedRoleIds);
                    });
                })
                ->when($protectedUserIds !== [], function (Builder $userQuery) use ($protectedRoleIds, $protectedUserIds): void {
                    $method = $protectedRoleIds === [] ? 'where' : 'orWhere';
                    $userQuery->{$method}(function (Builder $targetQuery) use ($protectedUserIds): void {
                        $targetQuery
                            ->where('auditable_type', ClientLogin::class)
                            ->whereIn('auditable_id', $protectedUserIds);
                    });
                });
        });
    }
}
