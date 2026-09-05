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
                'base' => 'starter-pg-table min-w-0 align-middle',
                'div' => 'starter-pg-frame',
                'table' => 'w-max min-w-full divide-y divide-slate-100 border-collapse',
                'container' => 'starter-pg-container dashcode-data-table',
                'actions' => 'flex items-center justify-center',
            ],
            'header' => [
                'thead' => 'border-t border-slate-100 bg-slate-100',
                'th' => 'px-3 py-3 text-xs font-semibold uppercase text-slate-600 align-middle',
            ],
            'body' => [
                'tbody' => 'bg-white divide-y divide-slate-100',
                'tr' => '',
                'td' => 'px-3 py-3 text-sm font-normal text-slate-600 align-middle break-words normal-case',
                'tdFilters' => 'starter-pg-filter-cell',
                'tdEmpty' => 'p-6 text-center text-sm text-slate-500 normal-case',
                'tdActionsContainer' => 'flex items-center justify-center',
            ],
        ]);
    }

    public function footer(): array
    {
        return array_replace(parent::footer(), [
            'view' => 'starter.powergrid.footer',
            'select' => 'form-control !py-1',
            'footer' => 'starter-pg-footer',
            'footer_with_pagination' => 'starter-pg-footer-inner starter-pg-footer-layout',
        ]);
    }

    public function checkbox(): array
    {
        return array_replace(parent::checkbox(), [
            'th' => 'text-center align-middle w-[50px] min-w-[48px] max-w-[50px]',
            'base' => 'flex items-center justify-center',
            'input' => 'table-checkbox block mx-auto',
        ]);
    }

    public function filterBoolean(): array
    {
        return array_replace(parent::filterBoolean(), [
            'view' => 'starter.powergrid.filters.boolean',
            'base' => 'starter-pg-filter starter-pg-filter-boolean',
            'select' => 'form-control w-full !py-1',
        ]);
    }

    public function filterDatePicker(): array
    {
        return array_replace(parent::filterDatePicker(), [
            'base' => 'starter-pg-filter starter-pg-filter-date',
            'input' => 'flatpickr flatpickr-input form-control w-full !py-1',
        ]);
    }

    public function filterInputText(): array
    {
        return array_replace(parent::filterInputText(), [
            'base' => 'starter-pg-filter starter-pg-filter-text',
            'select' => 'form-control w-full !py-1',
            'input' => 'form-control w-full !py-1',
        ]);
    }

    public function filterMultiSelect(): array
    {
        return array_replace(parent::filterMultiSelect(), [
            'base' => 'starter-pg-filter starter-pg-filter-multiselect',
            'select' => 'form-control w-full !py-1',
        ]);
    }

    public function filterNumber(): array
    {
        return array_replace(parent::filterNumber(), [
            'input' => 'form-control w-full !py-1 starter-pg-filter-number',
        ]);
    }

    public function filterSelect(): array
    {
        return array_replace(parent::filterSelect(), [
            'view' => 'starter.powergrid.filters.select',
            'base' => 'starter-pg-filter starter-pg-filter-select',
            'select' => 'form-control w-full !py-1',
        ]);
    }

    public function searchBox(): array
    {
        return array_replace(parent::searchBox(), [
            'input' => 'form-control starter-pg-search',
            'iconSearch' => 'starter-pg-search-icon',
            'iconClose' => 'text-slate-400',
        ]);
    }
}
