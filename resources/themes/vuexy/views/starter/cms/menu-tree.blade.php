@foreach ($nodes as $node)
    @php($index = $node['index'])
    <li class="cms-menu-item" data-cms-menu-item data-key="{{ $node['key'] }}" wire:key="cms-menu-item-{{ $node['key'] }}" x-data="{ expanded: @js(blank($node['label'] ?? '')) }">
        <div class="border rounded p-3">
            <div class="cms-menu-item-header">
                <button type="button" class="btn btn-sm cms-menu-drag-handle" data-cms-menu-handle aria-label="Tarik untuk memindahkan menu">@include('starter.templates.layouts.icon', ['name' => 'dots-vertical', 'class' => 'icon-sm'])</button>
                <strong class="cms-menu-item-title" title="{{ $node['label'] ?? 'Item baru' }}" x-bind:title="$wire.form.items.find(item => item.key === @js($node['key']))?.label || 'Item baru'" x-text="$wire.form.items.find(item => item.key === @js($node['key']))?.label || 'Item baru'">{{ filled($node['label'] ?? '') ? $node['label'] : 'Item baru' }}</strong>
                <button type="button" class="btn btn-sm" @click="expanded = ! expanded" :aria-expanded="expanded" aria-controls="cms-menu-details-{{ $node['key'] }}">Detail</button>
            </div>
            <div class="cms-menu-item-content vstack gap-3" id="cms-menu-details-{{ $node['key'] }}" x-show="expanded" x-cloak>
                <div class="row g-3">
                    <div class="col-12 col-md-6">@include('starter.cms.fields.input', ['field' => 'items.'.$index.'.label', 'label' => 'Label Menu', 'required' => true, 'maxlength' => 255])</div>
                    <div class="col-12 col-md-6">@include('starter.cms.fields.input', ['field' => 'items.'.$index.'.url', 'label' => 'Tujuan Tautan', 'required' => true, 'maxlength' => 2000])</div>
                </div>
                <div>
                    <label class="form-label" for="cms-menu-parent-{{ $node['key'] }}">Menu Induk</label>
                    <select id="cms-menu-parent-{{ $node['key'] }}" class="form-select" wire:change="changeMenuParent({{ $index }}, $event.target.value)">
                        <option value="" @selected(blank($node['parent_key'] ?? ''))>Tanpa induk (tingkat utama)</option>
                        @if ($node['invalid_parent'])<option value="{{ $node['parent_key'] }}" selected disabled>Induk tidak valid: {{ $node['invalid_parent_label'] }}</option>@endif
                        @foreach ($node['parent_options'] as $parent)
                            <option value="{{ $parent['key'] }}" @selected(($node['parent_key'] ?? '') === $parent['key'])>{{ filled($parent['label']) ? $parent['label'] : 'Item tanpa label' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="btn-list">
                    <button type="button" class="btn btn-sm" wire:click="moveMenuSibling({{ $index }}, -1)" aria-label="Pindahkan menu ke atas">Atas</button>
                    <button type="button" class="btn btn-sm" wire:click="moveMenuSibling({{ $index }}, 1)" aria-label="Pindahkan menu ke bawah">Bawah</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeMenuItem({{ $index }})">Lepas</button>
                </div>
            </div>
        </div>
        <ul class="cms-menu-children" data-cms-menu-list data-parent-key="{{ $node['key'] }}" aria-label="Submenu {{ $node['label'] ?? '' }}">
            @include('starter.cms.menu-tree', ['nodes' => $node['children']])
        </ul>
    </li>
@endforeach
