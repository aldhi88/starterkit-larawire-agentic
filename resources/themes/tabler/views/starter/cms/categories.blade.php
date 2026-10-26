<div @if ($resource === 'post_categories') data-cms-content-page @endif>
    @include('starter-shared::cms.assets', ['cmsSortableAssets' => $resource === 'post_categories'])
    <div class="page-header mt-0 mb-3" data-starter-region="page-header">
        <div class="row g-2 align-items-center">
            <div class="col"><h2 class="page-title">{{ $cmsResource->label() }}</h2><p class="text-secondary mb-0">Kelompokkan postingan dan susun subkategori di bawah kategori induknya.</p></div>
        </div>
    </div>
    <div class="cms-category-layout" data-starter-region="category-workspace">
        <section class="card" data-starter-region="category-form" aria-labelledby="category-form-title">
            <form novalidate wire:submit="save">
                <div class="card-header"><h3 id="category-form-title" class="card-title">{{ $contentId ? 'Edit Kategori' : 'Tambah Kategori' }}</h3></div>
                <div class="card-body vstack gap-3">
                    <div>
                        <label class="form-label" for="cms-category-name">Nama Kategori <span aria-hidden="true">*</span></label>
                        <input id="cms-category-name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name" maxlength="255" required autocomplete="off" x-data x-on:cms-category-editing.window="$nextTick(() => $el.focus())">
                        @error('name')<div class="text-danger small mt-1" role="alert">{{ $message }}</div>@enderror
                    </div>
                    @if ($resource === 'post_categories')
                        <div>
                            <label class="form-label" for="cms-category-parent">Kategori Induk</label>
                            <select id="cms-category-parent" class="form-select @error('parent_id') is-invalid @enderror" wire:model.defer="parentId">
                                <option value="">Kategori utama</option>
                                @foreach ($postCategoryTree as $category)
                                    @if ($category['id'] !== $contentId)
                                        <option value="{{ $category['id'] }}">{{ $category['path'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-hint mt-1">Pilih induk untuk membuat subkategori, misalnya Berita › Daerah.</div>
                            @error('parent_id')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    @if ($contentId)
                        <p class="text-secondary small">Kode untuk pengembang: <code>{{ $mappingCode }}</code>. Mengubah nama kategori tidak mengubah kode ini.</p>
                    @else
                        <p class="text-secondary small mb-0">Kode untuk pengembang dibuat otomatis.</p>
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
