<?php

namespace Aldhi88\StarterKit\Installation;

class FreshLaravelReport
{
    /** @param list<string> $findings */
    public function __construct(
        public readonly array $findings,
        public readonly bool $migrationsHaveRun,
    ) {}

    public function isFresh(): bool
    {
        return $this->findings === [];
    }
}
