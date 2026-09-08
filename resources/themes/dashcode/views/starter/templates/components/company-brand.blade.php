<a href="{{ url('/') }}" class="{{ $clientLogoUrl ? 'starter-brand' : 'dashcode-landing-brand' }}">
    <img src="{{ $clientLogoUrl ?: asset('assets/dashcode/images/logo/logo-c.svg') }}" class="{{ $clientLogoUrl ? 'starter-brand-image' : '' }}" alt="{{ $clientLogoUrl ? ($clientName ?: config('app.name')) : '' }}" data-starter-brand-logo data-fallback-src="{{ asset('assets/dashcode/images/logo/logo-c.svg') }}" @if ($clientLogoUrl) data-company-logo="true" @endif>
    @unless ($clientLogoUrl)
        <strong>{{ config('app.name') }}</strong>
    @endunless
</a>
