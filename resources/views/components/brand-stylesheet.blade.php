@if ($stylesheet = \Aldhi88\StarterKit\Support\Starter\StarterBrandStylesheet::configured())
    <link rel="stylesheet" href="{{ route('starter.brand.css', [], false) }}?v={{ $stylesheet->fingerprint() }}" data-starter-brand-stylesheet>
@endif
