<?php

namespace Aldhi88\StarterKit\Contracts\Cms;

use Aldhi88\StarterKit\Models\Cms\Category;
use Aldhi88\StarterKit\Models\Cms\Comment;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Gallery;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Cms\Menu;
use Aldhi88\StarterKit\Models\Cms\MenuCategory;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CmsRepositoryInterface
{
    /** @return Builder<Content> */
    public function contentTable(string $kind, string $archive): Builder;

    /** @return Builder<Category> */
    public function categoryTable(string $kind, string $archive): Builder;

    /** @return Builder<Media> */
    public function mediaTable(string $archive): Builder;

    /** @return Builder<Gallery> */
    public function galleryTable(string $archive): Builder;

    /** @return Builder<Menu> */
    public function menuTable(string $archive): Builder;

    /** @return Builder<MenuCategory> */
    public function menuCategoryTable(string $archive): Builder;

    /** @return Builder<Comment> */
    public function commentTable(string $archive): Builder;

    public function content(int $id, string $kind, bool $archived = false, bool $lock = false): ?Content;

    /** @param list<int> $ids @return Collection<int,Content> */
    public function postsForUpdate(array $ids, bool $lock = false): Collection;

    public function category(int $id, string $kind, bool $archived = false, bool $lock = false): ?Category;

    public function media(int $id, bool $archived = false, bool $lock = false): ?Media;

    public function gallery(int $id, bool $archived = false, bool $lock = false): ?Gallery;

    public function menu(int $id, bool $archived = false, bool $lock = false): ?Menu;

    public function menuCategory(int $id, bool $archived = false, bool $lock = false): ?MenuCategory;

    public function comment(int $id, bool $archived = false, bool $lock = false): ?Comment;

    /** @param array<string,mixed> $data */
    public function storeContent(?Content $record, array $data, int $actorId): Content;

    /** @param list<int> $mediaIds */
    public function syncContentMedia(Content $content, array $mediaIds, int $actorId): void;

    /** @param list<int>|null $categoryIds */
    public function syncPostCategories(Content $content, ?array $categoryIds, int $actorId): void;

    /** @param list<string> $publicIds @return Collection<int, Media> */
    public function bodyMedia(array $publicIds, bool $lock = false): Collection;

    /** @return LengthAwarePaginator<int, Media> */
    public function paginateMediaLibrary(string $search, string $type, string $archive, int $perPage, int $page, bool $activeOnly = false): LengthAwarePaginator;

    public function categoryCodeExists(string $code, ?string $kind): bool;

    /** @param array<string,mixed> $data */
    public function storeCategory(?Category $record, array $data, int $actorId): Category;

    /** @param array<string,mixed> $data */
    public function storeMedia(?Media $record, array $data, int $actorId): Media;

    /** @param array<string,mixed> $data @param list<int> $mediaIds */
    public function storeGallery(?Gallery $record, array $data, array $mediaIds, int $actorId): Gallery;

    /** @param array<string,mixed> $data */
    public function storeMenu(?Menu $record, array $data, int $actorId): Menu;

    /** @param array<string,mixed> $data */
    public function storeMenuCategory(?MenuCategory $record, array $data, ?int $actorId): MenuCategory;

    /** @param array<string,mixed> $data */
    public function storeComment(?Comment $record, array $data, int $actorId): Comment;

    public function transition(Content|Category|Media|Gallery|Menu|MenuCategory|Comment $record, string $operation, int $actorId): void;

    public function inUse(Content|Category|Media|Gallery|Menu|MenuCategory|Comment $record, bool $permanent): bool;

    /** @return array<string,int> */
    public function relationCounts(Content|Category|Media|Gallery|Menu|MenuCategory|Comment $record): array;

    public function hasModule(ClientLogin $actor, string $module): bool;

    /** @return Collection<int,Category> */
    public function categoryOptions(string $kind): Collection;

    /** All unarchived post categories, including inactive rows; 501 detects the 500-item ordering limit. @return Collection<int,Category> */
    public function postCategoriesForOrdering(bool $lock = false): Collection;

    /** @return Collection<int,Media> */
    public function mediaOptions(string $search = '', array $include = []): Collection;

    /** @return Collection<int,Content> */
    public function postOptions(): Collection;

    /** @return Collection<int,MenuCategory> */
    public function menuCategoryOptions(): Collection;

    /** @return list<int> */
    public function galleryMediaIds(Gallery $gallery): array;

    /** Fixed PowerGrid + lifecycle composition, never an arbitrary model/table. @return Builder<Content|Category|Media|Gallery|Menu|MenuCategory|Comment> */
    public function filteredWorkspace(CmsResource $resource, array $filters, string $archive): Builder;

    /** @param array<string, mixed> $filters */
    public function countWorkspace(CmsResource $resource, array $filters, string $archive): int;

    /** @return list<int> */
    public function filteredIds(CmsResource $resource, array $filters, string $archive, int $limit): array;

    public function publishedPostForSubmission(string $slug): ?Content;

    /** @param array{author_name:string,author_email:?string,body:string} $data */
    public function storeSubmittedComment(int $contentId, array $data, ?int $actorId): Comment;

    public function seedMenuCategories(): void;
}
