<div>
    @unless ($embedded)
        <div class="page-header d-print-none mt-0 mb-3" aria-label="Header halaman">
            <div class="row g-3 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Starter / Manajemen User</div>
                    <h2 class="page-title">Roles</h2>
                    <div class="text-secondary">Kelola role tanpa mencampurkan daftar dengan form pengaturan akses.</div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('starter.settings.roles.create') }}" class="btn btn-primary" data-starter-navigate>
                        @include('starter.templates.layouts.icon', ['name' => 'file-plus', 'class' => 'icon-sm me-1'])
                        Tambah Role
                    </a>
                </div>
            </div>
        </div>
    @endunless

    <livewire:starter.user-management.roles-table />

    {{-- Legacy markup remains temporarily compile-safe while PowerGrid owns the rendered table. --}}
    @if (false)
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Daftar Role</h3>
                <p class="card-subtitle">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($roleCount) }} role terdaftar</p>
            </div>
            <div class="card-actions">
                <a
                    href="{{ route('starter.settings.roles.create') }}"
                    class="btn btn-primary"
                    data-role-create-location="content"
                    data-starter-navigate
                >
                    @include('starter.templates.layouts.icon', ['name' => 'file-plus', 'class' => 'icon-sm me-1'])
                    Tambah Role
                </a>
            </div>
        </div>

        <div class="card-body border-bottom py-3">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg">
                    <input
                        type="search"
                        class="form-control"
                        maxlength="100"
                        placeholder="Cari nama, kode, atau deskripsi role"
                        aria-label="Cari role"
                        wire:model.live.debounce.350ms="search"
                    >
                </div>
                <div class="col-12 col-lg-auto text-secondary small">
                    Menampilkan {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($roles->firstItem() ?? 0) }}–{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($roles->lastItem() ?? 0) }} dari {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($roles->total()) }} role
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table" style="table-layout: fixed; min-width: 42rem;">
                <thead>
                    <tr>
                        <th style="width: 52%;">Role</th>
                        <th class="text-nowrap" style="width: 26%;">Cakupan Akses</th>
                        <th style="width: 12%;">User</th>
                        <th style="width: 10%;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        @php
                            $roleAppCount = $role->isSuperuser()
                                ? $totalAppCount
                                : $role->mods->pluck('app_id')->filter()->unique()->count();
                            $roleModuleCount = $role->isSuperuser()
                                ? $totalModuleCount
                                : $role->mods->count();
                        @endphp
                        <tr
                            class="{{ $role->isSuperuser() ? 'bg-danger-lt' : '' }}"
                            wire:key="role-row-{{ $role->id }}"
                            @if ($role->isSuperuser()) data-default-role @endif
                        >
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span
                                        class="avatar avatar-sm flex-shrink-0 {{ $role->isSuperuser() ? 'bg-danger-lt text-danger' : 'bg-primary-lt text-primary' }}"
                                        data-role-avatar
                                    >
                                        @include('starter.templates.layouts.icon', ['name' => $role->isSuperuser() ? 'shield-check' : 'shield-lock', 'class' => 'm-0'])
                                    </span>
                                    <div class="overflow-hidden">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-semibold">{{ $role->name }}</span>
                                            @if ($role->isSuperuser())
                                                <span class="badge bg-danger-lt text-danger">Role Default</span>
                                            @endif
                                            @if (! $role->isSuperuser() && $role->canManageSettings())
                                                <span class="badge bg-azure-lt text-azure">
                                                    @include('starter.templates.layouts.icon', ['name' => 'settings', 'class' => 'icon-sm me-1'])
                                                    Pengaturan
                                                </span>
                                            @endif
                                            @if (! $role->isSuperuser() && $role->canViewLogs())
                                                <span class="badge bg-purple-lt text-purple">
                                                    @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon-sm me-1'])
                                                    Log
                                                </span>
                                            @endif
                                        </div>
                                        <div class="small text-secondary text-truncate">
                                            @if ($role->isSuperuser())
                                                <span class="font-monospace">{{ $role->code }}</span>
                                                <span class="mx-1">·</span>
                                                Tidak dapat diedit, hanya dapat dilihat oleh Superuser.
                                            @else
                                                <span class="font-monospace">{{ $role->code }}</span>
                                                <span class="mx-1">·</span>
                                                {{ filled($role->desc) ? $role->desc : 'Belum ada deskripsi' }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <button
                                    type="button"
                                    class="starter-role-access-trigger d-inline-flex align-items-center gap-2 border-0 bg-transparent p-0 text-start"
                                    wire:click="showRoleAccess({{ $role->id }})"
                                    aria-label="Lihat detail akses role {{ $role->name }}"
                                    data-role-access-summary
                                    data-role-access-trigger
                                >
                                    <span class="avatar avatar-xs rounded {{ $role->isSuperuser() ? 'bg-success-lt text-success' : 'bg-primary-lt text-primary' }} flex-shrink-0">
                                        @include('starter.templates.layouts.icon', ['name' => 'apps', 'class' => 'icon-sm m-0'])
                                    </span>
                                    <span class="min-w-0">
                                        <span class="starter-role-access-title d-block fw-semibold text-truncate">
                                            {{ $role->isSuperuser() ? 'Akses penuh' : \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($roleAppCount).' app' }}
                                        </span>
                                        <span class="d-block small text-secondary fw-normal" data-role-module-count>{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($roleModuleCount) }} module</span>
                                    </span>
                                    @include('starter.templates.layouts.icon', ['name' => 'chevron-right', 'class' => 'icon-sm text-secondary flex-shrink-0'])
                                </button>
                            </td>
                            <td>
                                @if ($role->client_logins_count > 0)
                                    <button
                                        type="button"
                                        class="starter-table-action-link"
                                        wire:click="showRoleUsers({{ $role->id }})"
                                        aria-label="Lihat user dalam role {{ $role->name }}"
                                        data-role-users-trigger
                                    >
                                        {{ $role->client_logins_count }} user
                                    </button>
                                @else
                                    <span class="text-secondary">0 user</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a
                                    href="{{ route('starter.settings.roles.edit', $role->id) }}"
                                    class="btn btn-sm"
                                    data-starter-navigate
                                >
                                    {{ $role->isSuperuser() ? 'Lihat' : 'Edit' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty py-5">
                                    <div class="empty-icon">
                                        @include('starter.templates.layouts.icon', ['name' => 'users-group'])
                                    </div>
                                    <p class="empty-title">Role tidak ditemukan</p>
                                    <p class="empty-subtitle text-secondary">Coba kata kunci lain atau buat role baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($roles->hasPages())
            <div class="card-footer d-flex align-items-center">
                <div class="ms-auto">
                    {{ $roles->links() }}
                </div>
            </div>
        @endif
    </div>

    @endif

    @if ($roleUsersModalOpen)
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="role-users-modal-title" wire:click.self="closeRoleUsersModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="role-users-modal-title">User dalam Role</h3>
                            <div class="text-secondary small">{{ $roleUsersRoleName }} · {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal(count($roleUsers)) }} user</div>
                        </div>
                        <button type="button" class="btn-close" aria-label="Tutup" wire:click="closeRoleUsersModal"></button>
                    </div>
                    <div class="modal-body overflow-auto" style="max-height: min(32rem, calc(100vh - 14rem));">
                        <div class="list-group list-group-flush">
                            @forelse ($roleUsers as $user)
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="avatar avatar-sm">{{ str($user['name'])->substr(0, 1)->upper() }}</span>
                                        <div class="overflow-hidden">
                                            <div class="fw-semibold text-truncate">{{ $user['name'] }}</div>
                                            <div class="text-secondary small text-truncate">{{ $user['email'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="empty">
                                    <p class="empty-title">Belum ada user</p>
                                    <p class="empty-subtitle text-secondary">Role ini belum digunakan oleh akun login mana pun.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" wire:click="closeRoleUsersModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @if ($roleAccessModalOpen)
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="role-access-modal-title" wire:click.self="closeRoleAccessModal">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="role-access-modal-title">Detail Akses Role</h3>
                            <div class="text-secondary small">
                                {{ $roleAccessRoleName }}
                                <span class="mx-1">·</span>
                                <span class="font-monospace">{{ $roleAccessRoleCode }}</span>
                            </div>
                        </div>
                        <button type="button" class="btn-close" aria-label="Tutup" wire:click="closeRoleAccessModal"></button>
                    </div>
                    <div class="modal-body">
                        @if ($roleAccessIsFull)
                            <div class="alert alert-success" role="note">
                                <div class="d-flex gap-2">
                                    @include('starter.templates.layouts.icon', ['name' => 'shield-check', 'class' => 'icon-sm flex-shrink-0 mt-1'])
                                    <div>
                                        <div class="fw-semibold">Akses penuh role default</div>
                                        <div class="small">Superuser otomatis dapat mengakses seluruh app dan module yang terdaftar.</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (! $roleAccessIsFull)
                            <div class="card card-sm mb-3">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item">
                                        <div class="d-flex gap-2">
                                            @include('starter.templates.layouts.icon', ['name' => $roleAccessCanManageSettings ? 'settings' : 'lock', 'class' => 'icon-sm flex-shrink-0 mt-1 '.($roleAccessCanManageSettings ? 'text-azure' : 'text-secondary')])
                                            <div>
                                                <div class="fw-semibold">
                                                    {{ $roleAccessCanManageSettings ? 'Dapat mengakses Pengaturan' : 'Tidak dapat mengakses Pengaturan' }}
                                                </div>
                                                <div class="small text-secondary">
                                                    {{ $roleAccessCanManageSettings
                                                        ? 'Dapat mengelola role, user, dan profil perusahaan.'
                                                        : 'Tidak memiliki izin mengelola pusat Pengaturan.' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex gap-2">
                                            @include('starter.templates.layouts.icon', ['name' => $roleAccessCanViewLogs ? 'history' : 'lock', 'class' => 'icon-sm flex-shrink-0 mt-1 '.($roleAccessCanViewLogs ? 'text-purple' : 'text-secondary')])
                                            <div>
                                                <div class="fw-semibold">
                                                    {{ $roleAccessCanViewLogs ? 'Dapat melihat Log Aktivitas' : 'Tidak dapat melihat Log Aktivitas' }}
                                                </div>
                                                <div class="small text-secondary">
                                                    {{ $roleAccessCanViewLogs
                                                        ? 'Dapat meninjau riwayat pembuatan, perubahan, dan penghapusan data.'
                                                        : 'Riwayat perubahan data tidak dapat dibuka oleh role ini.' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="vstack gap-3" data-role-access-detail>
                            @forelse ($roleAccessApps as $app)
                                <div class="border rounded overflow-hidden">
                                    <div class="d-flex align-items-center gap-2 bg-body-tertiary px-3 py-2 border-bottom">
                                        @include('starter.templates.layouts.icon', ['name' => 'apps', 'class' => 'icon-sm text-primary flex-shrink-0'])
                                        <div class="fw-semibold">{{ $app['name'] }}</div>
                                        <span class="badge bg-secondary-lt ms-auto">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal(count($app['modules'])) }} module</span>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        @foreach ($app['modules'] as $module)
                                            <div class="list-group-item">
                                                <div class="d-flex align-items-baseline gap-2">
                                                    <span class="fw-semibold">{{ $module['name'] }}</span>
                                                    <span class="small text-secondary font-monospace">{{ $module['code'] }}</span>
                                                </div>
                                                @if (filled($module['desc']))
                                                    <div class="small text-secondary mt-1">{{ $module['desc'] }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="empty py-4">
                                    <div class="empty-icon">
                                        @include('starter.templates.layouts.icon', ['name' => 'shield-lock'])
                                    </div>
                                    <p class="empty-title">Belum ada akses module</p>
                                    <p class="empty-subtitle text-secondary">Role ini belum memiliki app atau module yang dapat diakses.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="text-secondary small me-auto">
                            {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($roleAccessAppCount) }} app · {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($roleAccessModuleCount) }} module
                        </div>
                        <button type="button" class="btn" wire:click="closeRoleAccessModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
