<?php

namespace Altekno\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'client_role_id',
    'name',
    'username',
    'email',
    'email_verified_at',
    'password',
    'profile_photo',
    'status',
    'must_change_password',
    'password_changed_at',
    'failed_login_count',
    'locked_until',
    'last_login_at',
    'last_login_ip',
    'remember_token',
    'auth_version',
])]
#[Hidden(['password', 'remember_token'])]
class ClientLogin extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'starter_client_logins';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
            'auth_version' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->locked_until === null || $this->locked_until->isPast());
    }

    /**
     * Get the role assigned to this login account.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(ClientRole::class, 'client_role_id');
    }
}
