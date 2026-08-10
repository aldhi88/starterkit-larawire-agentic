<?php

namespace Altekno\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    public function clientLogins(): HasMany
    {
        return $this->hasMany(ClientLogin::class);
    }

    /**
     * Get all modules allowed for this role.
     */
    public function mods(): BelongsToMany
    {
        return $this->belongsToMany(AppMod::class, 'pivot_client_roles_app_mods')
            ->withTimestamps();
    }

    /**
     * Get app default page menus configured for this role.
     */
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
