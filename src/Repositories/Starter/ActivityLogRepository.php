<?php

namespace Aldhi88\StarterKit\Repositories\Starter;

use Aldhi88\StarterKit\Contracts\Starter\ActivityLogInterface;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Support\Starter\ActivityLogFilters;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActivityLogRepository implements ActivityLogInterface
{
    /** @var list<string>|null */
    private ?array $protectedRoleIds = null;

    /** @var list<string>|null */
    private ?array $protectedUserIds = null;

    /** @return Builder<ActivityLog> */
    public function tableQueryForViewer(ClientLogin $viewer): Builder
    {
        $outcome = DB::connection()->getDriverName() === 'sqlite'
            ? "json_extract(metadata, '$.outcome')"
            : "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.outcome'))";
        $actions = $this->viewerQuery($viewer)
            ->selectRaw('MAX(id) as id')
            ->selectRaw('action_id')
            ->selectRaw('MAX(created_at) as created_at')
            ->selectRaw("CASE WHEN COUNT(DISTINCT action_label) > 1 THEN 'Aktivitas gabungan' ELSE MAX(action_label) END as original_label")
            ->selectRaw('MAX(auditable_label) as target_label')
            ->selectRaw('COUNT(DISTINCT auditable_type) as target_types')
            ->selectRaw('COUNT(DISTINCT auditable_id) as target_ids')
            ->selectRaw('COUNT(auditable_label) as labelled_count')
            ->selectRaw('COUNT(DISTINCT auditable_label) as target_labels')
            ->selectRaw('SUM(COALESCE(access_count, 1)) as occurrences')
            ->selectRaw('MAX(last_access_at) as last_access_at')
            ->selectRaw('MIN(created_at) as first_activity_at')
            ->selectRaw('MAX(event) as event')
            ->selectRaw('COUNT(DISTINCT event) as event_count')
            ->selectRaw("CASE WHEN COUNT(DISTINCT {$outcome}) > 1 THEN 'mixed' WHEN COUNT({$outcome}) = COUNT(*) THEN MAX({$outcome}) END as outcome")
            ->selectRaw('MAX(source) as source')
            ->selectRaw('COUNT(DISTINCT ('.$this->categoryExpression().')) as category_count')
            ->selectRaw('MAX('.$this->categoryExpression().') as category_value')
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

        $concat = DB::connection()->getDriverName() === 'sqlite'
            ? "COALESCE(original_label, 'Aktivitas') || ' — ' || target_label"
            : "CONCAT(COALESCE(original_label, 'Aktivitas'), ' — ', target_label)";
        $multiple = DB::connection()->getDriverName() === 'sqlite'
            ? "COALESCE(original_label, 'Aktivitas') || ' — beberapa objek/tabel'"
            : "CONCAT(COALESCE(original_label, 'Aktivitas'), ' — beberapa objek/tabel')";
        $contains = DB::connection()->getDriverName() === 'sqlite'
            ? 'instr(LOWER(original_label), LOWER(target_label)) > 0'
            : 'LOCATE(LOWER(target_label), LOWER(original_label)) > 0';

        $summaries = DB::query()->fromSub($actions, 'grouped_actions')->select('*')
            ->selectRaw("CASE
                WHEN target_types > 1 OR target_ids > 1 OR tables_count > 1 THEN {$multiple}
                WHEN target_labels = 1 AND labelled_count = changes_count AND target_label <> ''
                    AND NOT COALESCE({$contains}, 0) THEN {$concat}
                ELSE COALESCE(original_label, 'Aktivitas') END as action_label")
            ->selectRaw("CASE WHEN category_count > 1 THEN 'mixed' ELSE category_value END as category");

        return $activity->newQuery()->fromSub($summaries, 'starter_activity_log_actions');
    }

    private function categoryExpression(): string
    {
        return "CASE WHEN event = 'security' THEN 'security'
            WHEN source IN ('automated', 'console') THEN 'automated'
            WHEN event IN ('access', 'browse', 'view', 'read', 'search', 'filter', 'sort', 'paginate') THEN 'access'
            ELSE 'business' END";
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

        $groups = $this->viewerQuery($viewer)
            ->whereIn('action_id', $actionIds->all())
            ->orderBy('sequence')
            ->orderBy('id')
            ->get()
            ->groupBy('action_id');

        return collect($groups->all())->mapWithKeys(
            fn (EloquentCollection $logs, int|string $actionId): array => [
                (string) $actionId => collect($logs->all()),
            ],
        );
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
            'total_changes' => (int) $metrics->total_changes,
            'today_changes' => (int) $metrics->today_changes,
            'active_actor_count' => (int) $metrics->active_actor_count,
        ];
    }

    public function filterOptionsForViewer(ClientLogin $viewer): array
    {
        $isSuperuser = $viewer->role->isSuperuser();
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

    /** @return Builder<ActivityLog> */
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
                        if (! $viewer->role->isSuperuser()) {
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

    /** @return Builder<ActivityLog> */
    private function viewerQuery(ClientLogin $viewer): Builder
    {
        $query = ActivityLog::query();

        if ($viewer->role->isSuperuser()) {
            return $query;
        }

        $protectedRoleIds = $this->protectedRoleIds ??= ClientRole::withTrashed()
            ->where(function (Builder $roleQuery): void {
                $roleQuery->where('is_system', true)->orWhere('code', 'superuser');
            })
            ->pluck('id')
            ->map(fn (int|string $id): string => (string) $id)
            ->all();
        $protectedUserIds = $this->protectedUserIds ??= ClientLogin::withTrashed()
            ->whereIn('client_role_id', $protectedRoleIds)
            ->pluck('id')
            ->map(fn (int|string $id): string => (string) $id)
            ->all();

        // Exclude the complete action so sibling entries cannot expose protected context.
        $protectedActions = ActivityLog::query()->select('action_id')
            ->where(function (Builder $protectedQuery) use ($protectedRoleIds, $protectedUserIds): void {
                $protectedQuery->where('actor_is_superuser', true)
                    ->when($protectedUserIds !== [], fn (Builder $actorQuery): Builder => $actorQuery
                        ->orWhereIn('client_login_id', $protectedUserIds))
                    ->when($protectedRoleIds !== [], function (Builder $roleQuery) use ($protectedRoleIds): void {
                        $roleQuery->orWhere(function (Builder $targetQuery) use ($protectedRoleIds): void {
                            $targetQuery
                                ->where('auditable_type', ClientRole::class)
                                ->whereIn('auditable_id', $protectedRoleIds);
                        });
                    })
                    ->when($protectedUserIds !== [], function (Builder $userQuery) use ($protectedUserIds): void {
                        $userQuery->orWhere(function (Builder $targetQuery) use ($protectedUserIds): void {
                            $targetQuery
                                ->where('auditable_type', ClientLogin::class)
                                ->whereIn('auditable_id', $protectedUserIds);
                        });
                    });
            });

        return $query->whereNotIn('action_id', $protectedActions);
    }
}
