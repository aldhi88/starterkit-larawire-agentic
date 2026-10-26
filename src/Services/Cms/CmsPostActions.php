<?php

namespace Aldhi88\StarterKit\Services\Cms;

use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Models\Cms\Content;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CmsPostActions
{
    public function __construct(private readonly CmsRepositoryInterface $records, private readonly CmsManager $manager, private readonly AuditLogService $auditLogs) {}

    /** @param list<int> $ids @param array<string,mixed>|null $filters @return array{ids:list<int>,version:string,title:string,category_ids:list<int>,scheduled:int} */
    public function prepare(ClientLogin $actor, array $ids, ?array $filters = null): array
    {
        $this->manager->authorize($actor, CmsResource::Posts);
        try {
            return DB::transaction(function () use ($actor, $ids, $filters): array {
                $ids = $this->scopeIds($actor, $ids, $filters);
                $posts = $this->records->postsForUpdate($ids);
                $this->ensureComplete($posts, $ids);
                $this->audit('quick_action_preview', ['outcome' => 'success', 'count' => count($ids), 'filtered' => $filters !== null]);

                return ['ids' => $ids, 'version' => $this->version($posts),
                    'title' => $posts->count() === 1 ? (string) $posts->first()->getAttribute('title') : '',
                    'category_ids' => $posts->count() === 1 ? $this->categoryIds($posts->first()) : [],
                    'scheduled' => $posts->filter(fn (Content $post): bool => $post->getAttribute('status') === 'published' && $post->getAttribute('published_at')?->isFuture())->count()];
            });
        } catch (Throwable $exception) {
            $this->failed($exception);
            throw $exception;
        }
    }

    /** @param list<int> $ids @param list<int> $categoryIds @param array<string,mixed>|null $filters */
    public function apply(ClientLogin $actor, string $action, array $ids, string $version, string $categoryMode = 'add', array $categoryIds = [], ?array $filters = null): int
    {
        $this->manager->authorize($actor, CmsResource::Posts);
        try {
            Validator::make(['action' => $action, 'mode' => $categoryMode, 'category_ids' => $categoryIds], [
                'action' => ['required', Rule::in(['publish_now', 'draft', 'categories'])],
                'mode' => ['required', Rule::in(['add', 'replace', 'remove', 'clear'])],
                'category_ids' => ['array', 'max:100'], 'category_ids.*' => ['integer', 'min:1', 'distinct'],
            ])->validate();
            $ids = $this->scopeIds($actor, $ids, null);
            $categoryIds = array_map('intval', $categoryIds);

            return $this->auditLogs->withinAction('cms.posts.quick_'.$action, 'Mengubah postingan dari tabel', function () use ($actor, $action, $ids, $version, $categoryMode, $categoryIds, $filters): int {
                return DB::transaction(function () use ($actor, $action, $ids, $version, $categoryMode, $categoryIds, $filters): int {
                    if ($filters !== null && $this->scopeIds($actor, [], $filters) !== $ids) {
                        throw ValidationException::withMessages(['action' => 'Hasil filter telah berubah. Tutup dialog dan pilih aksi kembali.']);
                    }
                    $posts = $this->records->postsForUpdate($ids, true);
                    $this->ensureComplete($posts, $ids);
                    if (! hash_equals($this->version($posts), $version)) {
                        throw ValidationException::withMessages(['action' => 'Ada postingan yang telah berubah. Tutup dialog dan pilih aksi kembali.']);
                    }
                    if ($action === 'categories' && $categoryMode !== 'clear') {
                        if ($categoryIds === []) {
                            throw ValidationException::withMessages(['category_ids' => 'Pilih minimal satu kategori, atau pilih Kosongkan kategori.']);
                        }
                        foreach ($categoryIds as $id) {
                            $category = $this->records->category($id, 'post', false, true);
                            if (! $category || ($categoryMode !== 'remove' && $category->getAttribute('status') !== 'active')) {
                                throw ValidationException::withMessages(['category_ids' => 'Kategori tidak tersedia. Periksa pilihan kategori.']);
                            }
                        }
                    }
                    $before = [];
                    $after = [];
                    foreach ($posts as $post) {
                        $old = ['status' => $post->getAttribute('status'), 'published_at' => $post->getRawOriginal('published_at'), 'category_ids' => $this->categoryIds($post)];
                        $data = [];
                        $categories = $old['category_ids'];
                        if ($action === 'publish_now') {
                            if (blank($post->getAttribute('title')) || blank($post->getAttribute('slug'))) {
                                throw ValidationException::withMessages(['action' => 'Lengkapi judul dan alamat postingan sebelum menerbitkan.']);
                            }
                            $data['status'] = 'published';
                            if ($old['status'] !== 'published' || ! $post->getAttribute('published_at') || $post->getAttribute('published_at')->isFuture()) {
                                $data['published_at'] = now();
                            }
                        } elseif ($action === 'draft') {
                            $data['status'] = 'draft';
                        } else {
                            $categories = match ($categoryMode) {
                                'add' => array_values(array_unique([...$categories, ...$categoryIds])),
                                'replace' => $categoryIds,
                                'remove' => array_values(array_diff($categories, $categoryIds)),
                                'clear' => [],
                                default => throw ValidationException::withMessages(['category_ids' => 'Mode perubahan kategori tidak valid.']),
                            };
                            if (count($categories) > 100) {
                                throw ValidationException::withMessages(['category_ids' => 'Satu postingan maksimal memiliki 100 kategori.']);
                            }
                            $data['category_id'] = $categories[0] ?? null;
                        }
                        $this->records->storeContent($post, $data, $actor->id);
                        if ($action === 'categories') {
                            $this->records->syncPostCategories($post, $categories, $actor->id);
                        }
                        $before[$post->getKey()] = $old;
                        $after[$post->getKey()] = ['status' => $post->getAttribute('status'), 'published_at' => $post->getRawOriginal('published_at'), 'category_ids' => $categories];
                    }
                    $this->auditLogs->recordManual('quick_'.$action, Content::class, 'bulk', oldValues: ['posts' => $before], newValues: ['posts' => $after], tableName: 'cms_contents', auditableLabel: 'Postingan', metadata: ['outcome' => 'success', 'count' => count($ids), 'category_mode' => $action === 'categories' ? $categoryMode : null]);

                    return count($ids);
                });
            });
        } catch (Throwable $exception) {
            $this->failed($exception);
            throw $exception;
        }
    }

    /** @param list<int> $ids @param array<string,mixed>|null $filters @return list<int> */
    private function scopeIds(ClientLogin $actor, array $ids, ?array $filters): array
    {
        if ($filters !== null) {
            $this->manager->countFiltered($actor, CmsResource::Posts, $filters, 'active');
            $ids = $this->records->filteredIds(CmsResource::Posts, $filters, 'active', 1001);
        }
        Validator::make(['ids' => $ids], ['ids' => ['required', 'array', 'min:1', 'max:1000'], 'ids.*' => ['integer', 'min:1', 'distinct']], [
            'ids.required' => 'Pilih minimal satu postingan.', 'ids.max' => 'Batasi pilihan atau filter hingga 1.000 postingan.',
        ])->validate();
        $ids = array_map('intval', $ids);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    /** @param Collection<int,Content> $posts @param list<int> $ids */
    private function ensureComplete(Collection $posts, array $ids): void
    {
        if ($posts->count() !== count($ids)) {
            throw ValidationException::withMessages(['action' => 'Ada postingan yang tidak tersedia atau sudah diarsipkan. Perbarui pilihan Anda.']);
        }
    }

    /** @return list<int> */
    private function categoryIds(Content $post): array
    {
        $ids = $post->getRelation('categories')->modelKeys();
        if ($post->getAttribute('category_id')) {
            $primary = (int) $post->getAttribute('category_id');
            $ids = [$primary, ...array_values(array_diff($ids, [$primary]))];
        }

        return $ids;
    }

    /** @param Collection<int,Content> $posts */
    private function version(Collection $posts): string
    {
        return hash('sha256', json_encode($posts->map(fn (Content $post): array => [$post->getAttributes(), $this->categoryIds($post)])->all(), JSON_THROW_ON_ERROR));
    }

    /** @param array<string,mixed> $metadata */
    private function audit(string $event, array $metadata): void
    {
        $this->auditLogs->withinAction('cms.posts.'.$event, 'Aksi cepat postingan', fn () => $this->auditLogs->recordManual($event, Content::class, 'bulk', tableName: 'cms_contents', auditableLabel: 'Postingan', metadata: $metadata));
    }

    private function failed(Throwable $exception): void
    {
        $this->audit('quick_action_failed', ['outcome' => $exception instanceof ValidationException ? 'validation_failed' : 'failed', 'exception' => class_basename($exception)]);
    }
}
