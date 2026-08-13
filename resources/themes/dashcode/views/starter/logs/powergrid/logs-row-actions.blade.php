<div class="dashcode-row-action" x-data="{ open: false, top: 0, left: 0 }" @click.outside="open = false" @scroll.window="open = false" @resize.window="open = false">
    <button type="button" class="dashcode-table-action" @click="
        open = !open;
        if(open) {
            let rect = $el.getBoundingClientRect();
            top = rect.bottom + 4;
            left = Math.min(rect.left, window.innerWidth - 152);
        }
    " aria-label="Aksi">
        @include('starter.templates.layouts.icon', ['name' => 'dots-vertical'])
    </button>
    <template x-teleport="body">
        <ul class="dashcode-table-dropdown" :class="{ 'show': open }" x-show="open" x-cloak :style="`position: fixed; top: ${top}px; left: ${left}px; z-index: 1060;`" @click.outside="open = false">
        <li>
            <button type="button" class="dashcode-table-dropdown-item" wire:click="$dispatch('starter-log-detail-request', { actionId: @js($row->action_id) })">Lihat Detail</button>
        </li>
    </ul>
    </template>
</div>
