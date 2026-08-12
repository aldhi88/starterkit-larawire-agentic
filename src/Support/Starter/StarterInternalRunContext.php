<?php

namespace Aldhi88\StarterKit\Support\Starter;

use Closure;
use LogicException;

class StarterInternalRunContext
{
    private ?string $mode = null;

    public function allows(string ...$modes): bool
    {
        return $this->mode !== null && in_array($this->mode, $modes, true);
    }

    public function run(string $mode, Closure $callback): mixed
    {
        if ($this->mode !== null) {
            throw new LogicException('Starter internal command context is already active.');
        }

        $this->mode = $mode;

        try {
            return $callback();
        } finally {
            $this->mode = null;
        }
    }
}
