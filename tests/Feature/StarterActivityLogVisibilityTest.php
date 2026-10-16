<?php

use Aldhi88\StarterKit\Livewire\Starter\Logs\ActivityLogIndex;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Starter\ActivityLogRepository;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Starter\ActivityLogFilters;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185235_create_starter_logs_table.php'] as $migration) {
        (require StarterPaths::path('database/migrations/starter/'.$migration))->up();
    }

    DB::table('starter_client_roles')->insert([
        ['id' => 1, 'code' => 'superuser', 'name' => 'Superuser', 'is_system' => false, 'deleted_at' => null],
        ['id' => 2, 'code' => 'operator', 'name' => 'Operator', 'is_system' => false, 'deleted_at' => null],
        ['id' => 3, 'code' => 'system-archived', 'name' => 'Archived System', 'is_system' => true, 'deleted_at' => now()],
    ]);
    foreach ([1 => 'Superuser', 2 => 'Operator', 3 => 'Archived System'] as $id => $name) {
        DB::table('starter_client_logins')->insert([
            'id' => $id, 'client_role_id' => $id, 'name' => $name,
            'username' => 'actor-'.$id, 'email' => 'actor-'.$id.'@example.test',
            'deleted_at' => $id === 3 ? now() : null,
        ]);
    }
});

function visibilityFilters(array $input = []): ActivityLogFilters
{
    return ActivityLogFilters::fromInput(...array_replace([
        'search' => '', 'dateFrom' => '', 'dateTo' => '', 'event' => '', 'actor' => '',
        'role' => '', 'app' => '', 'table' => '', 'route' => '', 'ipPrefix' => '', 'actionPrefix' => '',
    ], $input));
}

function visibilityLog(array $attributes = []): string
{
    $actionId = (string) Str::ulid();
    DB::table('starter_logs')->insert(array_replace([
        'action_id' => $actionId, 'client_login_id' => 2,
        'action_key' => 'test.browse', 'action_label' => 'Confidential activity',
        'actor_name' => 'Operator', 'actor_username' => 'actor-2', 'actor_role' => 'Operator',
        'actor_is_superuser' => false, 'event' => 'browse',
        'auditable_type' => 'BusinessRecord', 'auditable_id' => '1',
        'table_name' => 'confidential_table', 'app_key' => 'confidential_app',
        'route_name' => 'confidential.route', 'created_at' => now(),
    ], $attributes));

    return $attributes['action_id'] ?? $actionId;
}

