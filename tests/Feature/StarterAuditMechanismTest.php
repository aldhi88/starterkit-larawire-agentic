<?php

use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Starter\ActivityLogRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Support\Starter\ActivityLogPresentation;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditTestRecord extends Model
{
    protected $table = 'audit_test_records';

    protected $guarded = [];
}

beforeEach(function (): void {
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_07_27_185235_create_starter_logs_table.php', '2026_11_02_000000_add_access_aggregation_to_starter_logs.php'] as $migration) {
        (require StarterPaths::path('database/migrations/starter/'.$migration))->up();
    }
    Schema::create('audit_test_records', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('last_seen_at')->nullable();
        $table->string('api_token')->nullable();
        $table->timestamps();
    });
    $role = ClientRole::create(['code' => 'operator', 'name' => 'Operator']);
    $this->actor = ClientLogin::create(['name' => 'Operator', 'username' => 'operator', 'email' => 'operator@example.test', 'client_role_id' => $role->id]);
    $this->actingAs($this->actor);
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    $this->audit = app(AuditLogService::class);
    config(['app.key' => 'test-hmac-key', 'starter.audit.access_window_seconds' => 60, 'starter.audit.access_actions' => ['requests.browse']]);
    $this->travelTo(now()->setTime(12, 0, 5));
});

function auditRead(AuditLogService $audit, array $state = [], string $target = 'requests', string $session = 'private-session'): void
{
    $audit->recordAccess('requests.browse', 'Menelusuri request', 'Request', $target, state: $state, sessionScope: $session);
}

it('ignores only configured technical updates and preserves important and sensitive changes', function (): void {
    config(['starter.audit.models' => [AuditTestRecord::class => ['ignore_updated' => ['last_seen_at', 'api_token'], 'label' => 'Request']]]);
    $model = AuditTestRecord::create(['name' => 'CPS-2026-000123']);
    $model->update(['last_seen_at' => '1']);
    DB::enableQueryLog();
    $this->audit->recordModelEvent('updated', $model);
    expect(DB::getQueryLog())->toBe([]);
    DB::disableQueryLog();
    expect(ActivityLog::count())->toBe(0);
    AuditTestRecord::updated(fn (AuditTestRecord $record) => $this->audit->recordModelEvent('updated', $record));
    $model->update(['name' => 'CPS-2026-000124', 'last_seen_at' => '2']);
    $log = ActivityLog::firstOrFail();
    expect($log->old_values)->toBe(['name' => 'CPS-2026-000123'])
        ->and($log->new_values)->toBe(['name' => 'CPS-2026-000124'])
        ->and($log->action_label)->toBe('Mengubah Request — CPS-2026-000124');
    $model->update(['api_token' => 'never-store-me']);
    expect(ActivityLog::latest('id')->first()->new_values)->toBe(['api_token' => '[REDACTED]']);
    config(['starter.audit.models' => []]);
    $model->update(['last_seen_at' => '3']);
    expect(ActivityLog::count())->toBe(3);
});

it('coalesces canonical states with one write per occurrence and retains audit meaning without plaintext', function (): void {
    $this->actor->load('role');
    DB::enableQueryLog();
    for ($i = 0; $i < 10; $i++) {
        auditRead($this->audit, $i % 2 ? ['page' => 1, 'search' => 'sensitive-search'] : ['search' => 'sensitive-search', 'page' => 1]);
    }
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    expect(count($queries))->toBe(10); // Array cache; no lookup/lock query per repeat.
    $log = ActivityLog::firstOrFail();
    expect(ActivityLog::count())->toBe(1)->and($log->access_count)->toBe(10)
        ->and($log->last_access_at->equalTo($log->created_at))->toBeTrue()
        ->and($log->toJson())->not->toContain('sensitive-search', 'private-session');
    $summary = (new ActivityLogRepository)->tableQueryForViewer($this->actor)->firstOrFail();
    expect(ActivityLogPresentation::summary($summary))->toContain('10 akses (dirangkum)', 'Berhasil');
});

