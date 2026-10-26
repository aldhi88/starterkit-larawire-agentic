<div @if ($resource === 'post_categories') data-cms-content-page @endif>
    @include('starter-shared::cms.assets')
    <div class="page-header mt-0 mb-3" data-starter-region="page-header">
        <h2 class="page-title">{{ $cmsResource->label() }}</h2>
        <p class="text-secondary">Kelompokkan postingan agar mudah ditemukan. Tambah kategori di sini atau ubah nama melalui menu Aksi.</p>
    </div>
    <div class="cms-category-layout" data-starter-region="category-workspace">
        <section class="card" data-starter-region="category-form" aria-labelledby="category-form-title">
            <form wire:submit="save">
                <div class="card-header"><h3 id="category-form-title" class="card-title">{{ $contentId ? 'Edit Kategori' : 'Tambah Kategori' }}</h3></div>
                <div class="card-body vstack gap-3">
                    <div>
                        <label class="form-label" for="cms-category-name">Nama Kategori <span aria-hidden="true">*</span></label>
                        <input id="cms-category-name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name" maxlength="255" required autocomplete="off" x-data x-on:cms-category-editing.window="$nextTick(() => $el.focus())">
                        @error('name')<div class="text-danger small mt-1" role="alert">{{ $message }}</div>@enderror
                    </div>
                    @if ($contentId)
                        <p class="text-secondary small">Kode untuk pengembang: <code>{{ $mappingCode }}</code>. Mengubah nama kategori tidak mengubah kode ini.</p>
                    @else
                        <p class="text-secondary small">Anda cukup mengisi nama. Kode untuk pengembang dibuat otomatis.</p>
                    @endif
                </div>
                <div class="card-footer d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">{{ $contentId ? 'Simpan Perubahan' : 'Tambah Kategori' }}</button>
                    @if ($contentId)<button type="button" class="btn btn-outline-secondary" wire:click="cancelEditing">Batal</button>@endif
                </div>
            </form>
        </section>
        <section class="card" data-starter-region="data-table" aria-label="Daftar kategori">
            <p class="cms-table-scroll-hint text-secondary small mb-0 px-3 pt-3">Geser tabel ke samping untuk melihat status dan tanggal kategori.</p>
            <livewire:cms.table :resource="$resource" :key="'cms-category-table-'.$resource" />
        </section>
    </div>
</div>
