<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Models\Starter\ActivityLog;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class AuditLogService
{
    /** @var list<string> */
    private const SENSITIVE_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'remember',
        'authorization',
        'cookie',
        'session',
        'credential',
        'api_key',
        'private_key',
    ];

    /**
     * @var array{id: string, key: string, label: string, sequence: int}|null
     */
    private ?array $actionContext = null;

    private ?string $requestId = null;

    private bool $automated = false;

    private ?ClientLogin $initiator = null;

    public function __construct(
        private readonly Application $application,
        private readonly ClientLoginInterface $clientLogins,
    ) {}

    public function withinAction(string $key, string $label, Closure $callback): mixed
    {
        $previousContext = $this->actionContext;
        $this->actionContext = [
            'id' => (string) Str::ulid(),
            'key' => $key,
            'label' => $label,
            'sequence' => 0,
        ];

        try {
            return DB::transaction($callback);
        } finally {
            $this->actionContext = $previousContext;
        }
    }

    /** Run background work with an explicit initiating actor; never borrow a worker's authentication. */
    public function withinAutomatedAction(string $key, string $label, Closure $callback, ?ClientLogin $initiator = null, ?string $actionId = null): mixed
    {
        $previous = [$this->automated, $this->initiator];
        $this->automated = true;
        $this->initiator = $initiator;

        try {
            if ($actionId !== null) {
                return DB::transaction(function () use ($actionId, $initiator, $key, $label, $callback): mixed {
                    // All continuations lock the first entry before assigning subsequent sequences.
                    $first = DB::table('starter_logs')->where('action_id', $actionId)->orderBy('sequence')->orderBy('id')->lockForUpdate()->first();
                    if ($first === null || (string) $first->client_login_id !== (string) $initiator?->getKey()
                        || $first->action_key !== $key || $first->action_label !== $label) {
                        throw new InvalidArgumentException('An automated continuation must match its original action and actor.');
                    }
                    $previousContext = $this->actionContext;
                    $this->actionContext = [
                        'id' => $actionId, 'key' => $key, 'label' => $label,
                        'sequence' => (int) DB::table('starter_logs')->where('action_id', $actionId)->max('sequence'),
                    ];
                    try {
                        return $callback();
                    } finally {
                        $this->actionContext = $previousContext;
                    }
                });
            }

            return $this->actionContext !== null ? DB::transaction($callback) : $this->withinAction($key, $label, $callback);
        } finally {
            [$this->automated, $this->initiator] = $previous;
        }
    }

    /**
     * Explicit successful read boundary. State is HMACed, never persisted as plaintext.
     * Fixed windows may split nearby reads at a boundary; they never extend indefinitely.
     *
     * @param  array<string, mixed>  $state  Normalized search/filter/sort/page state, without credentials.
     */
    public function recordAccess(string $key, string $label, string $auditableType, string|int $auditableId, ?string $auditableLabel = null, array $state = [], ?string $sessionScope = null): void
    {
        $login = $this->automated ? $this->initiator : Auth::user();
        if (! $login instanceof ClientLogin) {
            if (! $this->automated) {
                return;
            }
            $login = null;
        }
        $seconds = max(0, min(3600, (int) config('starter.audit.access_window_seconds', 0)));
        $allowed = config('starter.audit.access_actions', []);
        $sessionScope ??= request()->hasSession() ? request()->session()->getId() : null;
        $fingerprint = null;
        // Never merge into a multi-entry business action or an automated/security boundary.
        if ($login instanceof ClientLogin && $seconds > 0 && in_array($key, $allowed, true) && filled($sessionScope) && $this->actionContext === null && ! $this->automated) {
            $secret = (string) config('app.key');
            if ($secret === '') {
                throw new InvalidArgumentException('Access aggregation requires an application key.');
            }
            $fingerprint = hash_hmac('sha256', json_encode([
                $login->getKey(), $login->client_role_id, $this->clientLogins->loadRole($login)->role->isSuperuser(),
                $sessionScope, request()->getHost(), request()->path(), request()->route()?->getName(), $key, $label,
                $auditableType, (string) $auditableId, $auditableLabel,
                $this->canonicalState($state), intdiv(now()->timestamp, $seconds), $seconds,
            ], JSON_THROW_ON_ERROR), $secret);
        }
        $this->insert(
            login: $login, event: 'access', auditableType: $auditableType,
            auditableId: (string) $auditableId, auditableLabel: $auditableLabel,
            tableName: null, oldValues: [], newValues: [], actionLabel: $label,
            metadata: ['outcome' => 'success', 'aggregation' => $fingerprint ? 'fixed_window' : 'none', 'window_seconds' => $seconds],
            actionKey: $key, accessFingerprint: $fingerprint,
        );
    }

    /** @param array<mixed> $state @return array<mixed> */
    private function canonicalState(array $state): array
    {
        if (! array_is_list($state)) {
            ksort($state);
        }
        foreach ($state as $key => $value) {
            if (is_array($value)) {
                $state[$key] = $this->canonicalState($value);
            } elseif (! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Access state must contain only arrays and scalar values.');
            }
        }

        return $state;
    }

    public function recordModelEvent(string $event, Model $model): void
    {
        $login = $this->automated ? $this->initiator : Auth::user();

        if (! $login instanceof ClientLogin) {
            if (! $this->automated) {
                return;
            }
            $login = null;
        }

        if ($model instanceof ActivityLog || $model->getTable() === 'starter_logs') {
            return;
        }

        [$oldValues, $newValues] = $this->modelChanges($event, $model);

        if (str_starts_with($model->getTable(), 'cms_')) {
            $oldValues = $this->cmsAuditValues($oldValues);
            $newValues = $this->cmsAuditValues($newValues);
        }

        if ($event === 'updated' && $oldValues === [] && $newValues === []) {
            return;
        }

        $auditableLabel = $this->modelLabel($model);

        $this->insert(
            login: $login,
            event: $event,
            auditableType: $model::class,
            auditableId: (string) $model->getKey(),
            auditableLabel: $auditableLabel,
            tableName: $model->getTable(),
            oldValues: $oldValues,
            newValues: $newValues,
            actionLabel: $this->defaultActionLabel($event, $auditableLabel, $model::class),
        );
    }

    /**
     * Record a database change which does not emit Eloquent model events, such as a pivot sync.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function recordManual(
        string $event,
        string $auditableType,
        string|int $auditableId,
        array $oldValues = [],
        array $newValues = [],
        ?string $tableName = null,
        ?string $auditableLabel = null,
        array $metadata = [],
    ): void {
        $login = $this->automated ? $this->initiator : Auth::user();

        if (! $login instanceof ClientLogin) {
            if (! $this->automated) {
                return;
            }
            $login = null;
        }

        $this->insert(
            login: $login,
            event: $event,
            auditableType: $auditableType,
            auditableId: (string) $auditableId,
            auditableLabel: $auditableLabel,
            tableName: $tableName,
            oldValues: $this->sanitize($oldValues),
            newValues: $this->sanitize($newValues),
            metadata: $this->sanitize($metadata),
            actionLabel: $this->defaultActionLabel($event, $auditableLabel, $auditableType),
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordSecurityEvent(
        string $key,
        string $label,
        ?ClientLogin $target = null,
        ?ClientLogin $actor = null,
        array $metadata = [],
    ): void {
        if (! Schema::hasTable('starter_logs')) {
            return;
        }

        $targetId = 'unknown';
        $targetLabel = 'Akun tidak dikenal';

        if ($target instanceof ClientLogin) {
            $targetId = (string) $target->getKey();
            $targetLabel = $target->name;
        }

        try {
            $this->insert(
                login: $actor,
                event: 'security',
                auditableType: ClientLogin::class,
                auditableId: $targetId,
                auditableLabel: $targetLabel,
                tableName: 'starter_client_logins',
                oldValues: [],
                newValues: [],
                actionLabel: $label,
                metadata: $this->sanitize($metadata),
                actionKey: $key,
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function modelChanges(string $event, Model $model): array
    {
        if ($event === 'created') {
            return [[], $this->sanitize($model->getAttributes())];
        }

        if ($event === 'deleted') {
            return [$this->sanitize($model->getOriginal()), []];
        }

        $changes = $model->getChanges();
        unset($changes['updated_at']);
        $models = config('starter.audit.models', []);
        foreach (($models[$model::class]['ignore_updated'] ?? []) as $attribute) {
            // Sensitive/lifecycle changes must remain evidence even under a mistaken configuration.
            if (! $this->isSensitive($attribute) && ! in_array($attribute, ['deleted_at', 'created_by', 'updated_by'], true)) {
                unset($changes[$attribute]);
            }
        }

        $oldValues = [];

        foreach (array_keys($changes) as $key) {
            $oldValues[$key] = $model->getRawOriginal($key);
        }

        return [$this->sanitize($oldValues), $this->sanitize($changes)];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        unset($values['created_at'], $values['updated_at'], $values['deleted_at']);

        foreach ($values as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                $values[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }

    private function isSensitive(string $key): bool
    {
        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains(strtolower($key), $fragment)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function cmsAuditValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array($key, [
                'body', 'content', 'excerpt', 'description', 'caption',
                'author_email', 'email', 'path', 'original_name',
                'url', 'link_url', 'action_url', 'items',
            ], true)) {
                $values[$key] = $value === null ? null : '[REDACTED]';
            } elseif (is_string($value) && mb_strlen($value) > 512) {
                $values[$key] = '[CONTENT: '.mb_strlen($value).' characters]';
            } elseif (is_array($value)) {
                $values[$key] = $this->cmsAuditValues($value);
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    private function insert(
        ?ClientLogin $login,
        string $event,
        string $auditableType,
        string $auditableId,
        ?string $auditableLabel,
        ?string $tableName,
        array $oldValues,
        array $newValues,
        string $actionLabel,
        array $metadata = [],
        ?string $actionKey = null,
        ?string $accessFingerprint = null,
    ): void {
        $request = $this->application->runningInConsole() ? null : request();
        $role = $login ? $this->clientLogins->loadRole($login)->role : null;
        $action = $this->action($event, $auditableType, $actionLabel, $actionKey);

        $values = [
            'client_login_id' => $login?->getKey(),
            'actor_name' => $login?->name,
            'actor_username' => $login?->username,
            'actor_role' => $role?->name,
            'actor_is_superuser' => $role?->isSuperuser() ?? false,
            'action_id' => $action['id'],
            'request_id' => $this->requestId ??= (string) Str::ulid(),
            'sequence' => $action['sequence'],
            'action_key' => $action['key'],
            'action_label' => $action['label'],
            'event' => $event,
            'table_name' => $tableName,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'auditable_label' => $auditableLabel,
            'old_values' => $oldValues === [] ? null : json_encode($oldValues, JSON_THROW_ON_ERROR),
            'new_values' => $newValues === [] ? null : json_encode($newValues, JSON_THROW_ON_ERROR),
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
            'app_key' => $this->appKey($request),
            'route_name' => $request?->route()?->getName(),
            'request_method' => $request?->method(),
            'request_path' => $request ? '/'.ltrim($request->path(), '/') : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'source' => $this->automated ? 'automated' : $this->source($request),
            'created_at' => now(),
        ];

        if ($accessFingerprint !== null) {
            $values['access_fingerprint'] = $accessFingerprint;
            $values['access_count'] = 1;
            $values['last_access_at'] = now();
            DB::table('starter_logs')->upsert([$values], ['access_fingerprint'], [
                'access_count' => DB::raw('access_count + 1'), 'last_access_at',
            ]);
        } else {
            DB::table('starter_logs')->insert($values);
        }

        Cache::forget(ActivityLog::FILTER_OPTIONS_CACHE_KEY.'.superuser');
        Cache::forget(ActivityLog::FILTER_OPTIONS_CACHE_KEY.'.delegated');
    }

    /**
     * @return array{id: string, key: string, label: string, sequence: int}
     */
    private function action(string $event, string $auditableType, string $fallbackLabel, ?string $actionKey = null): array
    {
        if ($this->actionContext === null) {
            return [
                'id' => (string) Str::ulid(),
                'key' => $actionKey ?? $this->defaultActionKey($event, $auditableType),
                'label' => $fallbackLabel,
                'sequence' => 1,
            ];
        }

        $this->actionContext['sequence']++;

        return $this->actionContext;
    }

    private function defaultActionKey(string $event, string $auditableType): string
    {
        return Str::of(class_basename($auditableType))
            ->snake()
            ->append('.'.$event)
            ->toString();
    }

    private function defaultActionLabel(string $event, ?string $auditableLabel, string $auditableType): string
    {
        $verb = config('starter.audit.event_labels', [])[$event] ?? match ($event) {
            'created' => 'Membuat',
            'deleted' => 'Menghapus',
            'restored' => 'Memulihkan',
            'updated' => 'Mengubah',
            'access', 'browse', 'view' => 'Mengakses',
            'approved' => 'Menyetujui',
            'rejected', 'denied' => 'Menolak',
            'failed' => 'Gagal memproses',
            'queued' => 'Mengantrekan',
            'skipped' => 'Melewati',
            default => 'Aktivitas',
        };
        $models = config('starter.audit.models', []);
        $subject = $models[$auditableType]['label'] ?? ($auditableLabel ?: Str::headline(class_basename($auditableType)));
        if (isset($models[$auditableType]['label']) && filled($auditableLabel)) {
            $subject .= ' — '.$auditableLabel;
        }

        return Str::limit("{$verb} {$subject}", 255, '');
    }

    private function modelLabel(Model $model): ?string
    {
        $models = config('starter.audit.models', []);
        foreach (($models[$model::class]['label_attributes'] ?? ['name', 'title', 'label', 'username', 'code', 'email']) as $attribute) {
            if ($this->isSensitive($attribute)) {
                continue;
            }
            $value = isset($models[$model::class]['label_attributes'])
                ? ($model->getAttributes()[$attribute] ?? null)
                : $model->getAttribute($attribute);

            if (is_scalar($value) && filled($value)) {
                return Str::limit((string) $value, 255, '');
            }
        }

        return null;
    }

    private function appKey(mixed $request): ?string
    {
        if (! $request) {
            return null;
        }

        $host = $request->getHost();
        $domain = (string) config('app.domain');

        if ($domain !== '' && $host !== $domain && str_ends_with($host, '.'.$domain)) {
            return Str::before($host, '.'.$domain);
        }

        return $request->hasSession()
            ? $request->session()->get('starter.current_app_key')
            : null;
    }

    private function source(mixed $request): string
    {
        if ($this->application->runningInConsole()) {
            return 'console';
        }

        return $request?->is('api/*') ? 'api' : 'web';
    }
}
