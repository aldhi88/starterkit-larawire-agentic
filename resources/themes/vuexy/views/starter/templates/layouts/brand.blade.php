<a href="{{ $url ?? route('landing') }}" class="app-brand-link" aria-label="{{ $brandLogoAlt ?? config('app.name') }}" @if ($navigate ?? true) data-starter-navigate @endif>
    <span class="app-brand-logo">
        <img
            src="{{ $brandLogoUrl ?? asset('assets/vuexy/img/branding/vuexy-mark.svg') }}"
            alt=""
            class="vuexy-brand-mark"
            data-starter-brand-logo
            data-fallback-src="{{ asset('assets/vuexy/img/branding/vuexy-mark.svg') }}"
            @if (!empty($clientLogoUrl)) data-company-logo="true" @endif
        >
    </span>
    <span class="app-brand-text menu-text fw-bold ms-3 text-truncate">{{ $brandText ?? ($clientName ?: config('app.name')) }}</span>
</a>
