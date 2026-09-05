<div class="flex justify-end p-4">
    <label class="relative flex-1 max-w-md">
        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-9 h-full border-r border-r-slate-200 flex items-center justify-center text-slate-500">
            @include('starter.templates.layouts.icon', ['name' => 'search'])
        </span>
        <input type="search" class="form-control !pl-12" placeholder="Cari log aktivitas..." wire:model.live.debounce.350ms="search">
    </label>
</div>
