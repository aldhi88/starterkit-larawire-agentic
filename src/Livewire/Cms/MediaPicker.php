<?php

namespace Aldhi88\StarterKit\Livewire\Cms;

use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MediaPicker extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $ownerId = '';

    #[Locked]
    public string $resource = 'posts';

    #[Locked]
    public bool $mediaPickerOpen = false;

    #[Locked]
    public string $purpose = 'editor';

    public string $pickerSearch = '';

    public string $pickerType = 'all';

    #[Locked]
    public int $pickerPage = 1;

    #[Locked]
    public int $pickerLastPage = 1;

    /** @var list<array<string, mixed>> */
    #[Locked]
    public array $pickerItems = [];

    /** @var list<int|string> */
    public array $selectedMediaIds = [];

    /** @var list<TemporaryUploadedFile> */
    public array $editorUploads = [];

    /** @var list<array{name: string, message: string}> */
    #[Locked]
    public array $editorUploadErrors = [];

    private CmsManager $manager;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(CmsManager $manager, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->manager = $manager;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(string $ownerId, string $resource): void
    {
        $this->ownerId = $ownerId;
        $this->resource = $resource;
        $this->authorizeEditor();
    }

    #[On('cms-open-media-picker')]
    public function openMediaPicker(string $componentId, string $type = 'all', string $purpose = 'editor'): void
    {
        if ($componentId !== $this->ownerId) {
            return;
        }
        $this->authorizeEditor();
        abort_unless(in_array($type, ['all', 'image', 'pdf'], true) && in_array($purpose, ['editor', 'thumbnail'], true), 422);
        $this->purpose = $purpose;
        $this->pickerType = $purpose === 'thumbnail' ? 'image' : $type;
        $this->pickerSearch = '';
        $this->pickerPage = 1;
        $this->selectedMediaIds = [];
        $this->editorUploads = [];
        $this->editorUploadErrors = [];
        $this->resetErrorBag();
        $this->mediaPickerOpen = true;
        $this->loadPicker();
    }

    public function closeMediaPicker(): void
    {
        $this->authorizeEditor();
        $this->mediaPickerOpen = false;
        $this->editorUploads = [];
        $this->selectedMediaIds = [];
        $this->editorUploadErrors = [];
        $this->resetErrorBag();
        $this->dispatch('cms-editor-picker-closed', componentId: $this->ownerId, purpose: $this->purpose);
    }

    public function updatedPickerSearch(): void
    {
        $this->pickerPage = 1;
        $this->loadPicker();
    }

    public function updatedPickerType(): void
    {
        $this->pickerPage = 1;
        $this->loadPicker();
    }

    public function changePickerPage(int $page): void
    {
        $this->pickerPage = max(1, min($this->pickerLastPage, $page));
        $this->loadPicker();
    }

    public function resetPickerView(): void
    {
        $this->authorizeEditor();
        abort_unless($this->mediaPickerOpen, 422);
        $this->pickerSearch = '';
        $this->pickerType = $this->purpose === 'thumbnail' ? 'image' : 'all';
        $this->pickerPage = 1;
        $this->loadPicker();
    }

    public function finishEditorUploads(): void
    {
        $this->authorizeEditor();
        abort_unless($this->mediaPickerOpen, 422);
        $this->resetErrorBag('editorUploads');
        try {
            $result = $this->manager->uploadMediaBatch($this->authenticatedLogins->current(), $this->editorUploads, forEditor: true);
        } catch (ValidationException $exception) {
            $this->addError('editorUploads', (string) collect($exception->errors())->flatten()->first());

            return;
        }
        $this->editorUploadErrors = $result['failed'];
        $this->editorUploads = [];
        $eligible = array_values(array_filter($result['uploaded'], fn (array $media): bool => $this->purpose !== 'thumbnail' || str_starts_with($media['mime_type'], 'image/')));
        $uploadedIds = array_column($eligible, 'id');
        $this->selectedMediaIds = $this->purpose === 'thumbnail'
            ? array_slice($uploadedIds, 0, 1)
            : array_slice(array_unique([...$this->selectedMediaIds, ...$uploadedIds]), 0, 20);
        $this->pickerSearch = '';
        $this->pickerPage = 1;
        $this->loadPicker();
        if ($result['uploaded'] !== []) {
            $nextAction = $this->purpose === 'thumbnail' ? 'Gunakan Thumbnail' : 'Sisipkan Media Terpilih';
            $this->dispatch('starter-toast', type: 'success', message: count($result['uploaded']).' file diunggah dan dipilih. Klik '.$nextAction.' untuk melanjutkan.');
        }
    }

    public function insertSelectedMedia(): void
    {
        $this->authorizeEditor();
        abort_unless($this->mediaPickerOpen, 422);
        $this->resetErrorBag('selectedMediaIds');
        $validated = Validator::make(['selectedMediaIds' => $this->selectedMediaIds], [
            'selectedMediaIds' => ['required', 'array', 'min:1', 'max:'.($this->purpose === 'thumbnail' ? 1 : 20)],
            'selectedMediaIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ], [], ['selectedMediaIds' => 'pilihan media'])->validate();
        $ids = array_map('intval', $validated['selectedMediaIds']);
        $media = $this->manager->mediaOptions($this->authenticatedLogins->current(), '', $ids)->keyBy('id');
        foreach ($ids as $id) {
            $item = $media->get($id);
            if (! $item || ($this->purpose === 'thumbnail' && ! str_starts_with($item['mime_type'], 'image/'))) {
                throw ValidationException::withMessages(['selectedMediaIds' => 'Media tidak tersedia. Pilih kembali gambar atau file yang aktif.']);
            }
        }
        foreach ($ids as $id) {
            $this->dispatch($this->purpose === 'thumbnail' ? 'cms-thumbnail-selected' : 'cms-editor-insert', componentId: $this->ownerId, media: $media->get($id));
        }
        $this->closeMediaPicker();
    }

    public function render(): View
    {
        $this->authorizeEditor();

        return view(StarterTheme::viewName('starter.cms.media-picker'));
    }

    private function authorizeEditor(): void
    {
        $resource = CmsResource::from($this->resource);
        abort_unless($resource->isContent(), 422);
        $this->manager->authorize($this->authenticatedLogins->current(), $resource);
    }

    private function loadPicker(): void
    {
        $this->authorizeEditor();
        $this->pickerSearch = mb_substr(trim($this->pickerSearch), 0, 100);
        Validator::make(['pickerType' => $this->pickerType], ['pickerType' => [Rule::in($this->purpose === 'thumbnail' ? ['image'] : ['all', 'image', 'pdf'])]])->validate();
        $page = $this->manager->mediaLibrary($this->authenticatedLogins->current(), $this->pickerSearch, $this->pickerType, 'active', 24, $this->pickerPage, forEditor: true);
        $this->manager->browseMediaPicker($this->authenticatedLogins->current());
        $this->pickerItems = array_values($page->items());
        $this->pickerLastPage = max(1, $page->lastPage());
    }
}
