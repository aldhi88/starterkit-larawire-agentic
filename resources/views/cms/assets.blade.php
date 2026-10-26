@if ($cmsEditorAssets ?? false)
    @assets
        <link rel="stylesheet" href="{{ asset('assets/starter/vendor/quill/quill.snow.css') }}?v=2.0.3">
        <script src="{{ asset('assets/starter/vendor/quill/quill.js') }}?v=2.0.3" defer></script>
    @endassets
@endif
@if ($cmsSortableAssets ?? false)
    @assets
        <script src="{{ asset('assets/starter/vendor/sortable/Sortable.min.js') }}?v=1.15.7" defer></script>
    @endassets
@endif
@if (\Aldhi88\StarterKit\Support\Starter\StarterTheme::key() !== 'tabler')
    @assets
        @include('starter-shared::cms.runtime-assets')
    @endassets
@endif
