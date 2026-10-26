@if ($detailId !== null)
    <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="cms-media-detail-title" wire:click.self="closeDetails" x-data @keydown.escape.window="$wire.closeDetails()">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <form class="modal-content" wire:submit="saveDetails">
                <div class="modal-header"><h3 id="cms-media-detail-title" class="modal-title">Detail Media</h3><button type="button" class="btn-close" wire:click="closeDetails" aria-label="Tutup detail media"></button></div>
                <div class="modal-body">
                    <div class="cms-media-detail-layout">
                        <div class="min-w-0">
                            @if (($detail['status'] ?? '') === 'active' && filled($detailMeta['url'] ?? null) && str_starts_with($detailMeta['mime_type'] ?? '', 'image/'))<img src="{{ $detailMeta['url'] }}" alt="{{ $detail['alt_text'] ?? $detail['name'] ?? '' }}" class="cms-media-tile-preview">@else<div class="cms-media-file-placeholder bg-lighter text-muted">@include('starter.templates.layouts.icon',['name'=>'file-text','class'=>'icon'])<span>{{ ($detailMeta['mime_type'] ?? '') === 'application/pdf' ? 'Dokumen PDF' : 'Media' }}</span></div>@endif
                            <p class="text-secondary small mt-3">{{ $detailMeta['original_name'] ?? '' }}<br>{{ number_format(($detailMeta['size'] ?? 0)/1024,1,',','.') }} KB</p>
                            @if (($detail['status'] ?? '') === 'active' && filled($detailMeta['url'] ?? null))<a href="{{ $detailMeta['url'] }}" target="_blank" rel="noopener noreferrer" class="link-primary">Buka File</a>@endif
                        </div>
                        <div class="vstack gap-3">
                            <div><label class="form-label" for="cms-detail-name">Nama Media *</label><input id="cms-detail-name" type="text" class="form-control @error('detail.name') is-invalid @enderror" wire:model.defer="detail.name" maxlength="255" required>@error('detail.name')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror</div>
                            <div><label class="form-label" for="cms-detail-alt">Teks Alternatif Gambar</label><input id="cms-detail-alt" type="text" class="form-control @error('detail.alt_text') is-invalid @enderror" wire:model.defer="detail.alt_text" maxlength="255">@error('detail.alt_text')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror</div>
                            <div><label class="form-label" for="cms-detail-caption">Keterangan</label><textarea id="cms-detail-caption" class="form-control @error('detail.caption') is-invalid @enderror" rows="3" wire:model.defer="detail.caption" maxlength="5000"></textarea>@error('detail.caption')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror</div>
                            <div><label class="form-label" for="cms-detail-status">Status</label><select id="cms-detail-status" class="form-select" wire:model.defer="detail.status"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>@error('detail.status')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror</div>
                            <div><label class="form-label" for="cms-detail-replacement">Ganti File</label><input id="cms-detail-replacement" type="file" class="form-control" wire:model="replacement" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"><p class="text-secondary small mt-1">Opsional. Maksimal 10 MB.</p>@error('replacement')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror<div wire:loading wire:target="replacement" class="text-secondary small" role="status">Mengunggah pengganti…</div></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" wire:click="prepareAction('archive',false,{{ $detailId }})">Arsipkan Media</button><button type="button" class="btn btn-outline-secondary" wire:click="closeDetails">Batal</button><button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="replacement,saveDetails">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
@endif
