<?php

namespace Altekno\StarterKit\Repositories\Starter;

use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ClientLoginRepository implements ClientLoginInterface
{
    public function tableQueryForViewer(ClientLogin $viewer, string $archiveStatus = 'active'): Builder
    {
        $query = ClientLogin::query()
            ->select([
                'starter_client_logins.*',
                'list_role.name as role_name',
                'list_role.code as role_code',
                'list_role.is_system as role_is_system',
            ])
            ->leftJoin('starter_client_roles as list_role', 'list_role.id', '=', 'starter_client_logins.client_role_id');

        if ($archiveStatus === 'archived') {
            $query->onlyTrashed();
        } elseif ($archiveStatus === 'all') {
            $query->withTrashed();
        }

        if (! $viewer->role?->isSuperuser()) {
            $query->where('list_role.is_system', false)->where('list_role.code', '!=', 'superuser');
        }

        return $query;
    }

    public function findByUsername(string $username): ?ClientLogin
    {
        return ClientLogin::query()
            ->with('role')
            ->where(function (Builder $query) use ($username): void {
                $query
                    ->where('username', $username)
                    ->orWhere('email', $username);
            })
            ->first();
    }

    public function paginateForViewer(
        ClientLogin $viewer,
        string $search,
        string $status,
        int $perPage,
        string $pageName,
    ): LengthAwarePaginator {
        $query = ClientLogin::query()
            ->select('starter_client_logins.*')
            ->leftJoin('starter_client_roles as list_role', 'list_role.id', '=', 'starter_client_logins.client_role_id')
            ->with([
                'role' => fn ($roleQuery) => $roleQuery
                    ->select(['id', 'name', 'code', 'is_system'])
                    ->withCount('mods'),
            ]);

        if (! $viewer->role?->isSuperuser()) {
            $query
                ->where('list_role.is_system', false)
                ->where('list_role.code', '!=', 'superuser');
        }

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function (Builder $searchQuery) use ($term): void {
                $searchQuery
                    ->where('starter_client_logins.name', 'like', $term)
                    ->orWhere('starter_client_logins.username', 'like', $term)
                    ->orWhere('starter_client_logins.email', 'like', $term)
                    ->orWhere('list_role.name', 'like', $term);
            });
        }

        if (in_array($status, ['active', 'inactive', 'locked'], true)) {
            $query->where('starter_client_logins.status', $status);
        }

        return $query
            ->orderByRaw("CASE WHEN list_role.is_system = 1 OR list_role.code = 'superuser' THEN 0 ELSE 1 END")
            ->orderBy('starter_client_logins.name')
            ->orderBy('starter_client_logins.id')
            ->paginate($perPage, ['starter_client_logins.*'], $pageName);
    }

    public function findBasicById(int $id): ?ClientLogin
    {
        return ClientLogin::query()
            ->whereKey($id)
            ->first();
    }

    public function findForManagement(int $id): ?ClientLogin
    {
        return ClientLogin::query()
            ->with('role.mods.app')
            ->whereKey($id)
            ->first();
    }

    public function findWithTrashedForManagement(int $id): ?ClientLogin
    {
        return ClientLogin::withTrashed()
            ->with('role')
            ->whereKey($id)
            ->first();
    }

    public function createUser(array $data): ClientLogin
    {
        return ClientLogin::query()->create($data);
    }

    public function updateUser(ClientLogin $login, array $data): ClientLogin
    {
        $login->forceFill($data)->save();

        return $login;
    }

    public function refreshWithRole(ClientLogin $login): ClientLogin
    {
        return $login->refresh()->load('role');
    }

    public function loadRole(ClientLogin $login): ClientLogin
    {
        return $login->loadMissing('role');
    }

    public function countForViewer(ClientLogin $viewer): int
    {
        return ClientLogin::query()
            ->when(! $viewer->role?->isSuperuser(), fn (Builder $query): Builder => $query->whereHas(
                'role',
                fn (Builder $roleQuery): Builder => $roleQuery
                    ->where('is_system', false)
                    ->where('code', '!=', 'superuser'),
            ))
            ->count();
    }

    public function revokeRememberTokens(): int
    {
        return ClientLogin::query()
            ->whereNotNull('remember_token')
            ->update(['remember_token' => null]);
    }

    public function archive(ClientLogin $login): void
    {
        $login->delete();
    }

    public function restore(ClientLogin $login): void
    {
        $login->restore();
    }

    public function forceDelete(ClientLogin $login): void
    {
        $login->forceDelete();
    }
}
