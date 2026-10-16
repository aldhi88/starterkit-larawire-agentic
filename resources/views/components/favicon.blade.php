@php
    $defaultFaviconUrl = asset('assets/starter/images/application-icon.svg');
@endphp
<link rel="icon" href="{{ $clientFaviconUrl ?? $defaultFaviconUrl }}" data-starter-favicon data-fallback-src="{{ $defaultFaviconUrl }}">
