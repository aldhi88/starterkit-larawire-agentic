<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Exceptions\Starter\TemporaryPasswordDeliveryException;
use Aldhi88\StarterKit\Mail\Starter\TemporaryPasswordMail;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Throwable;

class TemporaryPasswordMailService
{
    public function __construct(
        private readonly MailFactory $mail,
    ) {}

    public function queueForNewAccount(ClientLogin $login, string $temporaryPassword): void
    {
        $this->queue($login, $temporaryPassword, passwordWasReset: false);
    }

    public function queueForReset(ClientLogin $login, string $temporaryPassword): void
    {
        $this->queue($login, $temporaryPassword, passwordWasReset: true);
    }

    private function queue(ClientLogin $login, string $temporaryPassword, bool $passwordWasReset): void
    {
        try {
            $message = new TemporaryPasswordMail(
                appName: (string) config('app.name', 'Aplikasi'),
                recipientName: $login->name,
                username: $login->username,
                temporaryPassword: $temporaryPassword,
                loginUrl: StarterNavigation::authLoginUrl(),
                passwordWasReset: $passwordWasReset,
            );
            $message->to($login->email, $login->name);

            $this->mail->mailer($this->mailerName())->queue($message);
        } catch (Throwable $exception) {
            throw new TemporaryPasswordDeliveryException(
                'Temporary password email could not be processed by the configured queue.',
                previous: $exception,
            );
        }
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
