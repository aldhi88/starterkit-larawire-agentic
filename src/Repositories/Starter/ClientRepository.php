<?php

namespace Altekno\StarterKit\Repositories\Starter;

use Altekno\StarterKit\Contracts\Starter\ClientInterface;
use Altekno\StarterKit\Models\Starter\Client;

class ClientRepository implements ClientInterface
{
    public function current(): Client
    {
        return Client::query()->firstOrFail();
    }

    public function updateProfile(Client $client, array $data): Client
    {
        $client->forceFill($data)->save();

        return $client;
    }
}
