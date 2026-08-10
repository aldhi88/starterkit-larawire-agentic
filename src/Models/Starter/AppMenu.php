<?php

namespace Altekno\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function mod(): BelongsTo
    {
        return $this->belongsTo(AppMod::class, 'app_mod_id');
    }

    /**
     * Get the route linked to this menu.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(AppRoute::class, 'app_route_id');
    }

    /**
     * Get this menu parent.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get this menu children.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get this menu children recursively.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->with(['route', 'childrenRecursive.route'])
            ->orderBy('order');
    }
}
