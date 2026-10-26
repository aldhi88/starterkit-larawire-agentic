<div x-data="{ open: false, top: 0, left: 0 }" @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window="open = false" @resize.window="open = false">
    <button type="button" class="btn btn-action border-0" aria-label="Aksi" aria-haspopup="menu" :aria-expanded="open.toString()" @click="open = !open; if (open) { const rect = $el.getBoundingClientRect(); $nextTick(() => { const menu = $refs.menu; left = Math.max(8, Math.min(rect.left, window.innerWidth - menu.offsetWidth - 8)); top = rect.bottom + menu.offsetHeight + 12 <= window.innerHeight ? rect.bottom + 4 : Math.max(8, rect.top - menu.offsetHeight - 4); }); }">
        @include('starter.templates.layouts.icon', ['name' => 'dots-vertical', 'class' => 'icon'])
    </button>
    <template x-teleport="body">
        <ul x-ref="menu" class="dropdown-menu" :class="{ 'show': open }" x-show="open" x-cloak :style="{ position: 'fixed', top: `${top}px`, left: `${left}px`, zIndex: 1060 }" @click.outside="open = false" role="menu">
            @if (! $row->trashed())
                @if ($cmsResource->isCategory() || $cmsResource === \Aldhi88\StarterKit\Support\Cms\CmsResource::MenuCategories)
                    <li><button type="button" class="dropdown-item" wire:click="$dispatch('cms-edit-category', { resource: '{{ $cmsResource->value }}', id: {{ $row->id }} })" @click="open = false" role="menuitem">Edit</button></li>
                @else
                    <li><a class="dropdown-item" href="{{ route($cmsResource->routePrefix().'.edit', ['contentId' => $row->id]) }}" @click="open = false" role="menuitem" data-starter-navigate>Edit</a></li>
                @endif
                <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-row-action', { action: 'archive', id: {{ $row->id }} })" @click="open = false" role="menuitem">Arsipkan</button></li>
            @else
                <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-row-action', { action: 'restore', id: {{ $row->id }} })" @click="open = false" role="menuitem">Pulihkan</button></li>
                <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-row-action', { action: 'delete_permanently', id: {{ $row->id }} })" @click="open = false" role="menuitem">Hapus Permanen</button></li>
            @endif
        </ul>
    </template>
</div>
