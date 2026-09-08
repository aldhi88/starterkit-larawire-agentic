@if ($clientLogoUrl)
    <img src="{{ $clientLogoUrl }}" class="starter-sidebar-brand-image starter-sidebar-brand-image-horizontal" alt="{{ $clientName ?: config('app.name') }}" data-starter-brand-logo data-fallback-src="{{ asset('assets/tabler/static/logo-small.svg') }}" data-company-logo="true">
@else
    {{ config('app.name') }}
@endif
