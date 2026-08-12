<?php

namespace Aldhi88\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $subdomain
 * @property string|null $desc
 * @property string|null $icon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, AppMod> $mods
 */
#[Fillable(['name', 'subdomain', 'desc', 'icon'])]
class App extends Model
{
    protected $table = 'starter_apps';

    /**
     * Get all modules registered for this app.
     */
    /** @return HasMany<AppMod, $this> */
    public function mods(): HasMany
    {
        return $this->hasMany(AppMod::class);
    }
}
