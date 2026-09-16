<?php

namespace Aldhi88\StarterKit\Rules\Starter;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class StarterPasswordRules
{
    public const MIN_LENGTH = 6;

    public const GENERATED_LENGTH = 12;

    /** @return list<mixed> */
    public static function rules(): array
    {
        return ['required', 'string', 'max:255', Password::min(self::MIN_LENGTH)->mixedCase()->numbers()];
    }

    /** @return list<string> */
    public static function localBootstrapRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    public static function generate(): string
    {
        do {
            $password = Str::password(self::GENERATED_LENGTH, symbols: false);
        } while (Validator::make(['password' => $password], ['password' => self::rules()])->fails());

        return $password;
    }
}
