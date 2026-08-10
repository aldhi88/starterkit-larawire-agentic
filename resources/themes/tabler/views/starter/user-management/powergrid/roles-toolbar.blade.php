<style>
    .dt--top-section { display: none !important; }
</style>
<div class="card-body border-bottom-0 pt-3 pb-3">
    <div class="row g-3 align-items-center">

        <div class="col-auto">
            <div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="btn dropdown-toggle" @click="open = !open" :class="{ 'show': open }" :aria-expanded="open">
                    @include('starter.templates.layouts.icon', ['name' => 'table', 'class' => 'icon-sm me-1'])
                    Aksi
                </button>
                <ul class="dropdown-menu" :class="{ 'show': open }" x-show="open" style="position: absolute; z-index: 1000; inset: 0px auto auto 0px; transform: translate3d(0px, 40px, 0px);">
                    @if ($archiveStatus !== 'archived')
                        <li>
                            <button type="button" class="dropdown-item {{ empty($checkboxValues) ? 'disabled' : '' }}" wire:click="prepareBulkAction('archive')" @click="open = false">
                                Arsipkan Terpilih
                            </button>
                        </li>
                    @endif
                    @if ($archiveStatus !== 'active')
                        <li>
                            <button type="button" class="dropdown-item {{ empty($checkboxValues) ? 'disabled' : '' }}" wire:click="prepareBulkAction('restore')" @click="open = false">
                                Pulihkan Terpilih
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item {{ empty($checkboxValues) ? 'disabled' : '' }}" wire:click="prepareBulkAction('forceDelete')" @click="open = false">
                                Hapus Permanen
                            </button>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-auto ms-auto d-flex flex-column flex-md-row gap-3">
            <select class="form-select w-auto" wire:model.live="archiveStatus" aria-label="Status arsip role">
                <option value="active">Data aktif</option>
                <option value="archived">Arsip</option>
                <option value="all">Semua data</option>
            </select>

            <div class="input-icon" style="min-width: 250px;">
                <span class="input-icon-addon">
                    @include('starter.templates.layouts.icon', ['name' => 'search', 'class' => 'icon-sm'])
                </span>
                <input type="search" class="form-control" placeholder="Cari nama, kode, atau deskripsi..." wire:model.live.debounce.350ms="search">
            </div>
        </div>

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
