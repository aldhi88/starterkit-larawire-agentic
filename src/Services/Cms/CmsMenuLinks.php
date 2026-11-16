<?php

namespace Aldhi88\StarterKit\Services\Cms;

use Aldhi88\StarterKit\Contracts\Cms\CmsContentLookupInterface;
use Aldhi88\StarterKit\Contracts\Cms\CmsReadRepositoryInterface;
use Aldhi88\StarterKit\Models\Cms\Menu;

class CmsMenuLinks
{
    public const TYPES = ['post' => 'Konten', 'page' => 'Halaman', 'post_category' => 'Kategori Konten', 'page_category' => 'Kategori Halaman'];

    public function __construct(private readonly CmsReadRepositoryInterface $reader) {}

    public function search(string $type, string $query): array
    {
        return $this->reader instanceof CmsContentLookupInterface ? $this->reader->menuTargets($type, null, $query) : [];
    }

    /** Resolve the referenced IDs once per type across all supplied items. */
    public function targets(array $items): array
    {
        if (! $this->reader instanceof CmsContentLookupInterface) {
            return [];
        }

        $targets = [];
        foreach (self::TYPES as $type => $label) {
            $ids = array_column(array_filter($items, fn ($item) => ($item['target_type'] ?? '') === $type), 'target_id');
            if ($ids !== []) {
                $targets[$type] = array_column($this->reader->menuTargets($type, array_values(array_unique(array_map('intval', $ids)))), null, 'id');
            }
        }

        return $targets;
    }

    /** Project current URLs and omit unavailable branches for existing flat and tree readers. */
    public function forPublic(array $items, array $targets): array
    {
        $visible = [];
        foreach ($items as $item) {
            $type = $item['target_type'] ?? 'custom';
            if ($type !== 'custom') {
                $url = $targets[$type][$item['target_id'] ?? 0]['url'] ?? null;
                if ($url === null) {
                    continue;
                }
                $item['url'] = $url;
                unset($item['target_type'], $item['target_id']);
            }
            $visible[$item['key']] = $item;
        }

        return array_values(array_filter($visible, static function ($item) use ($visible) {
            $parent = $item['parent_key'] ?? null;
            for ($depth = 0; filled($parent); $depth++) {
                if ($depth >= 2 || ! isset($visible[$parent])) {
                    return false;
                }
                $parent = $visible[$parent]['parent_key'] ?? null;
            }

            return true;
        }));
    }

    /** Menu instances returned by the public reader expose the familiar flat items array. */
    public function project(Menu $menu, array $targets): Menu
    {
        $menu->setAttribute('items', $this->forPublic($menu->getAttribute('items') ?? [], $targets));
        $menu->syncOriginalAttribute('items');

        return $menu;
    }
}
