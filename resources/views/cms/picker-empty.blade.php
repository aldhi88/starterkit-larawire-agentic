@php
    $hasPickerFilter = $pickerSearch !== '' || ($purpose !== 'thumbnail' && $pickerType !== 'all');
    $emptyHeading = $pickerSearch !== ''
        ? 'Media tidak ditemukan'
        : match ($pickerType) {
            'image' => 'Belum ada gambar',
            'pdf' => 'Belum ada PDF',
            default => 'Pustaka media masih kosong',
        };
@endphp
<div class="cms-picker-empty" role="status">
    <span class="cms-picker-empty-icon" aria-hidden="true">
        @include('starter.templates.layouts.icon', ['name' => $pickerSearch !== '' ? 'search' : 'photo', 'class' => 'cms-picker-empty-icon-svg'])
    </span>
    <h4 class="cms-picker-empty-title">{{ $emptyHeading }}</h4>
    <p class="cms-picker-empty-description">
        @if ($pickerSearch !== '')
            Periksa kata pencarian atau tampilkan semua media.
        @elseif ($hasPickerFilter)
            Unggah file di atas atau tampilkan semua media.
        @else
            Pilih file di atas untuk mulai mengisi pustaka media.
        @endif
    </p>
    @if ($hasPickerFilter)
        <button type="button" class="{{ $resetClass }}" wire:click="resetPickerView">Tampilkan semua media</button>
    @endif
</div>
