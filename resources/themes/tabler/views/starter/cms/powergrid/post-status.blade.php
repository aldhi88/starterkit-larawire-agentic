@if ($row->status === 'draft')
    <span class="badge bg-secondary-lt">Draf</span>
@elseif ($row->published_at?->isFuture())
    <span class="badge bg-azure-lt">Terjadwal</span>
@else
    <span class="badge bg-green-lt">Terbit</span>
@endif
