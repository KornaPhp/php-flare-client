<?php

namespace Spatie\FlareClient\Senders\Exceptions;

use Exception;

class ConnectionError extends Exception
{
    public function __construct(string $error)
    {
        parent::__construct("Could not perform request because: {$error}");
    }
}
