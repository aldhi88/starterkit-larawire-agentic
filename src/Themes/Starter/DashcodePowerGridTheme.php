<?php

namespace Aldhi88\StarterKit\Themes\Starter;

use PowerComponents\LivewirePowerGrid\Themes\Tailwind;

class DashcodePowerGridTheme extends Tailwind
{
    public function layout(): array
    {
        return array_replace(parent::layout(), [
            'table' => 'starter.powergrid.table-base',
            'pagination' => 'starter.powergrid.pagination',
        ]);
    }

    public function table(): array
    {
        return array_replace_recursive(parent::table(), [
            'layout' => [
                'base' => 'starter-pg-table inline-block min-w-full align-middle',
                'div' => 'starter-pg-frame overflow-x-auto',
                'table' => 'min-w-full divide-y divide-slate-100 table-fixed',
                'container' => 'starter-pg-container dashcode-data-table overflow-x-auto',
                'actions' => 'flex items-center justify-center',
            ],
            'header' => [
                'thead' => 'border-t border-slate-100',
                'th' => 'table-th',
            ],
            'body' => [
                'tbody' => 'bg-white divide-y divide-slate-100',
                'tr' => '',
                'td' => 'table-td whitespace-nowrap normal-case',
                'tdFilters' => 'starter-pg-filter-cell',
                'tdEmpty' => 'table-td p-6 text-center text-slate-500 normal-case',
                'tdActionsContainer' => 'flex items-center justify-center',
            ],
        ]);
    }

    public function footer(): array
    {
        return array_replace(parent::footer(), [
            'view' => 'starter.powergrid.footer',
            'select' => 'starter-pg-control starter-pg-select starter-pg-page-size',
            'footer' => 'starter-pg-footer',
            'footer_with_pagination' => 'starter-pg-footer-inner starter-pg-footer-layout',
        ]);
    }

    public function checkbox(): array
    {
        return array_replace(parent::checkbox(), [
            'th' => 'table-th text-center',
            'base' => 'flex items-center justify-center',
            'input' => 'table-checkbox',
        ]);
    }

    public function filterBoolean(): array
    {
        return array_replace(parent::filterBoolean(), [
            'base' => 'starter-pg-filter starter-pg-filter-boolean',
            'select' => 'starter-pg-control starter-pg-select',
        ]);
    }

    public function filterDatePicker(): array
    {
        return array_replace(parent::filterDatePicker(), [
            'base' => 'starter-pg-filter starter-pg-filter-date',
            'input' => 'flatpickr flatpickr-input starter-pg-control',
        ]);
    }

    public function filterInputText(): array
    {
        return array_replace(parent::filterInputText(), [
            'base' => 'starter-pg-filter starter-pg-filter-text',
            'select' => 'starter-pg-control starter-pg-select',
            'input' => 'starter-pg-control',
        ]);
    }

    public function filterMultiSelect(): array
    {
        return array_replace(parent::filterMultiSelect(), [
            'base' => 'starter-pg-filter starter-pg-filter-multiselect',
            'select' => 'starter-pg-control starter-pg-select',
        ]);
    }

    public function filterNumber(): array
    {
        return array_replace(parent::filterNumber(), [
            'input' => 'starter-pg-control starter-pg-filter-number',
        ]);
    }

    public function filterSelect(): array
    {
        return array_replace(parent::filterSelect(), [
            'base' => 'starter-pg-filter starter-pg-filter-select',
            'select' => 'starter-pg-control starter-pg-select',
        ]);
    }

    public function searchBox(): array
    {
        return array_replace(parent::searchBox(), [
            'input' => 'starter-pg-control starter-pg-search',
            'iconSearch' => 'starter-pg-search-icon',
            'iconClose' => 'text-slate-400',
        ]);
    }
}
