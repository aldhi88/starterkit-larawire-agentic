@php
    $isContent = $cmsResource->isContent();
    $isCategory = $cmsResource->isCategory() || $resource === 'menu_categories';
    $categoryOptions = $options['categories'] ?? [];
    $mediaOptions = collect($options['media'] ?? [])->filter(fn ($media) => str_starts_with($media['mime_type'] ?? '', 'image/'))->values()->all();
    $menuCategoryOptions = $options['menuCategories'] ?? $options['menu_categories'] ?? [];
    $statusChoices = collect($cmsResource->statusOptions())->map(fn ($label, $id) => compact('id', 'label'))->map(fn ($row) => ['id' => $row['id'], 'name' => $row['label']])->values()->all();
@endphp
<div data-cms-workspace @if (in_array($resource, ['posts', 'pages'], true)) data-cms-content-page @endif x-data="{ cmsBusy: false }" @cms-upload-start="cmsBusy = true" @cms-upload-finish="cmsBusy = false" @cms-upload-error="cmsBusy = false" x-on:livewire-upload-start="cmsBusy = true" x-on:livewire-upload-finish="cmsBusy = false" x-on:livewire-upload-error="cmsBusy = false">
    @include('starter-shared::cms.assets', ['cmsEditorAssets' => $isContent, 'cmsSortableAssets' => $resource === 'menus'])
    <div class="page-header mb-5" data-starter-region="page-header">
        <div class="dashcode-page-heading w-full">
            <div class="min-w-0">
                <h2 class="page-title">{{ $resource === 'comments' ? 'Moderasi Komentar' : ($isCreating ? 'Tambah ' : 'Edit ').$cmsResource->singular() }}</h2>
            </div>
            <div class="shrink-0">
                @include('starter-shared::cms.editor-actions')
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-5" role="alert">Perubahan belum tersimpan. Periksa kembali kolom yang ditandai.</div>
    @endif

    <form novalidate id="cms-content-form-{{ $this->getId() }}" wire:submit="save" @submit.capture="if (cmsBusy) { $event.preventDefault(); $event.stopImmediatePropagation(); }">
        @if (in_array($resource, ['posts', 'pages'], true))
            @include('starter.cms.post-form')
        @elseif ($resource === 'cards')
            @include('starter-shared::cms.card-form')
        @elseif ($resource === 'comments')
            @include('starter-shared::cms.comment-moderation')
        @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 min-w-0">
                <div class="card mb-5" data-starter-region="content-form">
                    <div class="card-header"><div><h3 class="card-title">Informasi Utama</h3><p class="card-subtitle">Kolom bertanda * wajib diisi.</p></div></div>
                    <div class="card-body p-6 space-y-5">
                        @include('starter.cms.fields.input', ['field' => 'name', 'label' => $isContent ? 'Nama Internal' : 'Nama', 'required' => true, 'maxlength' => 255, 'help' => $isContent ? 'Nama untuk mengenali entri di dashboard; dapat berbeda dari judul yang ditampilkan.' : null])
                        @if ($isContent)
                            @include('starter.cms.fields.input', ['field' => 'title', 'label' => 'Judul', 'maxlength' => 255])
                            @include('starter.cms.fields.input', ['field' => 'subtitle', 'label' => 'Subjudul', 'maxlength' => 255])
                            @include('starter.cms.fields.input', ['field' => 'slug', 'label' => 'Slug', 'maxlength' => 180, 'help' => $resource === 'cards' ? 'Opsional. Isi bila Card memerlukan identitas URL atau mapping khusus.' : 'Identitas URL konten. Jika kosong, dibuat dari judul atau nama internal.'])
                            @include('starter.cms.fields.input', ['field' => 'excerpt', 'label' => 'Ringkasan / Deskripsi Singkat', 'type' => 'textarea', 'rows' => 3, 'maxlength' => 2000])
                            @include('starter.cms.editor')
                        @elseif ($isCategory || $resource === 'menus')
                            @include('starter.cms.fields.input', ['field' => 'code', 'label' => 'Kode', 'required' => true, 'maxlength' => 100, 'help' => 'Kode stabil untuk mengambil kelompok konten. Gunakan huruf kecil, angka, titik, tanda hubung, atau garis bawah.'])
                            @if ($isCategory)
                                @include('starter.cms.fields.input', ['field' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'rows' => 5, 'maxlength' => 5000])
                            @endif
                        @elseif ($resource === 'galleries')
                            @include('starter.cms.fields.input', ['field' => 'slug', 'label' => 'Slug', 'maxlength' => 180])
                            @include('starter.cms.fields.input', ['field' => 'description', 'label' => 'Deskripsi Galeri', 'type' => 'textarea', 'rows' => 5, 'maxlength' => 5000])
                        @elseif ($resource === 'media')
                            <div>
                                <label class="form-label" for="cms-upload">{{ $isCreating ? 'File *' : 'Ganti File' }}</label>
                                <input id="cms-upload" type="file" class="form-control w-full @error('upload') is-invalid @enderror" wire:model="upload" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf">
                                <div class="text-sm text-slate-500 mt-1">Gambar JPG, PNG, WebP, GIF, atau dokumen PDF. Maksimal 10 MB.</div>
                                <div class="text-sm text-slate-500" wire:loading wire:target="upload" role="status">Mengunggah file…</div>
                                @error('upload')<div class="text-danger-500 text-sm mt-1" role="alert">{{ $message }}</div>@enderror
                            </div>
                            @if (filled($form['url'] ?? null))
                                <div class="rounded border border-slate-200 p-4">
                                    <a href="{{ $form['url'] }}" class="text-primary-500 font-medium" target="_blank" rel="noopener noreferrer">Buka file saat ini</a>
                                    <div class="text-sm text-slate-500">{{ $form['original_name'] ?? '' }} · {{ number_format(($form['size'] ?? 0) / 1024, 1, ',', '.') }} KB</div>
                                </div>
                            @endif
                            @include('starter.cms.fields.input', ['field' => 'alt_text', 'label' => 'Teks Alternatif Gambar', 'maxlength' => 255, 'help' => 'Deskripsikan gambar untuk aksesibilitas.'])
                            @include('starter.cms.fields.input', ['field' => 'caption', 'label' => 'Keterangan', 'type' => 'textarea', 'rows' => 4, 'maxlength' => 5000])
                        @endif
                    </div>
                </div>

                @if ($isContent)
                    <div class="card mb-5" data-starter-region="media-form">
                        <div class="card-header"><div><h3 class="card-title">Media dan Tautan</h3><p class="card-subtitle">Pilih media yang diperlukan; kolom lain dapat dibiarkan kosong.</p></div></div>
                        <div class="card-body p-6 space-y-5">
                            <div><label class="form-label" for="cms-media-search">Cari Media</label><input id="cms-media-search" type="search" class="form-control" wire:model.live.debounce.350ms="mediaSearch" maxlength="100" placeholder="Cari nama file atau media…"><div class="text-sm text-slate-500 mt-1">Cari untuk menemukan media lain di pustaka.</div></div>
                            @foreach (['image_id' => 'Gambar Utama', 'thumbnail_id' => 'Thumbnail', 'logo_id' => 'Logo / Ikon'] as $mediaField => $mediaLabel)
                                <div class="flex items-start gap-4" x-data="{ selected: @js((string) ($form[$mediaField] ?? '')), images: @js(collect($mediaOptions)->keyBy('id')->all()) }">
                                    <div class="flex-1 min-w-0">
                                        @include('starter.cms.fields.input', ['field' => $mediaField, 'label' => $mediaLabel, 'type' => 'select', 'choices' => $mediaOptions, 'emptyLabel' => 'Tanpa media', 'previewMedia' => true])
                                    </div>
                                    <img x-show="images[selected]" x-bind:src="images[selected]?.url || null" x-bind:alt="images[selected]?.name || ''" class="h-20 w-20 object-contain rounded border border-slate-200" loading="lazy" x-cloak>
                                </div>
                            @endforeach
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="">@include('starter.cms.fields.input', ['field' => 'link_label', 'label' => 'Label Tautan / Tombol', 'maxlength' => 100])</div>
                                <div class="">@include('starter.cms.fields.input', ['field' => 'link_url', 'label' => 'Tujuan Tautan', 'maxlength' => 2000, 'help' => 'URL HTTP/HTTPS atau path website, misalnya /kontak.'])</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($resource === 'galleries')
                    <div class="card mb-5" data-starter-region="gallery-form">
                        <div class="card-header"><div><h3 class="card-title">Foto Galeri</h3><p class="card-subtitle">Tambahkan foto dari Pustaka Media dan atur urutannya.</p></div></div>
                        <div class="card-body p-6 space-y-5">
                            <div><label class="form-label" for="cms-media-search">Cari Media</label><input id="cms-media-search" type="search" class="form-control" wire:model.live.debounce.350ms="mediaSearch" maxlength="100" placeholder="Cari nama file atau media…"><div class="text-sm text-slate-500 mt-1">Cari untuk menemukan media lain di pustaka.</div></div>
                            <div class="flex flex-col md:flex-row gap-3">
                                <label class="flex-1">
                                    <span class="block mb-2 form-label">Pilih Foto</span>
                                    <select class="form-control w-full" wire:model="selectedMediaId"><option value="">Pilih media</option>@foreach ($mediaOptions as $media)<option value="{{ $media['id'] }}">{{ $media['name'] }}</option>@endforeach</select>
                                </label>
                                <button type="button" class="btn cms-btn-neutral self-end" wire:click="addGalleryMedia">@include('starter.cms.action-icon', ['action' => 'add']) Tambah Foto</button>
                            </div>
                            @error('form.media_ids')<div class="text-danger-500 text-sm mt-1">{{ $message }}</div>@enderror
                            @forelse ($form['media_ids'] ?? [] as $index => $mediaId)
                                @php($galleryMedia = collect($mediaOptions)->firstWhere('id', (int) $mediaId))
                                <div class="flex flex-wrap items-center gap-3 border border-slate-200 rounded p-3" wire:key="gallery-media-{{ $mediaId }}">
                                    @if (filled($galleryMedia['url'] ?? null))<img src="{{ $galleryMedia['url'] }}" alt="{{ $galleryMedia['name'] }}" class="h-20 w-20 object-contain rounded border border-slate-200" loading="lazy">@endif
                                    <div class="flex-1 min-w-0"><strong>{{ $galleryMedia['name'] ?? 'Media #'.$mediaId }}</strong><div class="text-sm text-slate-500">Urutan {{ $index + 1 }}</div></div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-secondary" wire:click="moveGalleryMedia({{ $index }}, -1)" @disabled($index === 0) aria-label="Pindahkan foto ke atas">Atas</button>
                                        <button type="button" class="btn btn-sm btn-secondary" wire:click="moveGalleryMedia({{ $index }}, 1)" @disabled($index === count($form['media_ids']) - 1) aria-label="Pindahkan foto ke bawah">Bawah</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeGalleryMedia({{ $index }})">Lepas</button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded border border-dashed border-slate-300 p-6 text-center text-slate-500">Belum ada foto. Pilih media untuk mulai menyusun galeri.</div>
                            @endforelse
                        </div>
                    </div>
                @endif

                @if ($resource === 'menus')
                    <div class="card mb-5" data-starter-region="menu-items-form" data-cms-menu-builder>
                        <div class="card-header"><div><h3 class="card-title">Susunan Menu</h3><p class="card-subtitle">Tarik pegangan untuk mengurutkan. Tarik ke bawah item lain untuk membuat submenu.</p></div></div>
                        <div class="card-body p-6 space-y-5">
                            @error('form.items')<div class="text-danger-500 text-sm mt-1" role="alert">{{ $message }}</div>@enderror
                            <ul class="cms-menu-tree" data-cms-menu-list data-cms-menu-root aria-label="Susunan menu website">
                                @include('starter.cms.menu-tree', ['nodes' => $menuTree])
                            </ul>
                            @if (empty($menuTree))<div class="cms-menu-empty">Belum ada item. Tambahkan tautan pertama untuk mulai menyusun menu.</div>@endif
                            <button type="button" class="btn btn-outline-primary" wire:click="addMenuItem">@include('starter.cms.action-icon', ['action' => 'add']) Tambah Item Menu</button>
                        </div>
                    </div>
                @endif
            </div>
            <div class="min-w-0">
                <div class="card mb-5" data-starter-region="publication-form">
                    <div class="card-header"><div><h3 class="card-title">Pengaturan</h3><p class="card-subtitle">Atur status dan pengelompokan.</p></div></div>
                    <div class="card-body p-6 space-y-5">
                        @include('starter.cms.fields.input', ['field' => 'status', 'label' => 'Status', 'type' => 'select', 'choices' => $statusChoices, 'noEmpty' => true, 'required' => true])
                        @if ($isContent)
                            @include('starter.cms.fields.input', ['field' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'choices' => $categoryOptions, 'emptyLabel' => 'Tanpa kategori'])
                            @include('starter.cms.fields.input', ['field' => 'published_at', 'label' => 'Waktu Terbit', 'type' => 'datetime-local', 'help' => 'Tanggal mendatang menjadwalkan publikasi. Kosong berarti terbit saat disimpan ketika status Terbit.'])
                        @elseif ($resource === 'menus')
                            @include('starter.cms.fields.input', ['field' => 'category_id', 'label' => 'Kategori Menu', 'type' => 'select', 'choices' => $menuCategoryOptions, 'emptyLabel' => 'Tanpa kategori'])
                        @endif
                        @include('starter.cms.fields.input', ['field' => 'position', 'label' => 'Urutan', 'type' => 'number', 'help' => 'Angka lebih kecil ditampilkan lebih dahulu.'])
                    </div>
                </div>
                @if ($resource === 'cards')
                    <div class="rounded border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600" role="note">Satu Card mewakili satu entri, misalnya satu slide, layanan, atau logo klien. Isi hanya kolom yang diperlukan.</div>
                @endif
            </div>
        </div>
        @endif
    </form>
    @if ($isContent)
        <livewire:cms.media-picker :owner-id="$this->getId()" :resource="$resource" :key="'cms-media-picker-'.$this->getId()" />
    @endif
</div>
