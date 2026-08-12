<?php

namespace Aldhi88\StarterKit\Repositories\Starter;

use Aldhi88\StarterKit\Contracts\Starter\StarterConfigInterface;
use Aldhi88\StarterKit\Models\Starter\StarterConfig;

class StarterConfigRepository implements StarterConfigInterface
{
    public function findByKey(string $key): ?StarterConfig
    {
        return StarterConfig::query()->where('key', $key)->first();
    }

    public function findByKeyOrFail(string $key): StarterConfig
    {
        return StarterConfig::query()->where('key', $key)->firstOrFail();
    }

    public function updateValue(StarterConfig $config, string $value): StarterConfig
    {
        $config->forceFill(['value' => $value])->save();

        return $config;
    }
}
