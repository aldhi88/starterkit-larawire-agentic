<?php

namespace Aldhi88\StarterKit\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasCmsActors, SoftDeletes;

    protected $table = 'cms_categories';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['position' => 'integer', 'parent_id' => 'integer'];
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
