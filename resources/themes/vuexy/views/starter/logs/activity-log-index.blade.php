@php
    $eventLabels = [
        'created' => ['label' => 'Dibuat', 'class' => 'bg-label-success'],
        'updated' => ['label' => 'Diubah', 'class' => 'bg-label-warning'],
        'deleted' => ['label' => 'Dihapus', 'class' => 'bg-label-danger'],
        'restored' => ['label' => 'Dipulihkan', 'class' => 'bg-label-success'],
        'security' => ['label' => 'Keamanan', 'class' => 'bg-label-primary'],
    ];
    $activeFilterCount = collect([
        $search,
        $eventFilter,
        $actorFilter,
        $roleFilter,
        $appFilter,
        $tableFilter,
        $routeFilter,
        $ipFilter,
        $actionFilter,
    ])->filter(fn ($value): bool => filled($value))->count();
@endphp

<div x-data="{ advancedFiltersOpen: false }">
    <div class="page-header d-print-none mt-0 mb-3" data-starter-region="page-header">
        <div class="row g-3 align-items-end">
            <div class="col">
                <h2 class="page-title">Log Aktivitas</h2>
                <div class="text-secondary mt-1">Riwayat pembuatan, perubahan, dan penghapusan data dari seluruh app perusahaan.</div>
            </div>
            <div class="col-12 col-md-auto">
                <span class="badge bg-label-primary py-2 px-3">
                    @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon-sm me-1'])
                    Data hanya dapat dilihat
                </span>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-3 vuexy-activity-summary" data-starter-region="summary">
        <div class="col-sm-6 col-xl-4">
            <div class="card card-sm vuexy-activity-stat">
                <div class="card-body">
                    <div class="d-flex align-items-center vuexy-activity-stat-content">
                        <span class="avatar bg-label-primary vuexy-activity-stat-icon">
                            @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon'])
                        </span>
                        <div class="min-w-0">
                            <div class="text-secondary vuexy-activity-stat-label">Total Perubahan</div>
                            <div class="vuexy-activity-stat-value">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($totalChanges) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card card-sm vuexy-activity-stat">
                <div class="card-body">
                    <div class="d-flex align-items-center vuexy-activity-stat-content">
                        <span class="avatar bg-label-info vuexy-activity-stat-icon">
                            @include('starter.templates.layouts.icon', ['name' => 'table', 'class' => 'icon'])
                        </span>
                        <div class="min-w-0">
                            <div class="text-secondary vuexy-activity-stat-label">Perubahan Hari Ini</div>
                            <div class="vuexy-activity-stat-value">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($todayChanges) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card card-sm vuexy-activity-stat">
                <div class="card-body">
                    <div class="d-flex align-items-center vuexy-activity-stat-content">
                        <span class="avatar bg-label-primary vuexy-activity-stat-icon">
                            @include('starter.templates.layouts.icon', ['name' => 'users', 'class' => 'icon'])
                        </span>
                        <div class="min-w-0">
                            <div class="text-secondary vuexy-activity-stat-label">Pengguna Tercatat</div>
                            <div class="vuexy-activity-stat-value">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($activeActorCount) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" data-starter-region="data-table">
        <livewire:starter.logs.activity-logs-table />
    </div>

    {{-- Legacy filter/table markup is intentionally not rendered; PowerGrid is the server-side table authority. --}}
    @if (false)
    <div class="card mb-3" data-log-filter-card>
        <div class="card-header">
            <div>
                <h3 class="card-title">Filter Log</h3>
                <p class="card-subtitle">Semua filter berjalan langsung tanpa memuat ulang halaman.</p>
            </div>
            <div class="card-actions">
                @if ($activeFilterCount > 0)
                    <span class="badge bg-label-primary me-2">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($activeFilterCount) }} filter aktif</span>
                @endif
                <button type="button" class="btn btn-sm" wire:click="resetFilters" x-on:click="advancedFiltersOpen = false">
                    @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm me-1'])
                    Reset Filter
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-xl-4">
                    <label class="form-label" for="log-search">Pencarian</label>
                    <input
                        id="log-search"
                        type="search"
                        class="form-control"
                        maxlength="100"
                        placeholder="Aktivitas, user, tabel, route, ID..."
                        wire:model.live.debounce.350ms="search"
                    >
                </div>
                <div class="col-sm-6 col-xl-2">
                    <label class="form-label" for="log-date-from">Mulai Tanggal</label>
                    <input id="log-date-from" type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-sm-6 col-xl-2">
                    <label class="form-label" for="log-date-to">Sampai Tanggal</label>
                    <input id="log-date-to" type="date" class="form-control" wire:model.live="dateTo">
                </div>
                <div class="col-sm-6 col-xl-2">
                    <label class="form-label" for="log-event">Jenis Aktivitas</label>
                    <select id="log-event" class="form-select" wire:model.live="eventFilter">
                        <option value="">Semua jenis</option>
                        <option value="created">Dibuat</option>
                        <option value="updated">Diubah</option>
                        <option value="deleted">Dihapus</option>
                        <option value="security">Keamanan</option>
                    </select>
                </div>
                <div class="col-sm-6 col-xl-2">
                    <label class="form-label" for="log-actor">Pengguna</label>
                    <select id="log-actor" class="form-select" wire:model.live="actorFilter">
                        <option value="">Semua pengguna</option>
                        @foreach ($filterOptions['actors'] as $actor)
                            <option value="{{ $actor->id }}">{{ $actor->name }} · {{ $actor->username }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center mt-3 pt-3 border-top">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" x-on:click="advancedFiltersOpen = ! advancedFiltersOpen" x-bind:aria-expanded="advancedFiltersOpen">
                    <span x-show="! advancedFiltersOpen">@include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'icon-sm me-1'])</span>
                    <span x-show="advancedFiltersOpen" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'chevron-up', 'class' => 'icon-sm me-1'])</span>
                    <span x-text="advancedFiltersOpen ? 'Tutup filter lanjutan' : 'Buka filter lanjutan'">Buka filter lanjutan</span>
                </button>
                <div class="text-secondary small ms-auto">Periode awal: 30 hari terakhir</div>
            </div>

                <div class="row g-3 mt-0" data-advanced-log-filters x-show="advancedFiltersOpen" x-cloak>
                    <div class="col-sm-6 col-xl-2">
                        <label class="form-label" for="log-role">Role Pelaku</label>
                        <select id="log-role" class="form-select" wire:model.live="roleFilter">
                            <option value="">Semua role</option>
                            @foreach ($filterOptions['roles'] as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <label class="form-label" for="log-app">App</label>
                        <select id="log-app" class="form-select" wire:model.live="appFilter">
                            <option value="">Semua app</option>
                            @foreach ($filterOptions['apps'] as $app)
                                <option value="{{ $app }}">{{ $app }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <label class="form-label" for="log-table">Tabel Data</label>
                        <select id="log-table" class="form-select" wire:model.live="tableFilter">
                            <option value="">Semua tabel</option>
                            @foreach ($filterOptions['tables'] as $table)
                                <option value="{{ $table }}">{{ $table }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <label class="form-label" for="log-route">Route</label>
                        <select id="log-route" class="form-select" wire:model.live="routeFilter">
                            <option value="">Semua route</option>
                            @foreach ($filterOptions['routes'] as $route)
                                <option value="{{ $route }}">{{ $route }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-xl-2">
                        <label class="form-label" for="log-per-page">Baris per Halaman</label>
                        <select id="log-per-page" class="form-select" wire:model.live="perPage">
                            <option value="25">25 aktivitas</option>
                            <option value="50">50 aktivitas</option>
                            <option value="100">100 aktivitas</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <label class="form-label" for="log-action-id">ID Referensi Audit</label>
                        <input id="log-action-id" type="search" maxlength="26" class="form-control font-monospace" placeholder="Cari ID referensi audit" wire:model.live.debounce.350ms="actionFilter">
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <label class="form-label" for="log-ip">IP Address</label>
                        <input id="log-ip" type="search" maxlength="45" class="form-control" placeholder="Contoh: 192.168.1.10" wire:model.live.debounce.350ms="ipFilter">
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <label class="form-label">Cakupan Pencarian</label>
                        <div class="form-control-plaintext text-secondary small">
                            Aktivitas, user, target data, tabel, route, IP, dan ID referensi audit.
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <div class="card" data-log-table-card>
        <div class="card-header">
            <div>
                <h3 class="card-title">Riwayat Aktivitas</h3>
                <p class="card-subtitle">
                    Menampilkan {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($actions->firstItem() ?? 0) }}–{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($actions->lastItem() ?? 0) }} dari {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($actions->total()) }} aktivitas
                </p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-vcenter card-table">
                <thead>
                    <tr>
                        <th class="text-nowrap">Waktu</th>
                        <th>Pengguna</th>
                        <th>Aktivitas</th>
                        <th>Konteks</th>
                        <th class="w-1 text-end">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($actions as $action)
                        <tr wire:key="log-action-{{ $action['action_id'] }}">
                            <td class="text-nowrap">
                                <div class="fw-semibold">{{ $action['created_at']?->format('d M Y') ?? '-' }}</div>
                                <div class="small text-secondary">{{ $action['created_at']?->format('H:i:s') ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $action['actor_name'] }}</div>
                                    <div class="small text-secondary text-truncate">
                                        {{ $action['actor_username'] ? '@'.$action['actor_username'].' · ' : '' }}{{ $action['actor_role'] }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $action['action_label'] }}</div>
                                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                    @foreach ($action['events'] as $event)
                                        @php
                                            $eventMeta = $eventLabels[$event] ?? [
                                                'label' => ucfirst($event),
                                                'class' => 'bg-label-secondary',
                                            ];
                                        @endphp
                                        <span class="badge {{ $eventMeta['class'] }}">{{ $eventMeta['label'] }}</span>
                                    @endforeach
                                    <span class="small text-secondary">
                                        {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($action['changes_count']) }} perubahan · {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($action['tables_count']) }} tabel
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    @if ($action['app_key'])
                                        <span class="badge bg-label-primary">{{ $action['app_key'] }}</span>
                                    @endif
                                    <span class="badge bg-label-secondary">{{ $action['source'] ?: 'web' }}</span>
                                    @if ($action['ip_address'])
                                        <span class="small text-secondary">{{ $action['ip_address'] }}</span>
                                    @endif
                                </div>
                                <div class="small text-secondary text-truncate mt-1 starter-route-label" title="{{ $action['route_name'] }}">
                                    {{ $action['route_name'] ?: '-' }}
                                </div>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-label-primary text-nowrap"
                                    wire:click="showActionDetail('{{ $action['action_id'] }}')"
                                    aria-label="Lihat detail {{ $action['action_label'] }}"
                                >
                                    Detail
                                    @include('starter.templates.layouts.icon', ['name' => 'chevron-right', 'class' => 'icon-sm ms-1 m-0'])
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty py-5">
                                    <div class="empty-img">
                                        <span class="avatar avatar-xl bg-label-primary">
                                            @include('starter.templates.layouts.icon', ['name' => 'history', 'size' => 40])
                                        </span>
                                    </div>
                                    <p class="empty-title">Belum ada aktivitas sesuai filter</p>
                                    <p class="empty-subtitle text-secondary">
                                        Log akan muncul otomatis ketika ada perubahan data atau aktivitas keamanan.
                                    </p>
                                    @if ($activeFilterCount > 0)
                                        <div class="empty-action">
                                            <button type="button" class="btn btn-primary" wire:click="resetFilters">Reset Filter</button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($actions->hasPages())
            <div class="card-footer d-flex align-items-center">
                <div class="ms-auto">
                    {{ $actions->links() }}
                </div>
            </div>
        @endif
    </div>

    @endif

    @if ($detailModalOpen)
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="activity-log-detail-title" x-data x-trap.noscroll="true" @keydown.escape.window="$wire.closeActionDetail()" wire:click.self="closeActionDetail" data-starter-region="detail-dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="activity-log-detail-title">Detail Aktivitas</h3>
                        </div>
                        <button type="button" class="btn-close" aria-label="Tutup" wire:click="closeActionDetail"></button>
                    </div>
                    <div class="modal-body" tabindex="0" role="region" aria-label="Isi detail aktivitas">
                        @include('starter-shared::logs.activity-detail', ['sectionTitleClass' => 'starter-modal-section-title'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" wire:click="closeActionDetail">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
