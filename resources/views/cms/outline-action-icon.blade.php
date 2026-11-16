@php
    $paths = [
        'add' => 'M12 5v14M5 12h14',
        'save' => 'M6 3h10l5 5v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z M7 3v6h9V3 M7 21v-7h10v7',
        'upload' => 'M12 16V3m-5 5 5-5 5 5 M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3',
        'archive' => 'M3 4h18v4H3z M5 8v12h14V8 M10 12h4',
        'restore' => 'M3 4v6h6 M3 10a9 9 0 1 1 1 7',
        'delete' => 'M3 6h18 M9 6V3h6v3 M5 6l1 15h12l1-15 M10 10v7m4-7v7',
        'check' => 'm5 12 4 4L19 6',
        'draft' => 'm4 16-1 5 5-1L20 8l-4-4z M13 7l4 4',
        'guide' => 'M12 5c-3-2-6-2-9-1v15c3-1 6-1 9 1 3-2 6-2 9-1V4c-3-1-6-1-9 1z M12 5v15',
    ];
@endphp
<svg class="icon cms-action-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $paths[$action] }}"/></svg>
