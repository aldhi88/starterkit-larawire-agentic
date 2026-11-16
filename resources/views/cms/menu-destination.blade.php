<div class="cms-menu-destination" x-data="window.StarterCms.menuDestination($wire, @js($node['key']))">
    <div>
        <label class="form-label" for="cms-target-type-{{ $node['key'] }}">Jenis tujuan</label>
        <select id="cms-target-type-{{ $node['key'] }}" class="{{ $selectClass }}" :value="type" @change="changeType($event.target.value)">
            <option value="custom">URL khusus</option>
            @foreach (\Aldhi88\StarterKit\Services\Cms\CmsMenuLinks::TYPES as $type => $label)
                <option value="{{ $type }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div x-show="type === 'custom'">
        @include('starter.cms.fields.input', ['field' => 'items.'.$index.'.url', 'label' => 'Tujuan Tautan', 'maxlength' => 2000, 'placeholder' => '/pendaftaran atau https://…'])
    </div>
    <div x-show="type !== 'custom'" x-cloak>
        <span class="form-label">Tujuan terpilih</span>
        <div class="cms-target-summary">
            <div>
                <strong x-text="item?.target_name || (item?.target_id ? 'Tujuan sudah tidak tersedia' : 'Belum dipilih')"></strong>
                <small x-show="item?.target_id && item?.target_name" x-text="item?.url"></small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" x-ref="trigger" :aria-expanded="open" aria-controls="cms-target-search-{{ $node['key'] }}" @click="open ? dismiss() : browse()" x-text="item?.target_id ? 'Ganti tujuan' : 'Pilih tujuan'"></button>
        </div>
        @error('form.items.'.$index.'.target_id')<div class="text-danger" role="alert">{{ $message }}</div>@enderror
    </div>
    <div id="cms-target-search-{{ $node['key'] }}" class="cms-target-search" x-show="open" x-cloak @keydown.escape.prevent.stop="dismiss()">
        <label class="form-label" for="cms-target-query-{{ $node['key'] }}">Cari tujuan</label>
        <input id="cms-target-query-{{ $node['key'] }}" class="form-control" type="search" maxlength="100" placeholder="Cari berdasarkan nama atau judul…" x-ref="query" @input="queue($event.target.value)" @keydown.enter.prevent>
        <div class="cms-target-results" :aria-busy="busy">
            <p class="form-hint" role="status" x-show="busy || message" x-text="busy ? 'Mencari…' : message"></p>
            <template x-for="option in options" :key="option.id">
                <button type="button" class="cms-target-option" @click="select(option)">
                    <strong x-text="option.name"></strong><small x-text="option.url"></small>
                </button>
            </template>
        </div>
    </div>
</div>
