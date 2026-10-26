<div data-starter-dialog-component>
    @if ($mediaPickerOpen)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="cms-picker-title-{{ $ownerId }}" wire:click.self="closeMediaPicker" data-cms-picker data-cms-picker-owner="{{ $ownerId }}">
            <div class="modal-dialog modal-dialog-scrollable cms-picker-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title" id="cms-picker-title-{{ $ownerId }}">{{ $purpose === 'thumbnail' ? 'Pilih Thumbnail' : 'Tambahkan Media' }}</h3>
                            <p class="text-secondary small mb-0 mt-1">{{ $purpose === 'thumbnail' ? 'Pilih satu gambar untuk sampul postingan.' : 'Pilih gambar atau PDF, lalu sisipkan semua media yang dipilih.' }}</p>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeMediaPicker" aria-label="Tutup pemilih media"></button>
                    </div>
                    <div class="modal-body vstack gap-4">
                        <div class="cms-media-toolbar">
                            <div class="cms-media-search">
                                <label class="form-label" for="cms-picker-search-{{ $ownerId }}">Cari Media</label>
                                <input id="cms-picker-search-{{ $ownerId }}" type="search" class="form-control" wire:model.live.debounce.350ms="pickerSearch" maxlength="100" placeholder="Cari nama…">
                            </div>
                            <div>
                                <label class="form-label" for="cms-picker-type-{{ $ownerId }}">Jenis File</label>
                                <select id="cms-picker-type-{{ $ownerId }}" class="form-select" wire:model.live="pickerType">
                                    @if ($purpose !== 'thumbnail')<option value="all">Semua</option>@endif
                                    <option value="image">Gambar</option>
                                    @if ($purpose !== 'thumbnail')<option value="pdf">PDF</option>@endif
                                </select>
                            </div>
                        </div>
                        <div class="border rounded p-3 vstack gap-2" x-data="{ progress: 0, filenames: 'Belum ada file dipilih' }" x-on:livewire-upload-progress="progress = $event.detail.progress">
                            <div class="form-label cms-picker-upload-heading">Unggah ke Pustaka Media</div>
                            <div class="cms-picker-upload-controls">
                                <input id="cms-editor-uploads-{{ $ownerId }}" type="file" multiple class="cms-picker-file-input" wire:model="editorUploads" aria-label="Pilih file media untuk diunggah" x-on:change="filenames = Array.from($event.target.files).map(file => file.name).join(', ') || 'Belum ada file dipilih'" accept="{{ $purpose === 'thumbnail' ? 'image/jpeg,image/png,image/webp,image/gif' : 'image/jpeg,image/png,image/webp,image/gif,application/pdf' }}">
                                <label for="cms-editor-uploads-{{ $ownerId }}" class="btn btn-outline-secondary">Pilih File</label>
                                <span class="text-secondary small cms-picker-file-names" x-text="filenames">Belum ada file dipilih</span>
                            </div>
                            <div class="text-secondary small">Maksimal 20 file, 10 MB per file. File tersimpan di pustaka media.</div>
                            <div wire:loading wire:target="editorUploads" class="text-secondary small" role="status">Mengunggah <span x-text="progress">0</span>%…</div>
                            @error('editorUploads')<div class="alert alert-danger mb-0" role="alert">{{ $message }}</div>@enderror
                            @foreach ($editorUploadErrors as $editorUploadError)<div class="alert alert-danger mb-0" role="alert">{{ $editorUploadError['name'] }}: {{ $editorUploadError['message'] }}</div>@endforeach
                            @if (count($editorUploads))<div><button type="button" class="btn btn-outline-secondary" wire:click="finishEditorUploads" wire:loading.attr="disabled" wire:target="editorUploads,finishEditorUploads">Unggah {{ count($editorUploads) }} File</button></div>@endif
                        </div>
                        @error('selectedMediaIds')<div class="alert alert-danger mb-0" role="alert">{{ $message }}</div>@enderror
                        <div class="cms-picker-grid" role="group" aria-label="{{ $purpose === 'thumbnail' ? 'Pilih satu gambar thumbnail' : 'Pilih media untuk disisipkan' }}">
                            @forelse ($pickerItems as $pickerMedia)
                                <label class="card p-2 cms-picker-item mb-0" wire:key="picker-media-{{ $pickerMedia['id'] }}">
                                    <span class="d-flex align-items-center gap-2 mb-2">
                                        @if ($purpose === 'thumbnail')
                                            <input type="radio" name="cms-thumbnail-choice-{{ $ownerId }}" class="form-check-input m-0" wire:model.defer="selectedMediaIds.0" value="{{ $pickerMedia['id'] }}" aria-label="Pilih {{ $pickerMedia['name'] }}">
                                        @else
                                            <input type="checkbox" class="form-check-input m-0" wire:model.defer="selectedMediaIds" value="{{ $pickerMedia['id'] }}" aria-label="Pilih {{ $pickerMedia['name'] }}">
                                        @endif
                                        <span class="cms-picker-item-title small fw-semibold">{{ $pickerMedia['name'] }}</span>
                                    </span>
                                    @if (str_starts_with($pickerMedia['mime_type'], 'image/'))
                                        <img src="{{ $pickerMedia['url'] }}" alt="{{ $pickerMedia['alt_text'] ?: $pickerMedia['name'] }}" class="rounded" loading="lazy">
                                    @else
                                        <span class="cms-picker-preview">@include('starter.templates.layouts.icon', ['name' => 'file-text'])<span>PDF</span></span>
                                    @endif
                                </label>
                            @empty
                                @include('starter-shared::cms.picker-empty', ['resetClass' => 'btn btn-outline-secondary'])
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100 d-flex flex-wrap align-items-center justify-content-between gap-3">
                            @if ($pickerLastPage > 1)
                                <div class="d-flex flex-wrap align-items-center gap-2 cms-picker-pagination">
                                    <span class="text-secondary small">Halaman {{ $pickerPage }} dari {{ $pickerLastPage }}</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="changePickerPage({{ $pickerPage - 1 }})" @disabled($pickerPage <= 1) aria-label="Halaman media sebelumnya">Sebelumnya</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="changePickerPage({{ $pickerPage + 1 }})" @disabled($pickerPage >= $pickerLastPage) aria-label="Halaman media berikutnya">Berikutnya</button>
                                </div>
                            @endif
                            <div class="d-flex flex-wrap align-items-center gap-2 cms-picker-footer-actions">
                                <span class="text-secondary small cms-picker-selection-count" x-text="$wire.selectedMediaIds.filter(Boolean).length + ' dipilih'">0 dipilih</span>
                                <button type="button" class="btn btn-outline-secondary cms-picker-cancel" wire:click="closeMediaPicker">Batal</button>
                                <button type="button" class="btn btn-primary cms-picker-submit" wire:click="insertSelectedMedia" wire:loading.attr="disabled" wire:target="insertSelectedMedia,editorUploads,finishEditorUploads" x-bind:disabled="$wire.selectedMediaIds.filter(Boolean).length === 0">{{ $purpose === 'thumbnail' ? 'Gunakan Thumbnail' : 'Sisipkan Media Terpilih' }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
