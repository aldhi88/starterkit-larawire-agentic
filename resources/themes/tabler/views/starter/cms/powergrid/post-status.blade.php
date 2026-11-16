@if ($row->status === 'draft')
    <span class="badge bg-secondary-lt"><span class="cms-status-label">Draf</span></span>
@elseif ($row->published_at?->isFuture())
    <span class="badge bg-azure-lt"><span class="cms-status-label">Terjadwal</span></span>
@else
    <span class="badge bg-green-lt"><span class="cms-status-label">Terbit</span></span>
@endif
