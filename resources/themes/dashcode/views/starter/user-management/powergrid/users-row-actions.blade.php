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
        @if (! $row->trashed())
            <li>
                <a class="dashcode-table-dropdown-item" href="{{ route('starter.user-management.users.edit', ['userLoginId' => $row->id]) }}">Edit</a>
            </li>
            @if (! (bool) $row->role_is_system && $row->role_code !== 'superuser')
                <li>
                    <button type="button" class="dashcode-table-dropdown-item" wire:click="$dispatch('starter-user-reset-request', { id: {{ $row->id }} })">Reset password</button>
                </li>
                <li><hr class="dashcode-table-dropdown-divider"></li>
                <li>
                    <button type="button" class="dashcode-table-dropdown-item" wire:click="$dispatchSelf('prepare-row-action', { action: 'archive', id: {{ $row->id }} })">Arsipkan</button>
                </li>
            @endif
        @else
            <li>
                <button type="button" class="dashcode-table-dropdown-item" wire:click="$dispatchSelf('prepare-row-action', { action: 'restore', id: {{ $row->id }} })">Pulihkan</button>
            </li>
            <li>
                <button type="button" class="dashcode-table-dropdown-item dashcode-table-dropdown-danger" wire:click="$dispatchSelf('prepare-row-action', { action: 'forceDelete', id: {{ $row->id }} })">Hapus permanen</button>
            </li>
        @endif
    </ul>
    </template>
</div>
