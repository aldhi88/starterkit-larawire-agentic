<?php

namespace Aldhi88\StarterKit\Contracts\Cms;

use Aldhi88\StarterKit\Models\Cms\Category;
use Aldhi88\StarterKit\Models\Cms\Comment;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Gallery;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Cms\Menu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CmsReadRepositoryInterface
{
    /** @return LengthAwarePaginator<int,Content> */
    public function publishedContents(string $kind, ?string $category, int $perPage, string $pageName): LengthAwarePaginator;

    public function publishedBySlug(string $kind, string $slug): ?Content;

    /** @return Collection<int,Content> */
    public function cardsForCategory(string $category, int $limit): Collection;

    public function publicGallery(string $slug): ?Gallery;

    /** @return Collection<int,Menu> */
    public function menusForCategory(string $category, int $limit): Collection;

    public function publicMenu(string $code): ?Menu;

    public function publicMedia(string $publicId): ?Media;

    public function publicMediaById(int $id): ?Media;

    /** @return Collection<int,Category> */
    public function publicCategories(string $kind, int $limit): Collection;

    /** @return LengthAwarePaginator<int,Comment> */
    public function approvedComments(string $postSlug, int $perPage, string $pageName): LengthAwarePaginator;
}
