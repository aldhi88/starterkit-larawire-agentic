<?php

namespace Altekno\StarterKit\Repositories\Starter;

use Altekno\StarterKit\Contracts\Starter\ClientRoleInterface;
use Altekno\StarterKit\Models\Starter\AppMenu;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Models\Starter\ClientRole;
use Altekno\StarterKit\Models\Starter\ClientRoleAppLanding;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClientRoleRepository implements ClientRoleInterface
{
    public function tableQueryForViewer(ClientLogin $viewer, string $archiveStatus = 'active'): Builder
    {
        $query = ClientRole::query()
            ->select('starter_client_roles.*')
            ->withCount(['clientLogins', 'mods']);

        if ($archiveStatus === 'archived') {
            $query->onlyTrashed();
        } elseif ($archiveStatus === 'all') {
            $query->withTrashed();
        }

        if (! $viewer->role?->isSuperuser()) {
            $query->where('is_system', false)->where('code', '!=', 'superuser');
        }

        return $query;
    }

    public function allAssignableForViewer(ClientLogin $viewer): Collection
    {
        return ClientRole::query()
            ->with('mods.app')
            ->when(! $viewer->role?->isSuperuser(), fn (Builder $query): Builder => $query
                ->where('is_system', false)
                ->where('code', '!=', 'superuser'))
            ->orderBy('name')
            ->get();
    }

    public function paginateForViewer(
        ClientLogin $viewer,
        string $search,
        int $perPage,
        string $pageName,
    ): LengthAwarePaginator {
        $query = ClientRole::query()
            ->with([
                'mods' => fn ($moduleQuery) => $moduleQuery
                    ->select(['starter_app_mods.id', 'starter_app_mods.app_id']),
            ])
            ->withCount('clientLogins');

        if (! $viewer->role?->isSuperuser()) {
            $query
                ->where('is_system', false)
                ->where('code', '!=', 'superuser');
        }

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function (Builder $searchQuery) use ($term): void {
                $searchQuery
                    ->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('desc', 'like', $term);
            });
        }

        return $query
            ->orderByRaw("CASE WHEN is_system = 1 OR code = 'superuser' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function findBasicById(int $id): ?ClientRole
    {
        return ClientRole::query()
            ->whereKey($id)
            ->first();
    }

    public function findForManagement(int $id): ?ClientRole
    {
        return ClientRole::query()
            ->with(['mods.app', 'landings.menu.route', 'landings.menu.mod.app'])
            ->withCount('clientLogins')
            ->whereKey($id)
            ->first();
    }

    public function findWithTrashedForManagement(int $id): ?ClientRole
    {
        return ClientRole::withTrashed()
            ->with(['mods.app', 'landings.menu.route', 'landings.menu.mod.app'])
            ->withCount('clientLogins')
            ->whereKey($id)
            ->first();
    }

    public function clientLogins(ClientRole $role): Collection
    {
        return $role->clientLogins()
            ->orderBy('name')
            ->get(['id', 'client_role_id', 'name', 'email']);
    }

    public function createRole(array $data): ClientRole
    {
        return ClientRole::query()->create($data);
    }

    public function updateRole(ClientRole $role, array $data): ClientRole
    {
        $role->forceFill($data)->save();

        return $role;
    }

    public function countForViewer(ClientLogin $viewer): int
    {
        return ClientRole::query()
            ->when(! $viewer->role?->isSuperuser(), fn (Builder $query): Builder => $query
                ->where('is_system', false)
                ->where('code', '!=', 'superuser'))
            ->count();
    }

    public function accessSnapshot(ClientRole $role): array
    {
        return [
            'module_ids' => $role->mods()
                ->pluck('starter_app_mods.id')
                ->map(fn (int|string $id): int => (int) $id)
                ->all(),
            'landing_menu_ids' => $role->landings()
                ->pluck('app_menu_id', 'app_id')
                ->map(fn (int|string $id): int => (int) $id)
                ->all(),
            'can_manage_settings' => (bool) $role->can_manage_settings,
            'can_view_logs' => (bool) $role->can_view_logs,
        ];
    }

    public function syncMods(ClientRole $role, array $moduleIds): void
    {
        $role->mods()->sync($moduleIds);
    }

    public function syncLandings(ClientRole $role, array $landings): void
    {
        $appIds = collect($landings)->keys()->map(fn (int|string $id): int => (int) $id)->all();

        $staleLandings = ClientRoleAppLanding::query()->where('client_role_id', $role->id);

        $appIds === []
            ? $staleLandings->delete()
            : $staleLandings->whereNotIn('app_id', $appIds)->delete();

        foreach ($landings as $appId => $menuId) {
            ClientRoleAppLanding::query()->updateOrCreate([
                'client_role_id' => $role->id,
                'app_id' => (int) $appId,
            ], [
                'app_menu_id' => (int) $menuId,
            ]);
        }
    }

    public function detachMods(ClientRole $role): void
    {
        $role->mods()->detach();
    }

    public function detachLandings(ClientRole $role): void
    {
        $role->landings()->delete();
    }

    public function modIds(ClientRole $role): Collection
    {
        return $role->mods()->pluck('starter_app_mods.id');
    }

    public function landingMenuForApp(ClientRole $role, string $appSubdomain): ?AppMenu
    {
        return ClientRoleAppLanding::query()
            ->where('client_role_id', $role->id)
            ->whereHas('app', function ($query) use ($appSubdomain): void {
                $query->where('subdomain', $appSubdomain);
            })
            ->with('menu.route', 'menu.mod.app')
            ->first()
            ?->menu;
    }

    public function hasClientLogins(ClientRole $role): bool
    {
        return $role->clientLogins()->exists();
    }

    public function deleteRole(ClientRole $role): void
    {
        $role->delete();
    }

    public function restore(ClientRole $role): void
    {
        $role->restore();
    }

    public function forceDelete(ClientRole $role): void
    {
        $role->forceDelete();
    }
}