it('hides complete Superuser actions from every delegated query while preserving them for Superuser', function (): void {
    $hidden = collect([
        visibilityLog(['client_login_id' => 1, 'actor_is_superuser' => true]),
        visibilityLog(['client_login_id' => 1]), // Legacy snapshot: current actor ID still protects it.
        visibilityLog(['client_login_id' => null, 'actor_is_superuser' => true]), // Deleted actor snapshot.
        visibilityLog(['auditable_type' => ClientLogin::class, 'auditable_id' => '1']),
        visibilityLog(['auditable_type' => ClientRole::class, 'auditable_id' => '1']),
        visibilityLog(['auditable_type' => ClientLogin::class, 'auditable_id' => '3']),
        visibilityLog(['auditable_type' => ClientRole::class, 'auditable_id' => '3']),
        visibilityLog(['client_login_id' => 3]),
    ]);
    // An otherwise ordinary sibling must not reveal a protected action's context.
    visibilityLog(['action_id' => $hidden->first(), 'sequence' => 2]);
    $publicAttributes = [
        'action_label' => 'Public activity', 'table_name' => 'public_table',
        'app_key' => 'public_app', 'route_name' => 'public.route',
    ];
    $visible = collect([
        visibilityLog($publicAttributes),
        visibilityLog([...$publicAttributes, 'client_login_id' => null, 'actor_name' => null, 'actor_username' => null, 'actor_role' => null]),
    ]);
    $operator = ClientLogin::findOrFail(2);
    $superuser = ClientLogin::findOrFail(1);
    $repository = new ActivityLogRepository;

    foreach ([$operator, $superuser] as $viewer) {
        $expected = $viewer->role->isSuperuser() ? $visible->merge($hidden) : $visible;
        expect($repository->tableQueryForViewer($viewer)->pluck('action_id')->all())->toEqualCanonicalizing($expected->all());
        $page = $repository->paginateActionsForViewer($viewer, visibilityFilters(), 25, 'logsPage');
        expect($page->total())->toBe($expected->count())
            ->and($page->pluck('action_id')->all())->toEqualCanonicalizing($expected->all());
        $groups = $repository->entriesGroupedByActionForViewer($viewer, $visible->merge($hidden));
        expect($groups->keys()->all())->toEqualCanonicalizing($expected->all());

        foreach ($hidden as $actionId) {
            expect($repository->actionExistsForViewer($viewer, $actionId))->toBe($viewer->role->isSuperuser())
                ->and($repository->entriesForActionForViewer($viewer, $actionId)->isNotEmpty())->toBe($viewer->role->isSuperuser());
        }

        $expectedChanges = $viewer->role->isSuperuser() ? 11 : 2;
        expect($repository->metricsForViewer($viewer))->toBe([
            'total_changes' => $expectedChanges, 'today_changes' => $expectedChanges,
            'active_actor_count' => $viewer->role->isSuperuser() ? 3 : 1,
        ]);
        $options = $repository->filterOptionsForViewer($viewer);
        expect($options['actors']->pluck('id')->all())->toBe($viewer->role->isSuperuser() ? [2, 1] : [2]);
        foreach (['apps' => 'confidential_app', 'tables' => 'confidential_table', 'routes' => 'confidential.route'] as $key => $secret) {
            expect($options[$key]->contains($secret))->toBe($viewer->role->isSuperuser());
        }
    }

    foreach ([['search' => 'Confidential'], ['actor' => '1'], ['actionPrefix' => $hidden->first()], ['route' => 'confidential.route']] as $input) {
        expect($repository->paginateActionsForViewer($operator, visibilityFilters($input), 25, 'logsPage')->total())->toBe(0);
    }
    expect(DB::table('starter_logs')->count())->toBe(11); // Visibility never deletes audit history.
});

it('rejects a forged protected detail request and ignores legacy cached filter options', function (): void {
    $actionId = visibilityLog(['actor_is_superuser' => true]);
    $operator = ClientLogin::findOrFail(2);
    $repository = new ActivityLogRepository;
    $authentication = Mockery::mock(AuthenticatedLoginService::class);
    $authentication->shouldReceive('logViewer')->andReturn($operator);
    $component = new ActivityLogIndex;
    $component->boot($repository, $authentication);

    try {
        $component->showActionDetail($actionId);
        test()->fail('Protected details must not be available by action ID.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(404)
            ->and($component->detailModalOpen)->toBeFalse()
            ->and($component->selectedActionId)->toBeNull();
    }

    Cache::put('starter.activity-log.filter-options.delegated', [
        'roles' => ['Superuser'], 'apps' => ['confidential_app'], 'tables' => ['confidential_table'], 'routes' => ['confidential.route'],
    ], 600);
    $options = $repository->filterOptionsForViewer($operator);
    foreach (['roles', 'apps', 'tables', 'routes'] as $key) {
        expect($options[$key])->toBeEmpty();
    }
});

it('keeps historical Superuser snapshots hidden when no protected account or role remains', function (): void {
    visibilityLog(['client_login_id' => null, 'actor_is_superuser' => true]);
    DB::table('starter_client_logins')->whereIn('id', [1, 3])->delete();
    DB::table('starter_client_roles')->whereIn('id', [1, 3])->delete();

    expect((new ActivityLogRepository)->tableQueryForViewer(ClientLogin::findOrFail(2))->count())->toBe(0);
});
