<div class="row g-3 align-items-start" data-cms-post-form data-cms-tabler-post-form>
    <div class="col-12 col-xl-8">
        <div class="card mb-3" data-starter-region="content-form">
            <div class="card-body vstack gap-3">
                <div class="vstack gap-3" data-cms-post-slug x-data="{ autoSlug: @js($isCreating && blank($form['slug'] ?? '')) }">
                    <div>
                        <label class="form-label" for="cms-title">Judul Postingan <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="cms-title" type="text" class="form-control @error('form.title') is-invalid @enderror" wire:model.defer="form.title" data-cms-slug-source maxlength="255" required placeholder="Tulis judul postingan" x-on:input="if (autoSlug) $wire.$set('form.slug', window.StarterCms.slug($event.target.value), false)">
                        @error('form.title')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="cms-slug">Alamat Postingan <span class="text-secondary fw-normal">(opsional)</span></label>
                        <input id="cms-slug" type="text" class="form-control @error('form.slug') is-invalid @enderror" wire:model.defer="form.slug" data-cms-slug-target maxlength="180" x-on:input="autoSlug = false" placeholder="judul-postingan">
                        <div class="form-hint mt-1">Otomatis dari judul. Ubah jika memerlukan alamat khusus.</div>
                        @error('form.slug')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
                    </div>
                </div>
                @include('starter.cms.editor')
            </div>
        </div>
        <div class="card" data-starter-region="post-details-form">
            <div class="card-header"><h3 class="card-title">Keterangan Tambahan</h3></div>
            <div class="card-body vstack gap-3">
                @include('starter.cms.fields.input', ['field' => 'subtitle', 'label' => 'Subjudul', 'optional' => true, 'maxlength' => 255, 'placeholder' => 'Kalimat singkat untuk melengkapi judul', 'help' => 'Boleh dikosongkan; subjudul tidak ditampilkan.'])
                @include('starter.cms.fields.input', ['field' => 'excerpt', 'label' => 'Ringkasan', 'optional' => true, 'type' => 'textarea', 'rows' => 3, 'maxlength' => 2000, 'placeholder' => 'Tulis deskripsi singkat untuk daftar postingan…', 'help' => 'Jika kosong, ringkasan otomatis diambil dari awal isi postingan.'])
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card mb-3" data-starter-region="thumbnail-form" x-data="{ thumbnail: @js(collect($mediaOptions)->firstWhere('id', (int) ($form['thumbnail_id'] ?? 0))) }" x-on:cms-thumbnail-selected.window="if ($event.detail.componentId === @js($this->getId())) { thumbnail = $event.detail.media; $wire.$set('form.thumbnail_id', thumbnail.id, false) }">
            <div class="card-header"><h3 class="card-title">Thumbnail</h3></div>
            <div class="card-body vstack gap-3">
                <template x-if="thumbnail"><figure class="m-0"><img :src="thumbnail.url" :alt="thumbnail.alt_text || thumbnail.name" class="cms-media-tile-preview rounded border" loading="lazy"><figcaption class="text-secondary small mt-2" x-text="thumbnail.name"></figcaption></figure></template>
                <p class="text-secondary small mb-0" x-show="!thumbnail">Pilih gambar sampul dari pustaka atau unggah gambar baru.</p>
                <div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-sm" x-on:click="$dispatch('cms-open-media-picker', { componentId: @js($this->getId()), type: 'image', purpose: 'thumbnail' })"><span x-text="thumbnail ? 'Ganti Thumbnail' : 'Pilih Thumbnail'">Pilih Thumbnail</span></button><button type="button" class="btn btn-sm btn-outline-danger" x-show="thumbnail" x-cloak x-on:click="thumbnail = null; $wire.$set('form.thumbnail_id', null, false)">Hapus thumbnail</button></div>
                @error('form.thumbnail_id')<div class="text-danger small" role="alert">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card mb-3" data-starter-region="post-categories-form">
            <div class="card-header"><div><h3 class="card-title">Kategori</h3><p class="card-subtitle">Pilih satu atau beberapa kategori.</p></div></div>
            <div class="card-body">
                @include('starter.cms.category-picker', ['categories' => $postCategoryTree, 'field' => 'form.category_ids', 'pickerId' => 'cms-form-categories'])
                <details class="cms-post-category-create mt-3">
                    <summary>Buat kategori baru</summary>
                    <div class="vstack gap-2 pt-3">
                        <div>
                            <label class="form-label" for="cms-new-category-name">Nama kategori</label>
                            <input id="cms-new-category-name" type="text" class="form-control form-control-sm @error('newCategoryName') is-invalid @enderror" wire:model.defer="newCategoryName" maxlength="255" placeholder="Contoh: Berita Daerah" x-on:keydown.enter.prevent="$wire.createPostCategory()">
                            @error('newCategoryName')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="cms-new-category-parent">Kategori induk <span class="fw-normal text-secondary">(opsional)</span></label>
                            <select id="cms-new-category-parent" class="form-select form-select-sm @error('newCategoryParentId') is-invalid @enderror" wire:model.defer="newCategoryParentId">
                                <option value="">Kategori utama</option>
                                @foreach ($postCategoryTree as $category)
                                    <option value="{{ $category['id'] }}">{{ $category['path'] }}</option>
                                @endforeach
                            </select>
                            @error('newCategoryParentId')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
                        </div>
                        <button type="button" class="btn btn-sm align-self-start" wire:click="createPostCategory" wire:loading.attr="disabled" wire:target="createPostCategory">Buat dan pilih</button>
                    </div>
                </details>
                @error('form.category_ids')<div class="text-danger small" role="alert">{{ $message }}</div>@enderror
                @error('form.category_ids.*')<div class="text-danger small" role="alert">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="card" data-starter-region="publication-form">
            <div class="card-header"><h3 class="card-title">Publikasi</h3></div>
            <div class="card-body vstack gap-3">
                <div>@include('starter.cms.fields.input', ['field' => 'status', 'label' => 'Status', 'type' => 'select', 'choices' => $statusChoices, 'noEmpty' => true, 'required' => true])
                    <div class="form-hint mt-1">Draf belum tampil di website. Pilih Terbit saat postingan siap ditampilkan.</div>
                </div>
                <div data-cms-publish-picker>
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2"><label class="form-label mb-0" for="cms-published_at">Waktu Terbit (WIB)</label><button type="button" class="btn btn-sm" data-cms-publish-now>Sekarang</button></div>
                    <div class="input-icon">
                        <span class="input-icon-addon"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M16 3v4M8 3v4M4 11h16M8 15h2v2h-2z"/></svg></span>
                        <input id="cms-published_at" type="text" autocomplete="off" placeholder="Pilih tanggal dan jam" class="form-control @error('form.published_at') is-invalid @enderror" wire:model.defer="publishedAtDisplay" data-cms-publish-input readonly aria-haspopup="dialog" aria-expanded="false" aria-describedby="cms-published-help">
                    </div>
                    <div id="cms-published-help" class="form-hint mt-1">Jika status Terbit, waktu mendatang akan menjadwalkan postingan.</div>
                    @error('form.published_at')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer" data-starter-region="page-actions"><button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="save,upload,editorUploads,finishEditorUploads" x-bind:disabled="cmsBusy"><span wire:loading.remove wire:target="save">Simpan {{ $cmsResource->singular() }}</span><span wire:loading wire:target="save" role="status">Menyimpan…</span></button></div>
        </div>
    </div>
</div>
