@props([
    'enabledFilters' => [],
    'column' => null,
    'inline' => null,
    'filter' => null,
])

<div>
    @php
        $fieldClassName = data_get($filter, 'className');
        $field = strval(data_get($filter, 'field'));
        $title = strval(data_get($column, 'title'));
        $operators = (array) data_get($filter, 'operators', []);
        $placeholder = strval(data_get($filter, 'placeholder'));
        $componentAttributes = (array) data_get($filter, 'attributes', []);
        $inputTextOptions = $fieldClassName::getInputTextOperators();
        $inputTextOptions = count($operators) > 0 ? $operators : $inputTextOptions;
        $showSelectOptions = ! (count($inputTextOptions) === 1 && in_array('contains', $inputTextOptions));
        $defaultPlaceholder = data_get($column, 'placeholder') ?: data_get($column, 'title');
        $overridePlaceholder = $placeholder ?: $defaultPlaceholder;

        unset($filter['placeholder']);

        $defaultAttributes = $fieldClassName::getWireAttributes($field, $title);
        $selectClasses = theme_style($theme, 'filterInputText.select');
        $inputClasses = theme_style($theme, 'filterInputText.input');
        $params = array_merge([
            'showSelectOptions' => $showSelectOptions,
            'placeholder' => $componentAttributes['placeholder'] ?? $overridePlaceholder,
            ...data_get($filter, 'attributes'),
            ...$defaultAttributes,
        ], $filter);
    @endphp

    @if ($params['component'])
        @unset($params['operators'], $params['attributes'])

        <x-dynamic-component
            :component="$params['component']"
            :attributes="new \Illuminate\View\ComponentAttributeBag($params)"
        />
    @else
        <div @class([theme_style($theme, 'filterInputText.base'), 'space-y-1' => ! $inline])>
            @if (! $inline)
                <label class="form-label">{{ $title }}</label>
            @endif

            <div @class([
                'w-full space-y-2 sm:flex sm:space-y-0' => ! $inline && $showSelectOptions,
                'flex flex-col space-y-2' => $inline && $showSelectOptions,
            ])>
                @if ($showSelectOptions)
                    <div @class(['pl-0 w-full sm:pr-3 sm:w-1/2' => ! $inline])>
                        <select
                            class="{{ $selectClasses }}"
                            data-cy="input_text_options_{{ $tableName }}_{{ $field }}"
                            {{ $defaultAttributes['selectAttributes'] }}
                        >
                            @foreach ($inputTextOptions as $key => $value)
                                <option
                                    wire:key="input-text-options-{{ $tableName }}-{{ $key.'-'.$value }}"
                                    value="{{ $value }}"
                                >{{ trans('livewire-powergrid::datatable.input_text_options.'.$value) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div @class(['pl-0 w-full sm:w-1/2' => ! $inline && $showSelectOptions])>
                    <input
                        data-cy="input_text_{{ $tableName }}_{{ $field }}"
                        wire:key="input-{{ $field }}"
                        data-id="{{ $field }}"
                        @if (isset($enabledFilters[$field]['disabled']) && boolval($enabledFilters[$field]['disabled']))
                            disabled
                        @else
                            {{ $defaultAttributes['inputAttributes'] }}
                        @endif
                        type="text"
                        class="{{ $inputClasses }}"
                        placeholder="{{ $params['placeholder'] }}"
                    />
                </div>
            </div>
        </div>
    @endif
</div>
