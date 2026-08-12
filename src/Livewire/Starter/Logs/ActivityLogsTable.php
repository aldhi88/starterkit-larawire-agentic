<?php

namespace Aldhi88\StarterKit\Livewire\Starter\Logs;

use Aldhi88\StarterKit\Contracts\Starter\ActivityLogInterface;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

class ActivityLogsTable extends PowerGridComponent
{
    public string $tableName = 'starter-activity-logs-table';

    public string $primaryKey = 'action_id';

    public bool $showFilters = true;

    private ActivityLogInterface $activityLogs;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(ActivityLogInterface $activityLogs, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->activityLogs = $activityLogs;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function setUp(): array
    {
        $this->persist(['filters', 'sorting']);

        return [
            PowerGrid::header()->includeViewOnTop('starter.logs.powergrid.activity-logs-toolbar'),
            PowerGrid::footer()->showPerPage(25, [25, 50, 100])->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return $this->activityLogs->tableQueryForViewer($this->login());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('action_id')->add('action_label')->add('action_key')->add('actor_name')->add('actor_role')
            ->add('app_key')->add('route_name')->add('ip_address')->add('changes_count')->add('tables_count')
            ->add('created_at')
            ->add('created_at_label', fn (ActivityLog $log): string => $log->created_at->format('d M Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::action('Aksi'),
            Column::make('Waktu', 'created_at_label', 'created_at')->sortable(),
            Column::make('Aktivitas', 'action_label')->searchable()->sortable(),
            Column::make('Aktor', 'actor_name')->searchable()->sortable(),
            Column::make('Role', 'actor_role')->searchable()->sortable(),
            Column::make('Aplikasi', 'app_key')->searchable()->sortable(),
            Column::make('Route', 'route_name')->searchable()->sortable(),
            Column::make('IP', 'ip_address')->searchable()->sortable(),
            Column::make('Perubahan', 'changes_count')->sortable(),
            Column::make('Tabel', 'tables_count')->sortable(),
        ];
    }

    public function filters(): array
    {
        $options = $this->activityLogs->filterOptionsForViewer($this->login());

        return [
            Filter::datetimepicker('created_at_label', 'created_at')->params(['mode' => 'range']),
            Filter::inputText('action_label')->operators(['contains']),
            Filter::select('actor_name')->dataSource(
                $options['actors']
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->map(fn (string $name): array => ['value' => $name, 'label' => $name]),
            )->optionValue('value')->optionLabel('label'),
            Filter::select('actor_role')->dataSource($this->selectOptions($options['roles']))
                ->optionValue('value')->optionLabel('label'),
            Filter::select('app_key')->dataSource($this->selectOptions($options['apps']))
                ->optionValue('value')->optionLabel('label'),
            Filter::select('route_name')->dataSource($this->selectOptions($options['routes']))
                ->optionValue('value')->optionLabel('label'),
            Filter::inputText('ip_address')->operators(['starts_with']),
            Filter::number('changes_count')->placeholder('Min', 'Max'),
            Filter::number('tables_count')->placeholder('Min', 'Max'),
        ];
    }

    public function actionsFromView(ActivityLog $row): View
    {
        return view(StarterTheme::viewName('starter.logs.powergrid.logs-row-actions'), ['row' => $row]);
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->logViewer();
    }

    private function selectOptions(Collection $values): Collection
    {
        return $values->map(fn (string $value): array => ['value' => $value, 'label' => $value]);
    }
}
