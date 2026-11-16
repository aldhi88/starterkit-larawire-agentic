{{-- Asset state/actions are supplied by the owning Alpine component. No extra Livewire request. --}}
<div class="cms-asset-field">
    <button type="button" @if (isset($assetDynamicId)) :id="{{ $assetDynamicId }}" @else id="{{ $assetId }}" @endif class="btn btn-outline-secondary cms-asset-pick" @click="{{ $assetChoose }}" :aria-label="({{ $assetValue }}) ? 'Ganti {{ $assetLabel }}' : 'Pilih {{ $assetLabel }}'">
        <template x-if="{{ $assetValue }}?.url"><img :src="{{ $assetValue }}.url" :alt="{{ $assetValue }}.alt_text || {{ $assetValue }}.name" class="cms-asset-image" loading="lazy"></template>
        <span class="cms-asset-empty" x-bind:hidden="Boolean({{ $assetValue }}?.url)">
            @include('starter.templates.layouts.icon', ['name' => 'photo', 'class' => 'icon'])
            <span>Pilih {{ $assetLabel }}</span><small>Pustaka media atau unggah baru</small>
        </span>
        <span class="cms-asset-replace" x-bind:hidden="!({{ $assetValue }}?.url)">Ganti gambar</span>
    </button>
    <div class="cms-asset-caption" x-bind:hidden="!({{ $assetValue }}?.url)">
        <span x-text="{{ $assetValue }}?.name"></span>
        <button type="button" class="btn btn-sm btn-outline-danger" @click="{{ $assetRemove }}" aria-label="Lepas {{ $assetLabel }}">Lepas</button>
    </div>
    @error('form.'.$assetField)<div class="text-danger" role="alert">{{ $message }}</div>@enderror
</div>
