@php [$label, $type, $placeholder, $limit] = $fields[$field]; @endphp
<div x-bind:hidden="!(visible(@js($field)) || @js($errors->has('form.'.$field)))" wire:key="card-field-{{ $field }}">
    @if ($type === 'datetime-local')
        @include('starter-shared::cms.datetime-field', ['dateField' => $field, 'dateId' => 'cms-card-'.str_replace('.', '-', $field), 'dateLabel' => $label])
    @else
    <label class="form-label" for="cms-card-{{ str_replace('.', '-', $field) }}" x-text="label(@js($field), @js($label))">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="cms-card-{{ str_replace('.', '-', $field) }}" class="form-control @error('form.'.$field) is-invalid @enderror" rows="3" wire:model.defer="form.{{ $field }}" maxlength="{{ $limit }}" placeholder="{{ $placeholder }}"></textarea>
    @else
        <input id="cms-card-{{ str_replace('.', '-', $field) }}" type="{{ $type }}" class="form-control @error('form.'.$field) is-invalid @enderror" wire:model.defer="form.{{ $field }}" maxlength="{{ $limit }}" placeholder="{{ $placeholder }}" @if ($field === 'title') @input="if (autoName) $wire.$set('form.name', $event.target.value, false)" @endif>
    @endif
    @error('form.'.$field)<div class="text-danger" role="alert">{{ $message }}</div>@enderror
    @endif
</div>
