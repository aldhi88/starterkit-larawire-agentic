<div class="cms-preset-picker" @keydown.escape.stop="if (choosing) closeChooser()">
    <div class="cms-preset-summary">
        <span class="{{ $dash ? 'text-primary-500 bg-primary-100 dark:bg-slate-700 rounded' : (\Aldhi88\StarterKit\Support\Starter\StarterTheme::key() === 'vuexy' ? 'text-primary bg-label-primary rounded' : 'text-primary bg-primary-lt rounded') }} cms-preset-icon" aria-hidden="true">
            @foreach ($presetPresentation as $key => $icon)
                <span x-bind:hidden="preset !== @js($key)">@include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'icon'])</span>
            @endforeach
        </span>
        <div class="cms-preset-description"><span class="form-hint">Jenis Card</span><strong x-text="presets[preset]?.label"></strong><p class="form-hint" x-text="presets[preset]?.help"></p></div>
        <button type="button" id="cms-card-preset" x-ref="presetTrigger" x-bind:hidden="choosing" class="btn btn-sm btn-outline-primary" :aria-expanded="choosing.toString()" aria-controls="cms-preset-catalog" @click="choosing = !choosing; if (choosing) { search = ''; $nextTick(() => { $refs.presetSearch.focus(); $refs.presetOptions.scrollTop = 0 }) }">Ganti jenis</button>
    </div>
    @error('form.card_preset')<div class="text-danger" role="alert">{{ $message }}</div>@enderror
    <div id="cms-preset-catalog" class="cms-preset-catalog" x-bind:hidden="!choosing" x-cloak>
        <div class="cms-preset-search">
            <label class="cms-editor-accessible-label" for="cms-preset-search">Cari jenis Card</label>
            <input id="cms-preset-search" x-ref="presetSearch" type="search" class="form-control" x-model="search" @input="$refs.presetOptions.scrollTop = 0" placeholder="Cari jenis, misalnya profil atau agenda…" @keydown.enter.prevent autocomplete="off">
            <button type="button" class="btn cms-quiet-action" @click="closeChooser()">@include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon']) Tutup</button>
        </div>
        <div class="cms-preset-grid" x-ref="presetOptions">
            @foreach ($presetPresentation as $key => $icon)
                <button type="button" data-cms-preset-option="{{ $key }}" class="cms-preset-option" :aria-pressed="(preset === @js($key)).toString()" x-bind:hidden="!matches(@js($key))" @click="preset = @js($key); closeChooser()">
                    @include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'icon'])
                    <span><strong>{{ $presets[$key]['label'] }}</strong><small>{{ $presets[$key]['help'] }}</small></span>
                    <span class="cms-preset-check" x-bind:hidden="preset !== @js($key)" aria-hidden="true">@include('starter.templates.layouts.icon', ['name' => 'circle-check', 'class' => 'icon'])</span>
                </button>
            @endforeach
        </div>
        <p class="form-hint cms-preset-no-results" role="status" x-bind:hidden="Object.keys(presets).some(key => matches(key))">Jenis Card tidak ditemukan. Coba kata lain.</p>
        <p class="form-hint">Isian sebelumnya tetap tersimpan saat Anda mengganti jenis.</p>
    </div>
</div>
