<?php

namespace Aldhi88\StarterKit\Themes\Starter;

use PowerComponents\LivewirePowerGrid\Themes\Bootstrap5;

class VuexyPowerGridTheme extends Bootstrap5
{
    public function layout(): array
    {
        return array_replace(parent::layout(), ['table' => 'starter.powergrid.table-base', 'pagination' => 'starter.powergrid.pagination']);
    }

    public function table(): array
    {
        return array_replace_recursive(parent::table(), [
            'layout' => ['base' => 'p-0', 'div' => 'table-responsive', 'table' => 'table table-hover mb-0', 'container' => 'm-0', 'actions' => 'd-flex gap-2'],
            'header' => ['th' => 'text-nowrap'],
            'body' => ['td' => 'align-middle', 'tdFilters' => 'align-middle', 'tdEmpty' => 'text-center py-6', 'tdActionsContainer' => 'd-flex gap-2'],
        ]);
    }

    public function footer(): array
    {
        return array_replace(parent::footer(), [
            'view' => 'starter.powergrid.footer',
            'select' => 'form-select form-select-sm w-auto',
            'footer' => 'starter-pg-footer vuexy-grid-footer',
        ]);
    }

    public function checkbox(): array
    {
        return array_replace(parent::checkbox(), ['th' => 'text-center', 'base' => 'd-flex justify-content-center align-items-center', 'input' => 'form-check-input m-0']);
    }

    public function filterBoolean(): array
    {
        return array_replace(parent::filterBoolean(), [
            'base' => 'vuexy-pg-filter vuexy-pg-filter-boolean',
            'select' => 'form-select form-select-sm',
        ]);
    }

    public function filterDatePicker(): array
    {
        return array_replace(parent::filterDatePicker(), [
            'base' => 'vuexy-pg-filter vuexy-pg-filter-date',
            'input' => 'flatpickr flatpickr-input form-control form-control-sm',
        ]);
    }

    public function filterInputText(): array
    {
        return array_replace(parent::filterInputText(), [
            'base' => 'vuexy-pg-filter vuexy-pg-filter-text',
            'select' => 'form-select form-select-sm',
            'input' => 'form-control form-control-sm',
        ]);
    }

    public function filterMultiSelect(): array
    {
        return array_replace(parent::filterMultiSelect(), [
            'base' => 'vuexy-pg-filter vuexy-pg-filter-multiselect',
            'select' => 'form-select form-select-sm',
        ]);
    }

    public function filterNumber(): array
    {
        return array_replace(parent::filterNumber(), [
            'input' => 'form-control form-control-sm vuexy-pg-filter-number',
        ]);
    }

    public function filterSelect(): array
    {
        return array_replace(parent::filterSelect(), [
            'base' => 'vuexy-pg-filter vuexy-pg-filter-select',
            'select' => 'form-select form-select-sm',
        ]);
    }

    public function searchBox(): array
    {
        return array_replace(parent::searchBox(), [
            'input' => 'form-control form-control-sm',
            'iconSearch' => '',
        ]);
    }
}
