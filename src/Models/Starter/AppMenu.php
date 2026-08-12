<?php

namespace Aldhi88\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $label
 * @property string|null $icon
 * @property int $order
 * @property bool $is_landing_candidate
 * @property int $app_mod_id
 * @property int|null $app_route_id
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AppMod $mod
 * @property-read AppRoute|null $route
 * @property-read AppMenu|null $parent
 * @property-read Collection<int, AppMenu> $children
 * @property-read Collection<int, AppMenu> $childrenRecursive
 */
#[Fillable(['label', 'icon', 'order', 'is_landing_candidate', 'app_mod_id', 'app_route_id', 'parent_id'])]
class AppMenu extends Model
{
    protected $table = 'starter_app_menus';

    protected function casts(): array
    {
        return [
            'is_landing_candidate' => 'bool',
        ];
    }

    /**
     * Get the module that owns this menu.
     */
    /** @return BelongsTo<AppMod, $this> */
    public function mod(): BelongsTo
    {
        return $this->belongsTo(AppMod::class, 'app_mod_id');
    }

    /**
     * Get the route linked to this menu.
     */
    /** @return BelongsTo<AppRoute, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(AppRoute::class, 'app_route_id');
    }

    /**
     * Get this menu parent.
     */
    /** @return BelongsTo<AppMenu, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get this menu children.
     */
    /** @return HasMany<AppMenu, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get this menu children recursively.
     */
    /** @return HasMany<AppMenu, $this> */
    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->with(['route', 'childrenRecursive.route'])
            ->orderBy('order');
    }
}
