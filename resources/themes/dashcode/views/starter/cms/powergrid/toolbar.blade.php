<div class="p-4" data-starter-region="table-toolbar" x-data="{}">
    <div class="flex flex-col md:flex-row gap-3">
        <div class="flex items-center gap-3 shrink-0" data-starter-region="bulk-actions">
            <div class="dashcode-bulk-dropdown" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" class="btn btn-sm btn-outline-dark h-10" @click="open = !open" :aria-expanded="open" aria-haspopup="menu">Aksi</button>
                <ul class="dashcode-table-dropdown" :class="{ 'show': open }" x-show="open" x-cloak role="menu">
                    @if ($archiveStatus !== 'archived')
                        <li><button type="button" class="dashcode-table-dropdown-item" wire:click="prepareBulkAction('archive')" @click="open = false" :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" role="menuitem">Arsipkan Terpilih</button></li>
                        <li><button type="button" class="dashcode-table-dropdown-item" wire:click="prepareBulkAction('archive', true)" @click="open = false" role="menuitem">Arsipkan Hasil Filter</button></li>
                    @endif
                    @if ($archiveStatus !== 'active')
                        <li><button type="button" class="dashcode-table-dropdown-item" wire:click="prepareBulkAction('restore')" @click="open = false" :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" role="menuitem">Pulihkan Terpilih</button></li>
                        <li><button type="button" class="dashcode-table-dropdown-item" wire:click="prepareBulkAction('restore', true)" @click="open = false" role="menuitem">Pulihkan Hasil Filter</button></li>
                        <li><button type="button" class="dashcode-table-dropdown-item" wire:click="prepareBulkAction('delete_permanently')" @click="open = false" :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" role="menuitem">Hapus Permanen Terpilih</button></li>
                        <li><button type="button" class="dashcode-table-dropdown-item" wire:click="prepareBulkAction('delete_permanently', true)" @click="open = false" role="menuitem">Hapus Permanen Hasil Filter</button></li>
                    @endif
                </ul>
            </div>
            <div class="text-sm text-slate-500"><span x-text="$store.pgBulkActions.count(@js($tableName))">{{ count($checkboxValues) }}</span> dipilih</div>
        </div>
        <div class="flex-1 min-w-0">
            <select id="{{ $tableName }}-archive" class="form-control h-10 w-full margin-0" wire:model.live="archiveStatus" aria-label="Status Arsip"><option value="active">Data aktif</option><option value="archived">Arsip</option><option value="all">Semua data</option></select>
        </div>
        <div class="flex-1 min-w-0">
            <input id="{{ $tableName }}-search" type="search" class="form-control h-10 w-full margin-0" placeholder="{{ $resource === 'posts' ? 'Cari judul postingan…' : ($resource === 'post_categories' ? 'Cari nama kategori…' : 'Cari nama…') }}" maxlength="100" wire:model.live.debounce.350ms="search" aria-label="Cari {{ \Aldhi88\StarterKit\Support\Cms\CmsResource::from($resource)->label() }}">
        </div>
    </div>
</div>
@error('tableFilters')<div class="alert alert-danger m-4" role="alert">{{ $message }}</div>@enderror
@include('starter.cms.powergrid.confirmation')
