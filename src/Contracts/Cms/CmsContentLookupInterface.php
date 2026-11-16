<?php

namespace Aldhi88\StarterKit\Contracts\Cms;

use Aldhi88\StarterKit\Models\Cms\Content;

interface CmsContentLookupInterface
{
    public function publishedById(string $kind, int $id): ?Content;

    /** @param list<int>|null $ids @return list<array{id:int,name:string,url:string}> */
    public function menuTargets(string $type, ?array $ids, string $search = ''): array;
}
