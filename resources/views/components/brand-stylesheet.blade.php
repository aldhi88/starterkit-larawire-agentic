@if ($stylesheet = \Aldhi88\StarterKit\Support\Starter\StarterBrandStylesheet::configured())
    @if ($standalone ?? false)
        {{-- Error/maintenance pages cannot depend on an application CSS route. Only validated palette tokens and the trusted theme adapter are emitted. --}}
        <style data-starter-brand-stylesheet>{!! $stylesheet->css() !!}</style>
    @else
        <link rel="stylesheet" href="{{ route('starter.brand.css', [], false) }}?v={{ $stylesheet->fingerprint() }}" data-starter-brand-stylesheet>
    @endif
@endif
