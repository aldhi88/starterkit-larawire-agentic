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
    class="dashcode-role-form"
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
    <div class="page-header mb-3" aria-label="Header halaman" data-starter-region="page-header">
        <div class="dashcode-page-heading">
            <div class="min-w-0">
                <div class="page-pretitle">Pengaturan / Roles</div>
                <h2 class="page-title">{{ $isCreating ? 'Tambah Role' : ($isSuperuserRole ? 'Detail Role' : 'Edit Role') }}</h2>
                <div class="text-secondary mt-1">
                    {{ $isCreating ? 'Buat identitas role, pilih akses module, lalu tentukan halaman awal.' : 'Kelola identitas dan cakupan akses role pada halaman khusus ini.' }}
                </div>
            </div>
            <div>
                <div class="btn-list">
                    <a href="{{ route('starter.settings', ['section' => 'roles']) }}" class="btn btn-secondary" data-starter-navigate>
                        @include('starter.templates.layouts.icon', ['name' => 'arrow-left', 'class' => 'icon-sm'])
                        Batal dan Kembali
                    </a>
                    @if (! $isSuperuserRole)
                        <button type="submit" form="role-form" class="btn btn-primary">
                            @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'icon-sm'])
                            {{ $isCreating ? 'Simpan Role' : 'Simpan Perubahan' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form id="role-form" wire:submit="save">
        <div class="dashcode-role-layout" data-role-form-layout="split">
            <div data-role-identity-panel data-starter-region="identity-form">
                <div class="card sticky-xl-top">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Identitas Role</h3>
                            <p class="card-subtitle">Informasi dasar dan ringkasan cakupan akses role.</p>
                        </div>
                        @if ($roleId && ! $isSuperuserRole)
                            <div class="card-actions">
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="prepareRoleDeletion">
                                    @include('starter.templates.layouts.icon', ['name' => 'trash', 'class' => 'icon-sm'])
                                    Arsipkan Role
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="rounded border bg-slate-50 p-3 mb-4" data-role-form-summary>
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="avatar {{ $isSuperuserRole ? 'bg-danger-lt text-danger' : 'bg-primary-lt text-primary' }}">
                                    @include('starter.templates.layouts.icon', ['name' => $isSuperuserRole ? 'shield-check' : 'shield-lock', 'class' => 'icon'])
                                </span>
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">
                                        {{ filled($roleForm['name']) ? $roleForm['name'] : 'Role Baru' }}
                                    </div>
                                    <div class="small text-secondary font-monospace text-truncate">
                                        {{ filled($roleForm['code']) ? $roleForm['code'] : 'kode_role' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                @if ($isSuperuserRole)
                                    <span class="badge bg-danger-500 bg-opacity-30 text-danger-500 rounded-3xl">Role Sistem</span>
                                @elseif ($isCreating)
                                    <span class="badge bg-slate-500 bg-opacity-30 text-slate-500 rounded-3xl">Belum Disimpan</span>
                                @else
                                    <span class="badge bg-primary-500 bg-opacity-30 text-primary-500 rounded-3xl">Role Aktif</span>
                                @endif
                                <span class="badge bg-primary-lt text-primary">
                                    {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($grantedAppCount) }} app · {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($grantedModuleCount) }} module
                                </span>
                                @if ($roleForm['can_manage_settings'])
                                    <span class="badge bg-azure-lt text-azure">
                                        @include('starter.templates.layouts.icon', ['name' => 'settings', 'class' => 'icon-sm'])
                                        Pengaturan
                                    </span>
                                @endif
                                @if ($roleForm['can_view_logs'])
                                    <span class="badge bg-purple-lt text-purple">
                                        @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon-sm'])
                                        Log Aktivitas
                                    </span>
                                @endif
                                <span class="badge bg-secondary-lt">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($assignedUserCount) }} user</span>
                            </div>
                        </div>

                        <div class="dashcode-form-grid dashcode-section-gap">
                            <div>
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
                            <div>
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
                            <div>
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

                        <h3 class="starter-form-section-title mt-4">Akses Sistem Khusus</h3>
                        <div class="dashcode-form-grid" data-role-system-access>
                            <div>
                                <label class="starter-switch-row mb-4">
                                    <span class="starter-switch-control">
                                        <input
                                            id="role-can-manage-settings"
                                            type="checkbox"
                                            class="starter-switch-input"
                                            wire:model.defer="roleForm.can_manage_settings"
                                            @disabled($isSuperuserRole)
                                        >
                                        <span class="starter-switch-track" aria-hidden="true"></span>
                                    </span>
                                    <span class="starter-switch-label">
                                        <span class="starter-switch-title flex items-center gap-2">
                                            @include('starter.templates.layouts.icon', ['name' => 'settings', 'class' => 'icon-sm text-azure'])
                                            Akses Pengaturan
                                        </span>
                                        <span class="dashcode-help-text">
                                            Izinkan mengelola role, user, dan profil perusahaan.
                                        </span>
                                        @if ($isSuperuserRole)
                                            <span class="dashcode-help-text text-danger">Selalu aktif untuk role sistem.</span>
                                        @endif
                                    </span>
                                </label>
                                @error('roleForm.can_manage_settings') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                <label class="starter-switch-row m-0">
                                    <span class="starter-switch-control">
                                        <input
                                            id="role-can-view-logs"
                                            type="checkbox"
                                            class="starter-switch-input"
                                            wire:model.defer="roleForm.can_view_logs"
                                            @disabled($isSuperuserRole)
                                        >
                                        <span class="starter-switch-track" aria-hidden="true"></span>
                                    </span>
                                    <span class="starter-switch-label">
                                        <span class="starter-switch-title flex items-center gap-2">
                                            @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon-sm text-purple'])
                                            Lihat Log Aktivitas
                                        </span>
                                        <span class="dashcode-help-text">
                                            Izinkan melihat riwayat perubahan data pada seluruh app perusahaan.
                                        </span>
                                        @if ($isSuperuserRole)
                                            <span class="dashcode-help-text text-danger">Selalu aktif untuk role sistem.</span>
                                        @endif
                                    </span>
                                </label>
                                @error('roleForm.can_view_logs') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="dashcode-note-row">
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

            <div data-role-access-panel data-starter-region="module-access">
                <div class="card sticky-xl-top">
                    <div class="card-header flex items-center">
                        <div class="flex-1 min-w-0">
                            <h3 class="card-title text-truncate">Akses Module dan Halaman Awal</h3>
                            <p class="card-subtitle text-wrap">Pilih module per app, kemudian tentukan halaman pertama setelah login.</p>
                        </div>
                        <div class="card-actions ml-3 flex-shrink-0">
                            <span class="inline-flex items-center justify-center px-2 py-1 rounded border text-xs font-semibold leading-none {{ $isSuperuserRole ? 'border-success text-success bg-green-lt' : 'border-primary text-primary bg-blue-lt' }} whitespace-nowrap">
                                {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($grantedAppCount) }} / {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($appTotal) }} app
                            </span>
                        </div>
                    </div>

                    <div class="card-body border-bottom py-3">
                        <div class="dashcode-responsive-row">
                            <div class="text-secondary small">
                                Buka app untuk melihat module dan pilihan halaman awal.
                            </div>
                            <button type="button" class="btn btn-link btn-sm p-0 dashcode-push-right" x-on:click="toggleAllModuleApps()">
                                <span x-show="! allModuleAppsExpanded()">@include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'icon-sm'])</span>
                                <span x-show="allModuleAppsExpanded()" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'chevron-up', 'class' => 'icon-sm'])</span>
                                <span x-text="allModuleAppsExpanded() ? 'Tutup semua app' : 'Buka semua app'">Buka semua app</span>
                            </button>
                        </div>
                    </div>

                    <div class="dashcode-access-accordion" id="role-module-access">
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

                            <div class="dashcode-access-accordion-item" wire:key="role-app-modules-{{ $appKey }}">
                                <div class="dashcode-access-accordion-header">
                                    <button
                                        class="dashcode-access-accordion-button"
                                        type="button"
                                        x-on:click="toggleModuleApp(@js($appKey))"
                                        x-bind:class="{ collapsed: ! isModuleAppExpanded(@js($appKey)) }"
                                        x-bind:aria-expanded="isModuleAppExpanded(@js($appKey))"
                                        aria-controls="{{ $appAccordionId }}"
                                    >
                                        <span class="dashcode-grow">
                                            <span class="block font-semibold">{{ $appName }}</span>
                                            <span class="dashcode-help-text">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($appModules->count()) }} module tersedia</span>
                                        </span>
                                        <span class="badge {{ $grantedAppModules > 0 ? 'bg-primary-lt text-primary' : 'bg-secondary-lt' }}">
                                            {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($grantedAppModules) }} / {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($appModules->count()) }} module
                                        </span>
                                        <div class="dashcode-access-accordion-toggle">
                                            @include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'icon-1'])
                                        </div>
                                    </button>
                                </div>

                                <div
                                    id="{{ $appAccordionId }}"
                                    class="dashcode-access-accordion-panel"
                                    x-bind:class="{ show: isModuleAppExpanded(@js($appKey)) }"
                                >
                                    <div class="dashcode-access-accordion-body">
                                        <div class="vstack gap-3">
                                            @foreach ($appModules as $module)
                                                @php
                                                    $moduleGranted = $isSuperuserRole || in_array((string) $module->id, $selectedModuleIds, true);
                                                    $moduleLandingMenus = $moduleGranted ? $module->menus : collect();
                                                @endphp

                                                <div class="dashcode-role-module-option" wire:key="role-module-{{ $module->id }}">
                                                    <label class="dashcode-role-choice-row" for="module-{{ $module->id }}">
                                                        <input
                                                            type="checkbox"
                                                            class="dashcode-role-choice-input"
                                                            id="module-{{ $module->id }}"
                                                            value="{{ $module->id }}"
                                                            wire:model.live="roleForm.module_ids"
                                                            @checked($isSuperuserRole)
                                                            @disabled($isSuperuserRole)
                                                        >
                                                        <span class="dashcode-role-choice-indicator dashcode-role-choice-checkbox" aria-hidden="true"></span>
                                                        <span class="dashcode-role-choice-copy">
                                                            <span class="dashcode-role-module-title">
                                                                <span class="font-semibold">{{ $module->name }}</span>
                                                                <span class="small text-secondary font-monospace">{{ $module->code }}</span>
                                                            </span>
                                                            <span class="dashcode-help-text">
                                                                {{ filled($module->desc) ? $module->desc : 'Belum ada deskripsi.' }}
                                                            </span>
                                                        </span>
                                                    </label>

                                                    @if ($moduleGranted && $appId)
                                                        <div class="dashcode-role-landing-options">
                                                            @forelse ($moduleLandingMenus as $menu)
                                                                <label class="dashcode-role-choice-row dashcode-role-landing-choice">
                                                                    <input
                                                                        type="radio"
                                                                        class="dashcode-role-choice-input"
                                                                        value="{{ $menu->id }}"
                                                                        wire:model.defer="roleForm.landing_menu_ids.{{ $appId }}"
                                                                        @disabled($isSuperuserRole)
                                                                    >
                                                                    <span class="dashcode-role-choice-indicator dashcode-role-choice-radio" aria-hidden="true"></span>
                                                                    <span class="dashcode-role-choice-label">
                                                                        Jadikan <span class="font-semibold">{{ $menu->label }}</span> sebagai halaman awal
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
                            <div class="dashcode-empty-state py-5">
                                <p class="dashcode-empty-state-title">Belum ada app dan module</p>
                                <p class="dashcode-empty-state-description">Sinkronkan konfigurasi app sebelum membuat role.</p>
                            </div>
                        @endforelse
                    </div>

                    @error('roleForm.module_ids.*') <div class="text-danger small px-3 py-2">{{ $message }}</div> @enderror

                    <div class="card-footer position-sticky bottom-0 z-2 bg-body shadow-sm" data-starter-region="page-actions">
                        <div class="dashcode-responsive-row">
                            <div class="text-secondary small">
                                {{ $isSuperuserRole ? 'Role sistem hanya dapat dilihat.' : 'Pastikan setiap app memiliki halaman awal sebelum disimpan.' }}
                            </div>
                            <div class="dashcode-actions dashcode-push-right">
                                @if (! $isSuperuserRole)
                                    <button type="submit" class="btn btn-primary">
                                        @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'icon-sm'])
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
</div>
