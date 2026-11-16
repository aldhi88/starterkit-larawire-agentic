@php
    [$itemLabel, $itemType, $itemPlaceholder, $itemLimit] = $definition;
    $itemKey = str_replace('card_data.', '', $itemField);
@endphp
<div @if ($itemType === 'textarea') class="cms-card-item-wide" @endif x-bind:hidden="!itemVisible(@js($itemField)) && !itemErrors['form.card_data.items.' + index + '.{{ $itemKey }}']">
    @if ($itemType === 'datetime-local')
        <div data-cms-publish-picker :data-cms-date-field="'form.card_data.items.' + index + '.{{ $itemKey }}'" data-cms-date-format="iso" data-cms-date-label="{{ $itemLabel }}">
            <div class="cms-publication-time-label"><label class="form-label" :for="'cms-item-' + item.key + '-{{ $itemKey }}'">{{ $itemLabel }}</label><button type="button" class="btn btn-sm btn-outline-secondary" data-cms-publish-now>Sekarang</button></div>
            <input :id="'cms-item-' + item.key + '-{{ $itemKey }}'" type="text" class="form-control" :value="window.StarterCms.maskDateTime(item.{{ $itemKey }})" placeholder="Pilih tanggal dan jam" autocomplete="off" data-cms-publish-input readonly aria-haspopup="dialog" aria-expanded="false">
            <div class="cms-datetime-help"><span class="form-hint">WIB · jam 24 jam</span><button type="button" class="btn btn-sm btn-link" data-cms-date-clear aria-label="Kosongkan {{ $itemLabel }}">Kosongkan</button></div>
        </div>
    @else
        <label class="form-label" :for="'cms-item-' + item.key + '-{{ $itemKey }}'" x-text="itemFieldLabel(@js($itemField), @js($itemLabel))">{{ $itemLabel }}</label>
        @if ($itemType === 'textarea')
            <textarea :id="'cms-item-' + item.key + '-{{ $itemKey }}'" class="form-control" rows="{{ $itemKey === 'body' ? 5 : 3 }}" x-model="item.{{ $itemKey }}" maxlength="{{ $itemLimit }}" placeholder="{{ $itemPlaceholder }}" :aria-invalid="Boolean(itemErrors['form.card_data.items.' + index + '.{{ $itemKey }}']).toString()"></textarea>
        @else
            <input :id="'cms-item-' + item.key + '-{{ $itemKey }}'" type="text" class="form-control" x-model="item.{{ $itemKey }}" maxlength="{{ $itemLimit }}" placeholder="{{ $itemPlaceholder }}" :aria-invalid="Boolean(itemErrors['form.card_data.items.' + index + '.{{ $itemKey }}']).toString()">
        @endif
    @endif
    <div class="text-danger" role="alert" x-text="itemErrors['form.card_data.items.' + index + '.{{ $itemKey }}']?.join(' ')"></div>
</div>
