<div class="p-4 space-y-3 md:flex md:flex-row md:space-y-0 justify-between">
    <div class="flex items-center gap-3" data-starter-region="bulk-actions">
        <div class="starter-bulk-dropdown" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="btn btn-outline-dark inline-flex items-center gap-2" @click="open = !open" :class="{ 'show': open }" :aria-expanded="open">
                @include('starter.templates.layouts.icon', ['name' => 'table'])
                <span>Aksi</span>
                @include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'starter-button-chevron'])
            </button>
            <ul class="dashcode-bulk-dropdown" :class="{ 'show': open }" x-show="open" x-cloak>
                @if ($archiveStatus !== 'archived')
                    <li>
                        <button type="button" class="dashcode-table-dropdown-item {{ empty($checkboxValues) ? 'disabled' : '' }}" wire:click="prepareBulkAction('archive')" @click="open = false">
                            Arsipkan Terpilih
                        </button>
                    </li>
                @endif
                @if ($archiveStatus !== 'active')
                    <li>
                        <button type="button" class="dashcode-table-dropdown-item {{ empty($checkboxValues) ? 'disabled' : '' }}" wire:click="prepareBulkAction('restore')" @click="open = false">
                            Pulihkan Terpilih
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dashcode-table-dropdown-item dashcode-table-dropdown-danger {{ empty($checkboxValues) ? 'disabled' : '' }}" wire:click="prepareBulkAction('forceDelete')" @click="open = false">
                            Hapus Permanen
                        </button>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    <div class="flex flex-1 flex-wrap items-center justify-end gap-3" data-starter-region="filters">
        <div class="flex-1 max-w-[180px]">
            <select class="form-control w-full" wire:model.live="archiveStatus" aria-label="Status arsip user">
                <option value="active">Data aktif</option>
                <option value="archived">Arsip</option>
                <option value="all">Semua data</option>
            </select>
        </div>

        <label class="relative flex-1 max-w-md">
            <span class="absolute left-0 top-1/2 -translate-y-1/2 w-9 h-full border-r border-r-slate-200 flex items-center justify-center text-slate-500">
                @include('starter.templates.layouts.icon', ['name' => 'search'])
            </span>
            <input type="search" class="form-control margin-0 !pl-12" placeholder="Cari nama, username, atau email..." wire:model.live.debounce.350ms="search">
        </label>
    </div>
</div>

@include('starter.templates.components.danger-modal', [
    'id' => 'users-lifecycle-modal',
    'title' => $pendingAction === 'forceDelete' ? 'Hapus user permanen?' : ($pendingAction === 'restore' ? 'Pulihkan user?' : 'Arsipkan user?'),
    'message' => $pendingAction === 'forceDelete'
        ? 'Data yang dihapus permanen tidak dapat dipulihkan.'
        : count($pendingIds).' user akan diproses.',
    'confirmText' => $pendingAction === 'forceDelete' ? 'Hapus Permanen' : ($pendingAction === 'restore' ? 'Pulihkan' : 'Arsipkan'),
    'confirmAction' => 'executePendingAction',
    'cancelAction' => 'cancelPendingAction',
    'visible' => $pendingAction !== null,
    'dismissOnConfirm' => false,
])
