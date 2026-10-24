{{-- Only owner-selected native illustrations are passed by the error layouts. --}}
@if (\Aldhi88\StarterKit\Support\Starter\StarterBrandPalette::configured())
    <svg class="{{ $illustrationClass }}" viewBox="0 0 {{ $illustrationWidth }} {{ $illustrationHeight }}" aria-hidden="true" focusable="false" data-starter-error-illustration>
        <defs>
            <filter id="starter-error-brand-tint" color-interpolation-filters="sRGB">
                <feColorMatrix in="SourceGraphic" type="saturate" values="0" result="gray" />
                <feFlood flood-color="var(--starter-brand-primary)" result="brand" />
                <feBlend in="brand" in2="gray" mode="color" />
                <feComposite in2="SourceGraphic" operator="in" />
            </filter>
        </defs>
        <image href="{{ $illustrationUrl }}" width="{{ $illustrationWidth }}" height="{{ $illustrationHeight }}" filter="url(#starter-error-brand-tint)" />
    </svg>
@else
    <img class="{{ $illustrationClass }}" src="{{ $illustrationUrl }}" width="{{ $illustrationWidth }}" height="{{ $illustrationHeight }}" alt="" aria-hidden="true" data-starter-error-illustration>
@endif
