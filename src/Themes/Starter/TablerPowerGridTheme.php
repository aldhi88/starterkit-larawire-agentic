<?php

namespace Altekno\StarterKit\Themes\Starter;

use PowerComponents\LivewirePowerGrid\Themes\Bootstrap5;

class TablerPowerGridTheme extends Bootstrap5
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
                'base' => 'p-0 align-middle d-block',
                'div' => 'table-responsive m-0',
                'table' => 'table table-vcenter table-hover card-table border-top mb-0',
                'container' => 'm-0',
                'actions' => 'd-flex align-items-center justify-content-end gap-1',
            ],
            'header' => [
                'th' => 'bg-surface-secondary text-secondary text-nowrap small px-3 py-2',
            ],
            'body' => [
                'td' => 'align-middle px-3 py-2',
                'tdFilters' => 'px-3 py-2',
                'tdEmpty' => 'p-4 text-secondary text-center',
                'tdActionsContainer' => 'd-flex align-items-center justify-content-end gap-1',
            ],
        ]);
    }

    public function footer(): array
    {
        return array_replace(parent::footer(), [
            'view' => 'starter.powergrid.footer',
            'select' => 'form-select form-select-sm w-auto',
            'footer' => 'starter-pg-footer-layout border-top px-3 py-2 w-100',
        ]);
    }

    public function checkbox(): array
    {
        return array_replace(parent::checkbox(), [
            'th' => 'fs-6 text-center text-nowrap',
            'base' => 'm-0 d-flex align-items-center justify-content-center',
            'input' => 'form-check-input m-0 align-middle',
        ]);
    }

    public function filterBoolean(): array
    {
        return array_replace(parent::filterBoolean(), [
            'base' => 'starter-pg-filter starter-pg-filter-boolean',
        ]);
    }

    public function filterInputText(): array
    {
        return array_replace(parent::filterInputText(), [
            'base' => 'starter-pg-filter starter-pg-filter-text',
        ]);
    }

    public function filterMultiSelect(): array
    {
        return array_replace(parent::filterMultiSelect(), [
            'base' => 'starter-pg-filter starter-pg-filter-multiselect',
        ]);
    }

    public function filterNumber(): array
    {
        return array_replace(parent::filterNumber(), [
            'base' => 'starter-pg-filter starter-pg-filter-number',
        ]);
    }

    public function filterSelect(): array
    {
        return array_replace(parent::filterSelect(), [
            'base' => 'starter-pg-filter starter-pg-filter-select',
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
