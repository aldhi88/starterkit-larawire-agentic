<?php

namespace Aldhi88\StarterKit\Livewire\Cms;

use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::landing')]
class Documentation extends Component
{
    public function render(): View
    {
        return view(StarterTheme::viewName('starter.cms.documentation'))->title('Panduan Penggunaan CMS');
    }
}
