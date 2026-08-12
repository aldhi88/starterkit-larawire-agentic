<?php

namespace Aldhi88\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $client_role_id
 * @property int $app_id
 * @property int $app_menu_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ClientRole $role
 * @property-read App $app
 * @property-read AppMenu $menu
 */
#[Fillable(['client_role_id', 'app_id', 'app_menu_id'])]
class ClientRoleAppLanding extends Model
{
    protected $table = 'pivot_client_roles_app_landings';

    /** @return BelongsTo<ClientRole, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(ClientRole::class, 'client_role_id');
    }

    /** @return BelongsTo<App, $this> */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /** @return BelongsTo<AppMenu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(AppMenu::class, 'app_menu_id');
    }
}
