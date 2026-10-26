@if ($postAction !== null)
    @php
        $actionLabel = match ($postAction) { 'publish_now' => 'Terbitkan sekarang', 'draft' => 'Jadikan draf', default => 'Ubah kategori' };
    @endphp
    <div class="modal modal-blur fade show d-block" role="dialog" aria-modal="true" aria-labelledby="cms-post-action-title" tabindex="-1" data-cms-post-action wire:key="post-action-{{ $postAction }}" wire:click.self="cancelPostAction">
        <div @class(['modal-dialog modal-dialog-centered modal-dialog-scrollable', 'modal-lg' => $postAction === 'categories'])>
            <form class="modal-content" wire:submit="executePostAction" novalidate>
                <div class="modal-header">
                    <h3 class="modal-title" id="cms-post-action-title">{{ $actionLabel }}</h3>
                    <button type="button" class="btn-close" aria-label="Tutup" wire:click="cancelPostAction" wire:loading.attr="disabled"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>{{ count($postIds) }} postingan</strong>
                        <span class="text-secondary">{{ $postFiltered ? 'dari semua hasil filter aktif, termasuk halaman lain.' : 'dipilih.' }}</span>
                        @if ($postTitle !== '')<div class="text-secondary small mt-1 text-break">{{ $postTitle }}</div>@endif
                    </div>
                    @if ($postAction === 'publish_now')
                        <p class="mb-0">Postingan akan langsung tampil di website. Tanggal postingan yang sudah terbit tetap dipertahankan.</p>
                        @if ($postScheduled > 0)<div class="alert alert-warning mt-3 mb-0">{{ $postScheduled }} postingan terjadwal akan diterbitkan sekarang, tanpa menunggu jadwalnya.</div>@endif
                    @elseif ($postAction === 'draft')
                        <p class="mb-0">Postingan tidak tampil di website dan tidak akan terbit otomatis selama berstatus draf. Isi dan kategori tetap tersimpan.</p>
                    @else
                        <p class="text-secondary small mb-3">Pilihan ini mengganti kategori postingan. Tanpa centang berarti tanpa kategori.</p>
                        @include('starter.cms.category-picker', ['categories' => $postCategories, 'field' => 'postCategoryIds', 'pickerId' => 'cms-quick-categories', 'twoColumns' => true])
                        @error('postCategoryIds')<div class="text-danger small mt-2" role="alert">{{ $message }}</div>@enderror
                    @endif
                    @error('postAction')<div class="alert alert-danger mt-3 mb-0" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-ghost-secondary" wire:click="cancelPostAction" wire:loading.attr="disabled">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="executePostAction">{{ $postAction === 'categories' ? 'Simpan kategori' : $actionLabel }}</span>
                        <span wire:loading wire:target="executePostAction">Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-backdrop fade show" data-cms-post-backdrop aria-hidden="true"></div>
@endif
