<?php

use Aldhi88\StarterKit\Support\Starter\StarterBootstrap;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

function renderLivewireAuthenticationException(Request $request): Response
{
    $handler = app(ExceptionHandler::class);

    expect($handler)->toBeInstanceOf(Handler::class);

    StarterBootstrap::configureExceptions(new Exceptions($handler));

    return $handler->render($request, new AuthenticationException('Unauthenticated.'));
}

it('returns json 401 with the safe originating page for an unauthenticated livewire request', function (): void {
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);

    $request = Request::create('https://hr.company.test/livewire-4.4.0/update', 'POST');
    $request->headers->set('X-Livewire', '1');
    $request->headers->set('X-Starter-Page-Url', 'https://hr.company.test/reports?month=9');

    $response = renderLivewireAuthenticationException($request);
    $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($response->getStatusCode())->toBe(401)
        ->and($payload['return_url'])->toBe('https://hr.company.test/reports?month=9')
        ->and($payload['login_url'])->toBe($payload['redirect'])
        ->and($payload['redirect'])->toBe(
            'https://company.test/auth/login?redirect='.urlencode('https://hr.company.test/reports?month=9'),
        )
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('falls back to post-login authorization instead of intending an internal livewire endpoint', function (): void {
    config([
        'app.domain' => 'company.test',
        'app.url' => 'https://company.test',
    ]);

    $request = Request::create('https://hr.company.test/livewire-4.4.0/update', 'POST');
    $request->headers->set('X-Livewire', '1');
    $request->headers->set('X-Starter-Page-Url', 'https://hr.company.test/livewire-4.4.0/update');
    $request->headers->set('referer', 'https://hr.company.test/session/activity');

    $response = renderLivewireAuthenticationException($request);
    $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($response->getStatusCode())->toBe(401)
        ->and($payload['return_url'])->toBeNull()
        ->and($payload['redirect'])->toBe('https://company.test/auth/login')
        ->and($payload['redirect'])->not->toContain('livewire')
        ->and($payload['redirect'])->not->toContain('session%2Factivity');
});
