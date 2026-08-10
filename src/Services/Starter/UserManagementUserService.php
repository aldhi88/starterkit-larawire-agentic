<?php

namespace Altekno\StarterKit\Services\Starter;

use Altekno\StarterKit\Contracts\Starter\AppModInterface;
use Altekno\StarterKit\Contracts\Starter\ClientLoginInterface;
use Altekno\StarterKit\Contracts\Starter\ClientRoleInterface;
use Altekno\StarterKit\Models\Starter\AppMod;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Models\Starter\ClientRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserManagementUserService
{
    /** @var Collection<int, AppMod>|null */
    private ?Collection $availableModulesCache = null;

    public function __construct(
        private readonly ClientLoginInterface $clientLogins,
        private readonly ClientRoleInterface $clientRoles,
        private readonly AppModInterface $appMods,
        private readonly AuditLogService $auditLogs,
    ) {}

    /**
     * @return LengthAwarePaginator<int, ClientLogin>
     */
    public function paginateUsers(
        ClientLogin $login,
        string $search,
        string $status,
        int $perPage = 10,
        string $pageName = 'usersPage',
    ): LengthAwarePaginator {
        return $this->clientLogins->paginateForViewer(
            $login,
            str($search)->trim()->limit(100, '')->toString(),
            $status,
            $perPage,
            $pageName,
        );
    }

    public function tableQuery(ClientLogin $login, string $archiveStatus = 'active'): Builder
    {
        return $this->clientLogins->tableQueryForViewer($login, $archiveStatus);
    }

    /** @param list<int> $ids */
    public function archiveUsers(ClientLogin $currentLogin, array $ids): int
    {
        return $this->mutateUsers($currentLogin, $ids, 'archive');
    }

    /** @param list<int> $ids */
    public function restoreUsers(ClientLogin $currentLogin, array $ids): int
    {
        return $this->mutateUsers($currentLogin, $ids, 'restore');
    }

    /** @param list<int> $ids */
    public function forceDeleteUsers(ClientLogin $currentLogin, array $ids): int
    {
        return $this->mutateUsers($currentLogin, $ids, 'forceDelete');
    }

    /** @return Collection<int, ClientRole> */
    public function roles(ClientLogin $login): Collection
    {
        return $this->clientRoles->allAssignableForViewer($login);
    }

    public function findUser(ClientLogin $currentLogin, int $id): ClientLogin
    {
        $login = $this->clientLogins->findForManagement($id);

        abort_unless($login instanceof ClientLogin, 404);
        abort_if($login->role?->isSuperuser() && ! $currentLogin->role?->isSuperuser(), 404);

        return $login;
    }

    public function findPasswordResetTarget(ClientLogin $currentLogin, int $id): ClientLogin
    {
        $login = $this->findUser($currentLogin, $id);

        abort_if(
            $login->role?->isSuperuser(),
            403,
            'Password Superuser hanya dapat diubah melalui Edit Profil Saya.',
        );

        return $login;
    }

    /**
     * @param  array{name: string, username: string, email: string, client_role_id: int|string, status: string, password?: string}  $data
     */
    public function saveUser(ClientLogin $currentLogin, ?int $userLoginId, array $data): ClientLogin
    {
        $role = $this->clientRoles->findBasicById((int) $data['client_role_id']);

        if (! $role instanceof ClientRole) {
            throw ValidationException::withMessages(['userForm.role_id' => 'Role tidak valid.']);
        }

        $login = $userLoginId ? $this->clientLogins->findForManagement($userLoginId) : null;

        if ($userLoginId && ! $login instanceof ClientLogin) {
            abort(404);
        }

        abort_if($login?->role?->isSuperuser() && ! $currentLogin->role?->isSuperuser(), 404);

        if ($role->isSuperuser() && ! $login?->role?->isSuperuser()) {
            throw ValidationException::withMessages(['userForm.role_id' => 'Role sistem Superuser tidak dapat diberikan ke akun lain.']);
        }

        if ($login?->role?->isSuperuser() && (
            (int) $data['client_role_id'] !== $login->client_role_id || $data['status'] !== 'active'
        )) {
            throw ValidationException::withMessages(['userForm.status' => 'Akun Superuser harus tetap aktif dengan role sistem.']);
        }

        $payload = [
            'name' => trim($data['name']),
            'username' => str($data['username'])->lower()->trim()->toString(),
            'email' => str($data['email'])->lower()->trim()->toString(),
            'client_role_id' => $role->id,
            'status' => $data['status'],
        ];

        if (! $login instanceof ClientLogin) {
            $payload += [
                'password' => $data['password'] ?? Str::password(16),
                'must_change_password' => true,
            ];
        }

        $actionKey = $login instanceof ClientLogin ? 'user.update' : 'user.create';
        $actionLabel = ($login instanceof ClientLogin ? 'Mengubah user ' : 'Membuat user ').$payload['name'];

        return $this->auditLogs->withinAction(
            $actionKey,
            $actionLabel,
            fn (): ClientLogin => $login instanceof ClientLogin
                ? $this->clientLogins->updateUser($login, $payload)
                : $this->clientLogins->createUser($payload),
        );
    }

    public function resetPassword(ClientLogin $currentLogin, int $userLoginId): string
    {
        $login = $this->findPasswordResetTarget($currentLogin, $userLoginId);
        $temporaryPassword = Str::password(16);

        $this->auditLogs->withinAction('user.reset_password', 'Reset password user '.$login->name, function () use ($login, $temporaryPassword): void {
            $this->clientLogins->updateUser($login, [
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'password_changed_at' => now(),
                'failed_login_count' => 0,
                'locked_until' => null,
                'remember_token' => Str::random(60),
                'auth_version' => max(1, (int) $login->auth_version) + 1,
            ]);
        });

        $this->auditLogs->recordSecurityEvent(
            'auth.password_reset_by_admin',
            'Password direset oleh administrator',
            target: $login,
            actor: $currentLogin,
            metadata: ['must_change_password' => true],
        );

        return $temporaryPassword;
    }

    public function appCount(): int
    {
        return $this->appMods->accessStats()['apps'];
    }

    /** @return Collection<int, AppMod> */
    public function availableModules(): Collection
    {
        return $this->availableModulesCache ??= $this->appMods->allForUserAccessPreview();
    }

    /** @param list<int> $ids */
    private function mutateUsers(ClientLogin $currentLogin, array $ids, string $operation): int
    {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        $changed = 0;

        foreach ($ids as $id) {
            $login = $this->clientLogins->findWithTrashedForManagement($id);

            if (! $login instanceof ClientLogin || $login->id === $currentLogin->id || $login->role?->isSuperuser()) {
                continue;
            }

            if ($operation === 'archive' && ! $login->trashed()) {
                $this->auditLogs->withinAction('user.archive', 'Mengarsipkan user '.$login->name, function () use ($login): void {
                    $this->clientLogins->updateUser($login, [
                        'remember_token' => null,
                        'auth_version' => max(1, (int) $login->auth_version) + 1,
                    ]);
                    $this->clientLogins->archive($login);
                });
                $changed++;
            } elseif ($operation === 'restore' && $login->trashed()) {
                $this->auditLogs->withinAction('user.restore', 'Memulihkan user '.$login->name, fn () => $this->clientLogins->restore($login));
                $changed++;
            } elseif ($operation === 'forceDelete' && $login->trashed()) {
                $this->auditLogs->withinAction('user.force_delete', 'Menghapus permanen user '.$login->name, fn () => $this->clientLogins->forceDelete($login));
                $changed++;
            }
        }

        return $changed;
    }
}
