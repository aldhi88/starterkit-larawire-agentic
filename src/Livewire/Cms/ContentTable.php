<?php

namespace Aldhi88\StarterKit\Livewire\Cms;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Cms\CmsManager;
use Aldhi88\StarterKit\Services\Cms\CmsPostActions;
use Aldhi88\StarterKit\Services\Starter\AuthenticatedLoginService;
use Aldhi88\StarterKit\Support\Cms\CmsCategoryTree;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Cms\CmsTime;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

class ContentTable extends PowerGridComponent
{
    #[Locked]
    public string $resource = 'posts';

    public string $tableName = 'cms-content-table';

    public bool $showFilters = true;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $archiveStatus = 'active';

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
    public bool $pendingFiltered = false;

    #[Locked]
    public int $pendingCount = 0;

    public string $confirmation = '';

    #[Locked]
    public string $lastBrowseState = '';

    /** @var array<string,mixed> */
    #[Locked]
    public array $lastValidTableState = [];

    private CmsManager $manager;

    private CmsPostActions $postActions;

    #[Locked]
    public ?string $postAction = null;

    /** @var list<int> */
    #[Locked]
    public array $postIds = [];

    /** @var array<string,mixed> */
    #[Locked]
    public array $postFilters = [];

    #[Locked]
    public bool $postFiltered = false;

    #[Locked]
    public string $postVersion = '';

    #[Locked]
    public string $postTitle = '';

    #[Locked]
    public int $postScheduled = 0;

    /** @var array<int,array<string,mixed>> */
    #[Locked]
    public array $postCategories = [];

    /** @var list<int|string> */
    public array $postCategoryIds = [];

    private AuthenticatedLoginService $authenticatedLogins;

    private bool $normalizationFailed = false;

    public function boot(CmsManager $manager, AuthenticatedLoginService $authenticatedLogins, CmsPostActions $postActions): void
    {
        $this->manager = $manager;
        $this->authenticatedLogins = $authenticatedLogins;
        $this->postActions = $postActions;
    }

    public function mount(): void
    {
        $this->tableName = 'cms-'.$this->resource.'-table';
        $this->sortField = $this->resource === 'posts' ? 'cms_contents.published_at' : $this->cmsResource()->table().'.id';
        $this->sortDirection = 'desc';
        if ($this->resource === 'post_categories' && StarterTheme::key() === 'tabler') {
            $this->sortField = 'hierarchy_order';
            $this->sortDirection = 'asc';
        }
        parent::mount();
        $this->theme['layout']['header'] = 'starter.cms.powergrid.header';
        $this->normalizeTableState();
        $this->clearSelection();
    }

    public function setUp(): array
    {
        $this->showCheckBox();
        $this->persist(['filters', 'sorting'], $this->tableName.($this->resource === 'post_categories' && StarterTheme::key() === 'tabler' ? '-hierarchy' : ''));

        return [
            PowerGrid::header()->includeViewOnTop('starter.cms.powergrid.toolbar'),
            PowerGrid::footer()->showPerPage(10, [10, 25, 50, 100])->showRecordCount(),
        ];
    }

    public function showCategoryOrder(): void
    {
        if ($this->resource !== 'post_categories' || StarterTheme::key() !== 'tabler') {
            return;
        }
        $this->manager->authorize($this->authenticatedLogins->current(), $this->cmsResource());
        $this->sortField = 'hierarchy_order';
        $this->sortDirection = 'asc';
        $this->persistState('sorting');
        $this->resetPage();
        $this->clearSelection();
        $this->refresh();
    }

    public function categoryRowsReorderable(): bool
    {
        return $this->resource === 'post_categories' && StarterTheme::key() === 'tabler'
            && $this->sortField === 'hierarchy_order' && $this->sortDirection === 'asc'
            && $this->archiveStatus === 'active';
    }

