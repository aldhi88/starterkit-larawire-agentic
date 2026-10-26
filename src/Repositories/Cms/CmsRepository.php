<?php

namespace Aldhi88\StarterKit\Repositories\Cms;

use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Models\Cms\Category;
use Aldhi88\StarterKit\Models\Cms\Comment;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Gallery;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Cms\Menu;
use Aldhi88\StarterKit\Models\Cms\MenuCategory;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Support\Cms\CmsCategoryTree;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CmsRepository implements CmsRepositoryInterface
{
    /**
     * @template T of Model
     *
     * @param  Builder<T>  $query
     * @return Builder<T>
     */
    private function archiveScope(Builder $query, string $archive): Builder
    {
        if ($archive === 'archived') {
            $query->withoutGlobalScope(SoftDeletingScope::class)->whereNotNull($query->getModel()->qualifyColumn('deleted_at'));
        } elseif ($archive === 'all') {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }

    public function contentTable(string $kind, string $archive): Builder
    {
        return $this->archiveScope(Content::query()->where('cms_contents.kind', $kind)
            ->when($kind === 'post', fn (Builder $query) => $query->with('categories'))
            ->leftJoin('cms_categories as cms_category', 'cms_category.id', '=', 'cms_contents.category_id')
            ->select('cms_contents.*', 'cms_category.name as category_name', 'cms_contents.position as sort_order'), $archive);
    }

    public function categoryTable(string $kind, string $archive): Builder
    {
        $query = $this->archiveScope(Category::query()->where('kind', $kind)->select('cms_categories.*', 'name as title', 'code as slug', 'position as sort_order'), $archive);
        if ($kind !== 'post') {
            return $query;
        }

        // Keep ancestry available even when a parent is outside the filtered page.
        $categories = Category::withTrashed()->where('kind', 'post')->get(['id', 'name', 'parent_id', 'position', 'status', 'deleted_at']);
        $version = CmsCategoryTree::orderVersion($categories->reject(fn (Category $category): bool => $category->trashed())->toArray());
        $tree = CmsCategoryTree::flatten($categories->toArray());
        $hierarchy = collect($tree)->keyBy('id');
        $order = 'CASE cms_categories.id';
        foreach ($tree as $rank => $category) {
            $order .= ' WHEN '.(int) $category['id'].' THEN '.(int) $rank;
        }
        $query->selectRaw($tree === [] ? '0 AS hierarchy_order' : $order.' ELSE '.count($tree).' END AS hierarchy_order');

        return $query->afterQuery(function ($rows) use ($hierarchy, $version) {
            foreach ($rows as $row) {
                if ($row instanceof Category) {
                    $node = $hierarchy->get($row->getKey());
                    $row->setAttribute('category_order_version', $version);
                    $row->setAttribute('hierarchy_depth', $node['depth'] ?? 0);
                    $row->setAttribute('hierarchy_path', $node['path'] ?? $row->getAttribute('name'));
                }
            }

            return $rows;
        });
    }

    /** @return Builder<Media> */
    public function mediaTable(string $archive): Builder
    {
        return $this->archiveScope(Media::query()->select('cms_media.*', 'name as title', 'position as sort_order'), $archive);
    }

    public function galleryTable(string $archive): Builder
    {
        return $this->archiveScope(Gallery::query()->select('cms_galleries.*', 'name as title', 'position as sort_order'), $archive);
    }

    public function menuTable(string $archive): Builder
    {
        return $this->archiveScope(Menu::query()->leftJoin('cms_menu_categories as cms_category', 'cms_category.id', '=', 'cms_menus.category_id')->select('cms_menus.*', 'cms_category.name as category_name', 'cms_menus.name as title', 'cms_menus.position as sort_order'), $archive);
    }

    public function menuCategoryTable(string $archive): Builder
    {
        return $this->archiveScope(MenuCategory::query()->select('cms_menu_categories.*', 'name as title', 'code as slug', 'position as sort_order'), $archive);
    }

    public function commentTable(string $archive): Builder
    {
        return $this->archiveScope(Comment::query()->leftJoin('cms_contents as cms_post', 'cms_post.id', '=', 'cms_comments.content_id')->select('cms_comments.*', 'cms_comments.author_name as name', 'cms_comments.author_name as title', 'cms_post.name as content_name'), $archive);
    }

    public function content(int $id, string $kind, bool $archived = false, bool $lock = false): ?Content
    {
        return Content::query()->when($archived, fn ($q) => $q->withTrashed())->where('kind', $kind)->when($lock, fn ($q) => $q->lockForUpdate())->find($id);
    }

    public function postsForUpdate(array $ids, bool $lock = false): Collection
    {
        return Content::query()->where('kind', 'post')->whereIn('id', $ids)->orderBy('id')
            ->with(['categories' => fn ($query) => $query->orderBy('cms_categories.id')])
            ->when($lock, fn (Builder $query) => $query->lockForUpdate())->get();
    }

    public function category(int $id, string $kind, bool $archived = false, bool $lock = false): ?Category
    {
        return Category::query()->when($archived, fn ($q) => $q->withTrashed())->where('kind', $kind)->when($lock, fn ($q) => $q->lockForUpdate())->find($id);
    }

    public function media(int $id, bool $archived = false, bool $lock = false): ?Media
    {
        return Media::query()->when($archived, fn ($q) => $q->withTrashed())->when($lock, fn ($q) => $q->lockForUpdate())->find($id);
    }

    public function gallery(int $id, bool $archived = false, bool $lock = false): ?Gallery
    {
        return Gallery::query()->when($archived, fn ($q) => $q->withTrashed())->when($lock, fn ($q) => $q->lockForUpdate())->find($id);
    }

    public function menu(int $id, bool $archived = false, bool $lock = false): ?Menu
    {
        return Menu::query()->when($archived, fn ($q) => $q->withTrashed())->when($lock, fn ($q) => $q->lockForUpdate())->find($id);
    }

    public function menuCategory(int $id, bool $archived = false, bool $lock = false): ?MenuCategory
    {
        return MenuCategory::query()->when($archived, fn ($q) => $q->withTrashed())->when($lock, fn ($q) => $q->lockForUpdate())->find($id);
    }

    public function comment(int $id, bool $archived = false, bool $lock = false): ?Comment
    {
        return Comment::query()->when($archived, fn ($q) => $q->withTrashed())->when($lock, fn ($q) => $q->lockForUpdate())->find($id);
    }

    /**
     * @template T of Model
     *
     * @param  T  $record
     * @param  array<string,mixed>  $data
     * @return T
     */
    private function persist(Model $record, array $data, ?int $actorId): Model
    {
        unset($data['id'], $data['created_by'], $data['updated_by'], $data['deleted_at']);
        $record->fill($data);
        if (! $record->exists) {
            $record->setAttribute('created_by', $actorId);
        }
        $record->setAttribute('updated_by', $actorId);
        $record->save();

        return $record;
    }

    public function storeContent(?Content $record, array $data, int $actorId): Content
    {
        return $this->persist($record ?? new Content, $data, $actorId);
    }

    public function syncContentMedia(Content $content, array $mediaIds, int $actorId): void
    {
        $existing = DB::table('cms_content_media')->where('content_id', $content->getKey())->pluck('created_by', 'media_id')->all();
        $rows = [];
        foreach (array_unique($mediaIds) as $id) {
            $rows[$id] = ['created_by' => array_key_exists($id, $existing) ? $existing[$id] : $actorId, 'updated_by' => $actorId];
        }
        $content->embeddedMedia()->sync($rows);
    }

    public function syncPostCategories(Content $content, ?array $categoryIds, int $actorId): void
    {
        if ($categoryIds === null) {
            $categoryIds = $content->categories()->pluck('cms_categories.id')->map(fn ($id): int => (int) $id)->all();
            if ($content->getAttribute('category_id') && ! in_array((int) $content->getAttribute('category_id'), $categoryIds, true)) {
                array_unshift($categoryIds, (int) $content->getAttribute('category_id'));
            }
        }
        $existing = DB::table('cms_content_categories')->where('content_id', $content->getKey())->pluck('created_by', 'category_id')->all();
        $rows = [];
        foreach (array_unique($categoryIds) as $categoryId) {
            $rows[$categoryId] = ['created_by' => $existing[$categoryId] ?? $actorId, 'updated_by' => $actorId];
        }
        $content->categories()->sync($rows);
    }

    public function bodyMedia(array $publicIds, bool $lock = false): Collection
    {
        return Media::query()->where('status', 'active')->whereIn('public_id', array_slice(array_unique($publicIds), 0, 100))
            ->orderBy('id')->when($lock, fn (Builder $query) => $query->lockForUpdate())->get();
    }

    public function paginateMediaLibrary(string $search, string $type, string $archive, int $perPage, int $page, bool $activeOnly = false): LengthAwarePaginator
    {
        $query = $this->mediaLibraryScope($this->mediaTable($archive), $search, $type);
        if ($activeOnly) {
            $query->where('cms_media.status', 'active');
        }

        return $query->orderByDesc('cms_media.id')->paginate($perPage, ['cms_media.*'], 'mediaPage', $page);
    }

    /**
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    private function mediaLibraryScope(Builder $query, string $search, string $type): Builder
    {
        if ($search !== '') {
            $term = '%'.mb_substr(trim($search), 0, 100).'%';
            $query->where(fn (Builder $media) => $media->where('cms_media.name', 'like', $term)->orWhere('cms_media.original_name', 'like', $term));
        }
        if ($type === 'image') {
            $query->where('cms_media.mime_type', 'like', 'image/%');
        } elseif ($type === 'pdf') {
            $query->where('cms_media.mime_type', 'application/pdf');
        }

        return $query;
    }

    public function categoryCodeExists(string $code, ?string $kind): bool
    {
        return $kind === null
            ? MenuCategory::withTrashed()->where('code', $code)->exists()
            : Category::withTrashed()->where('kind', $kind)->where('code', $code)->exists();
    }

    public function storeCategory(?Category $record, array $data, int $actorId): Category
    {
        return $this->persist($record ?? new Category, $data, $actorId);
    }

    public function storeMedia(?Media $record, array $data, int $actorId): Media
    {
        return $this->persist($record ?? new Media, $data, $actorId);
    }

    public function storeMenu(?Menu $record, array $data, int $actorId): Menu
    {
        return $this->persist($record ?? new Menu, $data, $actorId);
    }

    public function storeMenuCategory(?MenuCategory $record, array $data, ?int $actorId): MenuCategory
    {
        return $this->persist($record ?? new MenuCategory, $data, $actorId);
    }

    public function storeComment(?Comment $record, array $data, int $actorId): Comment
    {
        return $this->persist($record ?? new Comment, $data, $actorId);
    }

    public function storeGallery(?Gallery $record, array $data, array $mediaIds, int $actorId): Gallery
    {
        $gallery = $this->persist($record ?? new Gallery, $data, $actorId);
        $previous = DB::table('cms_gallery_media')->where('gallery_id', $gallery->getKey())->pluck('created_by', 'media_id')->all();
        $links = [];
        foreach ($mediaIds as $position => $mediaId) {
            $links[$mediaId] = ['position' => $position, 'created_by' => array_key_exists($mediaId, $previous) ? $previous[$mediaId] : $actorId, 'updated_by' => $actorId];
        }
        $gallery->media()->sync($links);

        return $gallery;
    }

    public function transition(Content|Category|Media|Gallery|Menu|MenuCategory|Comment $record, string $operation, int $actorId): void
    {
        $record->setAttribute('updated_by', $actorId);
        $record->save();
        if ($operation === 'archive') {
            $record->delete();
        } elseif ($operation === 'restore') {
            $record->restore();
        } else {
            if ($record instanceof Content) {
                Comment::withTrashed()->where('content_id', $record->getKey())->forceDelete();
            }
            $record->forceDelete();
        }
    }

    public function inUse(Content|Category|Media|Gallery|Menu|MenuCategory|Comment $record, bool $permanent): bool
    {
        if ($record instanceof Category) {
            return Content::withTrashed()->where('category_id', $record->getKey())->exists()
                || DB::table('cms_content_categories')->where('category_id', $record->getKey())->exists()
                || Category::withTrashed()->where('parent_id', $record->getKey())->exists();
        }
        if ($record instanceof MenuCategory) {
            return Menu::withTrashed()->where('category_id', $record->getKey())->exists();
        }
        if ($record instanceof Media) {
            return Content::withTrashed()->where(fn ($q) => $q->where('image_id', $record->getKey())->orWhere('thumbnail_id', $record->getKey())->orWhere('logo_id', $record->getKey()))->exists()
                || DB::table('cms_gallery_media')->where('media_id', $record->getKey())->exists()
                || DB::table('cms_content_media')->where('media_id', $record->getKey())->exists();
        }

        return false;
    }

    public function relationCounts(Content|Category|Media|Gallery|Menu|MenuCategory|Comment $record): array
    {
        if ($record instanceof Content) {
            return ['comments' => Comment::withTrashed()->where('content_id', $record->getKey())->count(), 'embedded_media' => DB::table('cms_content_media')->where('content_id', $record->getKey())->count()];
        }
        if ($record instanceof Gallery) {
            return ['media_links' => DB::table('cms_gallery_media')->where('gallery_id', $record->getKey())->count()];
        }

        return [];
    }

    public function hasModule(ClientLogin $actor, string $module): bool
    {
        $role = $actor->role()->first();
        if (! $role) {
            return false;
        }

        return $role->hasFullAccess() || $role->mods()->where('code', $module)->whereHas('app', fn ($q) => $q->where('subdomain', 'cms'))->exists();
    }

    public function categoryOptions(string $kind): Collection
    {
        return Category::query()->where('kind', $kind)->where('status', 'active')->orderBy('position')->orderBy('name')->limit(500)->get();
    }

    public function postCategoriesForOrdering(bool $lock = false): Collection
    {
        return Category::query()->where('kind', 'post')->orderBy('id')->limit(501)
            ->when($lock, fn ($query) => $query->lockForUpdate())->get();
    }

    public function mediaOptions(string $search = '', array $include = []): Collection
    {
        $matching = Media::query()->where('status', 'active')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.mb_substr(trim($search), 0, 100).'%'))
            ->latest('id')->limit(100)->get();
        $selected = $include === [] ? collect() : Media::query()->where('status', 'active')->whereIn('id', array_slice($include, 0, 100))->get();

        return $selected->merge($matching)->unique('id')->values();
    }

    public function postOptions(): Collection
    {
        return Content::query()->where('kind', 'post')->orderBy('name')->limit(500)->get(['id', 'name', 'title']);
    }

    public function menuCategoryOptions(): Collection
    {
        return MenuCategory::query()->where('status', 'active')->orderBy('position')->orderBy('name')->limit(500)->get();
    }

    public function galleryMediaIds(Gallery $gallery): array
    {
        return $gallery->media()->pluck('cms_media.id')->map(fn ($id): int => (int) $id)->all();
    }

    public function filteredWorkspace(CmsResource $resource, array $filters, string $archive): Builder
    {
        $query = match ($resource) {
            CmsResource::Posts, CmsResource::Pages, CmsResource::Cards => $this->contentTable($resource->kind(), $archive),
            CmsResource::PostCategories, CmsResource::PageCategories, CmsResource::CardCategories => $this->categoryTable($resource->kind(), $archive),
            CmsResource::Media => $this->mediaLibraryScope($this->mediaTable($archive), (string) ($filters['search'] ?? ''), (string) ($filters['media_type'] ?? 'all')), CmsResource::Galleries => $this->galleryTable($archive),
            CmsResource::Menus => $this->menuTable($archive), CmsResource::MenuCategories => $this->menuCategoryTable($archive), CmsResource::Comments => $this->commentTable($archive),
        };
        $fields = $resource->tableFields();
        foreach (['search', 'name'] as $filter) {
            if (filled($filters[$filter] ?? null)) {
                $term = '%'.mb_substr(trim((string) $filters[$filter]), 0, 100).'%';
                if ($resource !== CmsResource::Media || $filter !== 'search') {
                    $query->where($fields['name'], 'like', $term);
                }
            }
        }
        if (filled($filters['status'] ?? null)) {
            $query->where($fields['status'], $filters['status']);
        }
        if (isset($fields['category_id']) && filled($filters['category_id'] ?? null)) {
            if ($resource === CmsResource::Posts) {
                $query->where(fn (Builder $posts) => $posts->where($fields['category_id'], (int) $filters['category_id'])
                    ->orWhereHas('categories', fn (Builder $categories) => $categories->whereKey((int) $filters['category_id'])));
            } else {
                $query->where($fields['category_id'], (int) $filters['category_id']);
            }
        }
        if ($resource === CmsResource::Comments) {
            if (filled($filters['content_id'] ?? null)) {
                $query->where('cms_comments.content_id', (int) $filters['content_id']);
            }
            if (filled($filters['body'] ?? null)) {
                $query->where('cms_comments.body', 'like', '%'.mb_substr(trim((string) $filters['body']), 0, 100).'%');
            }
        }
        if (isset($fields['position'])) {
            if (isset($filters['position_min']) && $filters['position_min'] !== '') {
                $query->where($fields['position'], '>=', (int) $filters['position_min']);
            }
            if (isset($filters['position_max']) && $filters['position_max'] !== '') {
                $query->where($fields['position'], '<=', (int) $filters['position_max']);
            }
        }
        if (filled($filters['date_from'] ?? null)) {
            $query->where($fields['updated_at'], '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (filled($filters['date_to'] ?? null)) {
            $query->where($fields['updated_at'], '<', Carbon::parse($filters['date_to'])->startOfDay()->addDay());
        }

        return $query;
    }

    public function filteredIds(CmsResource $resource, array $filters, string $archive, int $limit): array
    {
        return $this->filteredWorkspace($resource, $filters, $archive)->reorder($resource->table().'.id')->limit($limit)->lockForUpdate()->pluck($resource->table().'.id')->map(fn ($id): int => (int) $id)->all();
    }

    public function countWorkspace(CmsResource $resource, array $filters, string $archive): int
    {
        return $this->filteredWorkspace($resource, $filters, $archive)->count();
    }

    public function publishedPostForSubmission(string $slug): ?Content
    {
        return Content::query()->where('kind', 'post')->where('slug', $slug)->where('status', 'published')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn (Builder $query) => $query->whereHas('categories', fn (Builder $category) => $category->where('kind', 'post')->where('status', 'active'))
                ->orWhere(fn (Builder $legacy) => $legacy->whereDoesntHave('categories')
                    ->where(fn (Builder $single) => $single->whereNull('category_id')->orWhereHas('category', fn (Builder $category) => $category->where('kind', 'post')->where('status', 'active')))))
            ->lockForUpdate()->first();
    }

    public function storeSubmittedComment(int $contentId, array $data, ?int $actorId): Comment
    {
        return $this->persist(new Comment, ['content_id' => $contentId, 'author_name' => $data['author_name'], 'author_email' => $data['author_email'], 'body' => $data['body'], 'status' => 'pending'], $actorId);
    }

    public function seedMenuCategories(): void
    {
        foreach (['main' => 'Menu Utama', 'other' => 'Menu Lainnya', 'footer' => 'Menu Bawah'] as $code => $name) {
            if (! MenuCategory::withTrashed()->where('code', $code)->exists()) {
                $this->storeMenuCategory(null, ['name' => $name, 'code' => $code, 'status' => 'active', 'position' => 0], null);
            }
        }
    }
}
