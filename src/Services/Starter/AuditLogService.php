<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Altekno\StarterKit\Models\Starter\ActivityLog;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
    ];

    /**
     * @var array{id: string, key: string, label: string, sequence: int}|null
     */
    private ?array $actionContext = null;

    private ?string $requestId = null;

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
            return $callback();
        } finally {
            $this->actionContext = $previousContext;
        }
    }

    public function recordModelEvent(string $event, Model $model): void
    {
        $login = Auth::user();

        if (! $login instanceof ClientLogin || $model instanceof ActivityLog || $model->getTable() === 'starter_logs') {
            return;
        }

        [$oldValues, $newValues] = $this->modelChanges($event, $model);

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
        $login = Auth::user();

        if (! $login instanceof ClientLogin) {
            return;
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

        try {
            $this->insert(
                login: $actor,
                event: 'security',
                auditableType: ClientLogin::class,
                auditableId: $target ? (string) $target->getKey() : 'unknown',
                auditableLabel: $target?->name ?? 'Akun tidak dikenal',
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
    ): void {
        $request = $this->application->runningInConsole() ? null : request();
        $role = $login ? $this->clientLogins->loadRole($login)->role : null;
        $action = $this->action($event, $auditableType, $actionLabel, $actionKey);

        DB::table('starter_logs')->insert([
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
            'source' => $this->source($request),
            'created_at' => now(),
        ]);

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
        $verb = match ($event) {
            'created' => 'Membuat',
            'deleted' => 'Menghapus',
            'restored' => 'Memulihkan',
            default => 'Mengubah',
        };
        $subject = $auditableLabel ?: Str::headline(class_basename($auditableType));

        return "{$verb} {$subject}";
    }

    private function modelLabel(Model $model): ?string
    {
        foreach (['name', 'title', 'label', 'username', 'code', 'email'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (filled($value)) {
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
