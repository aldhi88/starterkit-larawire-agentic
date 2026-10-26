<?php

namespace Aldhi88\StarterKit\Livewire\Cms;

use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts::app')]
class ContentIndex extends Component
{
    #[Locked]
    public string $resource = 'posts';

    private CmsManager $manager;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(CmsManager $manager, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->manager = $manager;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(string $resource): void
    {
        $this->resource = $resource;
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::from($resource));
    }

    public function render(): View
    {
        $resource = CmsResource::from($this->resource);
        $this->manager->authorize($this->authenticatedLogins->current(), $resource);

        return view(StarterTheme::viewName('starter.cms.index'), ['cmsResource' => $resource])
            ->title($resource->label());
    }
}
