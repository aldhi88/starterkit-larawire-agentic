<div x-data="{ open: false, top: 0, left: 0 }" @click.outside="open = false" @scroll.window="open = false" @resize.window="open = false">
    <button type="button" class="btn btn-action border-0" @click="
        open = !open;
        if(open) {
            let rect = $el.getBoundingClientRect();
            top = rect.bottom + 4;
            left = rect.left;
        }
    " aria-label="Aksi">
        @include('starter.templates.layouts.icon', ['name' => 'dots-vertical', 'class' => 'icon'])
    </button>
    <template x-teleport="body">
        <ul class="dropdown-menu" :class="{ 'show': open }" x-show="open" :style="`position: fixed; top: ${top}px; left: ${left}px; z-index: 1060; margin: 0; min-width: 140px;`" @click.outside="open = false">
        <li>
            <button type="button" class="dropdown-item" wire:click="$dispatch('starter-log-detail-request', { actionId: @js($row->action_id) })">Lihat Detail</button>
        </li>
    </ul>
    </template>
</div>
