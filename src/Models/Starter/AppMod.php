<?php

namespace Altekno\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'desc', 'app_id'])]
class AppMod extends Model
{
    protected $table = 'starter_app_mods';

    /**
     * Get the app that owns this module.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * Get all routes under this module.
     */
    public function routes(): HasMany
    {
        return $this->hasMany(AppRoute::class);
    }

    /**
     * Get all menus under this module.
     */
    public function menus(): HasMany
    {
        return $this->hasMany(AppMenu::class);
    }

    /**
     * Get all roles allowed to access this module.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(ClientRole::class, 'pivot_client_roles_app_mods')
            ->withTimestamps();
    }
}
