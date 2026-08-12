<?php

namespace Aldhi88\StarterKit\Contracts\Starter;

use Aldhi88\StarterKit\Models\Starter\Client;

interface ClientInterface
{
    public function current(): Client;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(Client $client, array $data): Client;
}
