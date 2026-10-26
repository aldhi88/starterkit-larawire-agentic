<div wire:key="cms-row-actions-{{ $cmsResource->value }}-{{ $row->id }}-{{ $row->status }}-{{ (int) $row->trashed() }}" x-data="{ open: false, top: 0, left: 0 }" @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window="open = false" @resize.window="open = false">
    <button type="button" class="btn btn-action border-0" aria-label="Aksi" aria-haspopup="menu" :aria-expanded="open.toString()" x-ref="trigger" @click="open = !open">
        @include('starter.templates.layouts.icon', ['name' => 'dots-vertical', 'class' => 'icon'])
    </button>
    <template x-teleport="body">
        <ul x-ref="menu" class="dropdown-menu" x-effect="if (open) { const menu = $el; $nextTick(() => requestAnimationFrame(() => { if (!open) return; const rect = $refs.trigger.getBoundingClientRect(); const viewport = window.visualViewport; const height = viewport?.height || window.innerHeight; const width = viewport?.width || window.innerWidth; left = Math.max(8, Math.min(rect.left, width - menu.offsetWidth - 8)); top = Math.max(8, Math.min(rect.bottom + 4, height - menu.offsetHeight - 8)); })); }" :class="{ 'show': open }" x-show="open" x-cloak :style="{ position: 'fixed', top: `${top}px`, left: `${left}px`, zIndex: 1060, maxHeight: 'calc(100dvh - 16px)', overflowY: 'auto' }" @click.outside="open = false" role="menu">
            @if (! $row->trashed())
                @if ($cmsResource->isCategory() || $cmsResource === \Aldhi88\StarterKit\Support\Cms\CmsResource::MenuCategories)
                    <li><button type="button" class="dropdown-item" wire:click="$dispatch('cms-edit-category', { resource: '{{ $cmsResource->value }}', id: {{ $row->id }} })" @click="open = false" role="menuitem">Edit</button></li>
                @else
                    <li><a class="dropdown-item" href="{{ route($cmsResource->routePrefix().'.edit', ['contentId' => $row->id]) }}" @click="open = false" role="menuitem" data-starter-navigate>Edit</a></li>
                @endif
                @if ($cmsResource === \Aldhi88\StarterKit\Support\Cms\CmsResource::Posts)
                    @if ($row->status !== 'published' || ! $row->published_at || $row->published_at->isFuture())
                        <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-post-action', { action: 'publish_now', id: {{ $row->id }} })" @click="open = false" role="menuitem">Terbitkan sekarang</button></li>
                    @endif
                    @if ($row->status !== 'draft')
                        <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-post-action', { action: 'draft', id: {{ $row->id }} })" @click="open = false" role="menuitem">Jadikan draf</button></li>
                    @endif
                    <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-post-action', { action: 'categories', id: {{ $row->id }} })" @click="open = false" role="menuitem">Ubah kategori</button></li>
                    <li><hr class="dropdown-divider"></li>
                @endif
                <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-row-action', { action: 'archive', id: {{ $row->id }} })" @click="open = false" role="menuitem">Arsipkan</button></li>
            @else
                <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-row-action', { action: 'restore', id: {{ $row->id }} })" @click="open = false" role="menuitem">Pulihkan</button></li>
                <li><button type="button" class="dropdown-item" wire:click="$dispatchSelf('cms-prepare-row-action', { action: 'delete_permanently', id: {{ $row->id }} })" @click="open = false" role="menuitem">Hapus Permanen</button></li>
            @endif
        </ul>
    </template>
</div>
