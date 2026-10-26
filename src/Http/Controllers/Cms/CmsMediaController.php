<?php

namespace Aldhi88\StarterKit\Http\Controllers\Cms;

use Aldhi88\StarterKit\Services\Cms\CmsReader;
use Aldhi88\StarterKit\Services\Starter\AuditLogService;
use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CmsMediaController
{
    public function __construct(
        private readonly CmsReader $reader,
        private readonly FilesystemManager $filesystems,
        private readonly AuditLogService $audit,
    ) {}

    public function __invoke(string $publicId): StreamedResponse
    {
        $media = $this->reader->media($publicId);
        abort_unless($media !== null, 404);

        $path = (string) $media->getAttribute('path');
        $mime = (string) $media->getAttribute('mime_type');
        abort_unless(
            preg_match('#^cms/media/[a-zA-Z0-9_-]+\.(?:jpe?g|png|gif|webp|pdf)$#D', $path) === 1
            && in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'], true),
            404,
        );

        $disk = $this->filesystems->disk('local');
        abort_unless($disk->exists($path), 404);

        $this->audit->withinAction('cms.media.download', 'Mengakses media CMS', function () use ($media): void {
            $this->audit->recordManual(
                event: 'read',
                auditableType: $media::class,
                auditableId: $media->getKey(),
                tableName: $media->getTable(),
                auditableLabel: (string) $media->getAttribute('name'),
            );
        });

        return $disk->response($path, (string) $media->getAttribute('original_name'), [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
            'Cache-Control' => 'no-store',
        ]);
    }
}
