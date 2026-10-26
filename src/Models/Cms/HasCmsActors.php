<?php

namespace Aldhi88\StarterKit\Models\Cms;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCmsActors
{
    public function initializeHasCmsActors(): void
    {
        $this->makeHidden(['created_by', 'updated_by']);
    }

    /** @return BelongsTo<ClientLogin,$this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(ClientLogin::class, 'created_by');
    }

    /** @return BelongsTo<ClientLogin,$this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(ClientLogin::class, 'updated_by');
    }
}
