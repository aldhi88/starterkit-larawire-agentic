<script src="{{ asset('assets/vuexy/js/vuexy.js') }}?v={{ file_exists(public_path('assets/vuexy/js/vuexy.js')) ? filemtime(public_path('assets/vuexy/js/vuexy.js')) : time() }}" data-navigate-once defer></script>
<script src="{{ asset('assets/starter/js/starter-runtime.js') }}" data-navigate-once defer></script>
@livewireScripts
@stack('page-scripts')
@includeIf('extensions.starter.layout.body-end')
