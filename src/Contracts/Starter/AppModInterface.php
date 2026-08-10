<?php

namespace Altekno\StarterKit\Contracts\Starter;

use Altekno\StarterKit\Models\Starter\App;
use Altekno\StarterKit\Models\Starter\AppMod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

interface AppModInterface
{
    /**
     * @return Collection<int, AppMod>
     */
    public function allForUserAccessPreview(): Collection;

    /**
     * @return Collection<int, AppMod>
     */
    public function allForRoleAccessManagement(): Collection;

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, AppMod>
     */
    public function forIdsWithNavigableMenus(array $ids): Collection;

    /**
     * @return array{apps: int, modules: int}
     */
    public function accessStats(): array;

    /**
     * @param  array<int, string>  $with
     * @param  array<int, int>  $onlyIds
     * @return EloquentCollection<int, AppMod>
     */
    public function forApp(App $app, array $with = [], array $onlyIds = []): EloquentCollection;
}
