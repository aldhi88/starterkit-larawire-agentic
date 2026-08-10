@php
    $eventLabels = [
        'created' => ['label' => 'Dibuat', 'class' => 'bg-success-lt text-success'],
        'updated' => ['label' => 'Diubah', 'class' => 'bg-warning-lt text-warning'],
        'deleted' => ['label' => 'Dihapus', 'class' => 'bg-danger-lt text-danger'],
        'restored' => ['label' => 'Dipulihkan', 'class' => 'bg-success-lt text-success'],
        'security' => ['label' => 'Keamanan', 'class' => 'bg-primary-lt text-primary'],
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
    $displayValue = function (mixed $value): string {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    };
@endphp

<div x-data="{ advancedFiltersOpen: false }">
    <div class="page-header d-print-none mt-0 mb-3">
        <div class="row g-3 align-items-end">
            <div class="col">
                <div class="page-pretitle">Sistem / Riwayat Data</div>
                <h2 class="page-title">Log Aktivitas</h2>
                <div class="text-secondary mt-1">Riwayat pembuatan, perubahan, dan penghapusan data dari seluruh app perusahaan.</div>
            </div>
            <div class="col-12 col-md-auto">
                <span class="badge bg-blue-lt text-blue py-2 px-3">
                    @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon-sm me-1'])
                    Data hanya dapat dilihat
                </span>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-xl-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-primary-lt text-primary me-3">
                            @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon'])
                        </span>
                        <div>
                            <div class="text-secondary">Total Perubahan</div>
                            <div class="h2 mb-0">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($totalChanges) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-azure-lt text-azure me-3">
                            @include('starter.templates.layouts.icon', ['name' => 'table', 'class' => 'icon'])
                        </span>
                        <div>
                            <div class="text-secondary">Perubahan Hari Ini</div>
                            <div class="h2 mb-0">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($todayChanges) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-purple-lt text-purple me-3">
                            @include('starter.templates.layouts.icon', ['name' => 'users', 'class' => 'icon'])
                        </span>
                        <div>
                            <div class="text-secondary">Pengguna Tercatat</div>
                            <div class="h2 mb-0">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($activeActorCount) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
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
                    <span class="badge bg-primary-lt text-primary me-2">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($activeFilterCount) }} filter aktif</span>
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
                    Menampilkan {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($actions->firstItem() ?? 0) }}–{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($actions->lastItem() ?? 0) }} dari {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($actions->total()) }} aktivitas
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
                                                'class' => 'bg-secondary-lt text-secondary',
                                            ];
                                        @endphp
                                        <span class="badge {{ $eventMeta['class'] }}">{{ $eventMeta['label'] }}</span>
                                    @endforeach
                                    <span class="small text-secondary">
                                        {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($action['changes_count']) }} perubahan · {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($action['tables_count']) }} tabel
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    @if ($action['app_key'])
                                        <span class="badge bg-blue-lt text-blue">{{ $action['app_key'] }}</span>
                                    @endif
                                    <span class="badge bg-secondary-lt">{{ $action['source'] ?: 'web' }}</span>
                                    @if ($action['ip_address'])
                                        <span class="small text-secondary">{{ $action['ip_address'] }}</span>
                                    @endif
                                </div>
                                <div class="small text-secondary text-truncate mt-1" style="max-width: 19rem;" title="{{ $action['route_name'] }}">
                                    {{ $action['route_name'] ?: '-' }}
                                </div>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-ghost-primary text-nowrap"
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
                                        <span class="avatar avatar-xl bg-primary-lt text-primary">
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
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="activity-log-detail-title" wire:click.self="closeActionDetail">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="activity-log-detail-title">Detail Aktivitas</h3>
                            <div class="text-secondary small">Informasi pelaku, sumber akses, dan perubahan data.</div>
                        </div>
                        <button type="button" class="btn-close" aria-label="Tutup" wire:click="closeActionDetail"></button>
                    </div>
                    <div class="modal-body p-0">
                        @if ($selectedLogs->isNotEmpty())
                            @php
                                $firstLog = $selectedLogs->first();
                                $maskActor = ! auth()->user()?->role?->isSuperuser() && $firstLog->actor_is_superuser;
                            @endphp
                            <div class="px-3 py-3 border-bottom">
                                <div class="d-flex flex-column flex-md-row gap-2 align-items-md-start">
                                    <div class="flex-fill min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="fw-bold">{{ $firstLog->action_label }}</span>
                                                <span class="badge bg-primary-lt text-primary">
                                                    {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($selectedLogs->count()) }} aktivitas · {{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($selectedLogs->pluck('table_name')->filter()->unique()->count()) }} tabel
                                            </span>
                                        </div>
                                        <div class="small text-secondary mt-1">
                                            {{ $maskActor ? 'Sistem' : ($firstLog->actor_name ?: '-') }}
                                            @if (! $maskActor && $firstLog->actor_username)
                                                · {{ '@'.$firstLog->actor_username }}
                                            @endif
                                            · {{ $maskActor ? 'Role disembunyikan' : ($firstLog->actor_role ?: '-') }}
                                        </div>
                                    </div>
                                    <div class="small text-secondary text-md-end text-nowrap">
                                        <div class="fw-semibold text-body">{{ $firstLog->created_at?->format('d M Y H:i:s') }}</div>
                                        <div>{{ $firstLog->app_key ?: 'global' }} · {{ $firstLog->source ?: 'web' }} · {{ $firstLog->ip_address ?: '-' }}</div>
                                    </div>
                                </div>

                                <div class="small mt-3">
                                    <span class="text-secondary">Route:</span>
                                    <span class="font-monospace">{{ $firstLog->route_name ?: '-' }}</span>
                                </div>

                                <details class="mt-2">
                                    <summary class="small text-primary cursor-pointer">Tampilkan referensi teknis</summary>
                                    <div class="rounded border bg-body-tertiary p-2 mt-2">
                                        <div class="row g-2 small">
                                            <div class="col-12">
                                                <div class="text-secondary">ID Referensi Audit</div>
                                                <div class="font-monospace text-break">{{ $firstLog->action_id }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-secondary">Kode Aksi</div>
                                                <div class="font-monospace text-break">{{ $firstLog->action_key ?: '-' }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-secondary">ID Permintaan</div>
                                                <div class="font-monospace text-break">{{ $firstLog->request_id ?: '-' }}</div>
                                            </div>
                                            <div class="col-12">
                                                <div class="text-secondary">Permintaan</div>
                                                <div class="font-monospace text-break">{{ $firstLog->request_method ?: '-' }} {{ $firstLog->request_path ?: '-' }}</div>
                                            </div>
                                            <div class="col-12">
                                                <div class="text-secondary">Perangkat / Browser</div>
                                                <div class="text-break">{{ $firstLog->user_agent ?: '-' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>

                            <div class="px-3 py-3 bg-body-tertiary">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h4 class="mb-0">Rincian Aktivitas</h4>
                                    <span class="small text-secondary">{{ \Altekno\StarterKit\Support\Starter\StarterNumber::decimal($selectedLogs->count()) }} item</span>
                                </div>
                                <div class="vstack gap-2">
                                    @foreach ($selectedLogs as $log)
                                        @php
                                            $eventMeta = $eventLabels[$log->event] ?? [
                                                'label' => ucfirst($log->event),
                                                'class' => 'bg-secondary-lt text-secondary',
                                            ];
                                            $changedFields = collect(array_keys($log->old_values ?? []))
                                                ->merge(array_keys($log->new_values ?? []))
                                                ->unique()
                                                ->values();
                                        @endphp
                                        <div class="card card-sm shadow-none" wire:key="activity-log-entry-{{ $log->id }}">
                                            <div class="card-header py-2">
                                                <div class="min-w-0">
                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                        <span class="badge {{ $eventMeta['class'] }}">{{ $eventMeta['label'] }}</span>
                                                        <span class="fw-semibold">{{ $log->auditable_label ?: class_basename($log->auditable_type) }}</span>
                                                        <span class="text-secondary">#{{ $log->auditable_id }}</span>
                                                    </div>
                                                    <div class="small text-secondary mt-1">
                                                        <span class="font-monospace">{{ $log->table_name ?: '-' }}</span>
                                                        · urutan {{ $log->sequence }}
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($changedFields->isNotEmpty())
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-vcenter card-table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 18%;">Kolom</th>
                                                                <th>Sebelum</th>
                                                                <th>Sesudah</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($changedFields as $field)
                                                                <tr>
                                                                    <td class="font-monospace text-secondary">{{ $field }}</td>
                                                                    <td><pre class="m-0 text-wrap small">{{ $displayValue(data_get($log->old_values ?? [], $field)) }}</pre></td>
                                                                    <td><pre class="m-0 text-wrap small">{{ $displayValue(data_get($log->new_values ?? [], $field)) }}</pre></td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="card-body py-3 text-secondary">Tidak ada payload perubahan yang dapat ditampilkan.</div>
                                            @endif
                                            @if (filled($log->auditable_type) || filled($log->metadata))
                                                <details class="border-top px-3 py-2">
                                                    <summary class="small text-secondary cursor-pointer">Atribut teknis item</summary>
                                                    <div class="small mt-2">
                                                        <div>
                                                            <span class="text-secondary">Model:</span>
                                                            <span class="font-monospace text-break">{{ $log->auditable_type ?: '-' }}</span>
                                                        </div>
                                                        @if (filled($log->metadata))
                                                            <div class="mt-2">
                                                                <span class="text-secondary">Metadata:</span>
                                                                <pre class="m-0 mt-1 text-wrap small">{{ $displayValue($log->metadata) }}</pre>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </details>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="empty py-5">
                                <p class="empty-title">Detail aktivitas tidak ditemukan</p>
                            </div>
                        @endif
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