it('keeps meaningful changes and expired windows separate', function (string $change): void {
    auditRead($this->audit, ['filter' => 'open', 'page' => 1, 'sort' => 'name']);
    $state = ['filter' => 'open', 'page' => 1, 'sort' => 'name'];
    $target = 'requests';
    $session = 'private-session';
    match ($change) {
        'filter' => $state['filter'] = 'closed',
        'page' => $state['page'] = 2,
        'sort' => $state['sort'] = '-name',
        'target' => $target = 'other-request',
        'session' => $session = 'another-session',
        'interval' => $this->travel(60)->seconds(),
        'actor' => $this->actingAs(ClientLogin::create(['name' => 'Other', 'username' => 'other', 'email' => 'other@example.test', 'client_role_id' => $this->actor->client_role_id])),
    };
    auditRead($this->audit, $state, $target, $session);
    expect(ActivityLog::count())->toBe(2);
})->with(['filter', 'page', 'sort', 'target', 'session', 'interval', 'actor']);

it('requires opt in and never coalesces manual mutations approvals failures or security', function (): void {
    config(['starter.audit.access_window_seconds' => 0]);
    auditRead($this->audit);
    auditRead($this->audit);
    config(['starter.audit.access_window_seconds' => 60]);
    foreach (['updated', 'approved', 'rejected', 'deleted', 'failed', 'denied', 'queued', 'skipped'] as $event) {
        for ($i = 0; $i < 2; $i++) {
            $this->audit->recordManual($event, 'Request', 1, metadata: ['password' => 'private', 'nested' => ['session_id' => 'private', 'token' => 'private']]);
        }
    }
    for ($i = 0; $i < 2; $i++) {
        $this->audit->recordSecurityEvent('login.failed', 'Login gagal', actor: $this->actor);
    }
    expect(ActivityLog::count())->toBe(20)->and(ActivityLog::whereNotNull('access_fingerprint')->count())->toBe(0)
        ->and(ActivityLog::all()->toJson())->not->toContain('private');
});

