<?php

namespace Aldhi88\StarterKit\Installation;

final readonly class StarterDatabaseProvisioning
{
    public function __construct(
        public bool $created,
        public string $connection,
        public string $driver,
        public string $database,
    ) {}
}
