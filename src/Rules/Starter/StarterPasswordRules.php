<?php

namespace Altekno\StarterKit\Rules\Starter;

use Illuminate\Validation\Rules\Password;

class StarterPasswordRules
{
    /** @return list<mixed> */
    public static function rules(): array
    {
        return ['required', 'string', 'max:255', Password::min(10)->mixedCase()->numbers()];
    }
}
