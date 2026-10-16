<a href="{{ $url ?? route('landing') }}" class="app-brand-link" aria-label="{{ $brandLogoAlt ?? config('app.name') }}" @if ($navigate ?? true) data-starter-navigate @endif>
    <span class="app-brand-logo">
        <img
            src="{{ $brandLogoUrl ?? asset('assets/starter/images/application-logo.svg') }}"
            alt=""
            class="vuexy-brand-mark vuexy-brand-mark-horizontal {{ $markClass ?? '' }}"
            data-starter-brand-logo
            data-fallback-src="{{ asset('assets/starter/images/application-logo.svg') }}"
            @if (!empty($clientLogoUrl)) data-company-logo="true" @endif
        >
    </span>
</a>
