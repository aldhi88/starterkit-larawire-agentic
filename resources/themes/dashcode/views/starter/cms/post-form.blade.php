<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start" data-cms-post-form>
    <div class="lg:col-span-2 min-w-0">
        <div class="card" data-starter-region="content-form">
            <div class="card-body p-6 space-y-5">
                <div class="space-y-5" data-cms-post-slug x-data="{ autoSlug: @js($isCreating && blank($form['slug'] ?? '')) }">
                    <div>
                        <label class="form-label" for="cms-title">Judul Postingan <span class="text-danger-500" aria-hidden="true">*</span></label>
                        <input id="cms-title" type="text" class="form-control w-full @error('form.title') is-invalid @enderror" wire:model.defer="form.title" data-cms-slug-source maxlength="255" required placeholder="Tulis judul postingan" x-on:input="if (autoSlug) $wire.$set('form.slug', window.StarterCms.slug($event.target.value), false)">
                        @error('form.title')<div class="text-danger-500 text-sm mt-1" role="alert">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="cms-slug">Alamat Postingan <span class="text-slate-500 font-normal">(opsional)</span></label>
                        <input id="cms-slug" type="text" class="form-control w-full @error('form.slug') is-invalid @enderror" wire:model.defer="form.slug" data-cms-slug-target maxlength="180" x-on:input="autoSlug = false" placeholder="judul-postingan">
                        <div class="text-sm text-slate-500 mt-1">Dibuat otomatis dari judul. Ubah hanya jika Anda ingin alamat khusus.</div>
                        @error('form.slug')<div class="text-danger-500 text-sm mt-1" role="alert">{{ $message }}</div>@enderror
                    </div>
                </div>
                @include('starter.cms.editor')
            </div>
        </div>
    </div>
    <div class="min-w-0">
        <div class="card mb-5" data-starter-region="publication-form">
            <div class="card-header"><h3 class="card-title">Publikasi</h3></div>
            <div class="card-body p-6 space-y-5">
                <div>@include('starter.cms.fields.input', ['field' => 'status', 'label' => 'Status', 'type' => 'select', 'choices' => $statusChoices, 'noEmpty' => true, 'required' => true])
                    <div class="text-sm text-slate-500 mt-1">Draf belum tampil di website. Pilih Terbit saat postingan siap ditampilkan.</div>
                </div>
                @include('starter.cms.fields.input', ['field' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'choices' => $categoryOptions, 'emptyLabel' => 'Tanpa kategori'])
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2"><label class="form-label mb-0" for="cms-published_at">Waktu Terbit (WIB)</label><button type="button" class="btn btn-secondary btn-sm" x-on:click="$wire.$set('form.published_at', window.StarterCms.localDateTime('Asia/Jakarta'), false)">Sekarang</button></div>
                    <input id="cms-published_at" type="datetime-local" class="form-control w-full @error('form.published_at') is-invalid @enderror" wire:model.defer="form.published_at">
                    <div class="text-sm text-slate-500 mt-1">Jika status Terbit dan waktunya belum tiba, postingan akan terjadwal otomatis.</div>
                    @error('form.published_at')<div class="text-danger-500 text-sm mt-1" role="alert">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer p-6 border-t border-slate-200" data-starter-region="page-actions"><button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="save,upload,editorUploads,finishEditorUploads" x-bind:disabled="cmsBusy"><span wire:loading.remove wire:target="save">Simpan {{ $cmsResource->singular() }}</span><span wire:loading wire:target="save" role="status">Menyimpan…</span></button></div>
        </div>
        <div class="card mb-5" data-starter-region="thumbnail-form" x-data="{ thumbnail: @js(collect($mediaOptions)->firstWhere('id', (int) ($form['thumbnail_id'] ?? 0))) }" x-on:cms-thumbnail-selected.window="if ($event.detail.componentId === @js($this->getId())) { thumbnail = $event.detail.media; $wire.$set('form.thumbnail_id', thumbnail.id, false) }">
            <div class="card-header"><h3 class="card-title">Thumbnail</h3></div>
            <div class="card-body p-6 space-y-5">
                <template x-if="thumbnail"><figure class="m-0"><img :src="thumbnail.url" :alt="thumbnail.alt_text || thumbnail.name" class="cms-media-tile-preview rounded border border-slate-200" loading="lazy"><figcaption class="text-sm text-slate-500 mt-2" x-text="thumbnail.name"></figcaption></figure></template>
                <p class="text-sm text-slate-500 mb-0" x-show="!thumbnail">Pilih gambar sampul dari pustaka atau unggah gambar baru.</p>
                <div class="flex flex-wrap gap-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('cms-open-media-picker', { componentId: @js($this->getId()), type: 'image', purpose: 'thumbnail' })"><span x-text="thumbnail ? 'Ganti Thumbnail' : 'Pilih Thumbnail'">Pilih Thumbnail</span></button><button type="button" class="btn btn-outline-danger" x-show="thumbnail" x-cloak x-on:click="thumbnail = null; $wire.$set('form.thumbnail_id', null, false)">Lepas</button></div>
                @error('form.thumbnail_id')<div class="text-danger-500 text-sm" role="alert">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="card" data-starter-region="post-details-form">
            <div class="card-header"><h3 class="card-title">Keterangan Tambahan</h3></div>
            <div class="card-body p-6 space-y-5">
                @include('starter.cms.fields.input', ['field' => 'subtitle', 'label' => 'Subjudul', 'maxlength' => 255])
                @include('starter.cms.fields.input', ['field' => 'excerpt', 'label' => 'Ringkasan', 'type' => 'textarea', 'rows' => 4, 'maxlength' => 2000])
            </div>
        </div>
    </div>
</div>
