<?php

namespace Altekno\StarterKit\Livewire\Starter\UserManagement;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Models\Starter\ClientRole;
use Altekno\StarterKit\Services\Starter\AuthenticatedLoginService;
use Altekno\StarterKit\Services\Starter\UserManagementUserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

class UsersTable extends PowerGridComponent
{
    public string $tableName = 'starter-users-table';

    public string $archiveStatus = 'active';

    public bool $showFilters = true;

    public ?string $pendingAction = null;

    /** @var list<int> */
    public array $pendingIds = [];

    private UserManagementUserService $users;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(UserManagementUserService $users, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->users = $users;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function setUp(): array
    {
        $this->showCheckBox();
        $this->persist(['filters', 'sorting']);

        return [
            PowerGrid::header()->includeViewOnTop('starter.user-management.powergrid.users-toolbar'),
            PowerGrid::footer()->showPerPage(10, [10, 25, 50, 100])->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return $this->users->tableQuery($this->login(), $this->archiveStatus);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('username')
            ->add('email')
            ->add('role_name')
            ->add('status')
            ->add('status_label', fn (ClientLogin $login): string => match ($login->status) {
                'active' => 'Aktif',
                'inactive' => 'Nonaktif',
                'locked' => 'Terkunci',
                default => ucfirst($login->status),
            })
            ->add('last_login_at')
            ->add('last_login_label', fn (ClientLogin $login): string => $login->last_login_at?->format('d M Y H:i') ?? 'Belum pernah')
            ->add('deleted_at');
    }

    public function columns(): array
    {
        return [
            Column::action('Aksi'),
            Column::make('Nama', 'name', 'starter_client_logins.name')->searchable()->sortable(),
            Column::make('Username', 'username', 'starter_client_logins.username')->searchable()->sortable(),
            Column::make('Email', 'email', 'starter_client_logins.email')->searchable()->sortable(),
            Column::make('Role', 'role_name', 'list_role.name')->searchable()->sortable(),
            Column::make('Status', 'status_label', 'starter_client_logins.status')->sortable(),
            Column::make('Login terakhir', 'last_login_label', 'starter_client_logins.last_login_at')->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('name', 'starter_client_logins.name')->operators(['contains']),
            Filter::inputText('username', 'starter_client_logins.username')->operators(['contains']),
            Filter::inputText('email', 'starter_client_logins.email')->operators(['contains']),
            Filter::select('role_name', 'list_role.name')->dataSource(
                $this->users->roles($this->login())
                    ->map(fn (ClientRole $role): array => ['value' => $role->name, 'label' => $role->name]),
            )->optionValue('value')->optionLabel('label'),
            Filter::select('status_label', 'starter_client_logins.status')->dataSource([
                ['value' => 'active', 'label' => 'Aktif'],
                ['value' => 'inactive', 'label' => 'Nonaktif'],
                ['value' => 'locked', 'label' => 'Terkunci'],
            ])->optionValue('value')->optionLabel('label'),
            Filter::datetimepicker('last_login_label', 'starter_client_logins.last_login_at')->params(['mode' => 'range']),
        ];
    }

    public function actionsFromView(ClientLogin $row): View
    {
        return view('starter.user-management.powergrid.users-row-actions', ['row' => $row]);
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
            'archive' => $this->users->archiveUsers($this->login(), $this->pendingIds),
            'restore' => $this->users->restoreUsers($this->login(), $this->pendingIds),
            'forceDelete' => $this->users->forceDeleteUsers($this->login(), $this->pendingIds),
            default => 0,
        };

        $this->checkboxValues = [];
        $this->cancelPendingAction();
        $this->dispatch('starter-toast', type: $changed > 0 ? 'success' : 'warning', message: $changed > 0 ? "{$changed} user berhasil diproses." : 'Tidak ada user yang dapat diproses.');
    }

    private function prepareAction(string $action, array $ids): void
    {
        abort_unless(in_array($action, ['archive', 'restore', 'forceDelete'], true), 422);

        $this->pendingIds = collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values()->all();
        $this->pendingAction = $this->pendingIds === [] ? null : $action;
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->settingsManager();
    }
}