    public function moveCategory(int $id, int $targetId, string $placement, string $version): bool
    {
        $this->manager->authorize($this->authenticatedLogins->current(), $this->cmsResource());
        abort_unless($this->categoryRowsReorderable(), 422);
        $this->resetErrorBag('order');
        try {
            $this->manager->movePostCategory($this->authenticatedLogins->current(), $id, $targetId, $placement, $version);
        } catch (ValidationException $exception) {
            $this->addError('order', $exception->validator->errors()->first());
            $this->refresh();

            return false;
        }
        $this->clearSelection();
        $this->refresh();
        $this->dispatch('starter-toast', type: 'success', message: 'Urutan kategori tersimpan.');

        return true;
    }

    public function noDataLabel(): string|View
    {
        return match ($this->resource) {
            'posts' => view()->make('starter-shared::cms.empty-table', [
                'icon' => 'file-plus',
                'heading' => 'Belum ada postingan',
                'hint' => 'Ubah pencarian atau klik Tambah Postingan untuk mulai.',
            ]),
            'post_categories' => view()->make('starter-shared::cms.empty-table', [
                'icon' => 'folder',
                'heading' => 'Belum ada kategori',
                'hint' => 'Isi Nama Kategori pada formulir untuk mulai.',
            ]),
            default => parent::noDataLabel(),
        };
    }

    public function datasource(): Builder
    {
        $this->normalizeTableState();

        return $this->manager->tableQuery($this->login(), $this->cmsResource(), $this->archiveStatus);
    }

    public function fields(): PowerGridFields
    {
        $statuses = $this->cmsResource()->statusOptions();

        return PowerGrid::fields()->add('id')
            ->add('name', fn (Model $row): string => $this->resource === 'post_categories' && StarterTheme::key() === 'tabler'
                ? view(StarterTheme::viewName('starter.cms.powergrid.category-name'), ['category' => $row, 'reorderable' => $this->categoryRowsReorderable()])->render()
                : e((string) $row->getAttribute('name')))
            ->add('category_name', function (Model $row): string {
                $label = $this->resource === 'posts' && $row->relationLoaded('categories')
                    ? ($row->getRelation('categories')->pluck('name')->implode(', ') ?: ($row->getAttribute('category_name') ?: 'Tanpa kategori'))
                    : (string) $row->getAttribute('category_name');

                return $this->resource === 'posts' && StarterTheme::key() === 'tabler' && $row->getAttribute('deleted_at') === null
                    ? view(StarterTheme::viewName('starter.cms.powergrid.post-categories'), ['row' => $row, 'label' => $label])->render()
                    : e($label);
            })
            ->add('position')->add('deleted_at')
            ->add('content_name', fn (Model $row): string => e((string) $row->getAttribute('content_name')))
            ->add('body_excerpt', fn (Model $row): string => e(Str::limit((string) $row->getAttribute('body'), 160)))
            ->add('status_label', fn (Model $row): string => $this->resource === 'posts' && StarterTheme::key() === 'tabler'
                ? view(StarterTheme::viewName('starter.cms.powergrid.post-status'), ['row' => $row])->render()
                : e($statuses[$row->getAttribute('status')] ?? (string) $row->getAttribute('status')))
            ->add('published_label', fn (Model $row): string => $row->getAttribute('published_at') ? CmsTime::forTable($row->getAttribute('published_at')) : '—')
            ->add('updated_label', function (Model $row): string {
                $updatedAt = $row->getAttribute('updated_at');
                if (! $updatedAt) {
                    return '—';
                }

                return in_array($this->resource, ['posts', 'post_categories'], true)
                    ? CmsTime::forTable($updatedAt)
                    : $updatedAt->format('d/m/Y H:i');
            });
    }

