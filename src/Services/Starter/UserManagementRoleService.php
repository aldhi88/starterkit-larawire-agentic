<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\AppModInterface;
use Altekno\StarterKit\Contracts\Starter\ClientRoleInterface;
use Altekno\StarterKit\Models\Starter\AppMod;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Models\Starter\ClientRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserManagementRoleService
{
    /** @var Collection<int, AppMod>|null */
    private ?Collection $availableModulesCache = null;

    public function __construct(
        private readonly ClientRoleInterface $clientRoles,
        private readonly AppModInterface $appMods,
        private readonly AuditLogService $auditLogs,
    ) {}

    /**
     * @return LengthAwarePaginator<int, ClientRole>
     */
    public function paginateRoles(
        ClientLogin $login,
        string $search,
        int $perPage = 10,
        string $pageName = 'rolesPage',
    ): LengthAwarePaginator {
        return $this->clientRoles->paginateForViewer(
            $login,
            str($search)->trim()->limit(100, '')->toString(),
            $perPage,
            $pageName,
        );
    }

    public function tableQuery(ClientLogin $login, string $archiveStatus = 'active'): Builder
    {
        return $this->clientRoles->tableQueryForViewer($login, $archiveStatus);
    }

    /** @return Collection<int, ClientRole> */
    public function filterOptions(ClientLogin $login, string $archiveStatus = 'active'): Collection
    {
        return $this->tableQuery($login, $archiveStatus)
            ->reorder('name')
            ->get(['starter_client_roles.id', 'starter_client_roles.name']);
    }

    /** @param list<int> $ids */
    public function archiveRoles(ClientLogin $login, array $ids): int
    {
        return $this->mutateRoles($login, $ids, 'archive');
    }

    /** @param list<int> $ids */
    public function restoreRoles(ClientLogin $login, array $ids): int
    {
        return $this->mutateRoles($login, $ids, 'restore');
    }

    /** @param list<int> $ids */
    public function forceDeleteRoles(ClientLogin $login, array $ids): int
    {
        return $this->mutateRoles($login, $ids, 'forceDelete');
    }

    /**
     * @return array{apps: int, modules: int}
     */
    public function moduleStats(): array
    {
        return $this->appMods->accessStats();
    }

    public function findRole(ClientLogin $login, int $id): ClientRole
    {
        $role = $this->clientRoles->findForManagement($id);

        abort_unless($role instanceof ClientRole, 404);
        abort_if($role->isSuperuser() && ! $login->role?->isSuperuser(), 404);

        return $role;
    }

    /**
     * @return Collection<int, ClientLogin>
     */
    public function roleUsers(ClientRole $role): Collection
    {
        return $this->clientRoles->clientLogins($role);
    }

    /**
     * @return Collection<int, AppMod>
     */
    public function availableModules(): Collection
    {
        return $this->availableModulesCache ??= $this->appMods->allForRoleAccessManagement();
    }

    /**
     * @param  array{name: string, code: string, desc?: ?string, can_manage_settings?: bool, can_view_logs?: bool}  $data
     * @param  array<int, int|string>  $moduleIds
     * @param  array<int|string, int|string|null>  $landingMenuIds
     */
    public function saveRole(ClientLogin $login, ?int $roleId, array $data, array $moduleIds, array $landingMenuIds = []): ClientRole
    {
        $code = $this->normalizeCode($data['code']);
        $moduleIds = $this->moduleIds($moduleIds);
        $landings = $this->landingMenuIds($moduleIds, $landingMenuIds);

        $actionKey = $roleId ? 'role.update' : 'role.create';
        $actionLabel = ($roleId ? 'Mengubah role ' : 'Membuat role ').trim($data['name']);

        return $this->auditLogs->withinAction($actionKey, $actionLabel, function () use ($roleId, $data, $moduleIds, $landings, $code): ClientRole {
            return DB::transaction(function () use ($roleId, $data, $moduleIds, $landings, $code): ClientRole {
                $role = $roleId ? $this->clientRoles->findBasicById($roleId) : null;

                if ($roleId && ! $role instanceof ClientRole) {
                    abort(404);
                }

                if ($role?->isSuperuser()) {
                    throw ValidationException::withMessages([
                        'role' => 'Role bawaan Superuser hanya dapat dilihat.',
                    ]);
                }

                $oldAccess = $role instanceof ClientRole
                    ? $this->clientRoles->accessSnapshot($role)
                    : [
                        'module_ids' => [],
                        'landing_menu_ids' => [],
                        'can_manage_settings' => false,
                        'can_view_logs' => false,
                    ];

                $payload = [
                    'code' => $code,
                    'name' => trim($data['name']),
                    'desc' => filled($data['desc'] ?? null) ? trim((string) $data['desc']) : null,
                    'can_manage_settings' => (bool) ($data['can_manage_settings'] ?? false),
                    'can_view_logs' => (bool) ($data['can_view_logs'] ?? false),
                ];

                $role = $role instanceof ClientRole
                    ? $this->clientRoles->updateRole($role, $payload)
                    : $this->clientRoles->createRole($payload);

                $this->clientRoles->syncMods($role, $moduleIds);
                $this->clientRoles->syncLandings($role, $landings);

                $this->auditLogs->recordManual(
                    'updated',
                    ClientRole::class,
                    $role->id,
                    [
                        'module_ids' => $oldAccess['module_ids'],
                        'landing_menu_ids' => $oldAccess['landing_menu_ids'],
                        'can_manage_settings' => $oldAccess['can_manage_settings'],
                        'can_view_logs' => $oldAccess['can_view_logs'],
                    ],
                    [
                        'module_ids' => $moduleIds,
                        'landing_menu_ids' => $landings,
                        'can_manage_settings' => $role->can_manage_settings,
                        'can_view_logs' => $role->can_view_logs,
                    ],
                    tableName: 'pivot_client_roles_app_mods',
                    auditableLabel: $role->name,
                    metadata: ['operation' => 'sync_role_access'],
                );

                return $this->clientRoles->findForManagement($role->id) ?? $role;
            });
        });
    }

    public function deleteRole(ClientLogin $login, int $roleId): void
    {
        $role = $this->findRole($login, $roleId);

        if ($role->isSuperuser()) {
            throw ValidationException::withMessages([
                'role' => 'Role bawaan Superuser tidak dapat dihapus.',
            ]);
        }

        if ($this->clientRoles->hasClientLogins($role)) {
            throw ValidationException::withMessages([
                'role' => 'Role masih digunakan oleh satu atau beberapa user.',
            ]);
        }

        $this->archiveRoles($login, [$role->id]);
    }

    private function normalizeCode(string $code): string
    {
        return Str::of($code)->lower()->slug('_')->toString();
    }

    /**
     * @param  array<int, int|string>  $moduleIds
     * @return array<int, int>
     */
    private function moduleIds(array $moduleIds): array
    {
        return collect($moduleIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $moduleIds
     * @param  array<int|string, int|string|null>  $landingMenuIds
     * @return array<int, int>
     */
    private function landingMenuIds(array $moduleIds, array $landingMenuIds): array
    {
        if ($moduleIds === []) {
            return [];
        }

        $mods = $this->appMods->forIdsWithNavigableMenus($moduleIds);

        $landings = [];

        foreach ($mods->groupBy('app_id') as $appId => $appMods) {
            $appName = $appMods->first()?->app?->name ?? 'app terpilih';
            $candidateMenuIds = $appMods
                ->flatMap(fn (AppMod $mod): Collection => $mod->menus)
                ->pluck('id')
                ->map(fn (int|string $id): int => (int) $id)
                ->unique()
                ->values();

            if ($candidateMenuIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'roleForm.landing_menu_ids' => "Module yang dipilih untuk {$appName} belum memiliki halaman awal.",
                ]);
            }

            $menuId = (int) ($landingMenuIds[$appId] ?? 0);

            if ($menuId === 0) {
                throw ValidationException::withMessages([
                    'roleForm.landing_menu_ids' => "Halaman awal wajib dipilih untuk {$appName}.",
                ]);
            }

            if (! $candidateMenuIds->contains($menuId)) {
                throw ValidationException::withMessages([
                    'roleForm.landing_menu_ids' => "Halaman awal untuk {$appName} harus berasal dari module yang dipilih.",
                ]);
            }

            $landings[(int) $appId] = $menuId;
        }

        return $landings;
    }

    /** @param list<int> $ids */
    private function mutateRoles(ClientLogin $login, array $ids, string $operation): int
    {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        $changed = 0;

        foreach ($ids as $id) {
            $role = $this->clientRoles->findWithTrashedForManagement($id);

            if (! $role instanceof ClientRole || $role->isSuperuser()) {
                continue;
            }

            if ($operation !== 'restore' && $this->clientRoles->hasClientLogins($role)) {
                continue;
            }

            if ($operation === 'archive' && ! $role->trashed()) {
                $this->auditLogs->withinAction('role.archive', 'Mengarsipkan role '.$role->name, fn () => $this->clientRoles->deleteRole($role));
                $changed++;
            } elseif ($operation === 'restore' && $role->trashed()) {
                $this->auditLogs->withinAction('role.restore', 'Memulihkan role '.$role->name, fn () => $this->clientRoles->restore($role));
                $changed++;
            } elseif ($operation === 'forceDelete' && $role->trashed()) {
                $this->auditLogs->withinAction('role.force_delete', 'Menghapus permanen role '.$role->name, function () use ($role): void {
                    DB::transaction(function () use ($role): void {
                        $this->clientRoles->detachLandings($role);
                        $this->clientRoles->detachMods($role);
                        $this->clientRoles->forceDelete($role);
                    });
                });
                $changed++;
            }
        }

        return $changed;
    }
}
