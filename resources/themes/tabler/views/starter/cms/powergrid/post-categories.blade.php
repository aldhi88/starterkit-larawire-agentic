<button type="button" class="bg-transparent border-0 p-0 text-start text-secondary d-inline-flex align-items-center gap-2" wire:click="preparePostAction('categories', {{ $row->id }})" aria-label="Ubah kategori: {{ $row->title }}" x-data="{ active: false }" @mouseenter="active = true" @mouseleave="active = false" @focus="active = true" @blur="active = false">
    <span @class(['cms-editable-category', 'small', 'text-body' => $label !== 'Tanpa kategori'])>{{ $label }}</span>
    <span :class="active ? 'opacity-75' : 'opacity-0'" aria-hidden="true">@include('starter.templates.layouts.icon', ['name' => 'pencil', 'size' => 14, 'class' => 'icon-sm m-0'])</span>
</button>
