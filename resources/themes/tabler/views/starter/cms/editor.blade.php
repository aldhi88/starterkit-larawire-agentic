<div class="cms-editor" data-cms-editor data-cms-media-base="{{ route('starter.cms.media', ['publicId' => str_repeat('0', 26)]) }}" wire:ignore>
    @if ($resource === 'posts')
        <span class="cms-editor-accessible-label" id="cms-body-label-{{ $this->getId() }}">Isi konten</span>
        <template data-cms-toolbar-media>
            <button type="button" class="ql-cms-media" data-cms-open-media aria-label="Sisipkan media" title="Sisipkan media dari pustaka">@include('starter-shared::cms.media-plus-icon')</button>
            <button type="button" class="ql-cms-expand" data-cms-editor-expand aria-label="Perbesar editor" title="Perbesar editor" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3H3v6M15 3h6v6M21 15v6h-6M3 15v6h6"/></svg></button>
        </template>
    @else
        <div class="cms-editor-heading d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <span class="cms-editor-accessible-label" id="cms-body-label-{{ $this->getId() }}">Isi konten</span>
            <div class="cms-editor-actions"><button type="button" class="btn cms-editor-media-button" data-cms-open-media>@include('starter-shared::cms.media-plus-icon')<span>Tambahkan Media</span></button></div>
        </div>
    @endif
    <textarea class="cms-editor-initial" data-cms-editor-initial aria-hidden="true" tabindex="-1">{{ $form['body'] ?? '' }}</textarea>
    <div class="cms-editor-canvas" data-cms-editor-canvas>
        <div data-cms-editor-surface aria-labelledby="cms-body-label-{{ $this->getId() }}"></div>
        <div class="cms-image-resize-outline" data-cms-image-overlay hidden>
            <button type="button" class="btn btn-primary cms-image-resize-handle" data-cms-image-handle aria-label="Ubah ukuran gambar dengan menarik sudut atau tombol panah" title="Tarik sudut untuk mengubah ukuran gambar. Tombol panah mengubah 10 px, Shift + panah 1 px.">↘</button>
        </div>
    </div>
    <div class="cms-image-controls card border p-3 mt-2" data-cms-image-controls hidden role="group" aria-label="Ukuran gambar terpilih">
        <div class="cms-image-controls-heading"><strong>Ukuran Gambar</strong><button type="button" class="btn btn-sm" data-cms-image-done>Selesai</button></div>
        <div class="cms-image-width-field">
            <div><label class="form-label" for="cms-image-width-{{ $this->getId() }}">Lebar (px)</label><input id="cms-image-width-{{ $this->getId() }}" type="number" class="form-control" min="1" max="10000" step="1" inputmode="numeric" data-cms-image-width></div>
            <button type="button" class="btn btn-primary btn-sm" data-cms-image-apply>Terapkan</button>
        </div>
        <div class="cms-image-presets" role="group" aria-label="Lebar gambar terhadap area konten">
            <button type="button" class="btn btn-sm" data-cms-image-percent="25">25%</button><button type="button" class="btn btn-sm" data-cms-image-percent="50">50%</button><button type="button" class="btn btn-sm" data-cms-image-percent="75">75%</button><button type="button" class="btn btn-sm" data-cms-image-percent="100">100%</button><button type="button" class="btn btn-sm" data-cms-image-original>Ukuran asli</button>
        </div>
        <div class="cms-image-size-readout" data-cms-image-size role="status" aria-live="polite"></div>
    </div>
    <div class="cms-editor-status text-secondary" data-cms-editor-status role="status">Tulis isi konten di sini. Pilih gambar dalam konten untuk mengatur ukurannya.</div>
</div>
@error('form.body')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
@error('editorUploads')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
@foreach ($editorUploadErrors as $editorUploadError)<div class="alert alert-danger" role="alert">{{ $editorUploadError['name'] }}: {{ $editorUploadError['message'] }}</div>@endforeach
