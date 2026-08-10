@php
    $appTotal = $modules->count();
    $selectedModuleIds = collect($roleForm['module_ids'])->map(fn ($id): string => (string) $id)->all();
    $isSuperuserRole = $selectedRole?->isSuperuser() ?? false;
    $grantedAppCount = $isSuperuserRole
        ? $appTotal
        : $modules
            ->filter(fn ($appModules): bool => $appModules->contains(
                fn ($module): bool => in_array((string) $module->id, $selectedModuleIds, true)
            ))
            ->count();
    $grantedModuleCount = $isSuperuserRole
        ? $modules->flatten(1)->count()
        : count($selectedModuleIds);
    $assignedUserCount = $selectedRole?->client_logins_count ?? 0;
    $moduleAppKeys = $modules
        ->map(fn ($appModules): string => 'app-'.($appModules->first()?->app_id ?? 'none'))
        ->values()
        ->all();
    $isCreating = $roleId === null;
@endphp

<div
    x-data="{
        moduleAppKeys: @js($moduleAppKeys),
        expandedModuleApps: [],
        isModuleAppExpanded(appKey) {
            return this.expandedModuleApps.includes(appKey);
        },
        allModuleAppsExpanded() {
            return this.moduleAppKeys.length > 0
                && this.moduleAppKeys.every((appKey) => this.isModuleAppExpanded(appKey));
        },
        toggleModuleApp(appKey) {
            this.expandedModuleApps = this.isModuleAppExpanded(appKey)
                ? this.expandedModuleApps.filter((key) => key !== appKey)
                : [...this.expandedModuleApps, appKey];
        },
        toggleAllModuleApps() {
            this.expandedModuleApps = this.allModuleAppsExpanded()
                ? []
                : [...this.moduleAppKeys];
        },
    }"
