@php
    $status = (string) $row->getAttribute('status');
    $scheduled = in_array($resource, ['posts', 'pages', 'cards'], true) && $status === 'published' && $row->getAttribute('published_at')?->isFuture();
    $label = $scheduled ? 'Terjadwal' : ($statuses[$status] ?? $status);
    $tone = $scheduled ? 'info' : match ($status) {
        'published', 'active', 'approved' => 'success',
        'pending' => 'warning',
        'rejected', 'spam' => 'danger',
        default => 'secondary',
    };
    $badgeClass = match (\Aldhi88\StarterKit\Support\Starter\StarterTheme::key()) {
        'vuexy' => 'bg-label-'.$tone,
        'dashcode' => match ($tone) {
            'info' => 'bg-azure-lt text-azure',
            default => 'bg-'.$tone.'-lt text-'.$tone,
        },
        default => 'bg-'.$tone.'-lt',
    };
@endphp
<span class="badge {{ $badgeClass }}"><span class="cms-status-label">{{ $label }}</span></span>
