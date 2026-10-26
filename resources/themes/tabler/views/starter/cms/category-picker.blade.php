<div data-cms-category-selection wire:key="{{ $pickerId }}-{{ md5(json_encode($categories)) }}" x-data="window.StarterCms.categoryPicker($wire.entangle(@js($field)))">
    @if (count($categories))
        <label class="visually-hidden" for="{{ $pickerId }}-search">Cari kategori</label>
        <input id="{{ $pickerId }}-search" type="search" class="form-control form-control-sm mb-2" placeholder="Cari kategori…" x-model.debounce.150ms="search" autocomplete="off">
        @php
            $branches = [];
            foreach ($categories as $item) {
                if ($item['depth'] === 0 || empty($branches)) {
                    $branches[] = [];
                }
                $branches[array_key_last($branches)][] = $item;
            }
        @endphp
        <div @class(['cms-category-tree', 'cms-category-tree-columns' => $twoColumns ?? false]) role="group" aria-label="Kategori postingan">
            @foreach ($branches as $branch)
                <div @class(['cms-category-branch', 'border rounded p-1' => $twoColumns ?? false]) x-show="visible.includes({{ $branch[0]['id'] }})" x-cloak>
                    @foreach ($branch as $category)
                        @php $childCount = count(array_filter($categories, fn ($item) => ($item['parent_id'] ?? null) === $category['id'])); @endphp
                        <div @class(['cms-category-tree-row', 'cms-category-tree-row--branch' => $childCount]) @if ($childCount) @click="if (!search.trim()) toggle({{ $category['id'] }})" @endif data-cms-category-node="{{ $category['id'] }}" x-show="visible.includes({{ $category['id'] }})" x-cloak style="--cms-category-depth: {{ $category['depth'] }}">
                            @if ($childCount)
                                <button type="button" class="btn btn-sm btn-ghost-secondary cms-category-tree-toggle" aria-label="Buka atau tutup subkategori {{ $category['name'] }}" :aria-expanded="(search.trim() !== '' || expanded.includes({{ $category['id'] }})).toString()" :disabled="search.trim() !== ''" @click.stop="toggle({{ $category['id'] }})">
                                    @include('starter.templates.layouts.icon', ['name' => 'chevron-right', 'class' => 'icon m-0'])
                                </button>
                            @else
                                <span class="cms-category-tree-toggle" aria-hidden="true"></span>
                            @endif
                            <div class="cms-category-tree-label">
                                <input type="checkbox" class="form-check-input m-0" @click.stop value="{{ $category['id'] }}" data-parent="{{ $category['parent_id'] ?? 0 }}" data-name="{{ $category['name'] }}" aria-label="{{ $category['path'] }}" :checked="selected.map(Number).includes({{ $category['id'] }})" @change="choose($el.closest('[data-cms-category-selection]'), {{ $category['id'] }}, $event.target.checked)">
                                <span @class(['fw-semibold' => $childCount])>{{ $category['name'] }}</span>
                                @if ($childCount)<span class="text-secondary small ms-auto" title="{{ $childCount }} subkategori">{{ $childCount }}</span>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <div class="text-secondary small p-3" x-show="visible.length === 0" x-cloak>Tidak ada kategori yang cocok.</div>
        </div>
        <div class="text-secondary small mt-2"><span x-text="selected.length">0</span> dipilih · Centang induk untuk seluruh cabang.</div>
    @else
        <p class="text-secondary small mb-0">Belum ada kategori aktif.</p>
    @endif
</div>
