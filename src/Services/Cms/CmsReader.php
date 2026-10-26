<?php

namespace Aldhi88\StarterKit\Services\Cms;

use Aldhi88\StarterKit\Contracts\Cms\CmsReadRepositoryInterface;
use Aldhi88\StarterKit\Models\Cms\Category;
use Aldhi88\StarterKit\Models\Cms\Comment;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Gallery;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Cms\Menu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CmsReader
{
    public function __construct(private readonly CmsReadRepositoryInterface $contents, private readonly CmsRichContent $richContent) {}

    /** @return LengthAwarePaginator<int,Content> */
    public function posts(?string $category = null, int $perPage = 12, string $pageName = 'postsPage'): LengthAwarePaginator
    {
        return $this->contents->publishedContents('post', $category, max(1, min(100, $perPage)), $this->pageName($pageName));
    }

    /** @return LengthAwarePaginator<int,Content> */
    public function pages(?string $category = null, int $perPage = 12, string $pageName = 'pagesPage'): LengthAwarePaginator
    {
        return $this->contents->publishedContents('page', $category, max(1, min(100, $perPage)), $this->pageName($pageName));
    }

    public function post(string $slug): ?Content
    {
        return $this->contents->publishedBySlug('post', $slug);
    }

    public function page(string $slug): ?Content
    {
        return $this->contents->publishedBySlug('page', $slug);
    }

    /** @return Collection<int,Content> */
    public function cards(string $category, int $limit = 100): Collection
    {
        return $this->contents->cardsForCategory($category, max(1, min(100, $limit)));
    }

    public function gallery(string $slug): ?Gallery
    {
        return $this->contents->publicGallery($slug);
    }

    /** @return Collection<int,Menu> */
    public function menus(string $category, int $limit = 100): Collection
    {
        return $this->contents->menusForCategory($category, max(1, min(100, $limit)));
    }

    public function menu(string $code): ?Menu
    {
        return $this->contents->publicMenu($code);
    }

    /** @return Collection<int,Category> */
    public function categories(string $kind, int $limit = 100): Collection
    {
        if (! in_array($kind, ['post', 'page', 'card'], true)) {
            throw new \InvalidArgumentException('Unknown CMS content kind.');
        }

        return $this->contents->publicCategories($kind, max(1, min(500, $limit)));
    }

    /** @return Collection<int,Category> */
    public function postCategories(int $limit = 100): Collection
    {
        return $this->categories('post', $limit);
    }

    /** @return Collection<int,Category> */
    public function pageCategories(int $limit = 100): Collection
    {
        return $this->categories('page', $limit);
    }

    /** @return Collection<int,Category> */
    public function cardCategories(int $limit = 100): Collection
    {
        return $this->categories('card', $limit);
    }

    /** @return LengthAwarePaginator<int,Comment> */
    public function comments(string $postSlug, int $perPage = 20, string $pageName = 'commentsPage'): LengthAwarePaginator
    {
        return $this->contents->approvedComments($postSlug, max(1, min(100, $perPage)), $this->pageName($pageName));
    }

    public function media(string $publicId): ?Media
    {
        return $this->contents->publicMedia($publicId);
    }

    public function mediaUrl(Media|int|null $media): ?string
    {
        if (is_int($media)) {
            $media = $this->contents->publicMediaById($media);
        }
        if (! $media || $media->trashed() || $media->getAttribute('status') !== 'active') {
            return null;
        }

        return route('starter.cms.media', ['publicId' => $media->getAttribute('public_id')]);
    }

    private function pageName(string $name): string
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $name) !== 1) {
            throw new \InvalidArgumentException('CMS paginator names must start with a letter and contain at most 64 letters, digits, underscores, or hyphens.');
        }

        return $name;
    }

    /** Render safe rich HTML while preserving existing Markdown content. */
    public function renderBody(Content|string|null $content): string
    {
        $body = $content instanceof Content ? $content->getAttribute('body') : $content;

        if ($content instanceof Content && $content->getAttribute('body_format') === 'html') {
            return $this->richContent->render((string) $body);
        }

        return Str::markdown((string) $body, ['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 20]);
    }

    /** @return list<array<string,mixed>> */
    public function menuTree(Menu $menu): array
    {
        $items = $menu->getAttribute('items') ?? [];
        $tree = function (?string $parent, int $depth) use (&$tree, $items): array {
            if ($depth > 3) {
                return [];
            }
            $result = [];
            foreach ($items as $item) {
                if (($item['parent_key'] ?? null) === $parent) {
                    $result[] = [...$item, 'children' => $tree($item['key'], $depth + 1)];
                }
            }

            return $result;
        };

        return $tree(null, 1);
    }
}
