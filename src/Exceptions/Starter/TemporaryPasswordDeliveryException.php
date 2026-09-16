<?php

namespace Aldhi88\StarterKit\Exceptions\Starter;

use RuntimeException;

class TemporaryPasswordDeliveryException extends RuntimeException
{
    // Marks a queue-registration failure before credential mutation is committed.
}
