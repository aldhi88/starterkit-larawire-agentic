<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-bs-theme="light" data-skin="default" data-template="front-pages-no-customizer" class="layout-navbar-fixed layout-wide">
<head>@include('starter.templates.layouts.head', ['vuexyPage' => 'landing'])</head>
<body class="vuexy-landing-page">
    {{ $slot ?? '' }}
    @yield('content')
    @include('starter.templates.layouts.scripts')
</body>
</html>
