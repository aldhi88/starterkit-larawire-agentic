<a href="{{ $backUrl }}" class="btn {{ $backClass ?? 'cms-quiet-action' }} cms-back-link" @if ($backNavigate ?? true) data-starter-navigate @endif>
    {{ $backLabel ?? 'Kembali' }}
    <svg class="icon icon-tabler icon-tabler-arrow-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
</a>
