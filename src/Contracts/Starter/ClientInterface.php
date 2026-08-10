<?php

namespace Altekno\StarterKit\Contracts\Starter;

use Altekno\StarterKit\Models\Starter\Client;

interface ClientInterface
{
    public function current(): Client;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(Client $client, array $data): Client;
}
