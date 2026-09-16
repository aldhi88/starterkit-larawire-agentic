<?php

namespace Aldhi88\StarterKit\Mail\Starter;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemporaryPasswordMail extends Mailable implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $appName,
        public readonly string $recipientName,
        public readonly string $username,
        public readonly string $temporaryPassword,
        public readonly string $loginUrl,
        public readonly bool $passwordWasReset,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->passwordWasReset
                ? 'Password sementara baru - '.$this->appName
                : 'Akun '.$this->appName.' telah dibuat',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'starter-shared::mail.temporary-password',
            text: 'starter-shared::mail.temporary-password-text',
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