    public function columns(): array
    {
        $fields = $this->cmsResource()->tableFields();
        $status = Column::make('Status', 'status_label', $fields['status'])->sortable();
        if (StarterTheme::key() === 'vuexy') {
            $status->headerAttribute('', 'min-width: 9rem;')->bodyAttribute('', 'min-width: 9rem;');
        }
        $columns = [
            Column::action('Aksi'),
            Column::make($this->resource === 'comments' ? 'Pengirim' : ($this->resource === 'posts' ? 'Judul' : 'Nama'), 'name', $fields['name'])->searchable()->sortable(),
            $status,
        ];

        if ($this->cmsResource() === CmsResource::Comments) {
            $columns[] = Column::make('Postingan', 'content_name', 'cms_comments.content_id');
            $columns[] = Column::make('Komentar', 'body_excerpt', 'cms_comments.body');
        }

        if (isset($fields['category_id'])) {
            $columns[] = Column::make('Kategori', 'category_name', $fields['category_id']);
        }

        if (isset($fields['position']) && $this->resource !== 'posts') {
            $columns[] = Column::make('Urutan', 'position', $fields['position'])->sortable();
        }

        if ($this->resource === 'posts') {
            $columns[] = Column::make('Waktu Terbit (WIB)', 'published_label', 'cms_contents.published_at')->sortable();
        }

        $columns[] = Column::make(in_array($this->resource, ['posts', 'post_categories'], true) ? 'Diperbarui (WIB)' : 'Diperbarui', 'updated_label', $fields['updated_at'])->sortable();

        return $columns;
    }

    public function filters(): array
    {
        $resource = $this->cmsResource();
        $fields = $resource->tableFields();
        $filters = [
            Filter::inputText('name', $fields['name'])->operators(['contains']),
            Filter::select('status_label', $fields['status'])->dataSource(collect($resource->statusOptions())->map(fn (string $label, string $value): array => compact('label', 'value'))->values()->all())
                ->optionLabel('label')->optionValue('value'),
        ];

        if (isset($fields['category_id'])) {
            $options = $this->manager->options($this->login(), $resource);
            $categories = $resource === CmsResource::Menus ? $options['menuCategories'] : $options['categories'];
            $categoryFilter = Filter::select('category_name', $fields['category_id'])->dataSource($categories)->optionLabel('name')->optionValue('id');
            if ($resource === CmsResource::Posts) {
                $categoryFilter->builder(fn (Builder $query, mixed $value): Builder => $query->where(fn (Builder $posts) => $posts->where('cms_contents.category_id', (int) $value)
                    ->orWhereHas('categories', fn (Builder $category) => $category->whereKey((int) $value))));
            }
            $filters[] = $categoryFilter;
        }

        if ($resource === CmsResource::Comments) {
            $options = $this->manager->options($this->login(), $resource);
            $filters[] = Filter::select('content_name', 'cms_comments.content_id')->dataSource($options['contents'])->optionLabel('name')->optionValue('id');
            $filters[] = Filter::inputText('body_excerpt', 'cms_comments.body')->operators(['contains']);
        }

        if (isset($fields['position']) && $resource !== CmsResource::Posts) {
            $filters[] = Filter::number('position', $fields['position'])->placeholder('Min', 'Max');
        }

        $dateField = $fields['updated_at'];
        $filters[] = Filter::datepicker('updated_label', $dateField)->params(['mode' => 'range'])
            ->builder(function (Builder $query, array $values) use ($dateField): Builder {
                if (filled($values['start'] ?? null)) {
                    $query->where($dateField, '>=', Carbon::parse($values['start'])->startOfDay());
                }
                if (filled($values['end'] ?? null)) {
                    $query->where($dateField, '<', Carbon::parse($values['end'])->startOfDay()->addDay());
                }

                return $query;
            });

        return $filters;
    }

    public function actionsFromView(Model $row): View
    {
        return view(StarterTheme::viewName('starter.cms.powergrid.row-actions'), ['row' => $row, 'cmsResource' => $this->cmsResource()]);
    }

    public function updatedArchiveStatus(): void
    {
        abort_unless(in_array($this->archiveStatus, ['active', 'archived', 'all'], true), 422);
        $this->clearSelection();
        $this->resetPage();
    }

    public function updated(string $name, mixed $value): void
    {
        if (str_starts_with($name, 'filters.') || str_starts_with($name, 'setUp.footer.') || in_array($name, ['search', 'archiveStatus', 'sortField', 'sortDirection'], true)) {
            $this->normalizeTableState();
            $this->clearSelection();
        }
    }

    public function sortBy(string $field, string $direction = 'asc'): void
    {
        parent::sortBy($field, $direction);
        $this->normalizeTableState();
        $this->clearSelection();
    }

