@php
    $theme = \Aldhi88\StarterKit\Support\Starter\StarterTheme::key();
    $dash = $theme === 'dashcode';
    $bodyClass = $dash ? 'card-body p-6 cms-authoring-stack' : 'card-body cms-authoring-stack';
@endphp
<div class="cms-authoring-layout" data-cms-post-form @if ($theme === 'tabler') data-cms-tabler-post-form @endif>
    <div class="cms-authoring-main">
        <section class="card" data-starter-region="content-form" aria-label="Isi {{ $cmsResource->singular() }}">
            <div class="{{ $bodyClass }}">
                <div class="cms-authoring-stack" data-cms-post-slug x-data="{ autoSlug: @js($isCreating && blank($form['slug'] ?? '')) }">
                    <div>
                        <label class="form-label" for="cms-title">Judul {{ $cmsResource->singular() }} <span class="{{ $dash ? 'text-danger-500' : 'text-danger' }}" aria-hidden="true">*</span></label>
                        <input id="cms-title" type="text" class="form-control cms-editor-title @error('form.title') is-invalid @enderror" wire:model.defer="form.title" data-cms-slug-source maxlength="255" required placeholder="{{ $resource === 'pages' ? 'Contoh: Visi dan Misi' : 'Tulis judul konten' }}" @input="if (autoSlug) $wire.$set('form.slug', window.StarterCms.slug($event.target.value), false)">
                        @error('form.title')<div class="{{ $dash ? 'text-danger-500' : 'invalid-feedback d-block' }}" role="alert">{{ $message }}</div>@enderror
                    </div>
                    <details class="cms-editor-disclosure cms-slug-disclosure" @if ($errors->has('form.slug')) open @endif>
                        <summary>Alamat {{ strtolower($cmsResource->singular()) }} <span class="form-hint" x-text="$wire.form.slug || 'Dibuat otomatis dari judul'"></span></summary>
                        <div>
                            <label class="form-label" for="cms-slug">Alamat khusus (opsional)</label>
                            <input id="cms-slug" type="text" class="form-control @error('form.slug') is-invalid @enderror" wire:model.defer="form.slug" data-cms-slug-target maxlength="180" @input="autoSlug = false" placeholder="{{ $resource === 'pages' ? 'visi-dan-misi' : 'judul-konten' }}">
                            <div class="form-hint">Otomatis dari judul. Ubah jika memerlukan alamat khusus.</div>
                            @include('starter-shared::cms.permalink', ['permalink' => $savedPermalink])
                            @error('form.slug')<div class="{{ $dash ? 'text-danger-500' : 'invalid-feedback d-block' }}" role="alert">{{ $message }}</div>@enderror
                        </div>
                    </details>
                </div>
                @include('starter.cms.editor')
            </div>
        </section>
        <section class="card" data-starter-region="post-details-form">
            <details class="cms-editor-disclosure {{ $bodyClass }}" @if (filled($form['subtitle'] ?? null) || filled($form['excerpt'] ?? null) || $errors->has('form.subtitle') || $errors->has('form.excerpt')) open @endif>
                <summary>Ringkasan &amp; subjudul <span class="form-hint">Opsional</span></summary>
                <div class="cms-authoring-stack">
                    @include('starter.cms.fields.input', ['field' => 'subtitle', 'label' => 'Subjudul', 'maxlength' => 255, 'help' => 'Kalimat pendek untuk melengkapi judul.'])
                    @include('starter.cms.fields.input', ['field' => 'excerpt', 'label' => 'Ringkasan', 'type' => 'textarea', 'rows' => 3, 'maxlength' => 2000, 'help' => 'Jika kosong, ringkasan diambil dari awal isi konten.'])
                </div>
            </details>
        </section>
    </div>
    <aside class="cms-authoring-sidebar">
        <section class="card" data-starter-region="publication-form">
            <div class="card-header"><h3 class="card-title">Publikasi</h3></div>
            <div class="{{ $bodyClass }}">
                @include('starter-shared::cms.publication-status')
                @if ($theme === 'tabler')
                    @include('starter.cms.publication-time')
                @else
                    @include('starter-shared::cms.datetime-field', ['dateField' => 'published_at', 'dateLabel' => 'Waktu Terbit (WIB)', 'dateHelp' => 'Tanggal mendatang menjadwalkan publikasi.'])
                @endif
            </div>
        </section>
        <section class="card" data-starter-region="thumbnail-form" x-data="{ thumbnail: @js(collect($mediaOptions)->firstWhere('id', (int) ($form['thumbnail_id'] ?? 0))) }" @cms-thumbnail-selected.window="if ($event.detail.componentId === @js($this->getId())) { thumbnail = $event.detail.media; $wire.$set('form.thumbnail_id', thumbnail.id, false) }">
            <div class="card-header"><h3 class="card-title">Thumbnail</h3></div>
            <div class="{{ $bodyClass }}">
                @include('starter-shared::cms.asset-field', [
                    'assetField' => 'thumbnail_id', 'assetId' => 'cms-post-thumbnail', 'assetLabel' => 'Thumbnail', 'assetValue' => 'thumbnail',
                    'assetChoose' => '$dispatch(\'cms-open-media-picker\', { componentId: '.json_encode($this->getId()).", type: 'image', purpose: 'thumbnail' })",
                    'assetRemove' => 'thumbnail = null; $wire.$set(\'form.thumbnail_id\', null, false)',
                ])
            </div>
        </section>
        <section class="card" data-starter-region="{{ $resource === 'posts' ? 'post-categories-form' : 'page-category-form' }}">
            <div class="card-header"><h3 class="card-title">Kategori</h3></div>
            <div class="{{ $bodyClass }}">
                @if ($resource === 'pages')
                    @include('starter.cms.page-category-field')
                @elseif ($theme === 'tabler')
                    @include('starter.cms.post-category-field')
                @else
                    @include('starter.cms.fields.input', ['field' => 'category_id', 'label' => 'Kategori Konten', 'type' => 'select', 'choices' => $categoryOptions, 'emptyLabel' => 'Tanpa kategori'])
                @endif
            </div>
        </section>
    </aside>
</div>
