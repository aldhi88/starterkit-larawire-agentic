<?php

namespace Aldhi88\StarterKit\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasCmsActors, SoftDeletes;

    protected $table = 'cms_media';

    protected $hidden = ['path', 'original_name'];

    protected $guarded = ['id', 'created_by', 'updated_by'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer', 'position' => 'integer'];
    }
}
