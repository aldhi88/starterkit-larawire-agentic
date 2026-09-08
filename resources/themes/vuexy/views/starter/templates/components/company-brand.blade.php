@include('starter.templates.layouts.brand', [
    'url' => url('/'),
    'navigate' => false,
    'clientLogoUrl' => $clientLogoUrl,
    'brandLogoUrl' => $clientLogoUrl ?: asset('assets/vuexy/img/branding/vuexy-mark.svg'),
    'brandLogoAlt' => $clientLogoUrl ? ($clientName ?: config('app.name')) : config('app.name'),
    'brandText' => config('app.name'),
    'markClass' => 'vuexy-brand-mark-horizontal',
    'showText' => ! $clientLogoUrl,
])
