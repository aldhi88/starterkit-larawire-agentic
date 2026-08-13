@php
    $type = (string) ($type ?? 'info');
    $type = in_array($type, ['success', 'info', 'warning', 'danger'], true) ? $type : 'info';
    $dismissible = $dismissible ?? true;
    $message = $message ?? '';
    $extraClass = trim((string) ($class ?? ''));
    $icon = $icon ?? match ($type) {
        'success' => 'check',
        'warning' => 'alert-triangle',
        'danger' => 'circle-x',
        default => 'info-circle',
    };
@endphp

<div class="dashcode-alert dashcode-alert-{{ $type }} {{ $extraClass }}" role="alert" data-starter-alert>
    <span class="dashcode-alert-icon flex-shrink-0">
        @include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'm-0'])
    </span>

    <div class="flex-fill">
        @if ($message instanceof \Illuminate\Contracts\Support\Htmlable)
            {!! $message->toHtml() !!}
        @else
            {{ $message }}
        @endif
    </div>

    @if ($dismissible)
        <button type="button" class="dashcode-icon-button ms-auto" aria-label="Tutup" data-starter-alert-dismiss>
            @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm'])
        </button>
    @endif
</div>
