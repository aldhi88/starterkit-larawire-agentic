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
    $validator = Validator::make(['password' => 'Abc123'], [
        'password' => StarterPasswordRules::rules(),
    ]);

    expect($validator->passes())->toBeTrue();
});

it('requires six characters with mixed case and a number', function (string $password): void {
    $validator = Validator::make(['password' => $password], [
        'password' => StarterPasswordRules::rules(),
    ]);

    expect($validator->fails())->toBeTrue();
})->with([
    'too short' => 'Ab12',
    'no uppercase' => 'abc123',
    'no lowercase' => 'ABC123',
    'no number' => 'Abcdef',
]);

it('generates valid readable passwords for the profile helper', function (): void {
    foreach (range(1, 20) as $iteration) {
        $password = StarterPasswordRules::generate();
        $validator = Validator::make(['password' => $password], [
            'password' => StarterPasswordRules::rules(),
        ]);

        expect($password)->toHaveLength(StarterPasswordRules::GENERATED_LENGTH)
            ->and($password)->toMatch('/^[A-Za-z0-9]+$/')
            ->and($validator->passes())->toBeTrue();
    }
});
