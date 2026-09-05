@php
    $flashToast = session('starter-toast');
@endphp

<div class="starter-toast-stack" data-starter-toast-stack aria-live="polite" aria-atomic="false"></div>

@if (is_array($flashToast) && filled($flashToast['message'] ?? null))
    <div
        hidden
        data-starter-flash-toast
        data-type="{{ $flashToast['type'] ?? 'info' }}"
        data-message="{{ $flashToast['message'] }}"
        @if (filled($flashToast['title'] ?? null)) data-title="{{ $flashToast['title'] }}" @endif
        @if (filled($flashToast['duration'] ?? null)) data-duration="{{ $flashToast['duration'] }}" @endif
    ></div>
@endif
