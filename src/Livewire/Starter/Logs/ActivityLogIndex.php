<?php

namespace Altekno\StarterKit\Livewire\Starter\Logs;

use Altekno\StarterKit\Contracts\Starter\ActivityLogInterface;
use Altekno\StarterKit\Models\Starter\ActivityLog;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Support\Starter\ActivityLogFilters;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Log Aktivitas')]
class ActivityLogIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $eventFilter = '';

    public string $actorFilter = '';

    public string $roleFilter = '';

    public string $appFilter = '';

    public string $tableFilter = '';

    public string $routeFilter = '';

    public string $ipFilter = '';

    public string $actionFilter = '';

    public int $perPage = 25;

    public bool $detailModalOpen = false;

    public ?string $selectedActionId = null;

    private ActivityLogInterface $activityLogs;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(
        ActivityLogInterface $activityLogs,
        AuthenticatedLoginService $authenticatedLogins,
    ): void {
        $this->activityLogs = $activityLogs;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(): void
    {
        $this->login();
        $this->setDefaultDates();
    }

    public function updated(string $property): void
    {
        if ($property === 'perPage' && ! in_array($this->perPage, [25, 50, 100], true)) {
            $this->perPage = 25;
        }

        if (in_array($property, $this->filterProperties(), true)) {
            $this->resetPage('logsPage');
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'eventFilter',
            'actorFilter',
            'roleFilter',
            'appFilter',
            'tableFilter',
            'routeFilter',
            'ipFilter',
            'actionFilter',
        ]);
        $this->perPage = 25;
        $this->setDefaultDates();
        $this->resetPage('logsPage');
    }

    #[On('starter-log-detail-request')]
    public function showActionDetail(string $actionId): void
    {
        abort_unless(preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $actionId) === 1, 404);
        abort_unless($this->activityLogs->actionExistsForViewer($this->login(), $actionId), 404);

        $this->selectedActionId = $actionId;
        $this->detailModalOpen = true;
    }

    public function closeActionDetail(): void
    {
        $this->detailModalOpen = false;
        $this->selectedActionId = null;
    }

    public function render()
    {
        $login = $this->login();
        $actionPage = $this->activityLogs->paginateActionsForViewer(
            $login,
            $this->filters(),
            $this->perPage,
            'logsPage',
        );
        $actionIds = $actionPage->getCollection()->pluck('action_id');
        $entries = $this->activityLogs->entriesGroupedByActionForViewer($login, $actionIds);

        $actionPage->setCollection($actionPage->getCollection()->map(
            fn (ActivityLog $summary): array => $this->actionSummary($summary, $entries->get($summary->action_id, collect()), $login),
        ));

        $metrics = $this->activityLogs->metricsForViewer($login);
        $selectedLogs = $this->selectedActionId
            ? $this->activityLogs->entriesForActionForViewer($login, $this->selectedActionId)
            : collect();

        return view('starter.logs.activity-log-index', [
            'actions' => $actionPage,
            'selectedLogs' => $selectedLogs,
            'filterOptions' => $this->activityLogs->filterOptionsForViewer($login),
            'totalChanges' => $metrics['total_changes'],
            'todayChanges' => $metrics['today_changes'],
            'activeActorCount' => $metrics['active_actor_count'],
        ]);
    }

    /**
     * @param  Collection<int, ActivityLog>  $entries
     * @return array<string, mixed>
     */
    private function actionSummary(ActivityLog $summary, Collection $entries, ClientLogin $viewer): array
    {
        $first = $entries->first();
        $actorIsMasked = ! $viewer->role?->isSuperuser() && ($first?->actor_is_superuser ?? false);

        return [
            'action_id' => $summary->action_id,
            'created_at' => $first?->created_at,
            'action_key' => $first?->action_key,
            'action_label' => $first?->action_label ?: 'Aktivitas data',
            'actor_name' => $actorIsMasked ? 'Sistem' : ($first?->actor_name ?: 'User tidak tersedia'),
            'actor_username' => $actorIsMasked ? null : $first?->actor_username,
            'actor_role' => $actorIsMasked ? 'Sistem' : ($first?->actor_role ?: '-'),
            'changes_count' => (int) $summary->changes_count,
            'tables_count' => (int) $summary->tables_count,
            'events' => $entries->pluck('event')->filter()->unique()->values()->all(),
            'app_key' => $first?->app_key,
            'route_name' => $first?->route_name,
            'ip_address' => $first?->ip_address,
            'source' => $first?->source,
        ];
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->logViewer();
    }

    private function setDefaultDates(): void
    {
        $this->dateFrom = now()->subDays(29)->toDateString();
        $this->dateTo = now()->toDateString();
    }

    private function filters(): ActivityLogFilters
    {
        return ActivityLogFilters::fromInput(
            search: $this->search,
            dateFrom: $this->dateFrom,
            dateTo: $this->dateTo,
            event: $this->eventFilter,
            actor: $this->actorFilter,
            role: $this->roleFilter,
            app: $this->appFilter,
            table: $this->tableFilter,
            route: $this->routeFilter,
            ipPrefix: $this->ipFilter,
            actionPrefix: $this->actionFilter,
        );
    }

    /**
     * @return list<string>
     */
    private function filterProperties(): array
    {
        return [
            'search',
            'dateFrom',
            'dateTo',
            'eventFilter',
            'actorFilter',
            'roleFilter',
            'appFilter',
            'tableFilter',
            'routeFilter',
            'ipFilter',
            'actionFilter',
            'perPage',
        ];
    }
}