>
    <div class="page-header d-print-none mt-0 mb-3" aria-label="Header halaman">
        <div class="row g-3 align-items-start">
            <div class="col min-w-0">
                <div class="page-pretitle">Pengaturan / Roles</div>
                <h2 class="page-title">{{ $isCreating ? 'Tambah Role' : ($isSuperuserRole ? 'Detail Role' : 'Edit Role') }}</h2>
                <div class="text-secondary mt-1">
                    {{ $isCreating ? 'Buat identitas role, pilih akses module, lalu tentukan halaman awal.' : 'Kelola identitas dan cakupan akses role pada halaman khusus ini.' }}
                </div>
            </div>
            <div class="col-12 col-md-auto align-self-md-end">
                <div class="btn-list">
                    <a href="{{ route('starter.settings', ['section' => 'roles']) }}" class="btn" data-starter-navigate>
                        @include('starter.templates.layouts.icon', ['name' => 'arrow-left', 'class' => 'icon-sm me-1'])
                        Batal dan Kembali
                    </a>
                    @if (! $isSuperuserRole)
                        <button type="submit" form="role-form" class="btn btn-primary">
                            @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'icon-sm me-1'])
                            {{ $isCreating ? 'Simpan Role' : 'Simpan Perubahan' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form id="role-form" wire:submit="save">
        <div class="row g-3 align-items-start" data-role-form-layout="split">
            <div class="col-12 col-xl-5" data-role-identity-panel>
                <div class="card position-xl-sticky" style="top: 1rem;">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Identitas Role</h3>
                            <p class="card-subtitle">Informasi dasar dan ringkasan cakupan akses role.</p>
                        </div>
                        @if ($roleId && ! $isSuperuserRole)
                            <div class="card-actions">
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="prepareRoleDeletion">
                                    @include('starter.templates.layouts.icon', ['name' => 'trash', 'class' => 'icon-sm me-1'])
                                    Arsipkan Role
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="rounded border bg-body-tertiary p-3 mb-4" data-role-form-summary>
                            <div class="d-flex align-items-center gap-3 min-w-0">
                                <span class="avatar {{ $isSuperuserRole ? 'bg-danger-lt text-danger' : 'bg-primary-lt text-primary' }}">
                                    @include('starter.templates.layouts.icon', ['name' => $isSuperuserRole ? 'shield-check' : 'shield-lock', 'class' => 'icon'])
                                </span>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">
                                        {{ filled($roleForm['name']) ? $roleForm['name'] : 'Role Baru' }}
                                    </div>
                                    <div class="small text-secondary font-monospace text-truncate">
                                        {{ filled($roleForm['code']) ? $roleForm['code'] : 'kode_role' }}
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                @if ($isSuperuserRole)
                                    <span class="status status-red status-lite">Role Sistem</span>
                                @elseif ($isCreating)
                                    <span class="status status-secondary status-lite">Belum Disimpan</span>
                                @else
                                    <span class="status status-blue status-lite">Role Aktif</span>
                                @endif
                                <span class="badge bg-primary-lt text-primary">
                                    {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($grantedAppCount) }} app · {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($grantedModuleCount) }} module
                                </span>
                                @if ($roleForm['can_manage_settings'])
                                    <span class="badge bg-azure-lt text-azure">
                                        @include('starter.templates.layouts.icon', ['name' => 'settings', 'class' => 'icon-sm me-1'])
                                        Pengaturan
                                    </span>
                                @endif
                                @if ($roleForm['can_view_logs'])
                                    <span class="badge bg-purple-lt text-purple">
                                        @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon-sm me-1'])
                                        Log Aktivitas
                                    </span>
                                @endif
                                <span class="badge bg-secondary-lt">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($assignedUserCount) }} user</span>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label required">Kode</label>
                                <input
                                    type="text"
                                    class="form-control @error('roleForm.code') is-invalid @enderror"
                                    placeholder="contoh: supervisor"
                                    wire:model.defer="roleForm.code"
                                    @readonly($isSuperuserRole)
                                >
                                <div class="form-hint">Huruf kecil, angka, tanda hubung, atau underscore.</div>
                                @error('roleForm.code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Nama Role</label>
                                <input
                                    type="text"
                                    class="form-control @error('roleForm.name') is-invalid @enderror"
                                    placeholder="contoh: Supervisor Operasional"
                                    wire:model.defer="roleForm.name"
                                    @readonly($isSuperuserRole)
                                >
                                @error('roleForm.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea
                                    class="form-control @error('roleForm.desc') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Jelaskan tanggung jawab dan batasan akses role ini."
                                    wire:model.defer="roleForm.desc"
                                    @readonly($isSuperuserRole)
                                ></textarea>
                                @error('roleForm.desc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h3 class="card-title mt-4">Akses Sistem Khusus</h3>
                        <div class="row g-3" data-role-system-access>
                            <div class="col-12">
                                <label class="form-check form-switch mb-4">
                                    <input
                                        id="role-can-manage-settings"
                                        type="checkbox"
                                        class="form-check-input"
                                        wire:model.defer="roleForm.can_manage_settings"
                                        @disabled($isSuperuserRole)
                                    >
                                    <span class="form-check-label">
                                        <span class="d-flex align-items-center gap-2 fw-semibold mb-1">
                                            @include('starter.templates.layouts.icon', ['name' => 'settings', 'class' => 'icon-sm text-azure'])
                                            Akses Pengaturan
                                        </span>
                                        <span class="d-block small text-secondary">
                                            Izinkan mengelola role, user, dan profil perusahaan.
                                        </span>
                                        @if ($isSuperuserRole)
                                            <span class="d-block small text-danger mt-1">Selalu aktif untuk role sistem.</span>
                                        @endif
                                    </span>
                                </label>
                                @error('roleForm.can_manage_settings') <div class="invalid-feedback d-block mt-n3 mb-3">{{ $message }}</div> @enderror

                                <label class="form-check form-switch m-0">
                                    <input
                                        id="role-can-view-logs"
                                        type="checkbox"
                                        class="form-check-input"
                                        wire:model.defer="roleForm.can_view_logs"
                                        @disabled($isSuperuserRole)
                                    >
                                    <span class="form-check-label">
                                        <span class="d-flex align-items-center gap-2 fw-semibold mb-1">
                                            @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon-sm text-purple'])
                                            Lihat Log Aktivitas
                                        </span>
                                        <span class="d-block small text-secondary">
                                            Izinkan melihat riwayat perubahan data pada seluruh app perusahaan.
                                        </span>
                                        @if ($isSuperuserRole)
                                            <span class="d-block small text-danger mt-1">Selalu aktif untuk role sistem.</span>
                                        @endif
                                    </span>
                                </label>
                                @error('roleForm.can_view_logs') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 text-secondary small mt-4 pt-3 border-top">
                            @include('starter.templates.layouts.icon', ['name' => 'info-circle', 'class' => 'icon-sm flex-shrink-0 mt-1'])
                            <div>
                                @if ($isSuperuserRole)
                                    Role bawaan Superuser memiliki akses penuh dan hanya dapat dilihat.
                                @else
                                    Akses sistem berdiri sendiri dari akses module. Halaman awal wajib dipilih untuk setiap app yang diberikan.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7" data-role-access-panel>
                <div class="card position-xl-sticky" style="top: 1rem;">
                    <div class="card-header d-flex align-items-center">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <h3 class="card-title text-truncate">Akses Module dan Halaman Awal</h3>
                            <p class="card-subtitle text-wrap">Pilih module per app, kemudian tentukan halaman pertama setelah login.</p>
                        </div>
                        <div class="card-actions ms-3 flex-shrink-0">
                            <span class="d-inline-flex align-items-center justify-content-center px-2 py-1 rounded border {{ $isSuperuserRole ? 'border-success text-success bg-green-lt' : 'border-primary text-primary bg-blue-lt' }} text-nowrap" style="font-size: 0.75rem; font-weight: 600; line-height: 1;">
                                {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($grantedAppCount) }} / {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($appTotal) }} app
                            </span>
                        </div>
                    </div>

                    <div class="card-body border-bottom py-3">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                            <div class="text-secondary small">
                                Buka app untuk melihat module dan pilihan halaman awal.
                            </div>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-sm-auto text-decoration-none" x-on:click="toggleAllModuleApps()">
                                <span x-show="! allModuleAppsExpanded()">@include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'icon-sm me-1'])</span>
                                <span x-show="allModuleAppsExpanded()" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'chevron-up', 'class' => 'icon-sm me-1'])</span>
                                <span x-text="allModuleAppsExpanded() ? 'Tutup semua app' : 'Buka semua app'">Buka semua app</span>
                            </button>
                        </div>
                    </div>

                    <div class="accordion accordion-inverted" id="role-module-access">
                        @forelse ($modules as $appName => $appModules)
                            @php
                                $grantedAppModules = $isSuperuserRole
                                    ? $appModules->count()
                                    : $appModules->filter(
                                        fn ($module): bool => in_array((string) $module->id, $selectedModuleIds, true)
                                    )->count();
                                $appId = $appModules->first()?->app_id;
                                $appKey = 'app-'.($appId ?? 'none');
                                $appAccordionId = 'role-app-modules-'.$appKey;
                            @endphp

                            <div class="accordion-item" wire:key="role-app-modules-{{ $appKey }}">
                                <div class="accordion-header">
                                    <button
                                        class="accordion-button"
                                        type="button"
                                        x-on:click="toggleModuleApp(@js($appKey))"
                                        x-bind:class="{ collapsed: ! isModuleAppExpanded(@js($appKey)) }"
                                        x-bind:aria-expanded="isModuleAppExpanded(@js($appKey))"
                                        aria-controls="{{ $appAccordionId }}"
                                    >
                                        <span class="me-auto">
                                            <span class="d-block fw-semibold">{{ $appName }}</span>
                                            <span class="d-block small text-secondary">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($appModules->count()) }} module tersedia</span>
                                        </span>
                                        <span class="badge {{ $grantedAppModules > 0 ? 'bg-primary-lt text-primary' : 'bg-secondary-lt' }}">
                                            {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($grantedAppModules) }} / {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($appModules->count()) }} module
                                        </span>
                                        <div class="accordion-button-toggle">
                                            @include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'icon-1'])
                                        </div>
                                    </button>
                                </div>

                                <div
                                    id="{{ $appAccordionId }}"
                                    class="accordion-collapse collapse"
                                    x-bind:class="{ show: isModuleAppExpanded(@js($appKey)) }"
                                >
                                    <div class="accordion-body">
                                        <div class="vstack gap-3">
                                            @foreach ($appModules as $module)
                                                @php
                                                    $moduleGranted = $isSuperuserRole || in_array((string) $module->id, $selectedModuleIds, true);
                                                    $moduleLandingMenus = $moduleGranted ? $module->menus : collect();
                                                @endphp

                                                <div class="form-check mb-0" wire:key="role-module-{{ $module->id }}">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input"
                                                        id="module-{{ $module->id }}"
                                                        value="{{ $module->id }}"
                                                        wire:model.live="roleForm.module_ids"
                                                        @checked($isSuperuserRole)
                                                        @disabled($isSuperuserRole)
                                                    >
                                                    <label class="form-check-label" for="module-{{ $module->id }}">
                                                        <span class="d-flex align-items-baseline gap-2">
                                                            <span class="fw-semibold">{{ $module->name }}</span>
                                                            <span class="small text-secondary font-monospace">{{ $module->code }}</span>
                                                        </span>
                                                        <span class="d-block small text-secondary">
                                                            {{ filled($module->desc) ? $module->desc : 'Belum ada deskripsi.' }}
                                                        </span>
                                                    </label>

                                                    @if ($moduleGranted && $appId)
                                                        <div class="mt-2 vstack gap-1">
                                                            @forelse ($moduleLandingMenus as $menu)
                                                                <label class="form-check mb-0 ps-4">
                                                                    <input
                                                                        type="radio"
                                                                        class="form-check-input w-3 h-3 ms-n4"
                                                                        value="{{ $menu->id }}"
                                                                        wire:model.defer="roleForm.landing_menu_ids.{{ $appId }}"
                                                                        @disabled($isSuperuserRole)
                                                                    >
                                                                    <span class="form-check-label small">
                                                                        Jadikan <span class="fw-semibold">{{ $menu->label }}</span> sebagai halaman awal
                                                                    </span>
                                                                </label>
                                                            @empty
                                                                <div class="text-warning small">Halaman awal belum tersedia untuk module ini.</div>
                                                            @endforelse
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('roleForm.landing_menu_ids') <div class="text-danger small mt-3">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty py-5">
                                <p class="empty-title">Belum ada app dan module</p>
                                <p class="empty-subtitle text-secondary">Sinkronkan konfigurasi app sebelum membuat role.</p>
                            </div>
                        @endforelse
                    </div>

                    @error('roleForm.module_ids.*') <div class="text-danger small px-3 py-2">{{ $message }}</div> @enderror

                    <div class="card-footer position-sticky bottom-0 z-2 bg-body shadow-sm">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                            <div class="text-secondary small">
                                {{ $isSuperuserRole ? 'Role sistem hanya dapat dilihat.' : 'Pastikan setiap app memiliki halaman awal sebelum disimpan.' }}
                            </div>
                            <div class="d-flex flex-nowrap gap-2 ms-sm-auto">
                                @if (! $isSuperuserRole)
                                    <button type="submit" class="btn btn-primary">
                                        @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'icon-sm me-1'])
                                        {{ $isCreating ? 'Simpan Role' : 'Simpan Perubahan' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @include('starter.templates.components.danger-modal', [
        'id' => 'delete-role-modal',
        'title' => 'Arsipkan role?',
        'message' => filled($deleteRoleName)
            ? 'Role '.$deleteRoleName.' akan dipindahkan ke arsip dan dapat dipulihkan.'
            : 'Role ini akan dipindahkan ke arsip dan dapat dipulihkan.',
        'confirmText' => 'Arsipkan Role',
        'confirmAction' => 'deleteSelectedRole',
        'cancelAction' => 'cancelRoleDeletion',
        'visible' => $deleteRoleModalOpen,
        'dismissOnConfirm' => false,
    ])
    @if ($deleteRoleModalOpen)
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
