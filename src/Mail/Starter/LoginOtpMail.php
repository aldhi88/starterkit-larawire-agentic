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

class LoginOtpMail extends Mailable implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $appName,
        public readonly string $brandName,
        public readonly ?string $brandLogoUrl,
        public readonly string $recipientName,
        public readonly string $otpCode,
        public readonly int $expiresInMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode OTP login - '.$this->brandName);
    }

    public function content(): Content
    {
        return new Content(
            view: (string) config('starter.auth.login_otp_mail_view', 'starter-mail::login-otp'),
            text: (string) config('starter.auth.login_otp_mail_text_view', 'starter-mail::login-otp-text'),
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
