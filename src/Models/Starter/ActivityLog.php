<?php

namespace Aldhi88\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $client_login_id
 * @property string $action_id
 * @property string|null $request_id
 * @property int $sequence
 * @property string $action_key
 * @property string|null $action_label
 * @property string|null $actor_name
 * @property string|null $actor_username
 * @property string|null $actor_role
 * @property bool $actor_is_superuser
 * @property string $event
 * @property string|null $table_name
 * @property string $auditable_type
 * @property string $auditable_id
 * @property string|null $auditable_label
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property array<string, mixed>|null $metadata
 * @property string|null $route_name
 * @property string|null $request_method
 * @property string|null $request_path
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $source
 * @property string|null $app_key
 * @property Carbon $created_at
 * @property Carbon|null $last_activity_at
 * @property int|string $changes_count
 * @property int|string $tables_count
 * @property int|string $total_changes
 * @property int|string $today_changes
 * @property int|string $active_actor_count
 * @property-read ClientLogin|null $login
 */
class ActivityLog extends Model
{
    public const FILTER_OPTIONS_CACHE_KEY = 'starter.activity-log.filter-options';

    public $timestamps = false;

    protected $table = 'starter_logs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'actor_is_superuser' => 'boolean',
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ClientLogin, $this> */
    public function login(): BelongsTo
    {
        return $this->belongsTo(ClientLogin::class, 'client_login_id');
    }
}
