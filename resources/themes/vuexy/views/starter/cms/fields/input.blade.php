@php
    $field = (string) $field;
    $type = $type ?? 'text';
    $fieldId = 'cms-'.str_replace('.', '-', $field);
    $required = (bool) ($required ?? false);
@endphp
<div>
    <label class="form-label" for="{{ $fieldId }}">{{ $label }}@if ($required) <span class="text-danger" aria-hidden="true">*</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $fieldId }}" class="form-control @error('form.'.$field) is-invalid @enderror" rows="{{ $rows ?? 4 }}" wire:model.defer="form.{{ $field }}" @if ($required) required @endif @if (isset($maxlength)) maxlength="{{ $maxlength }}" @endif></textarea>
    @elseif ($type === 'select')
        <select @if ($previewMedia ?? false) x-on:change="selected = $event.target.value" @endif id="{{ $fieldId }}" class="form-select @error('form.'.$field) is-invalid @enderror" wire:model.defer="form.{{ $field }}" @if ($required) required @endif>
            @if (! ($noEmpty ?? false))<option value="">{{ $emptyLabel ?? 'Pilih salah satu' }}</option>@endif
            @foreach ($choices ?? [] as $choice)
                <option value="{{ $choice['id'] }}">{{ $choice['name'] }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $fieldId }}" type="{{ $type }}" class="form-control @error('form.'.$field) is-invalid @enderror" wire:model.defer="form.{{ $field }}" @if ($required) required @endif @if (isset($maxlength)) maxlength="{{ $maxlength }}" @endif @if ($type === 'number') min="0" step="1" @endif>
    @endif
    @if (filled($help ?? null))<div class="form-hint mt-1">{{ $help }}</div>@endif
    @error('form.'.$field)<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
</div>
