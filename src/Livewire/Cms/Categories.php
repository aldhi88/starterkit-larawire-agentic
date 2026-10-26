<?php

namespace Aldhi88\StarterKit\Livewire\Cms;

use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Cms\CmsCategoryTree;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts::app')]
class Categories extends Component
{
    #[Locked]
    public string $resource = 'post_categories';

    #[Locked]
    public ?int $contentId = null;

    public string $name = '';

    public string $parentId = '';

    #[Locked]
    public string $mappingCode = '';

    private CmsManager $manager;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(CmsManager $manager, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->manager = $manager;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(string $resource, ?int $contentId = null): void
    {
        $kind = CmsResource::from($resource);
        abort_unless($kind->isCategory() || $kind === CmsResource::MenuCategories, 404);
        $this->resource = $resource;
        $this->manager->authorize($this->authenticatedLogins->current(), $kind);
        if ($contentId !== null) {
            $this->editCategory($resource, $contentId);
        }
    }

    #[On('cms-edit-category')]
    public function editCategory(string $resource, int $id): void
    {
        abort_unless($resource === $this->resource, 403);
        $editor = $this->manager->editor($this->authenticatedLogins->current(), CmsResource::from($resource), $id);
        $this->contentId = $id;
        $this->name = (string) $editor['data']['name'];
        $this->parentId = (string) ($editor['data']['parent_id'] ?? '');
        $this->mappingCode = (string) $editor['data']['code'];
        $this->resetErrorBag();
        $this->dispatch('cms-category-editing');
    }

    public function cancelEditing(): void
    {
        $this->contentId = null;
        $this->name = '';
        $this->parentId = '';
        $this->mappingCode = '';
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->resetErrorBag();
        try {
            $hierarchy = $this->resource === 'post_categories' && StarterTheme::key() === 'tabler';
            $this->manager->saveCategoryName($this->authenticatedLogins->current(), CmsResource::from($this->resource), $this->name, $this->contentId, $hierarchy && $this->parentId !== '' ? (int) $this->parentId : null, $hierarchy);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'code' ? 'name' : $field, $message);
                }
            }

            return;
        }
        $this->cancelEditing();
        $this->dispatch('pg:eventRefresh-cms-'.$this->resource.'-table');
        $this->dispatch('starter-toast', type: 'success', message: 'Kategori berhasil disimpan.');
    }

    public function render(): View
    {
        $resource = CmsResource::from($this->resource);
        $this->manager->authorize($this->authenticatedLogins->current(), $resource);

        $postCategoryTree = $resource === CmsResource::PostCategories && StarterTheme::key() === 'tabler'
            ? CmsCategoryTree::flatten($this->manager->options($this->authenticatedLogins->current(), $resource)['categories']) : [];

        return view(StarterTheme::viewName('starter.cms.categories'), ['cmsResource' => $resource, 'postCategoryTree' => $postCategoryTree])->title($resource->label());
    }
}
