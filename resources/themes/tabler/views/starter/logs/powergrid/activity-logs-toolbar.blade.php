<style>
    .dt--top-section { display: none !important; }
</style>
<div class="card-body border-bottom-0 pt-3 pb-3">
    <div class="row g-3 align-items-center justify-content-end">
        <div class="col-auto">
            <div class="input-icon" style="min-width: 250px;">
                <span class="input-icon-addon">
                    @include('starter.templates.layouts.icon', ['name' => 'search', 'class' => 'icon-sm'])
                </span>
                <input type="search" class="form-control" placeholder="Cari log aktivitas..." wire:model.live.debounce.350ms="search">
            </div>
        </div>
    </div>
</div>
