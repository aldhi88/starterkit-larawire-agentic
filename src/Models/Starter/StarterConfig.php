<?php

namespace Aldhi88\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string $label
 * @property string|null $description
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'group',
    'key',
    'value',
    'type',
    'label',
    'description',
    'order',
])]
class StarterConfig extends Model
{
    protected $table = 'starter_configs';

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }
}
