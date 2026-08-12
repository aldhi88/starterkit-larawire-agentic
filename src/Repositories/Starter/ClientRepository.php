<?php

namespace Aldhi88\StarterKit\Repositories\Starter;

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Models\Starter\Client;

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
