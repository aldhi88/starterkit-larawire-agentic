<?php

namespace Altekno\StarterKit\Models\Starter;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
