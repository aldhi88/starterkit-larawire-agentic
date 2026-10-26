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
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class MediaLibrary extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
    public string $mode = 'upload';

    #[Url(as: 'mediaSearch')]
    public string $search = '';

    #[Url(as: 'mediaType')]
    public string $type = 'all';

    #[Url]
    public string $archiveStatus = 'active';

    #[Url]
    public int $perPage = 24;

    /** @var list<TemporaryUploadedFile> */
    public array $uploads = [];

    /** @var list<array{name:string,message:string}> */
    #[Locked]
    public array $uploadFailures = [];

    #[Locked]
    public int $uploadedCount = 0;

    /** @var list<int|string> */
    public array $selected = [];

    #[Locked]
    public ?int $detailId = null;

    /** @var array<string,mixed> */
    public array $detail = [];

    /** @var array<string,mixed> */
    #[Locked]
    public array $detailMeta = [];

    public ?TemporaryUploadedFile $replacement = null;

    #[Locked]
    public ?string $pendingAction = null;

    /** @var list<int> */
    #[Locked]
    public array $pendingIds = [];

    /** @var array<string,mixed> */
    #[Locked]
    public array $pendingFilters = [];

    #[Locked]
    public string $pendingArchive = 'active';

    #[Locked]
    public int $pendingCount = 0;

    #[Locked]
    public bool $pendingFiltered = false;

    public string $confirmation = '';

    #[Locked]
    public string $lastBrowseState = '';

    /** @var array<string,mixed> */
    #[Locked]
    public array $lastValidFilters = [];

    private bool $invalidState = false;

    private CmsManager $manager;

    private AuthenticatedLoginService $authenticatedLogins;

    public function boot(CmsManager $manager, AuthenticatedLoginService $authenticatedLogins): void
    {
        $this->manager = $manager;
        $this->authenticatedLogins = $authenticatedLogins;
    }

    public function mount(string $resource = 'media', string $mode = 'upload', ?int $contentId = null): void
    {
        abort_unless(in_array($mode, ['upload', 'gallery'], true), 404);
        $this->mode = $mode;
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::Media);
        $this->normalizeFilters();
        if ($contentId !== null) {
            $this->openDetails($contentId);
        }
    }

    public function updated(string $name, mixed $value): void
    {
        if (in_array($name, ['search', 'type', 'archiveStatus', 'perPage'], true)) {
            $this->normalizeFilters();
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function updatedUploads(): void
    {
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::Media);
        $this->resetErrorBag('uploads');
        $this->uploadedCount = 0;
        $this->uploadFailures = [];
        try {
            $result = $this->manager->uploadMediaBatch($this->authenticatedLogins->current(), $this->uploads);
        } catch (ValidationException $exception) {
            $this->addError('uploads', (string) collect($exception->errors())->flatten()->first());
            $this->uploads = [];

            return;
        }
        $this->uploadedCount = count($result['uploaded']);
        $this->uploadFailures = $result['failed'];
        $this->uploads = [];
        $this->resetPage();
        $this->selected = [];
        if ($this->uploadedCount > 0) {
            $this->dispatch('starter-toast', type: 'success', message: $this->uploadedCount.' file berhasil ditambahkan.');
        }
    }

    public function openDetails(int $id): void
    {
        $editor = $this->manager->editor($this->authenticatedLogins->current(), CmsResource::Media, $id);
        $this->detailId = $id;
        $this->detail = array_intersect_key($editor['data'], array_flip(['name', 'alt_text', 'caption', 'status']));
        $this->detailMeta = array_intersect_key($editor['data'], array_flip(['url', 'mime_type', 'size', 'original_name']));
        $this->replacement = null;
        $this->resetErrorBag();
    }

    public function closeDetails(): void
    {
        $this->detailId = null;
        $this->detail = [];
        $this->detailMeta = [];
        $this->replacement = null;
        $this->resetErrorBag();
    }

    public function saveDetails(): void
    {
        abort_unless($this->detailId !== null, 422);
        $this->resetErrorBag();
        try {
            $this->manager->save($this->authenticatedLogins->current(), CmsResource::Media, $this->detail, $this->detailId, $this->replacement);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'upload' ? 'replacement' : 'detail.'.$field, $message);
                }
            }

            return;
        }
        $this->closeDetails();
        $this->resetPage();
        $this->selected = [];
        $this->dispatch('starter-toast', type: 'success', message: 'Detail media berhasil disimpan.');
    }

    public function selectPage(): void
    {
        if (! $this->normalizeFilters()) {
            return;
        }
        $page = $this->manager->mediaLibrary($this->authenticatedLogins->current(), $this->search, $this->type, $this->archiveStatus, $this->perPage, $this->getPage());
        $this->selected = collect($page->items())->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function goToMediaPage(int $page): void
    {
        abort_unless($page >= 1 && $page <= 1000000, 422);
        $this->setPage($page);
        $this->selected = [];
    }

    public function prepareAction(string $action, bool $filtered = false, ?int $id = null): void
    {
        if (! $this->normalizeFilters()) {
            return;
        }
        $this->manager->authorize($this->authenticatedLogins->current(), CmsResource::Media);
        Validator::make(['action' => $action, 'ids' => $id !== null ? [$id] : $this->selected], [
            'action' => ['required', Rule::in(['archive', 'restore', 'delete_permanently'])],
            'ids' => ['array', 'max:1000'], 'ids.*' => ['integer', 'min:1', 'distinct'],
        ])->validate();
        $this->pendingIds = $filtered ? [] : array_map('intval', $id !== null ? [$id] : $this->selected);
        $this->pendingFilters = $filtered ? ['search' => $this->search, 'media_type' => $this->type] : [];
        $this->pendingArchive = $this->archiveStatus;
        $this->pendingFiltered = $filtered;
        $this->pendingCount = $filtered
            ? $this->manager->countFiltered($this->authenticatedLogins->current(), CmsResource::Media, $this->pendingFilters, $this->pendingArchive)
            : count($this->pendingIds);
        $this->pendingAction = $this->pendingCount > 0 ? $action : null;
        $this->confirmation = '';
        $this->closeDetails();
        if ($this->pendingCount === 0) {
            $this->dispatch('starter-toast', type: 'warning', message: 'Tidak ada media yang dipilih.');
        }
    }

    public function cancelPendingAction(): void
    {
        $this->pendingAction = null;
        $this->pendingIds = [];
        $this->pendingFilters = [];
        $this->pendingCount = 0;
        $this->confirmation = '';
        $this->resetErrorBag('lifecycle');
    }

    public function executePendingAction(): void
    {
        if (! $this->normalizeFilters() || $this->pendingAction === null) {
            return;
        }
        if ($this->pendingAction === 'delete_permanently' && $this->confirmation !== 'HAPUS') {
            $this->addError('lifecycle', 'Ketik HAPUS untuk mengonfirmasi penghapusan permanen.');

            return;
        }
        try {
            $count = $this->pendingFiltered
                ? $this->manager->lifecycleFiltered($this->authenticatedLogins->current(), CmsResource::Media, $this->pendingAction, $this->pendingFilters, $this->pendingArchive, $this->pendingCount)
                : $this->manager->lifecycle($this->authenticatedLogins->current(), CmsResource::Media, $this->pendingAction, $this->pendingIds);
        } catch (ValidationException $exception) {
            $this->addError('lifecycle', (string) collect($exception->errors())->flatten()->first());

            return;
        }
        $this->cancelPendingAction();
        $this->selected = [];
        $this->resetPage();
        $this->dispatch('starter-toast', type: 'success', message: $count.' media berhasil diproses.');
    }

    public function dehydrate(): void
    {
        $state = hash('sha256', (string) json_encode([$this->search, $this->type, $this->archiveStatus, $this->perPage, $this->getPage()]));
        if ($state !== $this->lastBrowseState) {
            $this->manager->browse($this->authenticatedLogins->current(), CmsResource::Media, ['archive' => $this->archiveStatus, 'perPage' => $this->perPage, 'page' => $this->getPage()]);
            $this->lastBrowseState = $state;
        }
    }

    public function render(): View
    {
        $this->normalizeFilters();
        $media = $this->manager->mediaLibrary($this->authenticatedLogins->current(), $this->search, $this->type, $this->archiveStatus, $this->perPage, $this->getPage());

        return view(StarterTheme::viewName('starter.cms.media-library'), ['media' => $media])->title($this->mode === 'gallery' ? 'Galeri' : 'Pustaka Media');
    }

    private function normalizeFilters(): bool
    {
        $this->search = mb_substr(trim($this->search), 0, 100);
        $state = ['search' => $this->search, 'type' => $this->type, 'archiveStatus' => $this->archiveStatus, 'perPage' => $this->perPage];
        $validator = Validator::make($state, ['search' => ['string', 'max:100'], 'type' => [Rule::in(['all', 'image', 'pdf'])], 'archiveStatus' => [Rule::in(['active', 'archived', 'all'])], 'perPage' => ['integer', Rule::in([24, 48, 96])]]);
        if ($validator->fails()) {
            $safe = $this->lastValidFilters;
            $this->search = $safe['search'] ?? '';
            $this->type = $safe['type'] ?? 'all';
            $this->archiveStatus = $safe['archiveStatus'] ?? 'active';
            $this->perPage = $safe['perPage'] ?? 24;
            if (! $this->invalidState) {
                $this->manager->invalidBrowse($this->authenticatedLogins->current(), CmsResource::Media);
            }
            $this->invalidState = true;
            $this->addError('filters', 'Pilihan filter tidak valid. Pilihan sebelumnya dipertahankan.');

            return false;
        }
        $this->lastValidFilters = $state;
        if (! $this->invalidState) {
            $this->resetErrorBag('filters');
        }

        return ! $this->invalidState;
    }
}
