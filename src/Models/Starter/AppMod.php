<?php

namespace Aldhi88\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $desc
 * @property int $app_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|string $apps_count
 * @property int|string $modules_count
 * @property-read App $app
 * @property-read Collection<int, AppRoute> $routes
 * @property-read Collection<int, AppMenu> $menus
 * @property-read Collection<int, ClientRole> $roles
 */
#[Fillable(['code', 'name', 'desc', 'app_id'])]
class AppMod extends Model
{
    protected $table = 'starter_app_mods';

    /**
     * Get the app that owns this module.
     */
    /** @return BelongsTo<App, $this> */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * Get all routes under this module.
     */
    /** @return HasMany<AppRoute, $this> */
    public function routes(): HasMany
    {
        return $this->hasMany(AppRoute::class);
    }

    /**
     * Get all menus under this module.
     */
    /** @return HasMany<AppMenu, $this> */
    public function menus(): HasMany
    {
        return $this->hasMany(AppMenu::class);
    }

    /**
     * Get all roles allowed to access this module.
     */
    /** @return BelongsToMany<ClientRole, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(ClientRole::class, 'pivot_client_roles_app_mods')
            ->withTimestamps();
    }
}
