<div class="card-body py-3" data-starter-region="table-toolbar" x-data="{}">
    <div class="row g-2 align-items-center">
        <div class="col-auto" data-starter-region="bulk-actions">
            <div class="dropdown" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" class="btn dropdown-toggle" @click="open = !open" :aria-expanded="open" aria-haspopup="menu">Aksi</button>
                <ul class="dropdown-menu top-100 start-0 mt-1" :class="{ 'show': open }" x-show="open" x-cloak role="menu">
                    @if ($resource === 'posts')
                        @foreach ([false => 'Postingan terpilih', true => 'Semua hasil filter'] as $filteredScope => $scopeLabel)
                            @if ($filteredScope)<li><hr class="dropdown-divider my-1"></li>@endif
                            <li class="px-3 py-2 bg-light" role="presentation">
                                <div class="fw-bold text-body d-flex align-items-center gap-2 text-nowrap">
                                    {{ $scopeLabel }}
                                    @if (! $filteredScope)<span class="badge bg-secondary-lt" x-text="$store.pgBulkActions.count(@js($tableName))">{{ count($checkboxValues) }}</span>@endif
                                </div>
                                @if ($filteredScope)<div class="small text-secondary">Termasuk halaman lain</div>@endif
                            </li>
                            @if ($archiveStatus !== 'archived')
                                @foreach (['publish_now' => 'Terbitkan sekarang', 'draft' => 'Jadikan draf', 'categories' => 'Ubah kategori'] as $quickAction => $quickLabel)
                                    <li><button type="button" class="dropdown-item" wire:click="preparePostAction('{{ $quickAction }}', null, {{ $filteredScope ? 'true' : 'false' }})" @click="open = false" @if (! $filteredScope) :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" @endif role="menuitem">{{ $quickLabel }}</button></li>
                                @endforeach
                                <li><button type="button" class="dropdown-item" wire:click="prepareBulkAction('archive', {{ $filteredScope ? 'true' : 'false' }})" @click="open = false" @if (! $filteredScope) :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" @endif role="menuitem">Arsipkan</button></li>
                            @endif
                            @if ($archiveStatus !== 'active')
                                @foreach (['restore' => 'Pulihkan', 'delete_permanently' => 'Hapus permanen'] as $archiveAction => $archiveLabel)
                                    <li><button type="button" class="dropdown-item" wire:click="prepareBulkAction('{{ $archiveAction }}', {{ $filteredScope ? 'true' : 'false' }})" @click="open = false" @if (! $filteredScope) :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" @endif role="menuitem">{{ $archiveLabel }}</button></li>
                                @endforeach
                            @endif
                        @endforeach
                    @else
                    @if ($archiveStatus !== 'archived')
                        <li><button type="button" class="dropdown-item" wire:click="prepareBulkAction('archive')" @click="open = false" :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" role="menuitem">Arsipkan Terpilih</button></li>
                        <li><button type="button" class="dropdown-item" wire:click="prepareBulkAction('archive', true)" @click="open = false" role="menuitem">Arsipkan Hasil Filter</button></li>
                    @endif
                    @if ($archiveStatus !== 'active')
                        <li><button type="button" class="dropdown-item" wire:click="prepareBulkAction('restore')" @click="open = false" :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" role="menuitem">Pulihkan Terpilih</button></li>
                        <li><button type="button" class="dropdown-item" wire:click="prepareBulkAction('restore', true)" @click="open = false" role="menuitem">Pulihkan Hasil Filter</button></li>
                        <li><button type="button" class="dropdown-item" wire:click="prepareBulkAction('delete_permanently')" @click="open = false" :disabled="$store.pgBulkActions.count(@js($tableName)) === 0" role="menuitem">Hapus Permanen Terpilih</button></li>
                        <li><button type="button" class="dropdown-item" wire:click="prepareBulkAction('delete_permanently', true)" @click="open = false" role="menuitem">Hapus Permanen Hasil Filter</button></li>
                    @endif
                    @endif
                </ul>
            </div>
        </div>
        <div class="col-auto text-secondary small"><span x-text="$store.pgBulkActions.count(@js($tableName))">{{ count($checkboxValues) }}</span> dipilih</div>
        <div class="col-12 col-md-3 ms-md-auto">
            <label class="visually-hidden" for="{{ $tableName }}-archive">Status Arsip</label>
            <select id="{{ $tableName }}-archive" class="form-select" wire:model.live="archiveStatus"><option value="active">Data aktif</option><option value="archived">Arsip</option><option value="all">Semua data</option></select>
        </div>
        <div class="col-12 col-md-4">
            <label class="visually-hidden" for="{{ $tableName }}-search">Cari {{ \Aldhi88\StarterKit\Support\Cms\CmsResource::from($resource)->label() }}</label>
            <input id="{{ $tableName }}-search" type="search" class="form-control" placeholder="{{ $resource === 'posts' ? 'Cari judul postingan…' : ($resource === 'post_categories' ? 'Cari nama kategori…' : 'Cari nama…') }}" maxlength="100" wire:model.live.debounce.350ms="search">
        </div>
    </div>
    @if ($resource === 'post_categories')
        <div class="text-secondary small mt-2" id="cms-category-drag-help">
            @if ($this->categoryRowsReorderable())
                Seret pegangan baris untuk mengubah urutan dalam induk yang sama. Tersimpan otomatis.
                <span class="visually-hidden">Pada pegangan, gunakan Alt dan panah atas atau bawah untuk memindahkan dengan keyboard.</span>
            @elseif ($archiveStatus === 'active')
                <button type="button" class="btn btn-link btn-sm p-0" wire:click="showCategoryOrder">Tampilkan urutan hierarki</button> untuk menggeser baris.
            @endif
        </div>
        <div data-cms-category-feedback class="small mt-2" role="status" aria-live="polite"></div>
        @error('order')<div class="alert alert-danger mt-2 mb-0" role="alert">{{ $message }}</div>@enderror
    @endif
</div>
@error('tableFilters')<div class="alert alert-danger m-3" role="alert">{{ $message }}</div>@enderror
@include('starter.cms.powergrid.confirmation')

@if ($resource === 'posts')
    @include('starter.cms.powergrid.post-action')
@endif
