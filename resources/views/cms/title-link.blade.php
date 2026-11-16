@php($permalink = \Aldhi88\StarterKit\Support\Cms\CmsPermalink::for($kind, (string) $row->slug))
@if ($permalink)
    <button type="button" class="cms-title-copy" x-data="{ copied: false }" title="Salin tautan" aria-label="Salin tautan: {{ $row->title ?: $row->name }}" @click="(async () => { try { await navigator.clipboard.writeText(@js($permalink)); copied = true; setTimeout(() => copied = false, 2000); } catch { $dispatch('starter-toast', { type: 'warning', message: 'Buka Edit untuk menyalin alamat secara manual.' }); } })()">
        <span>{{ $row->title ?: $row->name }}</span>
        <svg class="cms-title-chain" :class="{ 'cms-title-chain-visible': copied }" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2"/></svg>
        <span class="cms-copy-result" x-show="copied" x-cloak role="status">Tersalin</span>
    </button>
@else
    {{ $row->title ?: $row->name }}
@endif
