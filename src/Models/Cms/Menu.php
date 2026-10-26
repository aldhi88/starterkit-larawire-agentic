<?php

namespace Aldhi88\StarterKit\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasCmsActors, SoftDeletes;

    protected $table = 'cms_menus';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['items' => 'array', 'position' => 'integer'];
    }

    /** @return BelongsTo<MenuCategory,$this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }
}