    public function updatedPage(): void
    {
        parent::updatedPage();
        $this->clearSelection();
    }

    #[On('pg:datePicker-{tableName}')]
    public function datePickerChanged(array $selectedDates, string $field, string $dateStr, string $label, string $type, string $timezone = 'UTC', string $dateFormat = 'Y-m-d H:i'): void
    {
        if (! isset($selectedDates[1])) {
            return;
        }
        $validator = Validator::make(['dates' => $selectedDates, 'field' => $field, 'type' => $type, 'dateStr' => $dateStr], [
            'dates' => ['required', 'array', 'size:2'], 'dates.*' => ['required', 'string', 'max:100', 'date'],
            'field' => ['required', Rule::in([$this->cmsResource()->tableFields()['updated_at']])],
            'type' => ['required', Rule::in(['date'])], 'dateStr' => ['required', 'string', 'max:200'],
        ]);
        if ($validator->fails()) {
            $this->invalidTableState('Rentang tanggal tidak valid. Pilihan sebelumnya dipertahankan.');

            return;
        }

        parent::datePickerChanged($selectedDates, $field, $dateStr, $label, $type, $timezone, $dateFormat);
        $this->normalizeTableState();
        $this->clearSelection();
    }

    public function dehydrate(): void
    {
        $fingerprint = $this->browseFingerprint();

        if ($this->lastBrowseState !== $fingerprint) {
            $this->manager->browse($this->login(), $this->cmsResource(), [
                'archive' => $this->archiveStatus,
                'page' => $this->getPage(),
                'perPage' => data_get($this->setUp, 'footer.perPage'),
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
            ]);
            $this->lastBrowseState = $fingerprint;
        }
    }

    #[On('cms-prepare-post-action')]
    public function preparePostAction(string $action, ?int $id = null, bool $filtered = false): void
    {
        abort_unless($this->resource === 'posts' && StarterTheme::key() === 'tabler', 404);
        abort_unless(in_array($action, ['publish_now', 'draft', 'categories'], true) && ! ($id !== null && $filtered), 422);
        if (! $this->normalizeTableState()) {
            return;
        }
        $this->cancelPostAction();
        $this->cancelPendingAction();
        $ids = $id !== null ? [$id] : array_map('intval', $this->checkboxValues);
        $filters = $filtered ? $this->scopeFilters() : null;
        try {
            $preview = $this->postActions->prepare($this->login(), $ids, $filters);
        } catch (ValidationException $exception) {
            $this->dispatch('starter-toast', type: 'warning', message: $exception->validator->errors()->first());

            return;
        }
        $this->postIds = $preview['ids'];
        $this->postVersion = $preview['version'];
        $this->postTitle = $preview['title'];
        $this->postScheduled = $preview['scheduled'];
        $this->postFilters = $filters ?? [];
        $this->postFiltered = $filtered;
        $this->postAction = $action;
        if ($action === 'categories') {
            $this->postCategories = CmsCategoryTree::flatten($this->manager->options($this->login(), CmsResource::Posts)['categories']);
            $this->postCategoryIds = $id !== null ? CmsCategoryTree::forSelection($preview['category_ids'], $this->postCategories) : [];
        }
    }

    public function cancelPostAction(): void
    {
        $this->postAction = null;
        $this->postIds = [];
        $this->postFilters = [];
        $this->postFiltered = false;
        $this->postVersion = '';
        $this->postTitle = '';
        $this->postScheduled = 0;
        $this->postCategories = [];
        $this->postCategoryIds = [];
        $this->resetErrorBag(['postAction', 'postCategoryIds']);
    }

