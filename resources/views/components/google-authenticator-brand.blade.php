@php
    $theme = $theme ?? \Aldhi88\StarterKit\Support\Starter\StarterTheme::key();
    $compact = (bool) ($compact ?? false);
    $class = trim((string) ($class ?? ''));
    $isDashcode = $theme === 'dashcode';
    $wrapperClass = $isDashcode
        ? 'inline-flex max-w-full items-center gap-2.5 rounded border border-slate-200 bg-white px-3 py-2'
        : 'd-inline-flex mw-100 align-items-center gap-2 border rounded bg-white px-3 py-2';
    $textClass = $isDashcode
        ? 'min-w-0 text-left leading-tight'
        : 'text-start lh-sm';
    $iconClass = $isDashcode ? 'relative flex-none' : 'position-relative flex-shrink-0';
@endphp

<div
    class="{{ $wrapperClass }}{{ $class !== '' ? ' '.$class : '' }}"
    data-starter-region="google-authenticator-brand"
    aria-label="Powered by Google Authenticator"
>
    <span class="{{ $iconClass }}" aria-hidden="true">
        <svg width="{{ $compact ? 30 : 36 }}" height="{{ $compact ? 30 : 36 }}" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12.25 7.75 18 17.7" stroke="#FBBC05" stroke-width="7" stroke-linecap="round"/>
            <path d="M7 18h11" stroke="#34A853" stroke-width="7" stroke-linecap="round"/>
            <path d="m12.25 28.25 5.75-9.95M23.75 28.25 18 18.3" stroke="#EA4335" stroke-width="7" stroke-linecap="round"/>
            <path d="m23.75 7.75-5.75 9.95M18 18h11" stroke="#4285F4" stroke-width="7" stroke-linecap="round"/>
        </svg>
    </span>
    <span class="{{ $textClass }}">
        <span class="{{ $isDashcode ? 'block text-xs font-medium uppercase tracking-wide text-slate-400' : 'd-block small text-secondary text-uppercase' }}">Powered by</span>
        <strong class="{{ $isDashcode ? 'block truncate text-sm font-semibold text-slate-800' : 'd-block text-body fw-semibold text-nowrap' }}">Google Authenticator</strong>
    </span>
</div>
