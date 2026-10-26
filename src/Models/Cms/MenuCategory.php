<?php

namespace Aldhi88\StarterKit\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuCategory extends Model
{
    use HasCmsActors, SoftDeletes;

    protected $table = 'cms_menu_categories';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
