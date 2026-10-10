<?php

namespace Aldhi88\StarterKit\Http\Controllers\Starter;

use Aldhi88\StarterKit\Support\Starter\StarterBrandStylesheet;
use Illuminate\Http\Response;

class BrandStylesheetController
{
    public function __invoke(): Response
    {
        $stylesheet = StarterBrandStylesheet::configured();
        abort_if($stylesheet === null, 404);

        return response($stylesheet->css(), 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
