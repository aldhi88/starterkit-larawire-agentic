<?php

namespace Aldhi88\StarterKit\Livewire\Starter\UserManagement;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Services\Starter\UserManagementRoleService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

class RolesTable extends PowerGridComponent
{
    public string $tableName = 'starter-roles-table';

    public string $archiveStatus = 'active';

    public bool $showFilters = true;

    public ?string $pendingAction = null;

    /** @var list<int> */
    public array $pendingIds = [];

    private UserManagementRoleService $roles;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(UserManagementRoleService $roles, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->roles = $roles;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function setUp(): array
    {
        $this->showCheckBox();
        $this->persist(['filters', 'sorting']);

        return [
            PowerGrid::header()->includeViewOnTop('starter.user-management.powergrid.roles-toolbar'),
            PowerGrid::footer()->showPerPage(10, [10, 25, 50, 100])->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return $this->roles->tableQuery($this->login(), $this->archiveStatus);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')->add('name')->add('code')->add('desc')->add('client_logins_count')->add('mods_count')
            ->add('settings_label', fn (ClientRole $role): string => $role->canManageSettings() ? 'Ya' : 'Tidak')
            ->add('logs_label', fn (ClientRole $role): string => $role->canViewLogs() ? 'Ya' : 'Tidak')
            ->add('deleted_at');
    }

    public function columns(): array
    {
        return [
            Column::action('Aksi'),
            Column::make('Role', 'name')->searchable()->sortable(),
            Column::make('Kode', 'code')->searchable()->sortable(),
            Column::make('Deskripsi', 'desc')->searchable()->sortable(),
            Column::make('User', 'client_logins_count')->sortable(),
            Column::make('Modul', 'mods_count')->sortable(),
            Column::make('Pengaturan', 'settings_label', 'can_manage_settings')->sortable(),
            Column::make('Log', 'logs_label', 'can_view_logs')->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('name')->dataSource(
                $this->roles->filterOptions($this->login(), $this->archiveStatus)
                    ->map(fn (ClientRole $role): array => ['value' => $role->name, 'label' => $role->name]),
            )->optionValue('value')->optionLabel('label'),
            Filter::inputText('code')->operators(['contains']),
            Filter::inputText('desc')->operators(['contains']),
            Filter::number('client_logins_count')->placeholder('Min', 'Max')
                ->builder(fn (Builder $query, array $values): Builder => $this->filterRelationCount($query, 'clientLogins', $values)),
            Filter::number('mods_count')->placeholder('Min', 'Max')
                ->builder(fn (Builder $query, array $values): Builder => $this->filterRelationCount($query, 'mods', $values)),
            Filter::boolean('settings_label', 'can_manage_settings')->label('Ya', 'Tidak'),
            Filter::boolean('logs_label', 'can_view_logs')->label('Ya', 'Tidak'),
        ];
    }

    public function actionsFromView(ClientRole $row): View
    {
        return view(StarterTheme::viewName('starter.user-management.powergrid.roles-row-actions'), ['row' => $row]);
    }

    public function prepareBulkAction(string $action): void
    {
        $this->prepareAction($action, $this->checkboxValues);
    }

    #[On('prepare-row-action')]
    public function prepareRowAction(string $action, int $id): void
    {
        $this->prepareAction($action, [$id]);
    }

    public function cancelPendingAction(): void
    {
        $this->pendingAction = null;
        $this->pendingIds = [];
    }

    public function executePendingAction(): void
    {
        $changed = match ($this->pendingAction) {
            'archive' => $this->roles->archiveRoles($this->login(), $this->pendingIds),
            'restore' => $this->roles->restoreRoles($this->login(), $this->pendingIds),
            'forceDelete' => $this->roles->forceDeleteRoles($this->login(), $this->pendingIds),
            default => 0,
        };
        $this->checkboxValues = [];
        $this->cancelPendingAction();
        $this->dispatch('starter-toast', type: $changed > 0 ? 'success' : 'warning', message: $changed > 0 ? "{$changed} role berhasil diproses." : 'Tidak ada role yang dapat diproses; pastikan role bukan sistem dan tidak sedang digunakan.');
    }

    private function prepareAction(string $action, array $ids): void
    {
        abort_unless(in_array($action, ['archive', 'restore', 'forceDelete'], true), 422);
        $this->pendingIds = collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values()->all();
        $this->pendingAction = $this->pendingIds === [] ? null : $action;
    }

    /** @param array{start?: int|string, end?: int|string} $values */
    private function filterRelationCount(Builder $query, string $relation, array $values): Builder
    {
        if (filled($values['start'] ?? null)) {
            $query->has($relation, '>=', (int) $values['start']);
        }

        if (filled($values['end'] ?? null)) {
            $query->has($relation, '<=', (int) $values['end']);
        }

        return $query;
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->settingsManager();
    }
}
