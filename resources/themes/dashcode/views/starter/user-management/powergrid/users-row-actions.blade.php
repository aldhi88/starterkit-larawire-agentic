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
        @if (! $row->trashed())
            <li>
                <a class="dashcode-table-dropdown-item" href="{{ route('starter.user-management.users.edit', ['userLoginId' => $row->id]) }}" @click="open = false" role="menuitem">
                    @include('starter.templates.layouts.icon', ['name' => 'edit', 'class' => 'dashcode-table-dropdown-icon'])
                    <span>Edit</span>
                </a>
            </li>
            @if (! (bool) $row->role_is_system && $row->role_code !== 'superuser')
                <li>
                    <button type="button" class="dashcode-table-dropdown-item" wire:click="$dispatch('starter-user-reset-request', { id: {{ $row->id }} })" @click="open = false" role="menuitem">
                        @include('starter.templates.layouts.icon', ['name' => 'lock', 'class' => 'dashcode-table-dropdown-icon'])
                        <span>Reset password</span>
                    </button>
                </li>
                <li><hr class="dashcode-table-dropdown-divider"></li>
                <li>
                    <button type="button" class="dashcode-table-dropdown-item" wire:click="$dispatchSelf('prepare-row-action', { action: 'archive', id: {{ $row->id }} })" @click="open = false" role="menuitem">
                        @include('starter.templates.layouts.icon', ['name' => 'archive', 'class' => 'dashcode-table-dropdown-icon'])
                        <span>Arsipkan</span>
                    </button>
                </li>
            @endif
        @else
            <li>
                <button type="button" class="dashcode-table-dropdown-item" wire:click="$dispatchSelf('prepare-row-action', { action: 'restore', id: {{ $row->id }} })" @click="open = false" role="menuitem">
                    @include('starter.templates.layouts.icon', ['name' => 'history', 'class' => 'dashcode-table-dropdown-icon'])
                    <span>Pulihkan</span>
                </button>
            </li>
            <li>
                <button type="button" class="dashcode-table-dropdown-item dashcode-table-dropdown-danger" wire:click="$dispatchSelf('prepare-row-action', { action: 'forceDelete', id: {{ $row->id }} })" @click="open = false" role="menuitem">
                    @include('starter.templates.layouts.icon', ['name' => 'trash', 'class' => 'dashcode-table-dropdown-icon'])
                    <span>Hapus permanen</span>
                </button>
            </li>
        @endif
    </ul>
    </template>
</div>
