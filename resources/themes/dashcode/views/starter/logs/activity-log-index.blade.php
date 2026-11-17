<div class="dashcode-activity-page">
    <div class="page-header dashcode-activity-header mb-5" aria-label="Header halaman" data-starter-region="page-header">
        <div>
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
        <div class="modal dashcode-detail-modal show" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="activity-log-detail-title" x-data x-trap.noscroll="true" @keydown.escape.window="$wire.closeActionDetail()" wire:click.self="closeActionDetail" data-starter-region="detail-dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="activity-log-detail-title">Detail Aktivitas</h3>
                        </div>
                        <button type="button" class="dashcode-icon-button" aria-label="Tutup" wire:click="closeActionDetail">
                            @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm'])
                        </button>
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
    @endif
</div>
