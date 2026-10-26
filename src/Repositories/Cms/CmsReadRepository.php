<?php

namespace Aldhi88\StarterKit\Repositories\Cms;

use Aldhi88\StarterKit\Contracts\Cms\CmsReadRepositoryInterface;
use Aldhi88\StarterKit\Models\Cms\Category;
use Aldhi88\StarterKit\Models\Cms\Comment;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Gallery;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Cms\Menu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CmsReadRepository implements CmsReadRepositoryInterface
{
    /** @return Builder<Content> */
    private function published(string $kind): Builder
    {
        $query = Content::query()->where('kind', $kind)->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
        if ($kind === 'post') {
            $query->where(fn (Builder $posts) => $posts
                ->whereHas('categories', fn (Builder $categories) => $categories->where('kind', 'post')->where('status', 'active'))
                ->orWhere(fn (Builder $legacy) => $legacy->whereDoesntHave('categories')
                    ->where(fn (Builder $single) => $single->whereNull('category_id')
                        ->orWhereHas('category', fn (Builder $category) => $category->where('kind', 'post')->where('status', 'active')))));
        } else {
            $query->where(fn (Builder $content) => $content->whereNull('category_id')->orWhereHas('category', fn (Builder $category) => $category->where('kind', $kind)->where('status', 'active')));
        }

        return $query
            ->with([
                'category',
                'categories' => fn ($categories) => $categories->where('kind', 'post')->where('status', 'active'),
                'image' => fn ($query) => $query->where('status', 'active')->where('mime_type', 'like', 'image/%'),
                'thumbnail' => fn ($query) => $query->where('status', 'active')->where('mime_type', 'like', 'image/%'),
                'logo' => fn ($query) => $query->where('status', 'active')->where('mime_type', 'like', 'image/%'),
            ]);
    }

    public function publishedContents(string $kind, ?string $category, int $perPage, string $pageName): LengthAwarePaginator
    {
        $categoryIds = [];
        if ($kind === 'post' && $category !== null) {
            $rows = Category::query()->where('kind', 'post')->where('status', 'active')->get(['id', 'parent_id', 'code']);
            $root = $rows->firstWhere('code', $category);
            if ($root) {
                $categoryIds = [(int) $root->id];
                do {
                    $previous = count($categoryIds);
                    foreach ($rows as $row) {
                        $parentId = $row->getAttribute('parent_id');
                        if ($parentId && in_array((int) $parentId, $categoryIds, true) && ! in_array((int) $row->id, $categoryIds, true)) {
                            $categoryIds[] = (int) $row->id;
                        }
                    }
                } while (count($categoryIds) > $previous);
            }
        }

        return $this->published($kind)->when($category !== null, function (Builder $query) use ($kind, $category, $categoryIds): void {
            if ($kind === 'post') {
                $query->where(fn (Builder $posts) => $posts->whereIn('category_id', $categoryIds)
                    ->orWhereHas('categories', fn (Builder $categories) => $categories->whereIn('cms_categories.id', $categoryIds)));
            } else {
                $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('code', $category));
            }
        })
            ->orderByDesc('published_at')->orderByDesc('id')->paginate($perPage, ['*'], $pageName);
    }

    public function publishedBySlug(string $kind, string $slug): ?Content
    {
        return $this->published($kind)->where('slug', $slug)->first();
    }

    public function cardsForCategory(string $category, int $limit): Collection
    {
        return $this->published('card')->whereHas('category', fn (Builder $query) => $query->where('code', $category))->orderBy('position')->orderBy('id')->limit($limit)->get();
    }

    public function publicGallery(string $slug): ?Gallery
    {
        return Gallery::query()->where('slug', $slug)->where('status', 'published')->with(['media' => fn ($query) => $query->where('status', 'active')->where('mime_type', 'like', 'image/%')])->first();
    }

    public function menusForCategory(string $category, int $limit): Collection
    {
        return Menu::query()->where('status', 'active')->whereHas('category', fn (Builder $query) => $query->where('code', $category)->where('status', 'active'))->with('category')->orderBy('position')->orderBy('id')->limit($limit)->get();
    }

    public function publicMenu(string $code): ?Menu
    {
        return Menu::query()->where('code', $code)->where('status', 'active')->where(fn (Builder $query) => $query->whereNull('category_id')->orWhereHas('category', fn (Builder $category) => $category->where('status', 'active')))->with('category')->first();
    }

    public function publicMedia(string $publicId): ?Media
    {
        return Media::query()->where('public_id', $publicId)->where('status', 'active')->first();
    }

    public function publicCategories(string $kind, int $limit): Collection
    {
        return Category::query()->where('kind', $kind)->where('status', 'active')->orderBy('position')->orderBy('name')->limit($limit)->get();
    }

    public function approvedComments(string $postSlug, int $perPage, string $pageName): LengthAwarePaginator
    {
        return Comment::query()->where('status', 'approved')->whereHas('content', function (Builder $query) use ($postSlug): void {
            $query->where('kind', 'post')->where('slug', $postSlug)->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())
                ->where(fn (Builder $content) => $content->whereHas('categories', fn (Builder $category) => $category->where('kind', 'post')->where('status', 'active'))
                    ->orWhere(fn (Builder $legacy) => $legacy->whereDoesntHave('categories')
                        ->where(fn (Builder $single) => $single->whereNull('category_id')->orWhereHas('category', fn (Builder $category) => $category->where('kind', 'post')->where('status', 'active')))));
        })->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage, ['id', 'content_id', 'author_name', 'body', 'created_at'], $pageName);
    }

    public function publicMediaById(int $id): ?Media
    {
        return Media::query()->whereKey($id)->where('status', 'active')->first();
    }
}
