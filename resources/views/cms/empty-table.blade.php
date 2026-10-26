<div class="cms-table-empty-message" role="status">
    <span class="cms-table-empty-icon" aria-hidden="true">
        @include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'cms-table-empty-icon-svg'])
    </span>
    <strong>{{ $heading }}</strong>
    <span>{{ $hint }}</span>
</div>
