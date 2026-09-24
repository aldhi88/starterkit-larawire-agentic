<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Contracts\Starter\AppModInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientRoleInterface;
use Aldhi88\StarterKit\Exceptions\Starter\TemporaryPasswordDeliveryException;
use Aldhi88\StarterKit\Models\Starter\AppMod;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Rules\Starter\StarterPasswordRules;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
        private readonly TemporaryPasswordMailService $temporaryPasswordMail,
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

    /** @return Builder<ClientLogin> */
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
        abort_if($login->role->isSuperuser() && ! $currentLogin->role->isSuperuser(), 404);

        return $login;
    }

    public function findPasswordResetTarget(ClientLogin $currentLogin, int $id): ClientLogin
    {
        $login = $this->findUser($currentLogin, $id);

        abort_if(
            $login->role->isSuperuser(),
            403,
            'Password Superuser hanya dapat diubah melalui Edit Profil Saya.',
        );

        return $login;
    }

    /**
     * @param  array{name: string, username: string, email: string, client_role_id: int|string, status: string}  $data
     */
    public function saveUser(
        ClientLogin $currentLogin,
        ?int $userLoginId,
        array $data,
        ?string $manualTemporaryPassword = null,
    ): ClientLogin {
        $role = $this->clientRoles->findBasicById((int) $data['client_role_id']);

        if (! $role instanceof ClientRole) {
            throw ValidationException::withMessages(['userForm.role_id' => 'Role tidak valid.']);
        }

        $login = $userLoginId ? $this->clientLogins->findForManagement($userLoginId) : null;

        if ($userLoginId && ! $login instanceof ClientLogin) {
            abort(404);
        }

        abort_if($login?->role?->isSuperuser() && ! $currentLogin->role->isSuperuser(), 404);

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

        if ($login instanceof ClientLogin) {
            return $this->auditLogs->withinAction(
                'user.update',
                'Mengubah user '.$payload['name'],
                fn (): ClientLogin => $this->clientLogins->updateUser($login, $payload),
            );
        }

        if ($manualTemporaryPassword !== null) {
            $passwordValidator = Validator::make(
                ['password' => $manualTemporaryPassword],
                ['password' => StarterPasswordRules::rules()],
                [],
                ['password' => 'password sementara'],
            );

            if ($passwordValidator->fails()) {
                throw ValidationException::withMessages([
                    'userForm.password' => $passwordValidator->errors()->get('password'),
                ]);
            }
        }

        $temporaryPassword = $manualTemporaryPassword ?? Str::password(16);
        $createdLogin = null;

        try {
            return $this->auditLogs->withinAction(
                'user.create',
                'Membuat user '.$payload['name'],
                function () use ($payload, $temporaryPassword, $manualTemporaryPassword, &$createdLogin): ClientLogin {
                    return DB::transaction(function () use ($payload, $temporaryPassword, $manualTemporaryPassword, &$createdLogin): ClientLogin {
                        $createdLogin = $this->clientLogins->createUser([
                            ...$payload,
                            'password' => $temporaryPassword,
                            'must_change_password' => true,
                        ]);

                        if ($manualTemporaryPassword === null) {
                            $this->temporaryPasswordMail->queueForNewAccount($createdLogin, $temporaryPassword);
                        }

                        return $createdLogin;
                    });
                },
            );
        } catch (TemporaryPasswordDeliveryException) {
            $this->recordTemporaryPasswordDeliveryFailure($currentLogin, $createdLogin, 'user_created');

            throw ValidationException::withMessages([
                'userForm.email' => 'User tidak dibuat karena email password sementara gagal diproses oleh queue. Periksa konfigurasi lalu coba lagi.',
            ]);
        }
    }

    public function resetPassword(ClientLogin $currentLogin, int $userLoginId): void
    {
        $login = $this->findPasswordResetTarget($currentLogin, $userLoginId);
        $temporaryPassword = Str::password(16);

        try {
            $this->auditLogs->withinAction(
                'user.reset_password',
                'Reset password user '.$login->name,
                function () use ($currentLogin, $login, $temporaryPassword): void {
                    DB::transaction(function () use ($currentLogin, $login, $temporaryPassword): void {
                        $this->clientLogins->updateUser($login, [
                            'password' => $temporaryPassword,
                            'must_change_password' => true,
                            'password_changed_at' => now(),
                            'failed_login_count' => 0,
                            'locked_until' => null,
                            'remember_token' => Str::random(60),
                            'auth_version' => max(1, (int) $login->auth_version) + 1,
                        ]);

                        $this->temporaryPasswordMail->queueForReset($login, $temporaryPassword);
                        $this->auditLogs->recordSecurityEvent(
                            'auth.password_reset_by_admin',
                            'Password direset oleh administrator',
                            target: $login,
                            actor: $currentLogin,
                            metadata: [
                                'must_change_password' => true,
                                'delivery' => 'queued_email',
                            ],
                        );
                    });
                },
            );
        } catch (TemporaryPasswordDeliveryException) {
            $this->recordTemporaryPasswordDeliveryFailure($currentLogin, $login, 'password_reset');

            throw ValidationException::withMessages([
                'passwordResetEmail' => 'Password tidak direset karena email gagal diproses oleh queue. Periksa konfigurasi lalu coba lagi.',
            ]);
        }
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

    private function recordTemporaryPasswordDeliveryFailure(
        ClientLogin $currentLogin,
        ?ClientLogin $target,
        string $operation,
    ): void {
        $this->auditLogs->recordSecurityEvent(
            'auth.temporary_password_delivery_failed',
            'Pengiriman email password sementara gagal',
            target: $target,
            actor: $currentLogin,
            metadata: [
                'operation' => $operation,
                'delivery' => 'queued_email',
            ],
        );
    }

    /** @param list<int> $ids */
    private function mutateUsers(ClientLogin $currentLogin, array $ids, string $operation): int
    {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        $changed = 0;

        foreach ($ids as $id) {
            $login = $this->clientLogins->findWithTrashedForManagement($id);

            if (! $login instanceof ClientLogin || $login->id === $currentLogin->id || $login->role->isSuperuser()) {
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
