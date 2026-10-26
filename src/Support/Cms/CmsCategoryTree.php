<?php

namespace Aldhi88\StarterKit\Support\Cms;

class CmsCategoryTree
{
    /** @param array<int,array{id:int,name:string,parent_id:int|null,position?:int}> $categories @return list<array{id:int,name:string,parent_id:int|null,depth:int,path:string}> */
    public static function flatten(array $categories): array
    {
        $byId = collect($categories)->keyBy('id');
        $children = [];
        foreach ($categories as $category) {
            $parentId = $category['parent_id'] ?? null;
            $children[$parentId && $byId->has($parentId) ? $parentId : 0][] = $category;
        }
        foreach ($children as &$siblings) {
            usort($siblings, fn (array $a, array $b): int => ((int) ($a['position'] ?? 0) <=> (int) ($b['position'] ?? 0)) ?: strnatcasecmp($a['name'], $b['name']) ?: ($a['id'] <=> $b['id']));
        }
        unset($siblings);
        $result = [];
        $seen = [];
        $visit = function (int $parentId, int $depth, string $path) use (&$visit, &$result, &$seen, $children): void {
            foreach ($children[$parentId] ?? [] as $category) {
                $id = (int) $category['id'];
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $label = $path === '' ? $category['name'] : $path.' › '.$category['name'];
                $result[] = [...$category, 'depth' => min($depth, 5), 'path' => $label];
                $visit($id, $depth + 1, $label);
            }
        };
        $visit(0, 0, '');
        foreach ($categories as $category) {
            if (! isset($seen[$category['id']])) {
                $visit((int) ($category['parent_id'] ?? 0), 0, '');
            }
        }

        return $result;
    }

    /** Preserve submitted values for validation; add available ancestors without selecting siblings.
     * @param  array  $ids  @param array<int,array<string,mixed>> $categories @return array
     */
    public static function withAncestors(array $ids, array $categories): array
    {
        $byId = collect($categories)->keyBy('id');
        $result = $ids;
        foreach ($ids as $id) {
            if (! is_int($id) && ! (is_string($id) && ctype_digit($id))) {
                continue;
            }
            $seen = [];
            while ($category = $byId->get((int) $id)) {
                $parent = $category['parent_id'] ?? null;
                if (! $parent || isset($seen[$parent]) || ! $byId->has($parent)) {
                    break;
                }
                $seen[$parent] = true;
                if (! in_array($parent, $result)) {
                    $result[] = (int) $parent;
                }
                $id = $parent;
            }
        }

        return $result;
    }

    /** Normalize older parent-only selections to the same branch selection as clicking a parent.
     * @param  array  $ids  @param array<int,array<string,mixed>> $categories @return array
     */
    public static function forSelection(array $ids, array $categories): array
    {
        $selected = self::withAncestors($ids, $categories);
        $children = [];
        foreach ($categories as $category) {
            $children[$category['parent_id'] ?? 0][] = (int) $category['id'];
        }
        foreach ($selected as $id) {
            if (! is_int($id) && ! (is_string($id) && ctype_digit($id))) {
                continue;
            }
            $branch = $children[(int) $id] ?? [];
            if ($branch === [] || array_filter($branch, fn (int $child): bool => in_array($child, $selected)) !== []) {
                continue;
            }
            $seen = [];
            while ($branch !== []) {
                $child = array_shift($branch);
                if (isset($seen[$child])) {
                    continue;
                }
                $seen[$child] = true;
                if (! in_array($child, $selected)) {
                    $selected[] = $child;
                }
                array_push($branch, ...($children[$child] ?? []));
            }
        }

        return $selected;
    }

    /** @param array<int,array<string,mixed>> $categories */
    public static function orderVersion(array $categories): string
    {
        usort($categories, fn (array $a, array $b): int => $a['id'] <=> $b['id']);
        $rows = array_map(fn (array $row): array => [
            'id' => (int) $row['id'], 'name' => (string) $row['name'],
            'parent_id' => isset($row['parent_id']) ? (int) $row['parent_id'] : null,
            'position' => (int) ($row['position'] ?? 0), 'status' => (string) ($row['status'] ?? 'active'),
        ], $categories);

        return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }
}
