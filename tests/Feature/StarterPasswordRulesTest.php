<?php

use Aldhi88\StarterKit\Rules\Starter\StarterPasswordRules;
use Illuminate\Support\Facades\Validator;

it('allows a simple non-empty password for local bootstrap only', function (): void {
    $local = Validator::make(['password' => '123'], [
        'password' => StarterPasswordRules::localBootstrapRules(),
    ]);
    $production = Validator::make(['password' => '123'], [
        'password' => StarterPasswordRules::rules(),
    ]);

    expect($local->passes())->toBeTrue()
        ->and($production->fails())->toBeTrue();
});

it('keeps strong passwords valid for production bootstrap', function (): void {
    $validator = Validator::make(['password' => 'StrongPass123'], [
        'password' => StarterPasswordRules::rules(),
    ]);

    expect($validator->passes())->toBeTrue();
});
