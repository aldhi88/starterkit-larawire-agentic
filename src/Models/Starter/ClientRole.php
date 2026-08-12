<?php

namespace Aldhi88\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $desc
 * @property bool $is_system
 * @property bool $can_manage_settings
 * @property bool $can_view_logs
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, ClientLogin> $clientLogins
 * @property-read Collection<int, AppMod> $mods
 * @property-read Collection<int, ClientRoleAppLanding> $landings
 */
#[Fillable(['code', 'name', 'desc', 'is_system', 'can_manage_settings', 'can_view_logs'])]
class ClientRole extends Model
{
    use SoftDeletes;

    protected $table = 'starter_client_roles';

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'can_manage_settings' => 'boolean',
            'can_view_logs' => 'boolean',
        ];
    }

    /**
     * Get all login accounts assigned to this role.
     */
    /** @return HasMany<ClientLogin, $this> */
    public function clientLogins(): HasMany
    {
        return $this->hasMany(ClientLogin::class);
    }

    /**
     * Get all modules allowed for this role.
     */
    /** @return BelongsToMany<AppMod, $this> */
    public function mods(): BelongsToMany
    {
        return $this->belongsToMany(AppMod::class, 'pivot_client_roles_app_mods')
            ->withTimestamps();
    }

    /**
     * Get app default page menus configured for this role.
     */
    /** @return HasMany<ClientRoleAppLanding, $this> */
    public function landings(): HasMany
    {
        return $this->hasMany(ClientRoleAppLanding::class);
    }

    /**
     * Determine if this role bypasses module-based authorization.
     */
    public function hasFullAccess(): bool
    {
        return $this->isSuperuser();
    }

    /**
     * Determine if this role is the built-in administrator role.
     */
    public function isAdmin(): bool
    {
        return $this->isSuperuser();
    }

    public function isSuperuser(): bool
    {
        return $this->is_system || $this->code === 'superuser';
    }

    /**
     * Determine if this role can open and manage the application settings.
     */
    public function canManageSettings(): bool
    {
        return $this->isSuperuser() || $this->can_manage_settings;
    }

    public function canViewLogs(): bool
    {
        return $this->isSuperuser() || $this->can_view_logs;
    }
}
