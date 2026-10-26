<?php

namespace Aldhi88\StarterKit\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Content extends Model
{
    use HasCmsActors, SoftDeletes;

    protected $table = 'cms_contents';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    /** @return BelongsToMany<Media, $this> */
    public function embeddedMedia(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'cms_content_media', 'content_id', 'media_id')->withTimestamps();
    }

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'position' => 'integer'];
    }

    protected function excerpt(): Attribute
    {
        return Attribute::get(function (mixed $value, array $attributes): ?string {
            $value = is_string($value) ? $value : null;
            if (filled($value) || ($attributes['kind'] ?? null) !== 'post') {
                return $value;
            }

            $body = (string) ($attributes['body'] ?? '');
            if (($attributes['body_format'] ?? 'markdown') !== 'html') {
                $body = Str::markdown($body, ['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 20]);
            }
            $body = preg_replace('/<(script|style|template)\b[^>]*>.*?<\/\1>/is', '', $body) ?? '';
            $body = preg_replace('/<br\b[^>]*>|<\/(?:p|div|h[1-6]|li|blockquote|pre)>/i', ' ', $body) ?? '';
            $plain = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plain = trim(preg_replace('/[\s\p{Z}]+/u', ' ', $plain) ?? '');

            return $plain === '' ? null : Str::limit($plain, 200, '…', preserveWords: true);
        });
    }

    /** @return BelongsTo<Category,$this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'cms_content_categories', 'content_id', 'category_id')->withTimestamps();
    }

    /** @return BelongsTo<Media,$this> */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    /** @return BelongsTo<Media,$this> */
    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'thumbnail_id');
    }

    /** @return BelongsTo<Media,$this> */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }

    /** @return HasMany<Comment,$this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'content_id');
    }
}
