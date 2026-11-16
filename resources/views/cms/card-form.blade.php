@php
    $presets = \Aldhi88\StarterKit\Support\Cms\CmsCardPresets::all();
    $dash = \Aldhi88\StarterKit\Support\Starter\StarterTheme::key() === 'dashcode';
    $bodyClass = $dash ? 'card-body p-6 space-y-4' : 'card-body vstack gap-3';
    $presetPresentation = [
        'banner' => 'photo', 'service' => 'apps', 'product' => 'school', 'cta' => 'circle-check',
        'profile' => 'user-circle', 'partner' => 'users-group', 'greeting' => 'messages', 'testimonial' => 'messages',
        'statistic' => 'report-analytics', 'faq' => 'info-circle', 'step' => 'menu-2', 'timeline' => 'history',
        'event' => 'activity', 'award' => 'shield-check', 'facility' => 'building', 'contact' => 'world',
        'download' => 'file-text', 'video' => 'player-play', 'free' => 'layout-cards',
    ];
    $fields = [
        'title' => ['Judul yang ditampilkan', 'text', 'Judul untuk pengunjung website', 255],
        'subtitle' => ['Subjudul', 'text', 'Keterangan singkat di bawah judul', 255],
        'excerpt' => ['Ringkasan', 'textarea', 'Jelaskan secara singkat dan mudah dipahami', 2000],
    ];
    foreach (\Aldhi88\StarterKit\Support\Cms\CmsCardPresets::extraFields() as $key => $field) $fields['card_data.'.$key] = [...$field, 500];
    $fields['link_label'] = ['Teks tombol', 'text', 'Pelajari lebih lanjut', 100];
    $fields['link_url'] = ['Tautan tujuan', 'text', 'https://… atau /halaman/profil', 2000];