it('keeps action sequences and rolls back business writes if audit persistence fails', function (): void {
    $this->audit->withinAction('request.approve', 'Menyetujui request', function (): void {
        $this->audit->recordManual('approved', 'Request', 1);
        auditRead($this->audit);
        $this->audit->recordManual('updated', 'Ledger', 2);
    });
    expect(ActivityLog::distinct()->count('action_id'))->toBe(1)
        ->and(ActivityLog::pluck('sequence')->all())->toBe([1, 2, 3])
        ->and(ActivityLog::whereNotNull('access_fingerprint')->count())->toBe(0);
    DB::statement("CREATE TRIGGER reject_audit BEFORE INSERT ON starter_logs BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
    expect(fn () => $this->audit->withinAction('request.create', 'Membuat request', function (): void {
        $model = AuditTestRecord::create(['name' => 'Must roll back']);
        $this->audit->recordModelEvent('created', $model);
    }))->toThrow(QueryException::class);
    expect(AuditTestRecord::count())->toBe(0)->and(ActivityLog::count())->toBe(3);
});

it('preserves automated initiators and distinguishes genuine system work', function (): void {
    $this->audit->withinAutomatedAction('job.run', 'Memproses request', fn () => $this->audit->recordManual('queued', 'Request', 1), $this->actor);
    $this->audit->withinAutomatedAction('job.scheduled', 'Memproses terjadwal', fn () => $this->audit->recordManual('updated', 'Request', 2));
    $logs = ActivityLog::orderBy('id')->get();
    expect($logs->pluck('client_login_id')->all())->toBe([$this->actor->id, null])
        ->and($logs->pluck('source')->all())->toBe(['automated', 'automated']);
    expect((new ActivityLogRepository)->tableQueryForViewer($this->actor)->where('category', 'automated')->count())->toBe(2);
});

it('summarizes single multiple historical and mixed results without choosing a representative target', function (): void {
    $this->audit->withinAction('approve', 'Menyetujui request', fn () => $this->audit->recordManual('approved', 'Request', 1, auditableLabel: 'CPS-2026-000123'));
    $this->audit->withinAction('bulk', 'Memproses request', function (): void {
        $this->audit->recordManual('updated', 'Request', 1, tableName: 'requests', auditableLabel: 'CPS-A', metadata: ['outcome' => 'success']);
        $this->audit->recordManual('failed', 'Ledger', 2, tableName: 'ledger', auditableLabel: 'LEDGER-B', metadata: ['outcome' => 'failed']);
    });
    $this->audit->withinAction('history', 'Aktivitas lama', fn () => $this->audit->recordManual('unknown', 'Request', 3));
    $this->audit->withinAction('already', 'Mengubah CPS-123', fn () => $this->audit->recordManual('updated', 'Request', 4, auditableLabel: 'CPS-123'));
    $query = (new ActivityLogRepository)->tableQueryForViewer($this->actor);
    $summaries = $query->get()->keyBy('action_key')->map(fn ($log) => ActivityLogPresentation::summary($log));
    expect($summaries['approve'])->toBe('Menyetujui request — CPS-2026-000123 · Berhasil')
        ->and($summaries['bulk'])->toBe('Memproses request — beberapa objek/tabel · Hasil beragam')
        ->and($summaries['history'])->toBe('Aktivitas lama')
        ->and($summaries['already'])->toBe('Mengubah CPS-123 · Berhasil')
        ->and((clone $query)->where('action_label', 'like', '%CPS-2026%')->count())->toBe(1);
    DB::enableQueryLog();
    $query->get()->each(fn ($log) => ActivityLogPresentation::summary($log));
    expect(DB::getQueryLog())->toHaveCount(1);
    DB::disableQueryLog();
});

it('does not add database cache queries when aggregating compared with ordinary recording', function (): void {
    Schema::create('audit_test_cache', function (Blueprint $table): void {
        $table->string('key')->primary();
        $table->mediumText('value');
        $table->integer('expiration');
    });
    config(['cache.default' => 'database', 'cache.stores.database.table' => 'audit_test_cache']);
    $this->actor->load('role');
    DB::enableQueryLog();
    for ($i = 0; $i < 10; $i++) {
        $this->audit->recordManual('browse', 'Request', 'list');
    }
    $ordinary = count(DB::getQueryLog());
    DB::flushQueryLog();
    for ($i = 0; $i < 10; $i++) {
        auditRead($this->audit);
    }
    $aggregated = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($ordinary)->toBe(30)->and($aggregated)->toBe(30)
        ->and(ActivityLog::where('event', 'browse')->count())->toBe(10)
        ->and(ActivityLog::where('event', 'access')->count())->toBe(1);
});

it('keeps unlisted access keys separate and preserves all five outcome meanings', function (): void {
    config(['starter.audit.access_actions' => []]);
    auditRead($this->audit);
    auditRead($this->audit);
    expect(ActivityLog::count())->toBe(2);
    foreach (['success', 'failed', 'denied', 'queued', 'skipped'] as $outcome) {
        $this->audit->withinAction('result.'.$outcome, 'Memproses request', fn () => $this->audit->recordManual('custom', 'Request', 1, metadata: ['outcome' => $outcome]));
    }
    $summaries = (new ActivityLogRepository)->tableQueryForViewer($this->actor)->get()->keyBy('action_key');
    foreach (['success' => 'Berhasil', 'failed' => 'Gagal', 'denied' => 'Ditolak', 'queued' => 'Diantrekan', 'skipped' => 'Dilewati'] as $outcome => $label) {
        expect(ActivityLogPresentation::summary($summaries['result.'.$outcome]))->toBe('Memproses request · '.$label);
    }
});

it('expands an existing log table without rewriting historical evidence', function (): void {
    $migration = require StarterPaths::path('database/migrations/starter/2026_11_02_000000_add_access_aggregation_to_starter_logs.php');
    $migration->down();
    $this->audit->recordManual('updated', 'Request', 1, newValues: ['status' => 'approved']);
    $before = (array) DB::table('starter_logs')->first();
    $migration->up();
    $after = (array) DB::table('starter_logs')->first();
    expect(array_intersect_key($after, $before))->toBe($before)
        ->and($after['access_count'])->toBeNull()->and($after['access_fingerprint'])->toBeNull();
});

it('continues queued work in its original action with ordered evidence and rejects mismatched actors', function (): void {
    $this->audit->withinAction('request.process', 'Memproses request', fn () => $this->audit->recordManual('queued', 'Request', 1));
    $action = ActivityLog::firstOrFail()->action_id;
    $this->audit->withinAutomatedAction('request.process', 'Memproses request', function (): void {
        $this->audit->recordManual('updated', 'Request', 1);
        $this->audit->recordManual('updated', 'Ledger', 2);
    }, $this->actor, $action);
    expect(ActivityLog::distinct()->count('action_id'))->toBe(1)
        ->and(ActivityLog::pluck('sequence')->all())->toBe([1, 2, 3])
        ->and(ActivityLog::where('source', 'automated')->count())->toBe(2);
    expect(fn () => $this->audit->withinAutomatedAction('request.process', 'Memproses request', fn () => null, actionId: $action))->toThrow(InvalidArgumentException::class);
    $this->audit->recordManual('updated', 'Request', 3);
    expect(ActivityLog::latest('id')->first()->source)->toBe('console');
});