    public function executePostAction(): void
    {
        abort_unless($this->resource === 'posts' && StarterTheme::key() === 'tabler', 404);
        if ($this->postAction === null) {
            return;
        }
        $this->resetErrorBag(['postAction', 'postCategoryIds']);
        try {
            $changed = $this->postActions->apply($this->login(), $this->postAction, $this->postIds, $this->postVersion,
                $this->postCategoryIds === [] ? 'clear' : 'replace', CmsCategoryTree::forSelection($this->postCategoryIds, $this->postCategories), $this->postFiltered ? $this->postFilters : null);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError(str_starts_with($field, 'category_ids') ? 'postCategoryIds' : 'postAction', $messages[0]);
            }

            return;
        }
        $this->cancelPostAction();
        $this->clearSelection();
        $this->resetPage();
        $this->refresh();
        $this->dispatch('starter-toast', type: 'success', message: $changed.' postingan berhasil diperbarui.');
    }

    public function prepareBulkAction(string $action, bool $filtered = false): void
    {
        $this->prepareAction($action, $this->checkboxValues, $filtered);
    }

    #[On('cms-prepare-row-action')]
    public function prepareRowAction(string $action, int $id): void
    {
        $this->prepareAction($action, [$id], false);
    }

    public function cancelPendingAction(): void
    {
        $this->pendingAction = null;
        $this->pendingIds = [];
        $this->pendingFilters = [];
        $this->pendingFiltered = false;
        $this->pendingCount = 0;
        $this->confirmation = '';
        $this->resetErrorBag('lifecycle');
    }

    public function executePendingAction(): void
    {
        if (! $this->normalizeTableState()) {
            return;
        }
        if ($this->pendingAction === null || $this->pendingCount < 1) {
            return;
        }

        if ($this->pendingAction === 'delete_permanently' && $this->confirmation !== 'HAPUS') {
            $this->addError('lifecycle', 'Ketik HAPUS untuk mengonfirmasi penghapusan permanen.');

            return;
        }

        try {
            if ($this->pendingFiltered) {
                $count = $this->manager->countFiltered($this->login(), $this->cmsResource(), $this->pendingFilters, $this->pendingArchive);
                if ($count !== $this->pendingCount) {
                    $this->pendingCount = $count;
                    $this->addError('lifecycle', 'Jumlah data berubah. Periksa jumlah terbaru dan konfirmasi kembali.');

                    return;
                }
                $changed = $this->manager->lifecycleFiltered($this->login(), $this->cmsResource(), $this->pendingAction, $this->pendingFilters, $this->pendingArchive, $this->pendingCount);
            } else {
                $changed = $this->manager->lifecycle($this->login(), $this->cmsResource(), $this->pendingAction, $this->pendingIds);
            }
        } catch (ValidationException $exception) {
            $this->addError('lifecycle', (string) collect($exception->errors())->flatten()->first());

            return;
        }

        $this->clearSelection();
        $this->cancelPendingAction();
        $this->dispatch('starter-toast', type: 'success', message: $changed.' data berhasil diproses.');
    }

    private function prepareAction(string $action, array $ids, bool $filtered): void
    {
        if (! $this->normalizeTableState()) {
            return;
        }
        abort_unless(in_array($action, ['archive', 'restore', 'delete_permanently'], true), 422);
        $this->manager->authorize($this->login(), $this->cmsResource());
        $this->pendingFiltered = $filtered;
        $this->pendingArchive = $filtered ? ($action === 'archive' ? 'active' : 'archived') : $this->archiveStatus;
        $this->pendingFilters = $filtered ? $this->scopeFilters() : [];
        $this->pendingIds = $filtered ? [] : collect($ids)->map(fn ($id): int => (int) $id)->filter(fn (int $id): bool => $id > 0)->unique()->values()->all();
        $this->pendingCount = $filtered
            ? $this->manager->countFiltered($this->login(), $this->cmsResource(), $this->pendingFilters, $this->pendingArchive)
            : count($this->pendingIds);
        $this->pendingAction = $this->pendingCount > 0 ? $action : null;

        if ($this->pendingCount === 0) {
            $this->dispatch('starter-toast', type: 'warning', message: 'Tidak ada data dalam cakupan yang dipilih.');
        }
    }

    /** @return array<string, mixed> */
    private function scopeFilters(): array
    {
        $fields = $this->cmsResource()->tableFields();
        $filters = [
            'search' => mb_substr(trim($this->search), 0, 100),
            'name' => $this->filterValue('input_text', $fields['name']) ?? '',
            'status' => $this->filterValue('select', $fields['status']) ?? '',
            'date_from' => $this->filterDate('start', $fields['updated_at']),
            'date_to' => $this->filterDate('end', $fields['updated_at']),
        ];
        if (isset($fields['category_id'])) {
            $filters['category_id'] = $this->filterValue('select', $fields['category_id']);
        }
        if (isset($fields['position']) && $this->resource !== 'posts') {
            $range = $this->filterValue('number', $fields['position']);
            $filters['position_min'] = data_get($range, 'start');
            $filters['position_max'] = data_get($range, 'end');
        }

        if ($this->cmsResource() === CmsResource::Comments) {
            $filters['content_id'] = $this->filterValue('select', 'cms_comments.content_id');
            $filters['body'] = $this->filterValue('input_text', 'cms_comments.body');
        }

        return $filters;
    }

    private function filterValue(string $type, string $field): mixed
    {
        $values = $this->filters[$type] ?? [];
        if (! is_array($values)) {
            throw ValidationException::withMessages(['tableFilters' => 'Format filter tabel tidak valid.']);
        }

        return array_key_exists($field, $values) ? $values[$field] : data_get($values, $field);
    }

    private function normalizeTableState(): bool
    {
        try {
            $this->validateTableState();

            return ! $this->normalizationFailed;
        } catch (ValidationException) {
            $safe = $this->lastValidTableState;
            $this->filters = $safe['filters'] ?? [];
            $this->search = $safe['search'] ?? '';
            $this->sortField = $safe['sortField'] ?? ($this->resource === 'posts' ? 'cms_contents.published_at' : $this->cmsResource()->table().'.id');
            $this->sortDirection = $safe['sortDirection'] ?? 'desc';
            $this->archiveStatus = $safe['archiveStatus'] ?? 'active';
            data_set($this->setUp, 'footer.perPage', $safe['perPage'] ?? 10);
            $this->multiSort = false;
            $this->sortArray = [];
            $this->invalidTableState('Filter atau pengurutan tidak valid. Pilihan sebelumnya dipertahankan; periksa tanggal dan batas angka.');

            return false;
        }
    }

    private function validateTableState(): void
    {
        $resource = $this->cmsResource();
        $fields = $resource->tableFields();
        $textFields = [$fields['name']];
        if ($resource === CmsResource::Comments) {
            $textFields[] = 'cms_comments.body';
        }
        $selectFields = [$fields['status'] => ['nullable', Rule::in(array_keys($resource->statusOptions()))]];
        if (isset($fields['category_id'])) {
            $selectFields[$fields['category_id']] = ['nullable', 'integer', 'min:1'];
        }
        if ($resource === CmsResource::Comments) {
            $selectFields['cms_comments.content_id'] = ['nullable', 'integer', 'min:1'];
        }

        $clean = [];
        $values = [];
        $rules = [];
        $this->search = mb_substr(trim($this->search), 0, 100);
        foreach ($textFields as $index => $field) {
            $value = $this->filterValue('input_text', $field);
            $operator = $this->filterValue('input_text_options', $field);
            $values['text_'.$index] = is_string($value) ? mb_substr($value, 0, 100) : $value;
            $values['operator_'.$index] = $operator;
            $rules['text_'.$index] = ['nullable', 'string', 'max:100'];
            $rules['operator_'.$index] = ['nullable', Rule::in(['contains'])];
            if ($value !== null) {
                data_set($clean, 'input_text.'.$field, $values['text_'.$index]);
            }
            if ($operator !== null) {
                data_set($clean, 'input_text_options.'.$field, $operator);
            }
        }
        foreach ($selectFields as $index => $fieldRules) {
            $field = (string) $index;
            $key = 'select_'.count($values);
            $values[$key] = $this->filterValue('select', $field);
            $rules[$key] = $fieldRules;
            if ($values[$key] !== null) {
                data_set($clean, 'select.'.$field, $values[$key]);
            }
        }
        if (isset($fields['position']) && $resource !== CmsResource::Posts) {
            $range = $this->filterValue('number', $fields['position']);
            if ($range !== null && ! is_array($range)) {
                throw ValidationException::withMessages(['tableFilters' => 'Format rentang urutan tidak valid.']);
            }
            foreach (['start', 'end'] as $boundary) {
                $values['number_'.$boundary] = data_get($range, $boundary);
                $rules['number_'.$boundary] = ['nullable', 'integer', 'min:0', 'max:1000000'];
                if ($values['number_'.$boundary] !== null) {
                    data_set($clean, 'number.'.$fields['position'].'.'.$boundary, $values['number_'.$boundary]);
                }
            }
        }
        $dateRange = $this->filterValue('date', $fields['updated_at']);
        if ($dateRange !== null && ! is_array($dateRange)) {
            throw ValidationException::withMessages(['tableFilters' => 'Format rentang tanggal tidak valid.']);
        }
        foreach (['start', 'end'] as $boundary) {
            $values['date_'.$boundary] = data_get($dateRange, $boundary);
            $rules['date_'.$boundary] = ['nullable', 'string', 'max:100', 'date'];
        }
        if (is_array($dateRange)) {
            $clean['date'][$fields['updated_at']] = array_intersect_key($dateRange, array_flip(['start', 'end', 'formatted']));
        }
        $values['archive'] = $this->archiveStatus;
        $rules['archive'] = ['required', Rule::in(['active', 'archived', 'all'])];
        $values['per_page'] = data_get($this->setUp, 'footer.perPage', 10);
        $rules['per_page'] = ['required', 'integer', Rule::in([10, 25, 50, 100])];
        $values['sort_field'] = $this->sortField;
        $rules['sort_field'] = ['required', Rule::in(array_filter([
            'id', $resource->table().'.id', 'name', 'status_label', 'updated_label',
            $fields['name'], $fields['status'], $fields['updated_at'],
            $resource === CmsResource::PostCategories ? 'hierarchy_order' : null,
            $resource === CmsResource::Posts ? 'published_label' : null,
            $resource === CmsResource::Posts ? 'cms_contents.published_at' : null,
            isset($fields['position']) && $resource !== CmsResource::Posts ? 'position' : null,
            $resource !== CmsResource::Posts ? ($fields['position'] ?? null) : null,
        ]))];
        $values['sort_direction'] = $this->sortDirection;
        $rules['sort_direction'] = ['required', Rule::in(['asc', 'desc'])];
        $validator = Validator::make($values, $rules);
        if ($validator->fails()) {
            throw ValidationException::withMessages(['tableFilters' => 'Filter atau pengurutan tidak valid. Periksa pilihan, tanggal, dan batas angka.']);
        }

        $this->filters = $clean;
        $this->multiSort = false;
        $this->sortArray = [];
        $this->lastValidTableState = [
            'filters' => $clean, 'search' => $this->search, 'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection, 'archiveStatus' => $this->archiveStatus,
            'perPage' => data_get($this->setUp, 'footer.perPage', 10),
        ];
        if (! $this->normalizationFailed) {
            $this->resetErrorBag('tableFilters');
        }
    }

    private function filterDate(string $boundary, string $field): ?string
    {
        $value = data_get($this->filterValue('date', $field), $boundary);

        return filled($value) ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    private function cmsResource(): CmsResource
    {
        return CmsResource::from($this->resource);
    }

    private function clearSelection(): void
    {
        $this->checkboxValues = [];
        $this->checkboxAll = false;
        $this->dispatch('pgBulkActions::clear', $this->tableName);
    }

    private function invalidTableState(string $message): void
    {
        if (! $this->normalizationFailed) {
            $this->manager->invalidBrowse($this->login(), $this->cmsResource());
        }
        $this->normalizationFailed = true;
        $this->addError('tableFilters', $message);
    }

    private function browseFingerprint(): string
    {
        return hash('sha256', (string) json_encode([
            $this->archiveStatus, $this->search, $this->filters, $this->sortField,
            $this->sortDirection, $this->getPage(), data_get($this->setUp, 'footer.perPage'),
        ]));
    }

    private function login(): ClientLogin
    {
        return $this->authenticatedLogins->current();
    }
}