@endphp
<div class="cms-authoring-layout" :data-card-preset="preset" data-cms-card-form
    x-data="window.StarterCms.cardForm($wire, { presets: @js($presets), collections: @js(\Aldhi88\StarterKit\Support\Cms\CmsCardPresets::collectionLabels()), hasErrors: @js($errors->any()), autoName: @js($isCreating && blank($form['name'] ?? '')), media: @js(collect($mediaOptions)->keyBy('id')), componentId: @js($this->getId()) })"
    @cms-thumbnail-selected.window="acceptMedia($event.detail)">
    <div class="cms-authoring-main">
        <section class="card" aria-label="Jenis dan isi card">
            <div class="{{ $bodyClass }}">
                @include('starter-shared::cms.card-preset-picker')
                <div class="cms-card-mode" x-bind:hidden="!repeatable" role="group" aria-label="Bentuk Card">
                    <div class="cms-mode-switch">
                        <button type="button" class="btn btn-sm cms-mode-choice" :aria-pressed="(mode === 'single').toString()" @click="chooseMode('single')">Satu item</button>
                        <button type="button" class="btn btn-sm cms-mode-choice" :aria-pressed="(mode === 'collection').toString()" @click="chooseMode('collection')">Beberapa item</button>
                    </div>
                    <span class="form-hint" x-text="isCollection ? 'Kelola urutan ' + itemLabel + ' dalam satu Card.' : 'Satu Card untuk satu ' + itemLabel + '.'"></span>
                </div>
                @error('form.card_data.mode')<div class="text-danger" role="alert">{{ $message }}</div>@enderror
                <div class="cms-card-writing cms-authoring-stack" :data-preset="isCollection ? 'collection' : preset" data-starter-region="content-form">
                    <div class="cms-card-assets" x-bind:hidden="!(visible('image_id') || visible('thumbnail_id') || visible('logo_id') || @js($errors->has('form.image_id') || $errors->has('form.thumbnail_id') || $errors->has('form.logo_id')))" aria-label="Gambar &amp; Identitas Visual">
                        @foreach (['image_id' => 'Gambar utama', 'thumbnail_id' => 'Thumbnail / sampul', 'logo_id' => 'Logo / ikon'] as $field => $label)
                            <div x-bind:hidden="!(visible(@js($field)) || @js($errors->has('form.'.$field)))">
                                <div class="form-label" x-text="label(@js($field), @js($label))">{{ $label }}</div>
                                @include('starter-shared::cms.asset-field', [
                                    'assetField' => $field, 'assetId' => 'cms-'.$field, 'assetLabel' => $label,
                                    'assetValue' => "selected[mediaIds['".$field."']]",
                                    'assetChoose' => "chooseMedia('".$field."')",
                                    'assetRemove' => "mediaIds['".$field."'] = null",
                                ])
                            </div>
                        @endforeach
                    </div>
                    <div class="cms-authoring-stack">
                        @foreach (['title', 'subtitle'] as $field)
                            @include('starter-shared::cms.card-field')
                        @endforeach
                    </div>
                    <div class="cms-card-detail-grid">
                        @foreach (array_keys(\Aldhi88\StarterKit\Support\Cms\CmsCardPresets::extraFields()) as $extra)
                            @include('starter-shared::cms.card-field', ['field' => 'card_data.'.$extra])
                        @endforeach
                    </div>
                    @include('starter-shared::cms.card-field', ['field' => 'excerpt'])
                    <div class="cms-card-body-field" x-bind:hidden="!(visible('body') || @js($errors->has('form.body')))">
                        <div class="form-label" x-text="label('body', 'Isi lengkap')">Isi lengkap</div>
                        @include('starter.cms.editor')
                    </div>
                    <div class="cms-card-link-fields" x-bind:hidden="!(visible('link_label') || visible('link_url') || @js($errors->has('form.link_label') || $errors->has('form.link_url')))">
                        <div class="cms-card-detail-grid">
                            @foreach (['link_label', 'link_url'] as $field)
                                @include('starter-shared::cms.card-field')
                            @endforeach
                        </div>
                        <p class="form-hint">Isi tujuan tautan; teks tombol boleh disesuaikan dengan ajakan Anda.</p>
                    </div>
                </div>
                @include('starter-shared::cms.card-items')
                <details class="cms-editor-disclosure" @if ($errors->has('form.slug')) open @endif>
                    <summary>Opsi tambahan</summary>
                    <div class="cms-authoring-stack">
                        <label class="cms-inline-check"><input type="checkbox" class="{{ $dash ? 'table-checkbox' : 'form-check-input' }}" x-model="more"><span>Tampilkan semua field tambahan</span></label>
                        @include('starter.cms.fields.input', ['field' => 'slug', 'label' => 'Slug Card', 'maxlength' => 180, 'help' => 'Opsional untuk mapping developer, misalnya sambutan-rektor.'])
                    </div>
                </details>
            </div>
        </section>
    </div>
    <aside class="cms-authoring-sidebar">
        <section class="card" data-starter-region="publication-form">
            <div class="card-header"><h3 class="card-title">Publikasi</h3></div>
            <div class="{{ $bodyClass }}">
                @include('starter-shared::cms.publication-status')
                @include('starter-shared::cms.datetime-field', ['dateField' => 'published_at', 'dateLabel' => 'Waktu terbit (WIB)', 'dateHelp' => 'Tanggal mendatang menjadwalkan Card berstatus Terbit.'])
            </div>
        </section>
        <section class="card">
            <div class="card-header"><h3 class="card-title">Pengelompokan</h3></div>
            <div class="{{ $bodyClass }}">
                @if ($contentId)<div class="form-hint">ID Card: <code>{{ $contentId }}</code></div>@endif
                <div @input="autoName = false">@include('starter.cms.fields.input', ['field' => 'name', 'label' => 'Nama Card', 'required' => true, 'maxlength' => 255, 'help' => 'Nama untuk mengenali card di dashboard. Terisi dari judul saat membuat card baru.'])</div>
                @include('starter.cms.fields.input', ['field' => 'category_id', 'label' => 'Kategori Card', 'type' => 'select', 'choices' => $categoryOptions, 'help' => 'Opsional. Kelompokkan beberapa Card; satu Card juga bisa dipanggil langsung lewat ID atau slug.'])
                @include('starter.cms.fields.input', ['field' => 'position', 'label' => 'Urutan', 'type' => 'number', 'help' => 'Angka kecil tampil lebih dahulu.'])
            </div>
        </section>
    </aside>
</div>
