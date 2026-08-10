<?php

namespace Altekno\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'subdomain', 'desc', 'icon'])]
class App extends Model
{
    protected $table = 'starter_apps';

    /**
     * Get all modules registered for this app.
     */
    public function mods(): HasMany
    {
        return $this->hasMany(AppMod::class);
    }
}
