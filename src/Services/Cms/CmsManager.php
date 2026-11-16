<?php

namespace Aldhi88\StarterKit\Services\Cms;

use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Models\Cms\Category;
use Aldhi88\StarterKit\Models\Cms\Comment;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Cms\Gallery;
use Aldhi88\StarterKit\Models\Cms\Media;
use Aldhi88\StarterKit\Models\Cms\Menu;
use Aldhi88\StarterKit\Models\Cms\MenuCategory;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Support\Cms\CmsCardPresets;
use Aldhi88\StarterKit\Support\Cms\CmsCategoryTree;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Cms\CmsTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CmsManager
{
    private readonly CmsMenuLinks $menuLinks;

    public function __construct(private readonly CmsRepositoryInterface $records, private readonly AuditLogService $auditLogs, private readonly CmsRichContent $richContent, ?CmsMenuLinks $menuLinks = null)
    {
        $this->menuLinks = $menuLinks ?? app(CmsMenuLinks::class);
    }

    public function authorize(ClientLogin $actor, CmsResource $resource): void
    {
        if (! $actor->isActive() || $actor->trashed() || Auth::id() !== $actor->getKey() || ! $this->records->hasModule($actor, $resource->module())) {
            $this->audit($resource, 'denied', ['outcome' => 'denied']);
            abort(403);
        }
    }

    /** @return Builder<Content|Category|Media|Gallery|Menu|MenuCategory|Comment> */
    public function tableQuery(ClientLogin $actor, CmsResource $resource, string $archive = 'active'): Builder
    {
        $this->authorize($actor, $resource);

        try {
            return $this->records->filteredWorkspace($resource, [], $this->archive($archive));
        } catch (Throwable $exception) {
            $this->failure($resource, $exception);
            throw $exception;
        }
    }

    /** @param array<string,mixed> $state */
    public function browse(ClientLogin $actor, CmsResource $resource, array $state = []): void
    {
        $this->authorize($actor, $resource);
        $this->audit($resource, 'browse', ['outcome' => 'success', 'state' => array_intersect_key($state, array_flip(['archive', 'page', 'perPage', 'sortField', 'sortDirection']))]);
    }

    public function menuTargetOptions(ClientLogin $actor, string $type, string $query): array
    {
        $this->authorize($actor, CmsResource::Menus);
        abort_unless(isset(CmsMenuLinks::TYPES[$type]) && mb_strlen($query) <= 100, 422);

        return $this->menuLinks->search($type, trim($query));
    }

    public function invalidBrowse(ClientLogin $actor, CmsResource $resource): void
    {
        $this->authorize($actor, $resource);
        $this->audit($resource, 'browse_failed', ['outcome' => 'validation_failed']);
    }

    public function browseDashboard(ClientLogin $actor): void
    {
        $this->authorizeDashboard($actor);
        $this->auditLogs->withinAction('cms.dashboard.browse', 'Membuka beranda CMS', fn () => $this->auditLogs->recordManual('read', 'cms_dashboard', 'dashboard', metadata: ['outcome' => 'success']));
    }

    /** @return array<string,int> */
    public function dashboard(ClientLogin $actor): array
    {
        $this->authorizeDashboard($actor);
        $counts = [];
        foreach (CmsResource::cases() as $resource) {
            if ($this->records->hasModule($actor, $resource->module())) {
                $counts[$resource->value] = $this->records->countWorkspace($resource, [], 'active');
            }
        }

        return $counts;
    }

    /** @return array{data:array<string,mixed>,categories:array,media:array,contents:array,menuCategories:array} */
    public function editor(ClientLogin $actor, CmsResource $resource, ?int $id = null): array
    {
        $this->authorize($actor, $resource);
        abort_if($resource === CmsResource::Comments && $id === null, 404);
        $record = $id ? $this->find($resource, $id) : null;
        if ($id !== null && $record === null) {
            $this->audit($resource, 'not_found', ['outcome' => 'not_found']);
            abort(404);
        }
        $defaults = ['name' => '', 'title' => '', 'subtitle' => '', 'slug' => '', 'excerpt' => '', 'body' => '',
            'category_id' => null, 'category_ids' => [], 'parent_id' => null, 'image_id' => null, 'thumbnail_id' => null, 'logo_id' => null,
            'link_label' => '', 'link_url' => '', 'status' => array_key_first($resource->statusOptions()),
            'published_at' => null, 'position' => 0, 'code' => '', 'description' => '', 'alt_text' => '', 'caption' => '',
            'media_ids' => [], 'items' => [], 'content_id' => null, 'author_name' => '', 'author_email' => ''];
        $data = $record ? array_replace($defaults, array_intersect_key($record->getAttributes(), $defaults)) : $defaults;
        if ($resource === CmsResource::Cards) {
            $data['card_preset'] = $record ? ($record->getAttribute('card_preset') ?: 'free') : 'service';
            $data['card_data'] = array_replace(['mode' => $record ? 'single' : 'collection', 'items' => []], $record?->getAttribute('card_data') ?: []);
        }
        if ($resource === CmsResource::Pages && $record && blank($data['title'])) {
            $data['title'] = $data['name'];
        }
        if (in_array($resource, [CmsResource::Posts, CmsResource::Pages, CmsResource::Cards], true) && $record === null) {
            $data['published_at'] = CmsTime::nowForInput();
        }
        if ($record instanceof Content && $record->getAttribute('published_at')) {
            $data['published_at'] = in_array($resource, [CmsResource::Posts, CmsResource::Pages, CmsResource::Cards], true)
                ? CmsTime::forInput($record->getAttribute('published_at'))
                : $record->getAttribute('published_at')->format('Y-m-d\TH:i');
        }
        if ($record instanceof Content && $resource === CmsResource::Posts) {
            $data['category_ids'] = $record->categories()->pluck('cms_categories.id')->map(fn ($id): int => (int) $id)->all();
            if ($data['category_ids'] === [] && $data['category_id']) {
                $data['category_ids'] = [(int) $data['category_id']];
            }
        }
        if ($resource->isContent()) {
            $body = (string) ($data['body'] ?? '');
            $data['body'] = $this->richContent->render($record?->getAttribute('body_format') === 'html' ? $body : Str::markdown($body, ['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 20]));
            $data['body_format'] = 'html';
        }
        if ($record instanceof Gallery) {
            $data['media_ids'] = $this->records->galleryMediaIds($record);
        }
        if ($record instanceof Menu) {
            $data['items'] = $record->getAttribute('items') ?? [];
            $targets = $this->menuLinks->targets($data['items']);
            foreach ($data['items'] as &$item) {
                $item['target_type'] ??= 'custom';
                $target = $targets[$item['target_type']][$item['target_id'] ?? 0] ?? null;
                $item['target_name'] = $target['name'] ?? '';
                $item['url'] = $target['url'] ?? ($item['url'] ?? '');
            }
            unset($item);
        }
        if ($record instanceof Media) {
            $data['url'] = $this->mediaUrl($record);
            $data['mime_type'] = $record->getAttribute('mime_type');
            $data['size'] = $record->getAttribute('size');
            $data['original_name'] = $record->getAttribute('original_name');
        }
        $options = $this->options($actor, $resource);
        $itemImages = [];
        foreach (data_get($data, 'card_data.items', []) as $item) {
            foreach (['image_id', 'thumbnail_id', 'logo_id'] as $field) {
                $itemImages[] = $item[$field] ?? null;
            }
        }
        $include = array_filter([$data['image_id'], $data['thumbnail_id'], $data['logo_id'], ...$data['media_ids'], ...$itemImages]);
        if ($include !== []) {
            $options['media'] = $this->mediaRows($this->records->mediaOptions('', array_map('intval', $include)));
        }
        if ($record instanceof Content && $data['category_id']) {
            $category = $this->records->category((int) $data['category_id'], $resource->kind(), true);
            if ($category && ! in_array($category->getKey(), array_column($options['categories'], 'id'), true)) {
                $options['categories'][] = ['id' => $category->getKey(), 'name' => $category->getAttribute('name')];
            }
        }
        if ($record instanceof Menu && $data['category_id']) {
            $category = $this->records->menuCategory((int) $data['category_id'], true);
            if ($category && ! in_array($category->getKey(), array_column($options['menuCategories'], 'id'), true)) {
                $options['menuCategories'][] = ['id' => $category->getKey(), 'name' => $category->getAttribute('name')];
            }
        }
        if ($record instanceof Comment) {
            $data['content_title'] = $record->content()->withTrashed()->value('name');
            $data['parent_id'] = $record->getAttribute('parent_id');
            $data['parent_available'] = $data['parent_id'] && $this->records->comment((int) $data['parent_id']) !== null;
            $data['replies'] = $record->replies()->latest('id')->limit(10)
                ->get(['id', 'author_name', 'body', 'status'])->reverse()->values()->toArray();
        }
        $this->audit($resource, $id ? 'read' : 'create_form', ['outcome' => 'success', 'record_id' => $id]);

        return ['data' => $data, ...$options];
    }

    /** @return array{categories:array,media:array,contents:array,menuCategories:array} */
    public function options(ClientLogin $actor, CmsResource $resource): array
    {
        $this->authorize($actor, $resource);

        return [
            'categories' => $resource->kind() ? $this->records->categoryOptions($resource->kind())->map(fn (Category $row): array => ['id' => $row->getKey(), 'name' => $row->getAttribute('name'), 'parent_id' => $row->getAttribute('parent_id'), 'position' => (int) $row->getAttribute('position')])->all() : [],
            'media' => ($resource->isContent() || $resource === CmsResource::Galleries) ? $this->mediaRows($this->records->mediaOptions()) : [],
            'contents' => $resource === CmsResource::Comments ? $this->records->postOptions()->map(fn (Content $row): array => ['id' => $row->getKey(), 'name' => $row->getAttribute('name')])->all() : [],
            'menuCategories' => $resource === CmsResource::Menus ? $this->records->menuCategoryOptions()->map(fn (MenuCategory $row): array => ['id' => $row->getKey(), 'name' => $row->getAttribute('name')])->all() : [],
        ];
    }

    /** @return Collection<int,array{id:int,name:string,url:string,mime_type:string}> */
    public function mediaOptions(ClientLogin $actor, string $search = '', array $include = []): Collection
    {
        $permitted = false;
        foreach (['posts', 'pages', 'cards', 'media'] as $module) {
            $permitted = $permitted || $this->records->hasModule($actor, $module);
        }
        if (Auth::id() !== $actor->getKey() || ! $actor->isActive() || $actor->trashed() || ! $permitted) {
            $this->audit(CmsResource::Media, 'picker_denied', ['outcome' => 'denied']);
            abort(403);
        }
        $this->audit(CmsResource::Media, 'picker', ['outcome' => 'success']);

        return collect($this->mediaRows($this->records->mediaOptions(mb_substr(trim($search), 0, 100), array_slice(array_map('intval', $include), 0, 100))));
    }

    /** @param array<string,mixed> $data */
    public function save(ClientLogin $actor, CmsResource $resource, array $data, ?int $id = null, ?UploadedFile $upload = null): Model
    {
        $this->authorize($actor, $resource);

        abort_if($resource === CmsResource::Comments && $id === null, 404);

        return $this->persist($actor, $resource, $data, $id, $upload);
    }

    /** @param array<string,mixed> $data */
    private function persist(ClientLogin $actor, CmsResource $resource, array $data, ?int $id = null, ?UploadedFile $upload = null, bool $withinBatch = false): Model
    {
        $storedPath = null;
        $oldPath = null;
        try {
            $perform = function () use ($actor, $resource, $data, $id, $upload, &$storedPath, &$oldPath): Model {
                return DB::transaction(function () use ($actor, $resource, $data, $id, $upload, &$storedPath, &$oldPath): Model {
                    $record = $id ? $this->find($resource, $id, false, true) : null;
                    if ($id !== null) {
                        abort_unless($record !== null, 404);
                    }
                    if ($record instanceof Comment) {
                        return $this->moderateComment($actor, $record, $data);
                    }
                    if ($record instanceof Content && ! array_key_exists('body_format', $data)) {
                        $data['body_format'] = $record->getAttribute('body_format') ?: 'markdown';
                    }
                    $payload = $this->validated($resource, $data, $id);
                    $categoryIds = null;
                    if ($resource === CmsResource::Posts && array_key_exists('category_ids', $payload)) {
                        $categoryIds = array_map('intval', $payload['category_ids']);
                        $payload['category_id'] = $categoryIds[0] ?? null;
                        unset($payload['category_ids']);
                    } elseif ($resource === CmsResource::Posts && $record instanceof Content
                        && (int) $record->getAttribute('category_id') !== (int) ($payload['category_id'] ?? null)) {
                        $categoryIds = $payload['category_id'] ? [(int) $payload['category_id']] : [];
                    }
                    if (in_array($resource, [CmsResource::Posts, CmsResource::Pages], true) && $record === null) {
                        $payload['position'] = 0;
                    }
                    $embeddedMediaIds = [];
                    if ($resource->isContent() && $payload['body_format'] === 'html') {
                        $rich = $this->richContent->prepare((string) ($payload['body'] ?? $record?->getAttribute('body')));
                        $payload['body'] = $rich['html'];
                        $embeddedMediaIds = $rich['mediaIds'];
                    }
                    if ($resource === CmsResource::Cards) {
                        foreach (data_get($payload, 'card_data.items', data_get($record?->getAttribute('card_data'), 'items', [])) as $item) {
                            foreach (['image_id', 'thumbnail_id', 'logo_id'] as $field) {
                                if (! empty($item[$field])) {
                                    $embeddedMediaIds[] = (int) $item[$field];
                                }
                            }
                        }
                    }
                    if ($resource === CmsResource::Media) {
                        if ($record === null && $upload === null) {
                            throw ValidationException::withMessages(['upload' => 'Pilih gambar atau PDF yang akan diunggah.']);
                        }
                        if ($upload !== null) {
                            $file = $this->uploadData($upload);
                            $storedPath = $file['path'];
                            $oldPath = $record?->getAttribute('path');
                            if ($record instanceof Media
                                && ! str_starts_with($file['mime_type'], 'image/')
                                && $this->records->inUse($record, false)) {
                                throw ValidationException::withMessages(['upload' => 'Media ini masih digunakan sebagai gambar. Ganti dengan gambar atau lepaskan relasinya terlebih dahulu.']);
                            }
                            $payload = [...$payload, ...$file];
                            if ($record === null) {
                                $payload['public_id'] = (string) Str::ulid();
                            }
                        }
                    }
                    $saved = match ($resource) {
                        CmsResource::Posts, CmsResource::Pages, CmsResource::Cards => $this->records->storeContent($record instanceof Content ? $record : null, $payload, $actor->id),
                        CmsResource::PostCategories, CmsResource::PageCategories, CmsResource::CardCategories => $this->records->storeCategory($record instanceof Category ? $record : null, $payload, $actor->id),
                        CmsResource::Media => $this->records->storeMedia($record instanceof Media ? $record : null, $payload, $actor->id),
                        CmsResource::Galleries => $this->records->storeGallery($record instanceof Gallery ? $record : null, $payload, array_map('intval', $data['media_ids'] ?? []), $actor->id),
                        CmsResource::Menus => $this->records->storeMenu($record instanceof Menu ? $record : null, $payload, $actor->id),
                        CmsResource::MenuCategories => $this->records->storeMenuCategory($record instanceof MenuCategory ? $record : null, $payload, $actor->id),
                        CmsResource::Comments => throw new \LogicException('Moderasi memerlukan komentar yang sudah ada.'),
                    };
                    if ($saved instanceof Content) {
                        $this->records->syncContentMedia($saved, $embeddedMediaIds, $actor->id);
                        if ($resource === CmsResource::Posts) {
                            $this->records->syncPostCategories($saved, $categoryIds, $actor->id);
                        }
                    }
                    $this->auditLogs->recordManual($id ? 'updated' : 'created', $saved::class, $saved->getKey(), tableName: $resource->table(), auditableLabel: $resource->label(), metadata: ['outcome' => 'success', 'fields' => array_keys($payload), 'gallery_items' => $resource === CmsResource::Galleries ? count($data['media_ids'] ?? []) : null]);
                    if ($oldPath && $oldPath !== $storedPath) {
                        DB::afterCommit(fn () => $this->removeFile($oldPath));
                    }

                    return $saved;
                });
            };

            return $withinBatch ? $perform() : $this->auditLogs->withinAction('cms.'.$resource->value.'.'.($id ? 'update' : 'create'), 'Menyimpan '.$resource->label(), $perform);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }
            $this->failure($resource, $exception);
            throw $exception;
        }
    }

    /** @param array<string,mixed> $data */
    private function moderateComment(ClientLogin $actor, Comment $record, array $data): Comment
    {
        $valid = Validator::make($data, [
            'status' => ['required', Rule::in(array_keys(CmsResource::Comments->statusOptions()))],
            'reply_body' => ['nullable', 'string', 'max:10000'],
            'reply_key' => ['required_with:reply_body', 'nullable', 'uuid'],
        ], [], ['reply_body' => 'balasan'])->validate();
        $reply = trim((string) ($valid['reply_body'] ?? ''));
        if ($reply !== '') {
            if ($record->getAttribute('parent_id') !== null) {
                throw ValidationException::withMessages(['reply_body' => 'Balas melalui komentar utama.']);
            }
            // The parent is already locked. A retried submission must not publish a second reply.
            if ($record->replies()->withTrashed()->where('reply_key', $valid['reply_key'])->exists()) {
                return $record;
            }
        }
        if (isset($data['original_status']) && $record->getAttribute('status') !== $data['original_status']
            && $record->getAttribute('status') !== $valid['status']) {
            throw ValidationException::withMessages(['status' => 'Status komentar sudah berubah. Muat ulang sebelum menyimpan.']);
        }
        $before = $record->getAttribute('status');
        if ($valid['status'] !== $before) {
            $this->records->storeComment($record, ['status' => $valid['status']], $actor->id);
        }
        $savedReply = $reply === '' ? null : $this->records->storeComment(null, [
            'content_id' => $record->getAttribute('content_id'), 'parent_id' => $record->getKey(),
            'reply_key' => $valid['reply_key'], 'author_name' => $actor->name,
            'body' => $reply, 'status' => 'approved',
        ], $actor->id);
        if ($before !== $valid['status'] || $savedReply !== null) {
            $this->auditLogs->recordManual('updated', Comment::class, $record->getKey(), tableName: 'cms_comments',
                auditableLabel: 'Moderasi komentar', metadata: ['outcome' => 'success', 'before_status' => $before,
                    'status' => $valid['status'], 'reply_id' => $savedReply?->getKey()]);
        }

        return $record;
    }

    /** @return LengthAwarePaginator<int,array<string,mixed>> */
    public function mediaLibrary(ClientLogin $actor, string $search = '', string $type = 'all', string $archive = 'active', int $perPage = 24, int $page = 1, bool $forEditor = false): LengthAwarePaginator
    {
        $forEditor ? $this->authorizeMediaPicker($actor) : $this->authorize($actor, CmsResource::Media);
        try {
            $parameters = Validator::make(compact('type', 'archive', 'perPage', 'page'), [
                'type' => ['required', Rule::in(['all', 'image', 'pdf'])],
                'archive' => ['required', Rule::in($forEditor ? ['active'] : ['active', 'archived', 'all'])],
                'perPage' => ['required', Rule::in([24, 48, 96])], 'page' => ['required', 'integer', 'min:1', 'max:1000000'],
            ])->validate();

            return $this->records->paginateMediaLibrary(mb_substr(trim($search), 0, 100), $parameters['type'], $parameters['archive'], $parameters['perPage'], $parameters['page'], $forEditor)
                ->through(fn (Media $media): array => $this->mediaLibraryRow($media));
        } catch (Throwable $exception) {
            $this->failure(CmsResource::Media, $exception);
            throw $exception;
        }
    }

    /** @param list<mixed> $uploads @return array{uploaded:list<array<string,mixed>>,failed:list<array{name:string,message:string}>} */
    public function uploadMediaBatch(ClientLogin $actor, array $uploads, bool $forEditor = false): array
    {
        $forEditor ? $this->authorizeMediaPicker($actor) : $this->authorize($actor, CmsResource::Media);
        try {
            Validator::make(['uploads' => $uploads], ['uploads' => ['required', 'array', 'min:1', 'max:20']])->validate();
        } catch (Throwable $exception) {
            $this->failure(CmsResource::Media, $exception);
            throw $exception;
        }

        return $this->auditLogs->withinAction('cms.media.upload_batch', 'Mengunggah media', function () use ($actor, $uploads): array {
            $result = ['uploaded' => [], 'failed' => []];
            foreach ($uploads as $upload) {
                $name = $upload instanceof UploadedFile ? mb_substr(basename($upload->getClientOriginalName()), 0, 255) : 'File';
                try {
                    if (! $upload instanceof UploadedFile) {
                        throw ValidationException::withMessages(['upload' => 'File tidak valid.']);
                    }
                    $media = $this->persist($actor, CmsResource::Media, ['name' => pathinfo($name, PATHINFO_FILENAME) ?: 'Media', 'status' => 'active'], upload: $upload, withinBatch: true);
                    if ($media instanceof Media) {
                        $result['uploaded'][] = $this->mediaLibraryRow($media);
                    }
                } catch (ValidationException $exception) {
                    $result['failed'][] = ['name' => $name, 'message' => (string) collect($exception->errors())->flatten()->first()];
                }
            }
            $this->auditLogs->recordManual('uploaded', Media::class, 'batch', tableName: 'cms_media', auditableLabel: 'Media', metadata: ['outcome' => $result['failed'] === [] ? 'success' : 'partial', 'uploaded' => count($result['uploaded']), 'failed' => count($result['failed'])]);

            return $result;
        });
    }

    public function saveCategoryName(ClientLogin $actor, CmsResource $resource, string $name, ?int $id = null, ?int $parentId = null, bool $updateParent = false): Model
    {
        $this->authorize($actor, $resource);
        abort_unless($resource->isCategory() || $resource === CmsResource::MenuCategories, 422);
        try {
            return DB::transaction(function () use ($actor, $resource, $name, $id, $parentId, $updateParent): Model {
                $validated = Validator::make(['name' => trim($name)], ['name' => ['required', 'string', 'max:255']], [], ['name' => 'nama kategori'])->validate();
                $existing = $id ? $this->find($resource, $id, false, true) : null;
                abort_if($id !== null && $existing === null, 404);
                $data = $existing ? array_intersect_key($existing->getAttributes(), array_flip(['code', 'description', 'status', 'position'])) : ['status' => 'active', 'position' => 0];
                if (! $existing) {
                    $base = mb_substr(Str::slug($validated['name']) ?: 'kategori', 0, 85);
                    $code = $base;
                    for ($suffix = 2; $this->records->categoryCodeExists($code, $resource->kind()); $suffix++) {
                        $code = $base.'-'.($suffix <= 100 ? $suffix : strtolower(substr((string) Str::ulid(), -10)));
                    }
                    $data['code'] = $code;
                }
                if ($resource === CmsResource::PostCategories && $updateParent) {
                    $data['parent_id'] = $parentId;
                }

                return $this->save($actor, $resource, ['name' => $validated['name'], ...$data], $id);
            });
        } catch (UniqueConstraintViolationException $exception) {
            $this->failure($resource, $exception);
            throw ValidationException::withMessages(['name' => 'Kategori dengan kode yang sama baru saja dibuat. Simpan kembali untuk memakai kode berikutnya.']);
        } catch (Throwable $exception) {
            $this->failure($resource, $exception);
            throw $exception;
        }
    }

    /** @return array{categories:array,version:string} */
    public function postCategoryOrder(ClientLogin $actor): array
    {
        $this->authorize($actor, CmsResource::PostCategories);
        try {
            $state = $this->categoryOrderState($this->records->postCategoriesForOrdering());
            $this->audit(CmsResource::PostCategories, 'order_view', ['outcome' => 'success']);

            return $state;
        } catch (Throwable $exception) {
            $this->failure(CmsResource::PostCategories, $exception);
            throw $exception;
        }
    }

    public function movePostCategory(ClientLogin $actor, int $id, int $targetId, string $placement, string $version): void
    {
        $this->authorize($actor, CmsResource::PostCategories);
        try {
            Validator::make(['placement' => $placement], ['placement' => ['required', Rule::in(['before', 'after', 'inside', 'root'])]])->validate();
            $this->auditLogs->withinAction('cms.post_categories.reorder', 'Mengatur susunan kategori konten', function () use ($actor, $id, $targetId, $placement, $version): void {
                DB::transaction(function () use ($actor, $id, $targetId, $placement, $version): void {
                    $records = $this->records->postCategoriesForOrdering(true);
                    $state = $this->categoryOrderState($records);
                    if (! hash_equals($state['version'], $version)) {
                        throw ValidationException::withMessages(['order' => 'Daftar kategori telah berubah. Silakan geser kembali.']);
                    }
                    $byId = $records->keyBy('id');
                    $source = $byId->get($id);
                    $target = $byId->get($targetId);
                    if (! $source || ($placement !== 'root' && (! $target || $id === $targetId))) {
                        throw ValidationException::withMessages(['order' => 'Kategori tujuan tidak tersedia.']);
                    }
                    $parentId = match ($placement) {
                        'root' => null,
                        'inside' => $targetId,
                        default => $target->parent_id,
                    };
                    $cursor = $parentId;
                    $seen = [$id];
                    while ($cursor) {
                        if (in_array($cursor, $seen, true) || ! $byId->has($cursor)) {
                            throw ValidationException::withMessages(['order' => 'Kategori tidak dapat dipindahkan ke dalam dirinya atau turunannya.']);
                        }
                        $seen[] = $cursor;
                        $cursor = $byId->get($cursor)->parent_id;
                    }
                    if ($parentId && $byId->get($parentId)->status !== 'active') {
                        throw ValidationException::withMessages(['order' => 'Kategori induk harus aktif.']);
                    }
                    $oldParent = $source->parent_id;
                    $siblings = array_values(array_filter($state['categories'], fn (array $row): bool => $row['id'] !== $id && $row['parent_id'] === $parentId));
                    $ids = array_column($siblings, 'id');
                    $index = in_array($placement, ['inside', 'root'], true) ? count($ids) : array_search($targetId, $ids, true) + ($placement === 'after' ? 1 : 0);
                    array_splice($ids, $index, 0, [$id]);
                    $before = [];
                    $after = [];
                    foreach ($ids as $offset => $categoryId) {
                        $category = $byId->get($categoryId);
                        $before[$categoryId] = ['parent_id' => $category->parent_id, 'position' => $category->position];
                        $after[$categoryId] = ['parent_id' => $parentId, 'position' => $offset + 1];
                        if ($before[$categoryId] !== $after[$categoryId]) {
                            $this->records->storeCategory($category, $after[$categoryId], $actor->id);
                        }
                    }
                    $this->auditLogs->recordManual('reordered', Category::class, $id, tableName: 'cms_categories', auditableLabel: $source->name, oldValues: $before, newValues: $after, metadata: ['outcome' => 'success', 'previous_parent_id' => $oldParent, 'parent_id' => $parentId]);
                });
            });
        } catch (Throwable $exception) {
            $this->failure(CmsResource::PostCategories, $exception);
            throw $exception;
        }
    }

    /** @param list<int> $ids */
    public function reorderPostCategories(ClientLogin $actor, array $ids, string $version): void
    {
        $resource = CmsResource::PostCategories;
        $this->authorize($actor, $resource);
        try {
            $validated = Validator::make(['order' => $ids], ['order' => ['required', 'array', 'min:1', 'max:500'], 'order.*' => ['required', 'integer', 'min:1', 'distinct']])->validate();
            $this->auditLogs->withinAction('cms.post_categories.reorder', 'Mengatur urutan kategori post', function () use ($actor, $validated, $version): void {
                DB::transaction(function () use ($actor, $validated, $version): void {
                    $records = $this->records->postCategoriesForOrdering(true);
                    $state = $this->categoryOrderState($records);
                    $ids = array_map('intval', $validated['order']);
                    if (! hash_equals($state['version'], $version) || count($ids) !== $records->count() || array_diff($ids, $records->pluck('id')->all()) !== []) {
                        throw ValidationException::withMessages(['order' => 'Daftar kategori telah berubah. Tabel sudah diperbarui; silakan geser kembali barisnya.']);
                    }
                    $byId = $records->keyBy('id');
                    $positions = [];
                    $before = [];
                    $after = [];
                    foreach ($ids as $id) {
                        $category = $byId->get($id);
                        $parent = $category->getAttribute('parent_id') ?? 0;
                        $position = $positions[$parent] = ($positions[$parent] ?? 0) + 1;
                        $before[$id] = (int) $category->getAttribute('position');
                        $after[$id] = $position;
                        if ($before[$id] !== $position) {
                            $this->records->storeCategory($category, ['position' => $position], $actor->id);
                        }
                    }
                    $this->auditLogs->recordManual('reordered', Category::class, 'post', tableName: 'cms_categories', auditableLabel: 'Kategori Konten', oldValues: ['positions' => $before], newValues: ['positions' => $after], metadata: ['outcome' => 'success', 'count' => count($ids)]);
                });
            });
        } catch (Throwable $exception) {
            $this->failure($resource, $exception);
            throw $exception;
        }
    }

    /** @param Collection<int,Category> $records @return array{categories:array,version:string} */
    private function categoryOrderState(Collection $records): array
    {
        if ($records->count() > 500) {
            throw ValidationException::withMessages(['order' => 'Pengaturan urutan mendukung hingga 500 kategori.']);
        }
        $rows = $records->map(fn (Category $row): array => array_intersect_key($row->getAttributes(), array_flip(['id', 'name', 'parent_id', 'position', 'status', 'updated_at'])))->all();

        return ['categories' => CmsCategoryTree::flatten($rows), 'version' => CmsCategoryTree::orderVersion($rows)];
    }

    private function authorizeMediaPicker(ClientLogin $actor): void
    {
        $permitted = false;
        foreach (['posts', 'pages', 'cards', 'media'] as $module) {
            $permitted = $permitted || $this->records->hasModule($actor, $module);
        }
        if (Auth::id() !== $actor->getKey() || ! $actor->isActive() || $actor->trashed() || ! $permitted) {
            $this->audit(CmsResource::Media, 'picker_denied', ['outcome' => 'denied']);
            abort(403);
        }
    }

    public function browseMediaPicker(ClientLogin $actor): void
    {
        $this->authorizeMediaPicker($actor);
        $this->audit(CmsResource::Media, 'picker', ['outcome' => 'success']);
    }

    /** @return array<string,mixed> */
    private function mediaLibraryRow(Media $media): array
    {
        return [
            'id' => (int) $media->getKey(), 'name' => (string) $media->getAttribute('name'),
            'url' => ! $media->trashed() && $media->getAttribute('status') === 'active' ? $this->mediaUrl($media) : '',
            'mime_type' => (string) $media->getAttribute('mime_type'), 'alt_text' => (string) $media->getAttribute('alt_text'),
            'caption' => (string) $media->getAttribute('caption'), 'original_name' => (string) $media->getAttribute('original_name'),
            'size' => (int) $media->getAttribute('size'), 'width' => $media->getAttribute('width'), 'height' => $media->getAttribute('height'),
            'status' => (string) $media->getAttribute('status'), 'archived' => $media->trashed(),
        ];
    }

    /** @param list<int> $ids */
    public function lifecycle(ClientLogin $actor, CmsResource $resource, string $operation, array $ids): int
    {
        $this->authorize($actor, $resource);
        try {
            $validated = Validator::make(['operation' => $operation, 'ids' => $ids], ['operation' => ['required', Rule::in(['archive', 'restore', 'delete_permanently'])], 'ids' => ['required', 'array', 'min:1', 'max:1000'], 'ids.*' => ['required', 'integer', 'min:1', 'distinct']])->validate();
            sort($validated['ids'], SORT_NUMERIC);

            return $this->auditLogs->withinAction('cms.'.$resource->value.'.'.$operation, 'Mengubah arsip '.$resource->label(), function () use ($actor, $resource, $operation, $validated): int {
                return DB::transaction(function () use ($actor, $resource, $operation, $validated): int {
                    $records = [];
                    foreach ($validated['ids'] as $id) {
                        $record = $this->find($resource, (int) $id, true, true);
                        abort_unless($record !== null, 404);
                        if (($operation === 'archive') === $record->trashed()) {
                            throw ValidationException::withMessages(['action' => 'Status arsip data telah berubah. Muat ulang daftar.']);
                        }
                        if ($operation !== 'restore' && $this->records->inUse($record, $operation === 'delete_permanently')) {
                            throw ValidationException::withMessages(['action' => 'Data masih digunakan. Lepaskan relasinya sebelum mengarsipkan atau menghapus.']);
                        }
                        $records[] = $record;
                    }
                    $relations = [];
                    foreach ($records as $record) {
                        $relations[$record->getKey()] = $this->records->relationCounts($record);
                        $path = $record instanceof Media && $operation === 'delete_permanently' ? $record->getAttribute('path') : null;
                        $this->records->transition($record, $operation, $actor->id);
                        if ($path) {
                            DB::afterCommit(fn () => $this->removeFile($path));
                        }
                    }
                    $this->auditLogs->recordManual($operation, 'cms_'.$resource->value, 'bulk', tableName: $resource->table(), auditableLabel: $resource->label(), metadata: ['outcome' => 'success', 'count' => count($records), 'ids' => $validated['ids'], 'relations' => $relations]);

                    return count($records);
                });
            });
        } catch (Throwable $exception) {
            $this->failure($resource, $exception);
            throw $exception;
        }
    }

    /** @param array<string,mixed> $filters */
    public function countFiltered(ClientLogin $actor, CmsResource $resource, array $filters = [], string $archive = 'active'): int
    {
        $this->authorize($actor, $resource);

        try {
            $count = $this->records->countWorkspace($resource, $this->filters($resource, $filters), $this->archive($archive));
            $this->audit($resource, 'scope_count', ['outcome' => 'success', 'count' => $count]);

            return $count;
        } catch (Throwable $exception) {
            $this->failure($resource, $exception);
            throw $exception;
        }
    }

    /** @param array<string,mixed> $filters */
    public function lifecycleFiltered(ClientLogin $actor, CmsResource $resource, string $operation, array $filters = [], string $archive = 'active', ?int $expectedCount = null): int
    {
        $this->authorize($actor, $resource);
        try {
            $normalizedFilters = $this->filters($resource, $filters);
            $archive = $this->archive($archive);

            return DB::transaction(function () use ($actor, $resource, $operation, $normalizedFilters, $archive, $expectedCount): int {
                $ids = $this->records->filteredIds($resource, $normalizedFilters, $archive, 1001);
                if (count($ids) > 1000) {
                    throw ValidationException::withMessages(['action' => 'Batasi filter hingga maksimal 1.000 data untuk satu tindakan.']);
                }
                if ($ids === []) {
                    throw ValidationException::withMessages(['action' => 'Tidak ada data yang sesuai filter.']);
                }
                if ($expectedCount !== null && count($ids) !== $expectedCount) {
                    throw ValidationException::withMessages(['action' => 'Jumlah data berubah. Periksa cakupan terbaru dan konfirmasi kembali.']);
                }

                return $this->lifecycle($actor, $resource, $operation, $ids);
            });
        } catch (Throwable $exception) {
            $this->failure($resource, $exception);
            throw $exception;
        }
    }

    public function installDefaults(): void
    {
        $this->records->seedMenuCategories();
    }

    /** @return Content|Category|Media|Gallery|Menu|MenuCategory|Comment|null */
    private function find(CmsResource $resource, int $id, bool $archived = false, bool $lock = false): ?Model
    {
        return match ($resource) {
            CmsResource::Posts, CmsResource::Pages, CmsResource::Cards => $this->records->content($id, $resource->kind(), $archived, $lock),
            CmsResource::PostCategories, CmsResource::PageCategories, CmsResource::CardCategories => $this->records->category($id, $resource->kind(), $archived, $lock),
            CmsResource::Media => $this->records->media($id, $archived, $lock), CmsResource::Galleries => $this->records->gallery($id, $archived, $lock),
            CmsResource::Menus => $this->records->menu($id, $archived, $lock), CmsResource::MenuCategories => $this->records->menuCategory($id, $archived, $lock), CmsResource::Comments => $this->records->comment($id, $archived, $lock),
        };
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function validated(CmsResource $resource, array $data, ?int $id): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }
        $rules = ['name' => ['required', 'string', 'max:255'], 'status' => ['required', Rule::in(array_keys($resource->statusOptions()))], 'position' => ['nullable', 'integer', 'min:0', 'max:1000000']];
        if (in_array($resource, [CmsResource::Posts, CmsResource::Pages], true)) {
            unset($data['name'], $data['position'], $rules['name'], $rules['position']);
        }
        $unique = fn (string $field) => Rule::unique($resource->table(), $field)->ignore($id)->when($resource->kind() !== null, fn ($rule) => $rule->where('kind', $resource->kind()));
        if ($resource->isContent()) {
            $data['body_format'] ??= 'markdown';
            $rules['body_format'] = ['required', Rule::in(['markdown', 'html'])];
            $slugSource = filled($data['title'] ?? null) ? $data['title'] : ($data['name'] ?? '');
            $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : ($resource === CmsResource::Cards ? null : Str::slug(is_string($slugSource) ? $slugSource : ''));
            $rules += ['title' => [in_array($resource, [CmsResource::Posts, CmsResource::Pages], true) ? 'required' : 'nullable', 'string', 'max:255'], 'subtitle' => ['nullable', 'string', 'max:255'], 'slug' => [$resource === CmsResource::Cards ? 'nullable' : 'required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $unique('slug')], 'excerpt' => ['nullable', 'string', 'max:2000'], 'body' => ['nullable', 'string', 'max:100000'], 'category_id' => ['nullable', 'integer'], 'image_id' => ['nullable', 'integer'], 'thumbnail_id' => ['nullable', 'integer'], 'logo_id' => ['nullable', 'integer'], 'link_label' => ['nullable', 'string', 'max:100'], 'link_url' => ['nullable', 'string', 'max:2000'], 'published_at' => ['nullable', 'date']];
            if ($resource === CmsResource::Cards) {
                $rules['card_preset'] = ['sometimes', 'required', 'string', Rule::in(array_keys(CmsCardPresets::all()))];
                $rules['card_data'] = ['sometimes', 'array:mode,items,'.implode(',', array_keys(CmsCardPresets::extraFields()))];
                foreach (CmsCardPresets::extraFields() as $key => $field) {
                    $rules['card_data.'.$key] = ['nullable', 'string', 'max:500'];
                }
                $rules['card_data.mode'] = ['sometimes', 'required', Rule::in(['single', 'collection'])];
                $rules['card_data.items'] = ['sometimes', 'array', 'list', 'max:50'];
                if (data_get($data, 'card_data.mode') === 'collection' && ($data['status'] ?? '') === 'published') {
                    $rules['card_data.items'] = ['required', 'array', 'list', 'min:1', 'max:50'];
                }
                $rules['card_data.items.*'] = ['required', 'array:key,'.implode(',', CmsCardPresets::itemFields())];
                $rules['card_data.items.*.key'] = ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'distinct'];
                foreach (CmsCardPresets::itemFields() as $field) {
                    $rules['card_data.items.*.'.$field] = match ($field) {
                        'image_id', 'thumbnail_id', 'logo_id' => ['nullable', 'integer', 'min:1'],
                        'starts_at', 'ends_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
                        'body' => ['nullable', 'string', 'max:10000'],
                        'excerpt', 'link_url' => ['nullable', 'string', 'max:2000'],
                        default => ['nullable', 'string', 'max:500'],
                    };
                }
                $rules['card_data.starts_at'] = ['nullable', 'date_format:Y-m-d\TH:i'];
                $rules['card_data.ends_at'] = ['nullable', 'date_format:Y-m-d\TH:i'];
                if (filled(data_get($data, 'card_data.starts_at'))) {
                    $rules['card_data.ends_at'][] = 'after_or_equal:card_data.starts_at';
                }
            }
            if ($resource === CmsResource::Pages) {
                $rules['category_id'] = ['required', 'integer', 'min:1'];
            }
            if ($resource === CmsResource::Posts && array_key_exists('category_ids', $data)) {
                $rules += ['category_ids' => ['array', 'max:100'], 'category_ids.*' => ['integer', 'min:1', 'distinct']];
            }
        } elseif ($resource->isCategory() || $resource === CmsResource::MenuCategories) {
            $data['code'] = filled($data['code'] ?? null) ? $data['code'] : Str::slug($data['name'] ?? '');
            $rules += ['code' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $unique('code')], 'description' => ['nullable', 'string', 'max:5000']];
            if ($resource === CmsResource::PostCategories) {
                $rules['parent_id'] = ['nullable', 'integer', 'min:1'];
            }
        } elseif ($resource === CmsResource::Media) {
            $rules += ['alt_text' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:5000']];
        } elseif ($resource === CmsResource::Galleries) {
            $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['name'] ?? '');
            $rules += ['slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $unique('slug')], 'description' => ['nullable', 'string', 'max:5000'], 'media_ids' => ['array', 'max:100'], 'media_ids.*' => ['integer', 'min:1', 'distinct']];
        } elseif ($resource === CmsResource::Menus) {
            $data['code'] = filled($data['code'] ?? null) ? $data['code'] : Str::slug($data['name'] ?? '');
            $rules += [
                'code' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $unique('code')],
                'category_id' => ['nullable', 'integer'],
                'items' => ['array', 'max:100'],
                'items.*.key' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', 'distinct'],
                'items.*.label' => ['required', 'string', 'max:255'],
                'items.*.url' => ['nullable', 'string', 'max:2000'],
                'items.*.target_type' => ['sometimes', 'required', Rule::in(['custom', ...array_keys(CmsMenuLinks::TYPES)])],
                'items.*.target_id' => ['nullable', 'integer', 'min:1'],
                'items.*.parent_key' => ['nullable', 'string', 'max:80'],
            ];
        }
        $valid = Validator::make($data, $rules, [], [
            'name' => $resource === CmsResource::Cards ? 'nama Card' : ($resource->isContent() ? 'nama internal' : 'nama'),
            'card_data.mode' => 'bentuk Card', 'card_data.items' => 'daftar item',
            'card_data.items.*.key' => 'identitas item',
            'card_data.items.*.title' => 'judul / nama item', 'card_data.items.*.body' => 'isi item',
            'card_data.items.*.image_id' => 'gambar item', 'card_data.items.*.logo_id' => 'logo item',
            'card_data.items.*.thumbnail_id' => 'thumbnail item', 'card_data.items.*.link_url' => 'tautan item',
            'card_data.items.*.starts_at' => 'waktu mulai kegiatan', 'card_data.items.*.ends_at' => 'waktu selesai kegiatan',
            'title' => 'judul', 'subtitle' => 'subjudul', 'slug' => 'slug',
            'excerpt' => 'ringkasan', 'body' => $resource === CmsResource::Comments ? 'komentar' : 'konten',
            'category_id' => 'kategori', 'category_ids' => 'kategori', 'category_ids.*' => 'kategori', 'parent_id' => 'kategori induk', 'image_id' => 'gambar utama',
            'thumbnail_id' => 'thumbnail', 'logo_id' => 'logo / ikon',
            'link_label' => 'label tautan', 'link_url' => 'tautan',
            'published_at' => 'jadwal terbit', 'position' => 'urutan',
            'code' => 'kode mapping', 'description' => 'deskripsi',
            'alt_text' => 'teks alternatif', 'caption' => 'keterangan', 'status' => 'status',
            'media_ids' => 'foto galeri', 'media_ids.*' => 'foto galeri',
            'items' => 'item menu', 'items.*.key' => 'identitas item menu',
            'items.*.label' => 'label item menu', 'items.*.url' => 'tautan item menu',
            'items.*.parent_key' => 'induk item menu',
            'content_id' => 'konten', 'author_name' => 'nama pengirim', 'author_email' => 'email pengirim',
        ] + collect(CmsCardPresets::extraFields())->mapWithKeys(fn ($field, $key) => ['card_data.'.$key => $field[0]])->all())->validate();
        foreach ($valid as $key => $value) {
            if ($value === '' && ! in_array($key, ['name', 'author_name', 'body'], true)) {
                $valid[$key] = null;
            }
        }
        if (in_array($resource, [CmsResource::Posts, CmsResource::Pages], true)) {
            $valid['name'] = $valid['title'];
        } elseif ($resource !== CmsResource::Comments) {
            $valid['position'] = (int) ($valid['position'] ?? 0);
        }
        if ($resource->kind()) {
            $valid['kind'] = $resource->kind();
        }
        if ($resource->isContent()) {
            if ($resource === CmsResource::Posts && array_key_exists('category_ids', $valid)) {
                foreach ($valid['category_ids'] as $categoryId) {
                    $category = $this->records->category((int) $categoryId, 'post', false, true);
                    if (! $category || $category->getAttribute('status') !== 'active') {
                        throw ValidationException::withMessages(['category_ids' => 'Salah satu kategori tidak tersedia. Pilih kembali kategori konten.']);
                    }
                }
                $valid['category_id'] = $valid['category_ids'][0] ?? null;
            }
            if (! empty($valid['category_id'])) {
                $category = $this->records->category((int) $valid['category_id'], $resource->kind(), false, true);
                if (! $category || $category->getAttribute('status') !== 'active') {
                    throw ValidationException::withMessages(['category_id' => 'Kategori tidak tersedia untuk jenis konten ini.']);
                }
            }
            foreach (['image_id', 'thumbnail_id', 'logo_id'] as $field) {
                if (! empty($valid[$field])) {
                    $this->image((int) $valid[$field], $field);
                } else {
                    $valid[$field] = null;
                }
            }
            if ($resource === CmsResource::Cards) {
                if (data_get($valid, 'card_data.mode') === 'collection' && ! array_key_exists($valid['card_preset'] ?? 'free', CmsCardPresets::collectionLabels())) {
                    throw ValidationException::withMessages(['card_data.mode' => 'Jenis Card ini menggunakan satu item. Pilih bentuk Satu item.']);
                }
                foreach (data_get($valid, 'card_data.items', []) as $index => $item) {
                    if (collect($item)->except('key')->filter(fn ($value) => filled($value))->isEmpty()) {
                        throw ValidationException::withMessages(['card_data.items.'.$index.'.title' => 'Isi nama, teks, atau gambar untuk item ini.']);
                    }
                    foreach (['image_id', 'thumbnail_id', 'logo_id'] as $field) {
                        if (! empty($item[$field])) {
                            $this->image((int) $item[$field], 'card_data.items.'.$index.'.'.$field);
                            $valid['card_data']['items'][$index][$field] = (int) $item[$field];
                        }
                    }
                    if (filled($item['link_url'] ?? null)) {
                        $this->safeUrl($item['link_url'], 'card_data.items.'.$index.'.link_url');
                    }
                    if (filled($item['starts_at'] ?? null) && filled($item['ends_at'] ?? null) && $item['ends_at'] < $item['starts_at']) {
                        throw ValidationException::withMessages(['card_data.items.'.$index.'.ends_at' => 'Waktu selesai tidak boleh sebelum waktu mulai.']);
                    }
                }
            }
            if (filled($valid['link_url'] ?? null)) {
                $this->safeUrl($valid['link_url'], 'link_url');
            }
            if ($valid['status'] === 'published' && empty($valid['published_at'])) {
                $valid['published_at'] = now();
            }
        }
        if ($resource === CmsResource::PostCategories && ! empty($valid['parent_id'])) {
            $parent = $this->records->category((int) $valid['parent_id'], 'post', false, true);
            if (! $parent || $parent->getAttribute('status') !== 'active' || $parent->getKey() === $id) {
                throw ValidationException::withMessages(['parent_id' => 'Kategori induk tidak tersedia.']);
            }
            $seen = [$id];
            while ($parent) {
                if (in_array($parent->getKey(), $seen, true)) {
                    throw ValidationException::withMessages(['parent_id' => 'Kategori tidak dapat menjadi induk bagi dirinya sendiri.']);
                }
                $seen[] = $parent->getKey();
                $parent = $parent->getAttribute('parent_id') ? $this->records->category((int) $parent->getAttribute('parent_id'), 'post', false, true) : null;
            }
        }
        if ($resource === CmsResource::Galleries) {
            foreach ($valid['media_ids'] ?? [] as $mediaId) {
                $this->image((int) $mediaId, 'media_ids');
            }
            unset($valid['media_ids']);
        }
        if ($resource === CmsResource::Menus) {
            if (! empty($valid['category_id'])) {
                $category = $this->records->menuCategory((int) $valid['category_id'], false, true);
                if (! $category || $category->getAttribute('status') !== 'active') {
                    throw ValidationException::withMessages(['category_id' => 'Kategori menu tidak tersedia.']);
                }
            }
            $valid['items'] = $this->menuItems($valid['items'] ?? []);
        }

        return $valid;
    }

    private function image(int $id, string $field): void
    {
        $media = $this->records->media($id, false, true);
        if (! $media || $media->getAttribute('status') !== 'active' || ! str_starts_with($media->getAttribute('mime_type'), 'image/')) {
            throw ValidationException::withMessages([$field => 'Pilih gambar aktif dari pustaka media.']);
        }
    }

    private function safeUrl(string $url, string $field): void
    {
        if (preg_match('/[\\x00-\\x20\\x7f]/', $url) || (! str_starts_with($url, '#') && ! (str_starts_with($url, '/') && ! str_starts_with($url, '//')) && ! (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) && ! parse_url($url, PHP_URL_USER) && ! parse_url($url, PHP_URL_PASS)))) {
            throw ValidationException::withMessages([$field => 'Gunakan URL http/https, path yang diawali /, atau #anchor.']);
        }
    }

    /** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    private function menuItems(array $items): array
    {
        $targets = $this->menuLinks->targets($items);
        $normalized = [];
        $parents = [];
        foreach ($items as $index => $item) {
            $type = $item['target_type'] ?? 'custom';
            $row = ['key' => $item['key'], 'label' => $item['label'], 'parent_key' => filled($item['parent_key'] ?? null) ? $item['parent_key'] : null];
            if ($type === 'custom') {
                $url = (string) ($item['url'] ?? '');
                $this->safeUrl($url, 'items.'.$index.'.url');
                $row['url'] = $url;
            } else {
                $id = (int) ($item['target_id'] ?? 0);
                if (! isset($targets[$type][$id])) {
                    throw ValidationException::withMessages(['items.'.$index.'.target_id' => 'Pilih tujuan yang tersedia untuk jenis tautan ini.']);
                }
                $row['target_type'] = $type;
                $row['target_id'] = $id;
            }
            $normalized[] = $row;
            $parents[$item['key']] = $row['parent_key'];
        }
        foreach ($parents as $key => $parent) {
            $seen = [$key];
            while ($parent !== null) {
                if (! array_key_exists($parent, $parents) || in_array($parent, $seen, true) || count($seen) >= 3) {
                    throw ValidationException::withMessages(['items' => 'Induk menu harus tersedia, tanpa siklus, dengan maksimal tiga tingkat.']);
                }
                $seen[] = $parent;
                $parent = $parents[$parent];
            }
        }

        return $normalized;
    }

    /** @return array<string,mixed> */
    private function uploadData(UploadedFile $upload): array
    {
        Validator::make(['upload' => $upload], ['upload' => ['required', 'file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf']], [], ['upload' => 'file'])->validate();
        $mime = $upload->getMimeType();
        $extension = match ($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'application/pdf' => 'pdf', default => throw ValidationException::withMessages(['upload' => 'Format file tidak didukung.'])
        };
        $dimensions = str_starts_with($mime, 'image/') ? @getimagesize($upload->getRealPath()) : false;
        if (str_starts_with($mime, 'image/') && (! $dimensions || $dimensions[0] > 12000 || $dimensions[1] > 12000)) {
            throw ValidationException::withMessages(['upload' => 'Gambar tidak valid atau melebihi ukuran 12.000 piksel.']);
        }
        $path = $upload->storeAs('cms/media', (string) Str::ulid().'.'.$extension, 'local');
        if (! is_string($path)) {
            throw new \RuntimeException('Unable to store CMS media.');
        }

        return ['path' => $path, 'original_name' => mb_substr(preg_replace('/[\\x00-\\x1f\\x7f]/', '', basename($upload->getClientOriginalName())), 0, 255), 'mime_type' => $mime, 'size' => $upload->getSize(), 'width' => $dimensions ? $dimensions[0] : null, 'height' => $dimensions ? $dimensions[1] : null];
    }

    private function archive(string $archive): string
    {
        return Validator::make(['archive' => $archive], ['archive' => ['required', Rule::in(['active', 'archived', 'all'])]])->validate()['archive'];
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    private function filters(CmsResource $resource, array $filters): array
    {
        return Validator::make($filters, ['media_type' => ['nullable', Rule::in(['all', 'image', 'pdf'])], 'search' => ['nullable', 'string', 'max:100'], 'name' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(array_keys($resource->statusOptions()))], 'category_id' => ['nullable', 'integer', 'min:1'], 'position_min' => ['nullable', 'integer', 'min:0'], 'position_max' => ['nullable', 'integer', 'min:0'], 'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d'], 'body' => ['nullable', 'string', 'max:100'], 'content_id' => ['nullable', 'integer', 'min:1']])->validate();
    }

    /** @param Collection<int,Media> $media @return list<array{id:int,name:string,url:string,mime_type:string}> */
    private function mediaRows(Collection $media): array
    {
        return $media->map(fn (Media $row): array => ['id' => (int) $row->getKey(), 'name' => (string) $row->getAttribute('name'), 'url' => $this->mediaUrl($row), 'mime_type' => (string) $row->getAttribute('mime_type')])->values()->all();
    }

    private function mediaUrl(Media $media): string
    {
        return route('starter.cms.media', ['publicId' => $media->getAttribute('public_id')]);
    }

    /** @param array<string,mixed> $metadata */
    private function audit(CmsResource $resource, string $action, array $metadata): void
    {
        $this->auditLogs->withinAction('cms.'.$resource->value.'.'.$action, $resource->label(), fn () => $this->auditLogs->recordManual($action, 'cms_'.$resource->value, 'workspace', tableName: $resource->table(), auditableLabel: $resource->label(), metadata: $metadata));
    }

    private function authorizeDashboard(ClientLogin $actor): void
    {
        if (Auth::id() !== $actor->getKey() || ! $actor->isActive() || $actor->trashed() || ! $this->records->hasModule($actor, 'dashboard')) {
            $this->auditLogs->withinAction('cms.dashboard.denied', 'Akses beranda CMS ditolak', fn () => $this->auditLogs->recordManual('denied', 'cms_dashboard', 'dashboard', metadata: ['outcome' => 'denied']));
            abort(403);
        }
    }

    private function removeFile(string $path): void
    {
        try {
            Storage::disk('local')->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function failure(CmsResource $resource, Throwable $exception): void
    {
        $this->audit($resource, 'failed', ['outcome' => $exception instanceof ValidationException ? 'validation_failed' : 'failed', 'exception' => class_basename($exception)]);
    }
}
