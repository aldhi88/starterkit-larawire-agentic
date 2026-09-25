<img
    @if ($clientLogoPreviewUrl) src="{{ $clientLogoPreviewUrl }}" @else hidden @endif
    x-bind:src="previewUrl || null"
    x-bind:hidden="! previewUrl"
    class="starter-client-logo-preview-image"
    alt="Pratinjau logo {{ $clientForm['name'] ?: 'perusahaan' }}"
>
<span
    @if ($clientLogoPreviewUrl) hidden @endif
    x-bind:hidden="Boolean(previewUrl)"
    class="starter-client-logo-placeholder"
>{{ $clientInitials }}</span>
