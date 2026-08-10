<?php

namespace Altekno\StarterKit\Contracts\Starter;

use Altekno\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface ClientLoginInterface
{
    public function tableQueryForViewer(ClientLogin $viewer, string $archiveStatus = 'active'): Builder;

    /** Find a login account by its username or email address. */
    public function findByUsername(string $username): ?ClientLogin;

    /**
     * @return LengthAwarePaginator<int, ClientLogin>
     */
    public function paginateForViewer(
        ClientLogin $viewer,
        string $search,
        string $status,
        int $perPage,
        string $pageName,
    ): LengthAwarePaginator;

    public function findBasicById(int $id): ?ClientLogin;

    public function findForManagement(int $id): ?ClientLogin;

    public function findWithTrashedForManagement(int $id): ?ClientLogin;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): ClientLogin;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUser(ClientLogin $login, array $data): ClientLogin;

    public function refreshWithRole(ClientLogin $login): ClientLogin;

    public function loadRole(ClientLogin $login): ClientLogin;

    public function countForViewer(ClientLogin $viewer): int;

    public function revokeRememberTokens(): int;

    public function archive(ClientLogin $login): void;

    public function restore(ClientLogin $login): void;

    public function forceDelete(ClientLogin $login): void;
}
