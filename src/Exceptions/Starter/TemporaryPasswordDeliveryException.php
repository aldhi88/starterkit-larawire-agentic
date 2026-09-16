<?php

namespace Aldhi88\StarterKit\Exceptions\Starter;

use RuntimeException;

class TemporaryPasswordDeliveryException extends RuntimeException
{
    // Marks a mail transport failure so credential mutations can be rolled back safely.
}
