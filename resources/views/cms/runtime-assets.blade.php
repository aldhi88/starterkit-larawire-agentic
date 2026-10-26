@php($cmsAssetVersion = substr(hash('sha256', hash_file('sha256', \Aldhi88\StarterKit\Support\Starter\StarterPaths::path('public/assets/starter/js/cms-workspace.js')).hash_file('sha256', \Aldhi88\StarterKit\Support\Starter\StarterPaths::path('public/assets/starter/css/cms-workspace.css'))), 0, 16))
<link rel="stylesheet" href="{{ asset('assets/starter/css/cms-workspace.css') }}?v={{ $cmsAssetVersion }}" data-navigate-track>
<script src="{{ asset('assets/starter/js/cms-workspace.js') }}?v={{ $cmsAssetVersion }}" data-navigate-track defer></script>
