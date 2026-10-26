<?php

namespace Aldhi88\StarterKit\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use HasCmsActors, SoftDeletes;

    protected $table = 'cms_galleries';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @return BelongsToMany<Media,$this> */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'cms_gallery_media', 'gallery_id', 'media_id')->withPivot('position')->withTimestamps()->orderByPivot('position');
    }
}
