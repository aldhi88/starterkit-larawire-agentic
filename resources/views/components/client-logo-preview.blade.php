<img
    @if ($clientLogoPreviewUrl) src="{{ $clientLogoPreviewUrl }}" @else hidden @endif
    x-bind:src="previewUrl || null"
    x-bind:hidden="! previewUrl"
    x-on:error="replace(null)"
    x-init="$nextTick(() => { if ($el.complete && ! $el.naturalWidth) replace(null) })"
    class="starter-client-logo-preview-image"
    alt="{{ $previewLabel ?? 'Pratinjau logo aplikasi' }}"
>
<span
    @if ($clientLogoPreviewUrl) hidden @endif
    x-bind:hidden="Boolean(previewUrl)"
    class="starter-client-logo-placeholder"
>{{ $clientInitials }}</span>
