@if ($pendingAction !== null)
    <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="cms-media-confirm-title" wire:click.self="cancelPendingAction" x-data @keydown.escape.window="$wire.cancelPendingAction()">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document"><form class="modal-content" wire:submit="executePendingAction">
            <div class="modal-header"><h3 id="cms-media-confirm-title" class="modal-title">{{ $pendingAction === 'delete_permanently' ? 'Hapus media permanen?' : ($pendingAction === 'restore' ? 'Pulihkan media?' : 'Arsipkan media?') }}</h3></div>
            <div class="modal-body vstack gap-3"><p>{{ $pendingCount }} media {{ $pendingFiltered ? 'dalam hasil filter' : 'terpilih' }} akan diproses.</p>
                @if ($pendingAction === 'delete_permanently')<p class="text-danger">File dan data akan dihapus permanen dan tidak dapat dipulihkan.</p><div><label class="form-label" for="cms-media-delete-confirm">Ketik HAPUS untuk melanjutkan</label><input id="cms-media-delete-confirm" type="text" class="form-control" wire:model.defer="confirmation" autocomplete="off" required pattern="HAPUS"></div>@else<p class="text-secondary small">{{ $pendingAction === 'restore' ? 'Media akan kembali ke daftar aktif.' : 'Media dapat dipulihkan dari Arsip. Media yang masih digunakan tidak akan diarsipkan.' }}</p>@endif
                @error('lifecycle')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" wire:click="cancelPendingAction">Batal</button><button type="submit" class="btn {{ $pendingAction === 'restore' ? 'btn-primary' : 'btn-danger' }}" wire:loading.attr="disabled" wire:target="executePendingAction">{{ $pendingAction === 'delete_permanently' ? 'Hapus Permanen' : ($pendingAction === 'restore' ? 'Pulihkan' : 'Arsipkan') }}</button></div>
        </form></div>
    </div><div class="modal-backdrop fade show"></div>
@endif
