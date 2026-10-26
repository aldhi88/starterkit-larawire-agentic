<?php

namespace Aldhi88\StarterKit\Livewire\Cms;

use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Dashboard extends Component
{
    private CmsManager $manager;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(CmsManager $manager, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->manager = $manager;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(): void
    {
        $this->manager->browseDashboard($this->authenticatedLogins->current());
    }

    public function render(): View
    {
        return view(StarterTheme::viewName('starter.cms.dashboard'), [
            'summary' => $this->manager->dashboard($this->authenticatedLogins->current()),
        ])->title('Beranda CMS');
    }
}
