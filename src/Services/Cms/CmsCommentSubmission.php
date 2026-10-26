<?php

namespace Aldhi88\StarterKit\Services\Cms;

use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use Aldhi88\StarterKit\Models\Cms\Comment;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** Host frontend routes own CSRF, request-shape validation, and throttling. */
class CmsCommentSubmission
{
    public function __construct(private readonly CmsRepositoryInterface $records, private readonly AuditLogService $auditLogs) {}

    public function submit(string $postSlug, string $authorName, ?string $authorEmail, string $body): Comment
    {
        try {
            return $this->auditLogs->withinAction('cms.comments.submit', 'Mengirim komentar', function () use ($postSlug, $authorName, $authorEmail, $body): Comment {
                return DB::transaction(function () use ($postSlug, $authorName, $authorEmail, $body): Comment {
                    $actor = Auth::user();
                    if ($actor instanceof ClientLogin) {
                        abort_unless($actor->isActive() && ! $actor->trashed(), 403);
                    }
                    $data = Validator::make([
                        'post_slug' => trim($postSlug), 'author_name' => trim(strip_tags($authorName)),
                        'author_email' => filled($authorEmail) ? trim($authorEmail) : null, 'body' => trim(strip_tags($body)),
                    ], [
                        'post_slug' => ['required', 'string', 'max:180'], 'author_name' => ['required', 'string', 'max:255'],
                        'author_email' => ['nullable', 'email', 'max:255'], 'body' => ['required', 'string', 'max:10000'],
                    ], [], ['post_slug' => 'postingan', 'author_name' => 'nama pengirim', 'author_email' => 'email pengirim', 'body' => 'komentar'])->validate();
                    $post = $this->records->publishedPostForSubmission($data['post_slug']);
                    abort_unless($post !== null, 404);
                    $comment = $this->records->storeSubmittedComment($post->getKey(), [
                        'author_name' => $data['author_name'], 'author_email' => $data['author_email'], 'body' => $data['body'],
                    ], $actor instanceof ClientLogin ? $actor->id : null);
                    $this->auditLogs->recordManual('submitted', Comment::class, $comment->getKey(), tableName: 'cms_comments', auditableLabel: 'Komentar', metadata: ['outcome' => 'success', 'post_id' => $post->getKey(), 'status' => 'pending']);

                    return $comment;
                });
            });
        } catch (Throwable $exception) {
            $this->auditLogs->withinAction('cms.comments.submit_failed', 'Komentar gagal dikirim', fn () => $this->auditLogs->recordManual('failed', Comment::class, 'submission', tableName: 'cms_comments', auditableLabel: 'Komentar', metadata: ['outcome' => 'failed', 'exception' => class_basename($exception)]));
            throw $exception;
        }
    }
}
