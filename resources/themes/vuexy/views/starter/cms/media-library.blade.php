<div>
    @include('starter-shared::cms.assets')
    <div class="page-header mt-0 mb-3" data-starter-region="page-header">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div><h2 class="page-title">{{ $mode === 'gallery' ? 'Galeri' : 'Pustaka Media' }}</h2><p class="text-secondary">Semua gambar dan dokumen Anda, mudah ditemukan dan digunakan kembali.</p></div>
            @if ($mode === 'gallery')<div class="d-flex flex-wrap gap-2"><a href="{{ route('cms.media.index') }}" class="btn btn-primary" data-starter-navigate>Upload Media</a>@if (\Illuminate\Support\Facades\Route::has('cms.media.albums.index'))<a href="{{ route('cms.media.albums.index') }}" class="btn btn-outline-secondary" data-starter-navigate>Kelola Album</a>@endif</div>@endif
        </div>
    </div>
    @if ($mode === 'upload')
        <section class="card mb-3" data-starter-region="media-upload">
            <div class="card-body">
                <div class="cms-media-dropzone" data-cms-dropzone x-data="{dragging:false,progress:0,uploading:false,uploadError:''}" :class="{'is-dragging':dragging}" @dragover.prevent="dragging=true" @dragleave.prevent="if (!$el.contains($event.relatedTarget)) dragging=false" @drop.prevent="dragging=false;if(!uploading) window.StarterCms.dropFiles($wire,$event,'uploads')" x-on:cms-upload-start="uploading=true;progress=0;uploadError=''" x-on:cms-upload-progress="progress=$event.detail.progress" x-on:cms-upload-finish="uploading=false;$refs.mediaFiles.value=''" x-on:cms-upload-error="uploading=false;uploadError='Upload belum berhasil. Silakan coba kembali.'" x-on:livewire-upload-start="uploading=true;progress=0;uploadError=''" x-on:livewire-upload-finish="uploading=false;$refs.mediaFiles.value=''" x-on:livewire-upload-error="uploading=false;uploadError='Upload belum berhasil. Silakan coba kembali.'" x-on:livewire-upload-progress="progress=$event.detail.progress">
                    <div class="h3 mb-2">Tarik file ke sini untuk mengunggah</div>
                    <p class="text-secondary small">Gambar JPG, PNG, WebP, GIF, atau PDF. Maksimal 20 file sekaligus, masing-masing 10 MB.</p>
                    <button type="button" class="btn btn-primary" @click="$refs.mediaFiles.click()" :disabled="uploading" wire:loading.attr="disabled" wire:target="uploads">Pilih File</button>
                    <input x-ref="mediaFiles" type="file" wire:model="uploads" multiple hidden accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" aria-label="Pilih file untuk diunggah">
                    <div x-show="uploading" x-cloak role="status"><div class="d-flex align-items-center justify-content-center gap-2 mt-3"><progress max="100" :value="progress" aria-label="Progres upload"></progress><span x-text="progress+'%'">0%</span></div></div>
                    <p x-show="uploadError" x-text="uploadError" x-cloak class="text-danger" role="alert"></p>
                    <div wire:loading wire:target="uploads" class="text-secondary small" role="status">Menyimpan file ke pustaka…</div>
                    @error('uploads')<div class="text-danger" role="alert">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>
    @endif
    @if ($uploadedCount > 0)<div class="alert alert-success mb-3" role="status">{{ $uploadedCount }} file berhasil ditambahkan ke pustaka.</div>@endif
    @if ($uploadFailures)
        <div class="alert alert-danger mt-3" role="alert"><strong>Beberapa file belum dapat diunggah.</strong><ul>@foreach ($uploadFailures as $failure)<li>{{ $failure['name'] }}: {{ $failure['message'] }}</li>@endforeach</ul></div>
    @endif
    <section class="card min-w-0" data-starter-region="media-library">
        <div class="card-body vstack gap-3">
            <div class="cms-media-toolbar" data-starter-region="media-toolbar">
                <div class="cms-media-search"><label for="cms-library-search" class="form-label">Cari Media</label><input id="cms-library-search" type="search" class="form-control" wire:model.live.debounce.350ms="search" placeholder="Cari nama media…" maxlength="100"></div>
                <div><label for="cms-library-type" class="form-label">Jenis File</label><select id="cms-library-type" class="form-select" wire:model.live="type"><option value="all">Semua file</option><option value="image">Gambar</option><option value="pdf">PDF</option></select></div>
                <div><label for="cms-library-archive" class="form-label">Status Arsip</label><select id="cms-library-archive" class="form-select" wire:model.live="archiveStatus"><option value="active">Data aktif</option><option value="archived">Arsip</option><option value="all">Semua data</option></select></div>
                <div><label for="cms-library-per-page" class="form-label">Per Halaman</label><select id="cms-library-per-page" class="form-select" wire:model.live="perPage"><option value="24">24</option><option value="48">48</option><option value="96">96</option></select></div>
            </div>
            @error('filters')<div class="alert alert-danger mt-3" role="alert">{{ $message }}</div>@enderror
            <div class="d-flex flex-wrap align-items-center gap-2" data-starter-region="media-selection">
                <span class="text-secondary small">{{ count($selected) }} dipilih · {{ $media->total() }} media</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="selectPage" @disabled($media->isEmpty())>Pilih Halaman Ini</button>
                @if ($selected)<button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearSelection">Batal Pilih</button>@endif
                <div class="dropdown" x-data="{open:false}" @click.outside="open=false" @keydown.escape.window="open=false">
                    <button type="button" class="btn btn-outline-secondary" @click="open=!open" :aria-expanded="open.toString()" aria-haspopup="menu">Aksi Media</button>
                    <div class="dropdown-menu" x-show="open" x-cloak :class="{'show':open}" role="menu">
                        @if ($archiveStatus !== 'archived')<button type="button" class="dropdown-item" wire:click="prepareAction('archive')" @click="open=false" @disabled(!$selected) role="menuitem">Arsipkan Terpilih</button><button type="button" class="dropdown-item" wire:click="prepareAction('archive',true)" @click="open=false" role="menuitem">Arsipkan Hasil Filter</button>@endif
                        @if ($archiveStatus !== 'active')<button type="button" class="dropdown-item" wire:click="prepareAction('restore')" @click="open=false" @disabled(!$selected) role="menuitem">Pulihkan Terpilih</button><button type="button" class="dropdown-item" wire:click="prepareAction('restore',true)" @click="open=false" role="menuitem">Pulihkan Hasil Filter</button><button type="button" class="dropdown-item" wire:click="prepareAction('delete_permanently')" @click="open=false" @disabled(!$selected) role="menuitem">Hapus Permanen Terpilih</button><button type="button" class="dropdown-item" wire:click="prepareAction('delete_permanently',true)" @click="open=false" role="menuitem">Hapus Permanen Hasil Filter</button>@endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="cms-media-grid" data-starter-region="media-grid">
                @forelse ($media as $file)
                    <article class="card min-w-0 @if(in_array($file['id'],array_map('intval',$selected),true)) border-primary @endif" wire:key="cms-media-{{ $file['id'] }}">
                        @if (!$file['archived'])<button type="button" class="cms-media-preview-button" wire:click="openDetails({{ $file['id'] }})" aria-label="Edit media {{ $file['name'] }}">@else<div>@endif
                            @if ($file['url'] && str_starts_with($file['mime_type'],'image/'))<img src="{{ $file['url'] }}" alt="{{ $file['alt_text'] ?: $file['name'] }}" class="cms-media-tile-preview" loading="lazy">@else<div class="cms-media-file-placeholder bg-lighter text-muted">@include('starter.templates.layouts.icon',['name'=>'file-text','class'=>'icon'])<span>{{ $file['mime_type'] === 'application/pdf' ? 'PDF' : 'Gambar' }}</span></div>@endif
                        @if (!$file['archived'])</button>@else</div>@endif
                        <div class="card-body p-2">
                            <label class="d-flex align-items-center gap-2 mb-1"><input type="checkbox" class="form-check-input m-0 flex-shrink-0" wire:model.live="selected" value="{{ $file['id'] }}" aria-label="Pilih {{ $file['name'] }}"><span class="text-truncate small fw-medium" title="{{ $file['name'] }}">{{ $file['name'] }}</span></label>
                            <div class="text-secondary small">{{ $file['size'] >= 1048576 ? number_format($file['size']/1048576,1,',','.') . ' MB' : number_format($file['size']/1024,0,',','.') . ' KB' }} · {{ $file['archived'] ? 'Arsip' : ($file['status'] === 'active' ? 'Aktif' : 'Nonaktif') }}</div>
                            @if ($file['archived'])<div class="d-flex flex-wrap gap-2 mt-2"><button type="button" class="btn btn-sm btn-outline-secondary" wire:click="prepareAction('restore',false,{{ $file['id'] }})">Pulihkan</button><button type="button" class="btn btn-sm btn-outline-danger" wire:click="prepareAction('delete_permanently',false,{{ $file['id'] }})">Hapus Permanen</button></div>@endif
                        </div>
                    </article>
                @empty
                    <div class="cms-media-empty text-center py-5"><h3 class="h3">Belum ada media yang sesuai</h3><p class="text-secondary small">{{ $search !== '' || $type !== 'all' ? 'Coba kata pencarian atau jenis file lain.' : 'Upload gambar atau PDF untuk mulai mengisi pustaka.' }}</p></div>
                @endforelse
            </div>
        </div>
        <div class="card-footer d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" aria-label="Pagination media">
            <span class="text-secondary small">Menampilkan {{ $media->firstItem() ?? 0 }}–{{ $media->lastItem() ?? 0 }} dari {{ $media->total() }} media</span>
            <div class="d-flex flex-wrap align-items-center gap-2"><button type="button" class="btn btn-sm btn-outline-secondary" wire:click="goToMediaPage({{ max(1,$media->currentPage()-1) }})" @disabled($media->onFirstPage()) aria-label="Halaman sebelumnya">Sebelumnya</button>@foreach (range(max(1,min($media->currentPage()-2,$media->lastPage()-4)),min($media->lastPage(),max(5,$media->currentPage()+2))) as $page)<button type="button" class="{{ $page === $media->currentPage() ? 'btn btn-primary' : 'btn btn-outline-secondary' }}" wire:click="goToMediaPage({{ $page }})" @if($page === $media->currentPage()) aria-current="page" @endif>{{ $page }}</button>@endforeach<button type="button" class="btn btn-sm btn-outline-secondary" wire:click="goToMediaPage({{ $media->currentPage()+1 }})" @disabled(!$media->hasMorePages()) aria-label="Halaman berikutnya">Berikutnya</button></div>
        </div>
    </section>
    @include('starter.cms.media-details')
    @include('starter.cms.media-confirmation')
</div>
