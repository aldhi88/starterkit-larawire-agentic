@if ($pendingAction !== null)
    <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="{{ $tableName }}-confirmation-title" wire:click.self="cancelPendingAction" x-data @keydown.escape.window="$wire.cancelPendingAction()">
        <div class="modal-dialog modal-sm" role="document">
            <form class="modal-content" wire:submit="executePendingAction">
                <div class="modal-header"><h3 id="{{ $tableName }}-confirmation-title" class="modal-title">{{ $pendingAction === 'delete_permanently' ? 'Hapus data permanen?' : ($pendingAction === 'restore' ? 'Pulihkan data?' : 'Arsipkan data?') }}</h3></div>
                <div class="modal-body vstack gap-3">
                    <p class="mb-0 text-secondary">{{ $pendingCount }} data {{ $pendingFiltered ? 'dalam hasil filter' : 'terpilih' }} akan diproses.</p>
                    @if ($pendingAction === 'delete_permanently')
                        <div class="alert alert-danger" role="note">{{ $resource === 'comments' ? 'Menghapus komentar utama juga akan menghapus semua balasannya secara permanen.' : 'Data akan dihapus permanen dan tidak dapat dipulihkan.' }}</div>
                        <div><label class="form-label" for="{{ $tableName }}-delete-confirm">Ketik HAPUS untuk melanjutkan</label><input id="{{ $tableName }}-delete-confirm" type="text" class="form-control" wire:model.defer="confirmation" autocomplete="off" required pattern="HAPUS"></div>
                    @else
                        <p class="mb-0 text-secondary small">{{ $pendingAction === 'restore' ? 'Data akan dikembalikan ke daftar aktif.' : 'Data akan dipindahkan ke arsip dan dapat dipulihkan.' }}</p>
                    @endif
                    @error('lifecycle')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
                </div>
                @include('starter.cms.confirmation-actions')
            </form>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
@endif
