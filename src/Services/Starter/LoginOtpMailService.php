<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Mail\Starter\LoginOtpMail;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Contracts\Mail\Factory as MailFactory;

class LoginOtpMailService
{
    public function __construct(
        private readonly MailFactory $mail,
        private readonly StarterContextService $context,
    ) {}

    public function send(ClientLogin $login, string $otpCode, int $expiresInMinutes): void
    {
        $brand = $this->context->brandData();
        $appName = (string) config('app.name', 'Aplikasi');

        $message = new LoginOtpMail(
            appName: $appName,
            brandName: filled($brand['clientName']) ? (string) $brand['clientName'] : $appName,
            brandLogoUrl: filled($brand['clientLogoUrl'])
                ? (string) $brand['clientLogoUrl']
                : $this->defaultThemeLogoUrl(),
            recipientName: $login->name,
            otpCode: $otpCode,
            expiresInMinutes: $expiresInMinutes,
        );
        $message->to($login->email, $login->name);

        $this->mail->mailer($this->mailerName())->send($message);
    }

    private function defaultThemeLogoUrl(): ?string
    {
        $path = config('starter.themes.'.StarterTheme::key().'.mail_logo');

        if (! is_string($path) || $path === '' || str_contains($path, '..')) {
            return null;
        }

        return asset(ltrim($path, '/'));
    }

    private function mailerName(): ?string
    {
        $defaultMailer = (string) config('mail.default');
        $transport = (string) config("mail.mailers.{$defaultMailer}.transport");

        if (! app()->isProduction()
            && $transport === 'log'
            && is_array(config('mail.mailers.array'))) {
            return 'array';
        }

        return $defaultMailer !== '' ? $defaultMailer : null;
    }
}
