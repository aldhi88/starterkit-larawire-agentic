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
 * @property string $name
 * @property string $uri
 * @property string $method
 * @property int $app_mod_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AppMod $mod
 * @property-read Collection<int, AppMenu> $menus
 */
#[Fillable(['name', 'uri', 'method', 'app_mod_id'])]
class AppRoute extends Model
{
    protected $table = 'starter_app_routes';

    /**
     * Get the module that owns this route.
     */
    /** @return BelongsTo<AppMod, $this> */
    public function mod(): BelongsTo
    {
        return $this->belongsTo(AppMod::class, 'app_mod_id');
    }

    /**
     * Get all menus pointing to this route.
     */
    /** @return HasMany<AppMenu, $this> */
    public function menus(): HasMany
    {
        return $this->hasMany(AppMenu::class);
    }
}
