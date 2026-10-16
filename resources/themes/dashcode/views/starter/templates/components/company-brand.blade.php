<a href="{{ url('/') }}" class="starter-brand">
    <img src="{{ $clientLogoUrl ?: asset('assets/starter/images/application-logo.svg') }}" class="starter-brand-image" alt="{{ $clientName ?: config('app.name') }}" data-starter-brand-logo data-fallback-src="{{ asset('assets/starter/images/application-logo.svg') }}" @if ($clientLogoUrl) data-company-logo="true" @endif>
</a>
