<?php

namespace Altekno\StarterKit\Contracts\Starter;

use Altekno\StarterKit\Models\Starter\ActivityLog;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Support\Starter\ActivityLogFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface ActivityLogInterface
{
    public function tableQueryForViewer(ClientLogin $viewer): Builder;

    /**
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function paginateActionsForViewer(
        ClientLogin $viewer,
        ActivityLogFilters $filters,
        int $perPage,
        string $pageName,
    ): LengthAwarePaginator;

    /**
     * @param  Collection<int, string>  $actionIds
     * @return Collection<string, Collection<int, ActivityLog>>
     */
    public function entriesGroupedByActionForViewer(ClientLogin $viewer, Collection $actionIds): Collection;

    /**
     * @return Collection<int, ActivityLog>
     */
    public function entriesForActionForViewer(ClientLogin $viewer, string $actionId): Collection;

    public function actionExistsForViewer(ClientLogin $viewer, string $actionId): bool;

    /**
     * @return array{total_changes: int, today_changes: int, active_actor_count: int}
     */
    public function metricsForViewer(ClientLogin $viewer): array;

    /**
     * @return array{
     *     actors: Collection<int, ClientLogin>,
     *     roles: Collection<int, string>,
     *     apps: Collection<int, string>,
     *     tables: Collection<int, string>,
     *     routes: Collection<int, string>
     * }
     */
    public function filterOptionsForViewer(ClientLogin $viewer): array;
}
