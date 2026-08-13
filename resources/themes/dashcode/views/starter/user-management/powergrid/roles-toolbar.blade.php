<div class="dashcode-pg-toolbar">
    <div class="dashcode-pg-toolbar-actions">
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

    <div class="dashcode-pg-toolbar-filters">
        <select class="starter-pg-control starter-pg-select dashcode-native-select dashcode-pg-archive-filter" wire:model.live="archiveStatus" aria-label="Status arsip role">
            <option value="active">Data aktif</option>
            <option value="archived">Arsip</option>
            <option value="all">Semua data</option>
        </select>

        <label class="dashcode-pg-search-field">
            <span class="dashcode-pg-search-icon">
                @include('starter.templates.layouts.icon', ['name' => 'search'])
            </span>
            <input type="search" class="starter-pg-control" placeholder="Cari nama, kode, atau deskripsi..." wire:model.live.debounce.350ms="search">
        </label>
    </div>
</div>
@include('starter.templates.components.danger-modal', [
    'id' => 'roles-lifecycle-modal',
    'title' => $pendingAction === 'forceDelete' ? 'Hapus role permanen?' : ($pendingAction === 'restore' ? 'Pulihkan role?' : 'Arsipkan role?'),
    'message' => $pendingAction === 'forceDelete' ? 'Data dan relasi akses role akan dihapus permanen.' : count($pendingIds).' role akan diproses. Role yang masih digunakan tidak akan diubah.',
    'confirmText' => $pendingAction === 'forceDelete' ? 'Hapus Permanen' : ($pendingAction === 'restore' ? 'Pulihkan' : 'Arsipkan'),
    'confirmAction' => 'executePendingAction', 'cancelAction' => 'cancelPendingAction',
    'visible' => $pendingAction !== null, 'dismissOnConfirm' => false,
])
@if ($pendingAction !== null)<div class="modal-backdrop fade show"></div>@endif
