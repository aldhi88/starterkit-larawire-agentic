<?php

namespace Altekno\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_role_id', 'app_id', 'app_menu_id'])]
class ClientRoleAppLanding extends Model
{
    protected $table = 'pivot_client_roles_app_landings';

    public function role(): BelongsTo
    {
        return $this->belongsTo(ClientRole::class, 'client_role_id');
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(AppMenu::class, 'app_menu_id');
    }
}
