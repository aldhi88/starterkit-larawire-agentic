<?php

namespace Aldhi88\StarterKit\Livewire\Cms;

use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Cms\CmsCategoryTree;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Cms\CmsTime;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
class ContentForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $resource = 'posts';

    #[Locked]
    public ?int $contentId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public string $publishedAtDisplay = '';

    public string $newCategoryName = '';

    public string $newCategoryParentId = '';

    public ?TemporaryUploadedFile $upload = null;

    public string $selectedMediaId = '';

    public string $mediaSearch = '';

    /** @var list<TemporaryUploadedFile> */
    public array $editorUploads = [];

    /** @var list<array{name: string, message: string}> */
    #[Locked]
    public array $editorUploadErrors = [];

    /** @var list<array{id:int,name:string,url:string,mime_type:string}> */
    #[Locked]
    public array $mediaChoices = [];

    /** @var array{categories:array,contents:array,menuCategories:array} */
    #[Locked]
    public array $referenceChoices = ['categories' => [], 'contents' => [], 'menuCategories' => []];

    private CmsManager $manager;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(CmsManager $manager, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->manager = $manager;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(string $resource, ?int $contentId = null): void
    {
        $this->resource = $resource;
        $this->contentId = $contentId;
        $editor = $this->manager->editor($this->authenticatedLogins->current(), CmsResource::from($resource), $contentId);
        $this->form = $editor['data'];
        if ($resource === 'posts' && StarterTheme::key() === 'tabler') {
            $this->form['category_ids'] = CmsCategoryTree::forSelection($this->form['category_ids'] ?? [], $editor['categories']);
            $this->publishedAtDisplay = CmsTime::forMaskedInput($this->form['published_at'] ?? null);
        } else {
            unset($this->form['category_ids']);
        }
        $this->mediaChoices = $editor['media'];
        $this->referenceChoices = [
            'categories' => $editor['categories'],
            'contents' => $editor['contents'],
            'menuCategories' => $editor['menuCategories'],
        ];
    }

    public function save(): mixed
    {
        $this->resetErrorBag();
        $resource = CmsResource::from($this->resource);

        try {
            $form = $this->form;
            if ($resource === CmsResource::Posts) {
                $form['published_at'] = StarterTheme::key() === 'tabler'
                    ? CmsTime::fromMaskedInput($this->publishedAtDisplay)
                    : CmsTime::fromInput($form['published_at'] ?? null);
            }
            if ($resource === CmsResource::Posts && StarterTheme::key() === 'tabler' && is_array($form['category_ids'] ?? null)) {
                $form['category_ids'] = CmsCategoryTree::forSelection($form['category_ids'], $this->referenceChoices['categories']);
            }
            $this->manager->save($this->authenticatedLogins->current(), $resource, $form, $this->contentId, $this->upload);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'upload' ? 'upload' : 'form.'.$field, $message);
                }
            }

            $this->dispatch('starter-toast', type: 'danger', message: 'Periksa kembali kolom yang ditandai.');

            return null;
        }

        session()->flash('starter-toast', ['type' => 'success', 'message' => $resource->singular().' berhasil disimpan.']);

        return $this->redirectRoute($resource->routePrefix().'.index', navigate: true);
    }

    public function createPostCategory(): void
    {
        abort_unless($this->resource === 'posts' && StarterTheme::key() === 'tabler', 422);
        $this->resetErrorBag('newCategoryName');
        $this->resetErrorBag('newCategoryParentId');
        try {
            $category = $this->manager->saveCategoryName(
                $this->authenticatedLogins->current(), CmsResource::PostCategories, $this->newCategoryName,
                parentId: $this->newCategoryParentId !== '' ? (int) $this->newCategoryParentId : null,
                updateParent: true,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'parent_id' ? 'newCategoryParentId' : 'newCategoryName', $message);
                }
            }

            return;
        }
        $this->referenceChoices['categories'][] = [
            'id' => $category->getKey(), 'name' => $category->getAttribute('name'),
            'parent_id' => $category->getAttribute('parent_id'),
        ];
        $this->form['category_ids'][] = (int) $category->getKey();
        $this->form['category_ids'] = CmsCategoryTree::forSelection($this->form['category_ids'], $this->referenceChoices['categories']);
        $this->newCategoryName = '';
        $this->newCategoryParentId = '';
        $this->dispatch('starter-toast', type: 'success', message: 'Kategori dibuat dan dipilih.');
    }

    #[Renderless]
    public function openMediaPicker(string $type = 'all', string $purpose = 'editor'): void
    {
        $this->authorizeEditor();
        abort_unless(in_array($type, ['all', 'image', 'pdf'], true) && in_array($purpose, ['editor', 'thumbnail'], true), 422);
        $this->dispatch('cms-open-media-picker', componentId: $this->getId(), type: $type, purpose: $purpose);
    }

    public function finishEditorUploads(): void
    {
        $this->authorizeEditor();
        try {
            $result = $this->manager->uploadMediaBatch($this->authenticatedLogins->current(), $this->editorUploads, forEditor: true);
        } catch (ValidationException $exception) {
            $this->addError('editorUploads', (string) collect($exception->errors())->flatten()->first());

            return;
        }
        $this->editorUploadErrors = $result['failed'];
        $this->editorUploads = [];
        foreach ($result['uploaded'] as $media) {
            $this->dispatch('cms-editor-insert', componentId: $this->getId(), media: $media);
        }
        if ($result['uploaded'] !== []) {
            $this->dispatch('starter-toast', type: 'success', message: count($result['uploaded']).' media diunggah dan disisipkan.');
        }
    }

    private function authorizeEditor(): void
    {
        $resource = CmsResource::from($this->resource);
        abort_unless($resource->isContent(), 422);
        $this->manager->authorize($this->authenticatedLogins->current(), $resource);
    }

    public function addGalleryMedia(): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::from($this->resource));
        abort_unless($this->resource === 'galleries', 422);
        $id = filter_var($this->selectedMediaId, FILTER_VALIDATE_INT);

        if ($id && ! in_array($id, array_map('intval', $this->form['media_ids'] ?? []), true)) {
            $this->form['media_ids'][] = $id;
        }

        $this->selectedMediaId = '';
    }

    public function updatedMediaSearch(): void
    {
        $resource = CmsResource::from($this->resource);
        abort_unless($resource->isContent() || $resource === CmsResource::Galleries, 422);
        $ids = array_filter([
            $this->form['image_id'] ?? null, $this->form['thumbnail_id'] ?? null,
            $this->form['logo_id'] ?? null, ...($this->form['media_ids'] ?? []),
        ]);
        $this->mediaChoices = $this->manager->mediaOptions($this->authenticatedLogins->current(), $this->mediaSearch, array_map('intval', $ids))->all();
    }

    public function removeGalleryMedia(int $index): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::from($this->resource));
        abort_unless($this->resource === 'galleries', 422);
        unset($this->form['media_ids'][$index]);
        $this->form['media_ids'] = array_values($this->form['media_ids']);
    }

    public function moveGalleryMedia(int $index, int $direction): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::from($this->resource));
        abort_unless($this->resource === 'galleries' && in_array($direction, [-1, 1], true), 422);
        $next = $index + $direction;

        if (isset($this->form['media_ids'][$index], $this->form['media_ids'][$next])) {
            [$this->form['media_ids'][$index], $this->form['media_ids'][$next]] = [$this->form['media_ids'][$next], $this->form['media_ids'][$index]];
        }
    }

    public function addMenuItem(): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::from($this->resource));
        abort_unless($this->resource === 'menus', 422);

        if (count($this->form['items'] ?? []) < 100) {
            $this->form['items'][] = ['key' => (string) Str::uuid(), 'label' => '', 'url' => '', 'parent_key' => ''];
        }
    }

    public function removeMenuItem(int $index): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::from($this->resource));
        abort_unless($this->resource === 'menus', 422);
        $removed = $this->form['items'][$index]['key'] ?? null;
        unset($this->form['items'][$index]);
        $this->form['items'] = array_values($this->form['items']);

        foreach ($this->form['items'] as &$item) {
            if (($item['parent_key'] ?? null) === $removed) {
                $item['parent_key'] = '';
            }
        }
    }

    public function moveMenuItem(int $index, int $direction): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::from($this->resource));
        abort_unless($this->resource === 'menus' && in_array($direction, [-1, 1], true), 422);
        $next = $index + $direction;

        if (isset($this->form['items'][$index], $this->form['items'][$next])) {
            [$this->form['items'][$index], $this->form['items'][$next]] = [$this->form['items'][$next], $this->form['items'][$index]];
        }
    }

    /** @param list<array{key: string, parent_key: ?string}> $order */
    public function reorderMenuItems(array $order): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::Menus);
        abort_unless($this->resource === 'menus', 422);
        $valid = Validator::make(['order' => $order], [
            'order' => ['array', 'max:100'],
            'order.*.key' => ['required', 'string', 'distinct'],
            'order.*.parent_key' => ['nullable', 'string'],
        ])->validate()['order'];
        $items = collect($this->form['items'] ?? [])->keyBy('key');
        if (count($valid) !== $items->count() || collect($valid)->pluck('key')->diff($items->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['form.items' => 'Daftar menu berubah. Muat ulang urutan sebelum melanjutkan.']);
        }
        $parents = collect($valid)->mapWithKeys(fn (array $item): array => [$item['key'] => filled($item['parent_key']) ? $item['parent_key'] : null])->all();
        foreach ($parents as $key => $parent) {
            $seen = [$key];
            while ($parent !== null) {
                if (! array_key_exists($parent, $parents) || in_array($parent, $seen, true) || count($seen) >= 3) {
                    $this->dispatch('cms-menu-reset', componentId: $this->getId());
                    throw ValidationException::withMessages(['form.items' => 'Menu dapat memiliki maksimal tiga tingkat dan tidak boleh berputar ke induknya sendiri.']);
                }
                $seen[] = $parent;
                $parent = $parents[$parent];
            }
        }
        $this->form['items'] = array_map(fn (array $item): array => [...$items[$item['key']], 'parent_key' => $parents[$item['key']] ?? ''], $valid);
        $this->resetErrorBag('form.items');
    }

    public function moveMenuSibling(int $index, int $direction): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::Menus);
        abort_unless($this->resource === 'menus' && in_array($direction, [-1, 1], true), 422);
        $items = $this->form['items'] ?? [];
        if (! isset($items[$index])) {
            return;
        }
        $parent = $items[$index]['parent_key'] ?? '';
        $siblings = array_keys(array_filter($items, fn (array $item): bool => ($item['parent_key'] ?? '') === $parent));
        $position = array_search($index, $siblings, true);
        $other = $siblings[$position + $direction] ?? null;
        if ($other !== null) {
            [$items[$index], $items[$other]] = [$items[$other], $items[$index]];
            $this->form['items'] = array_values($items);
        }
    }

    public function changeMenuParent(int $index, string $parentKey): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::Menus);
        abort_unless($this->resource === 'menus', 422);
        $items = $this->form['items'] ?? [];
        abort_unless(isset($items[$index]), 422);
        $order = array_map(fn (array $item): array => ['key' => $item['key'], 'parent_key' => $item['parent_key'] ?? ''], $items);
        $order[$index]['parent_key'] = $parentKey;
        $this->form['items'][$index]['parent_key'] = $parentKey;
        $this->reorderMenuItems($order);
    }

    /** @return list<array<string, mixed>> */
    private function menuTree(): array
    {
        $items = $this->form['items'] ?? [];
        $known = array_column($items, 'key');
        $parentOptions = $this->menuParentOptions($items);
        $decorate = static function (array $item, int $index, array $children) use ($parentOptions, $items): array {
            $choices = $parentOptions[$item['key']] ?? [];
            $invalidParent = filled($item['parent_key'] ?? null) && ! in_array($item['parent_key'], array_column($choices, 'key'), true);

            return [...$item, 'index' => $index, 'children' => $children, 'parent_options' => $choices,
                'invalid_parent' => $invalidParent, 'invalid_parent_label' => collect($items)->firstWhere('key', $item['parent_key'] ?? '')['label'] ?? $item['parent_key'] ?? ''];
        };
        $seen = [];
        $build = function (?string $parent, int $depth) use (&$build, &$seen, $items, $known, $decorate): array {
            if ($depth > 3) {
                return [];
            }
            $nodes = [];
            foreach ($items as $index => $item) {
                $key = (string) ($item['key'] ?? '');
                $itemParent = filled($item['parent_key'] ?? null) && in_array($item['parent_key'], $known, true) ? $item['parent_key'] : null;
                if ($key === '' || isset($seen[$key]) || $itemParent !== $parent) {
                    continue;
                }
                $seen[$key] = true;
                $nodes[] = $decorate($item, $index, $build($key, $depth + 1));
            }

            return $nodes;
        };
        $tree = $build(null, 1);
        foreach ($items as $index => $item) {
            if (is_array($item) && is_string($item['key'] ?? null) && ! isset($seen[$item['key']])) {
                $tree[] = $decorate($item, $index, []);
            }
        }

        return $tree;
    }

    /** @param array<int, array<string, mixed>> $items @return array<string, list<array{key: string, label: string}>> */
    private function menuParentOptions(array $items): array
    {
        $byKey = collect($items)->keyBy('key')->all();
        $height = function (string $key, array $seen = []) use (&$height, $items): int {
            if (in_array($key, $seen, true) || count($seen) >= 3) {
                return 4;
            }
            $seen[] = $key;
            $levels = 1;
            foreach ($items as $item) {
                if (($item['parent_key'] ?? '') === $key) {
                    $levels = max($levels, 1 + $height($item['key'], $seen));
                }
            }

            return $levels;
        };
        $options = [];
        foreach ($items as $item) {
            $key = $item['key'];
            $subtreeLevels = $height($key);
            $options[$key] = [];
            foreach ($items as $candidate) {
                $parent = $candidate['key'];
                $seen = [$key];
                $levels = 0;
                while (filled($parent)) {
                    if (in_array($parent, $seen, true) || ! isset($byKey[$parent])) {
                        $levels = 4;
                        break;
                    }
                    $seen[] = $parent;
                    $levels++;
                    $parent = $byKey[$parent]['parent_key'] ?? '';
                }
                if ($levels + $subtreeLevels <= 3) {
                    $options[$key][] = ['key' => $candidate['key'], 'label' => $candidate['label'] ?? ''];
                }
            }
        }

        return $options;
    }

    public function render(): View
    {
        $resource = CmsResource::from($this->resource);
        $this->manager->authorize($this->authenticatedLogins->current(), $resource);
        $options = [...$this->referenceChoices, 'media' => $this->mediaChoices];

        return view(StarterTheme::viewName('starter.cms.form'), [
            'cmsResource' => $resource,
            'options' => $options,
            'isCreating' => $this->contentId === null,
            'menuTree' => $resource === CmsResource::Menus ? $this->menuTree() : [],
            'postCategoryTree' => $resource === CmsResource::Posts && StarterTheme::key() === 'tabler' ? CmsCategoryTree::flatten($options['categories']) : [],
        ])->title(($this->contentId === null ? 'Tambah ' : 'Edit ').$resource->singular());
    }
}
