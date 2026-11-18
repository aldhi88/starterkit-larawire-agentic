<?php

namespace Aldhi88\StarterKit\Support\Starter;

class GoogleLogin
{
    public static function enabled(): bool
    {
        return (bool) config('starter.google.enabled') && filled(config('starter.google.client_id'))
            && filled(config('starter.google.client_secret'));
    }
}
