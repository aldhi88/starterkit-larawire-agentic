@php
    $eventLabels = [
        'created' => ['label' => 'Dibuat', 'class' => 'bg-success-lt text-success'],
        'updated' => ['label' => 'Diubah', 'class' => 'bg-warning-lt text-warning'],
        'deleted' => ['label' => 'Dihapus', 'class' => 'bg-danger-lt text-danger'],
        'restored' => ['label' => 'Dipulihkan', 'class' => 'bg-success-lt text-success'],
        'security' => ['label' => 'Keamanan', 'class' => 'bg-primary-lt text-primary'],
    ];
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

<div class="dashcode-activity-page">
    <div class="page-header dashcode-activity-header mb-5" aria-label="Header halaman" data-starter-region="page-header">
        <div>
            <div class="page-pretitle">Sistem / Riwayat Data</div>
            <h2 class="page-title">Log Aktivitas</h2>
            <div class="text-secondary mt-1">Riwayat pembuatan, perubahan, dan penghapusan data dari seluruh app perusahaan.</div>
        </div>
        <span class="badge bg-blue-lt text-blue dashcode-activity-readonly inline-flex items-center gap-2 px-3 py-2">
            @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'icon-sm'])
            Data hanya dapat dilihat
        </span>
    </div>

    <div class="dashcode-activity-stats" data-starter-region="summary">
        <div class="rounded-[6px] border border-primary-500 bg-[#EAE5FF] px-4 py-[18px]">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-primary-700 text-white" aria-hidden="true">
                    @include('starter.templates.layouts.icon', ['name' => 'history'])
                </span>
                <div class="overflow-hidden">
                    <div class="mb-1 text-sm font-medium text-slate-600">Total Perubahan</div>
                    <div class="text-lg font-medium text-slate-900">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($totalChanges) }}</div>
                </div>
            </div>
        </div>
        <div class="rounded-[6px] border border-info-500 bg-[#E5F9FF] px-4 py-[18px]">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-info-700 text-white" aria-hidden="true">
                    @include('starter.templates.layouts.icon', ['name' => 'table'])
                </span>
                <div class="overflow-hidden">
                    <div class="mb-1 text-sm font-medium text-slate-600">Perubahan Hari Ini</div>
                    <div class="text-lg font-medium text-slate-900">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($todayChanges) }}</div>
                </div>
            </div>
        </div>
        <div class="rounded-[6px] border border-success-500 bg-[#EDFFE5] px-4 py-[18px]">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-success-700 text-white" aria-hidden="true">
                    @include('starter.templates.layouts.icon', ['name' => 'users'])
                </span>
                <div class="overflow-hidden">
                    <div class="mb-1 text-sm font-medium text-slate-600">Pengguna Tercatat</div>
                    <div class="text-lg font-medium text-slate-900">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($activeActorCount) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashcode-table-card" data-starter-region="data-table">
        <livewire:starter.logs.activity-logs-table />
    </div>

    @if ($detailModalOpen)
        <div class="modal dashcode-detail-modal show" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="activity-log-detail-title" wire:click.self="closeActionDetail" data-starter-region="detail-dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="activity-log-detail-title">Detail Aktivitas</h3>
                            <div class="text-secondary small">Informasi pelaku, sumber akses, dan perubahan data.</div>
                        </div>
                        <button type="button" class="dashcode-icon-button" aria-label="Tutup" wire:click="closeActionDetail">
                            @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm'])
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        @if ($selectedLogs->isNotEmpty())
                            @php
                                $firstLog = $selectedLogs->first();
                                $maskActor = ! auth()->user()?->role?->isSuperuser() && $firstLog->actor_is_superuser;
                            @endphp
                            <div class="px-3 py-3 border-bottom">
                                <div class="dashcode-responsive-row dashcode-align-start">
                                    <div class="flex-fill min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold">{{ $firstLog->action_label }}</span>
                                                <span class="badge bg-primary-lt text-primary">
                                                    {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($selectedLogs->count()) }} aktivitas · {{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($selectedLogs->pluck('table_name')->filter()->unique()->count()) }} tabel
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
                                        <div class="font-semibold text-body">{{ $firstLog->created_at?->format('d M Y H:i:s') }}</div>
                                        <div>{{ $firstLog->app_key ?: 'global' }} · {{ $firstLog->source ?: 'web' }} · {{ $firstLog->ip_address ?: '-' }}</div>
                                    </div>
                                </div>

                                <div class="small mt-3">
                                    <span class="text-secondary">Route:</span>
                                    <span class="font-monospace">{{ $firstLog->route_name ?: '-' }}</span>
                                </div>

                                <details class="mt-2">
                                    <summary class="small text-primary cursor-pointer">Tampilkan referensi teknis</summary>
                                    <div class="rounded border bg-slate-50 p-2 mt-2">
                                        <div class="dashcode-form-grid dashcode-form-grid-2 small">
                                            <div class="dashcode-grid-full">
                                                <div class="text-secondary">ID Referensi Audit</div>
                                                <div class="font-monospace text-break">{{ $firstLog->action_id }}</div>
                                            </div>
                                            <div>
                                                <div class="text-secondary">Kode Aksi</div>
                                                <div class="font-monospace text-break">{{ $firstLog->action_key ?: '-' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-secondary">ID Permintaan</div>
                                                <div class="font-monospace text-break">{{ $firstLog->request_id ?: '-' }}</div>
                                            </div>
                                            <div class="dashcode-grid-full">
                                                <div class="text-secondary">Permintaan</div>
                                                <div class="font-monospace text-break">{{ $firstLog->request_method ?: '-' }} {{ $firstLog->request_path ?: '-' }}</div>
                                            </div>
                                            <div class="dashcode-grid-full">
                                                <div class="text-secondary">Perangkat / Browser</div>
                                                <div class="text-break">{{ $firstLog->user_agent ?: '-' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>

                            <div class="px-3 py-3 bg-slate-50">
                                <div class="dashcode-inline-heading">
                                    <h4 class="mb-0">Rincian Aktivitas</h4>
                                    <span class="small text-secondary">{{ \Aldhi88\StarterKit\Support\Starter\StarterNumber::decimal($selectedLogs->count()) }} item</span>
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
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="badge {{ $eventMeta['class'] }}">{{ $eventMeta['label'] }}</span>
                                                        <span class="font-semibold">{{ $log->auditable_label ?: class_basename($log->auditable_type) }}</span>
                                                        <span class="text-secondary">#{{ $log->auditable_id }}</span>
                                                    </div>
                                                    <div class="small text-secondary mt-1">
                                                        <span class="font-monospace">{{ $log->table_name ?: '-' }}</span>
                                                        · urutan {{ $log->sequence }}
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($changedFields->isNotEmpty())
                                                <div class="overflow-x-auto dashcode-data-table">
                                                    <table class="min-w-full divide-y divide-slate-100">
                                                        <thead>
                                                            <tr>
                                                                <th class="table-th">Kolom</th>
                                                                <th class="table-th">Sebelum</th>
                                                                <th class="table-th">Sesudah</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white divide-y divide-slate-100">
                                                            @foreach ($changedFields as $field)
                                                                <tr>
                                                                    <td class="table-td font-monospace text-secondary">{{ $field }}</td>
                                                                    <td class="table-td"><pre class="m-0 text-wrap small">{{ $displayValue(data_get($log->old_values ?? [], $field)) }}</pre></td>
                                                                    <td class="table-td"><pre class="m-0 text-wrap small">{{ $displayValue(data_get($log->new_values ?? [], $field)) }}</pre></td>
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
                            <div class="dashcode-empty-state py-5">
                                <p class="dashcode-empty-state-title">Detail aktivitas tidak ditemukan</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" wire:click="closeActionDetail">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
