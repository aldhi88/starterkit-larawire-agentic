@if ($pendingAction !== null)
    <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="{{ $tableName }}-confirmation-title" wire:click.self="cancelPendingAction" x-data @keydown.escape.window="$wire.cancelPendingAction()">
        <div class="modal-dialog modal-sm" role="document">
            <form class="modal-content" wire:submit="executePendingAction">
                <div class="modal-header"><h3 id="{{ $tableName }}-confirmation-title" class="modal-title">{{ $pendingAction === 'delete_permanently' ? 'Hapus data permanen?' : ($pendingAction === 'restore' ? 'Pulihkan data?' : 'Arsipkan data?') }}</h3></div>
                <div class="modal-body vstack gap-3">
                    <p class="mb-0 text-secondary">{{ $pendingCount }} data {{ $pendingFiltered ? 'dalam hasil filter' : 'terpilih' }} akan diproses.</p>
                    @if ($pendingAction === 'delete_permanently')
                        <div class="alert alert-danger" role="note">Data akan dihapus permanen dan tidak dapat dipulihkan.</div>
                        <div><label class="form-label" for="{{ $tableName }}-delete-confirm">Ketik HAPUS untuk melanjutkan</label><input id="{{ $tableName }}-delete-confirm" type="text" class="form-control" wire:model.defer="confirmation" autocomplete="off" required pattern="HAPUS"></div>
                    @else
                        <p class="mb-0 text-secondary small">{{ $pendingAction === 'restore' ? 'Data akan dikembalikan ke daftar aktif.' : 'Data akan dipindahkan ke arsip dan dapat dipulihkan.' }}</p>
                    @endif
                    @error('lifecycle')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn " wire:click="cancelPendingAction" wire:loading.attr="disabled" wire:target="executePendingAction">Batal</button>
                    <button type="submit" class="btn {{ $pendingAction === 'restore' ? 'btn-primary' : 'btn-danger' }}" wire:loading.attr="disabled" wire:target="executePendingAction"><span wire:loading.remove wire:target="executePendingAction">{{ $pendingAction === 'delete_permanently' ? 'Hapus Permanen' : ($pendingAction === 'restore' ? 'Pulihkan' : 'Arsipkan') }}</span><span wire:loading wire:target="executePendingAction" role="status">Memproses…</span></button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
@endif
