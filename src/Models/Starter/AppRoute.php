<?php

namespace Altekno\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'uri', 'method', 'app_mod_id'])]
class AppRoute extends Model
{
    protected $table = 'starter_app_routes';

    /**
     * Get the module that owns this route.
     */
    public function mod(): BelongsTo
    {
        return $this->belongsTo(AppMod::class, 'app_mod_id');
    }

    /**
     * Get all menus pointing to this route.
     */
    public function menus(): HasMany
    {
        return $this->hasMany(AppMenu::class);
    }
}
