<?php

namespace Aldhi88\StarterKit\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasCmsActors, SoftDeletes;

    protected $table = 'cms_comments';

    protected $hidden = ['author_email'];

    protected $guarded = ['id', 'created_by', 'updated_by'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return [];
    }

    /** @return BelongsTo<Content,$this> */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
