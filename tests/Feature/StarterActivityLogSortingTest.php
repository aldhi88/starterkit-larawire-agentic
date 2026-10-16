<?php

use Aldhi88\StarterKit\Contracts\Starter\ActivityLogInterface;
use Aldhi88\StarterKit\Livewire\Starter\Logs\ActivityLogsTable;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Starter\ActivityLogRepository;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Livewire\LivewireServiceProvider;
use PowerComponents\LivewirePowerGrid\DataSource\Processors\Database\Pipelines\Sorting;
use PowerComponents\LivewirePowerGrid\Providers\PowerGridServiceProvider;
use PowerComponents\LivewirePowerGrid\Themes\Bootstrap5;

function activitySortingTable(): ActivityLogsTable
{
    app()->register(LivewireServiceProvider::class);
    app()->register(PowerGridServiceProvider::class);
    config(['livewire-powergrid.theme' => Bootstrap5::class, 'livewire-powergrid.persist_driver' => 'session']);

    $viewer = new ClientLogin;
    $viewer->setRelation('role', new ClientRole(['code' => 'superuser']));
    $authentication = Mockery::mock(AuthenticatedLoginService::class);
    $authentication->shouldReceive('logViewer')->andReturn($viewer);
    $repository = Mockery::mock(ActivityLogInterface::class);
    $repository->shouldReceive('filterOptionsForViewer')->andReturn([
        'actors' => collect(), 'roles' => collect(), 'apps' => collect(), 'routes' => collect(),
    ]);
    $repository->shouldReceive('tableQueryForViewer')->andReturnUsing(
        fn () => (new ActivityLogRepository)->tableQueryForViewer($viewer),
    );

    $table = new ActivityLogsTable;
    $table->boot($repository, $authentication);
    $table->mount();

    return $table;
}

it('orders grouped activity by the latest timestamp before paginating rather than insertion ID', function (): void {
    Schema::create('starter_client_logins', fn (Blueprint $table) => $table->id());
    (require StarterPaths::path('database/migrations/starter/2026_07_27_185235_create_starter_logs_table.php'))->up();

    foreach ([
        [1, 'newest', '2026-10-16 15:30:00'],
        [2, 'oldest', '2026-10-15 23:59:59'],
        [3, 'middle', '2026-10-16 09:00:00'],
        [4, 'middle', '2026-10-16 09:00:01'],
    ] as [$id, $action, $time]) {
        DB::table('starter_logs')->insert([
            'id' => $id, 'action_id' => $action, 'action_key' => 'test.browse',
            'action_label' => $action, 'event' => 'browse', 'auditable_type' => 'test',
            'auditable_id' => '1', 'created_at' => $time,
        ]);
    }

    $table = activitySortingTable();
    $query = (new Sorting($table))->handle($table->datasource(), fn ($query) => $query);
    $firstPage = (clone $query)->paginate(2);
    $secondPage = (clone $query)->paginate(2, ['*'], 'page', 2);

    expect($firstPage->pluck('action_id')->all())->toBe(['newest', 'middle'])
        ->and($secondPage->pluck('action_id')->all())->toBe(['oldest']);

    $table->sortBy('created_at');
    $ascending = (new Sorting($table))->handle($table->datasource(), fn ($query) => $query);
    expect($ascending->pluck('action_id')->all())->toBe(['oldest', 'middle', 'newest']);
});

it('repairs the persisted implicit ID sort while keeping filters and explicit column sorting', function (string $field, string $direction, string $expectedField, string $expectedDirection): void {
    $filters = ['input_text' => ['action_label' => 'Login']];
    Session::put('pg:starter-activity-logs-table', json_encode([
        'sortField' => $field, 'sortDirection' => $direction, 'sortArray' => [], 'multiSort' => false,
        'filters' => $filters, 'enabledFilters' => [],
    ], JSON_THROW_ON_ERROR));

    $table = activitySortingTable();
    expect($table->sortField)->toBe($expectedField)
        ->and($table->sortDirection)->toBe($expectedDirection)
        ->and($table->filters)->toBe($filters);

    $restored = activitySortingTable();
    expect($restored->sortField)->toBe($expectedField)
        ->and($restored->sortDirection)->toBe($expectedDirection);
})->with([
    ['id', 'asc', 'created_at', 'desc'],
    ['created_at', 'asc', 'created_at', 'asc'],
    ['action_label', 'desc', 'action_label', 'desc'],
]);
