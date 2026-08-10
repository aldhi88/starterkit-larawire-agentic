<?php

namespace Altekno\StarterKit\Contracts\Starter;

use Altekno\StarterKit\Models\Starter\App;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

interface AppInterface
{
    public function findBySubdomain(string $subdomain): ?App;

    public function countRegistered(): int;

    /**
     * @return EloquentCollection<int, App>
     */
    public function allOrderedByName(): EloquentCollection;

    /**
     * @param  array<int, int>  $modIds
     * @return EloquentCollection<int, App>
     */
    public function whereHasModIds(array $modIds): EloquentCollection;
}
