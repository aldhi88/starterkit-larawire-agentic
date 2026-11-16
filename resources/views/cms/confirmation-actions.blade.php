@php
    [$variant, $icon, $label] = match ($pendingAction) {
        'archive' => ['btn-warning', 'archive', 'Arsipkan'],
        'restore' => ['btn-success', 'restore', 'Pulihkan'],
        'delete_permanently' => ['btn-danger', 'delete', 'Hapus Permanen'],
    };
@endphp
<div class="modal-footer{{ isset($footerClass) ? ' '.$footerClass : '' }}">
    <button type="button" class="btn cms-btn-neutral" wire:click="cancelPendingAction" wire:loading.attr="disabled" wire:target="executePendingAction">Batal</button>
    <button type="submit" class="btn {{ $variant }}" wire:loading.attr="disabled" wire:target="executePendingAction">
        @include('starter.cms.action-icon', ['action' => $icon])
        <span wire:loading.remove wire:target="executePendingAction">{{ $label }}</span>
        <span wire:loading wire:target="executePendingAction" role="status">Memproses…</span>
    </button>
</div>
