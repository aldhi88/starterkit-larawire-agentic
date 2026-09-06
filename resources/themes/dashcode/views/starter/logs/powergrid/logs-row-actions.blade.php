<div class="dashcode-row-action" x-data="{ open: false, top: 0, left: 0 }" @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window="open = false" @resize.window="open = false">
    <button type="button" class="dashcode-table-action" @click="
        open = !open;
        if(open) {
            const rect = $el.getBoundingClientRect();
            $nextTick(() => {
                const menu = $refs.menu;
                left = Math.max(8, Math.min(rect.left, window.innerWidth - menu.offsetWidth - 8));
                top = rect.bottom + menu.offsetHeight + 12 <= window.innerHeight
                    ? rect.bottom + 4
                    : Math.max(8, rect.top - menu.offsetHeight - 4);
            });
        }
    " aria-label="Buka menu aksi" aria-haspopup="menu" :aria-expanded="open.toString()">
        @include('starter.templates.layouts.icon', ['name' => 'dots-vertical'])
    </button>
    <template x-teleport="body">
        <ul x-ref="menu" class="dashcode-table-dropdown dashcode-row-dropdown" :class="{ 'show': open }" x-show="open" x-cloak :style="`position: fixed; top: ${top}px; left: ${left}px; z-index: 1060;`" @click.outside="open = false" role="menu">
        <li>
            <button type="button" class="dashcode-table-dropdown-item" wire:click="$dispatch('starter-log-detail-request', { actionId: @js($row->action_id) })" @click="open = false" role="menuitem">
                @include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'dashcode-table-dropdown-icon'])
                <span>Lihat Detail</span>
            </button>
        </li>
    </ul>
    </template>
</div>
