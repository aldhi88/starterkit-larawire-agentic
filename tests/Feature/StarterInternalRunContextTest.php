<?php

use Aldhi88\StarterKit\Support\Starter\StarterInternalRunContext;

it('allows only the active internal mode and clears it after execution', function (): void {
    $context = new StarterInternalRunContext;

    expect($context->allows('deploy'))->toBeFalse();

    $result = $context->run('deploy', function () use ($context): string {
        expect($context->allows('deploy'))->toBeTrue()
            ->and($context->allows('install'))->toBeFalse();

        return 'ok';
    });

    expect($result)->toBe('ok')
        ->and($context->allows('deploy'))->toBeFalse();
});

it('rejects nested internal execution contexts', function (): void {
    $context = new StarterInternalRunContext;

    $context->run('deploy', fn () => $context->run('install', fn () => null));
})->throws(LogicException::class);
