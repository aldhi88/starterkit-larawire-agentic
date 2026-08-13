<div class="dashcode-pg-toolbar dashcode-pg-toolbar-search-only">
    <label class="dashcode-pg-search-field">
        <span class="dashcode-pg-search-icon">
            @include('starter.templates.layouts.icon', ['name' => 'search'])
        </span>
        <input type="search" class="starter-pg-control" placeholder="Cari log aktivitas..." wire:model.live.debounce.350ms="search">
    </label>
</div>
