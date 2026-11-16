@php
    $dateId = $dateId ?? 'cms-'.str_replace('.', '-', $dateField);
    $dateValue = data_get($form, $dateField);
@endphp
<div data-cms-publish-picker data-cms-date-field="{{ $dateModel ?? 'form.'.$dateField }}" data-cms-date-format="{{ $dateFormat ?? 'iso' }}" data-cms-date-label="{{ $dateLabel }}">
    <div class="cms-publication-time-label">
        <label class="form-label" for="{{ $dateId }}">{{ $dateLabel }}</label>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-cms-publish-now>Sekarang</button>
    </div>
    <div wire:ignore>
        <input id="{{ $dateId }}" type="text" class="form-control" value="{{ \Aldhi88\StarterKit\Support\Cms\CmsTime::forMaskedInput(is_string($dateValue) ? $dateValue : null) }}" placeholder="Pilih tanggal dan jam" autocomplete="off" data-cms-publish-input readonly aria-haspopup="dialog" aria-expanded="false" aria-describedby="{{ $dateId }}-help">
    </div>
    <div class="cms-datetime-help" id="{{ $dateId }}-help"><span class="form-hint">{{ $dateHelp ?? 'Waktu Indonesia Barat (WIB).' }}</span><button type="button" class="btn btn-sm btn-link" data-cms-date-clear aria-label="Kosongkan {{ $dateLabel }}">Kosongkan</button></div>
    @error('form.'.$dateField)<div class="{{ $dash ? 'text-danger-500' : 'invalid-feedback d-block' }}" role="alert">{{ $message }}</div>@enderror
</div>
