@php
    $columns = collect($columns)->map(function ($column) {
        return data_forget($column, 'rawQueries');
    });
@endphp

<div @if ($deferLoading) wire:init="fetchDatasource" @endif>
    <div class="col-md-12">
        @include(theme_style($theme, 'layout.header'), ['enabledFilters' => $enabledFilters])
    </div>

    @if (config('livewire-powergrid.filter') === 'outside')
        @php
            $filtersFromColumns = $columns->filter(fn ($column) => filled(data_get($column, 'filters')));
        @endphp

        @includeWhen($filtersFromColumns->count() > 0, 'livewire-powergrid::components.frameworks.bootstrap5.filter')
    @endif

    @include(theme_style($theme, 'footer.view'), ['position' => 'top'])

    <div class="{{ theme_style($theme, 'table.layout.div') }}">
        @include($table)
    </div>

    @include(theme_style($theme, 'footer.view'), ['position' => 'bottom'])
</div>
