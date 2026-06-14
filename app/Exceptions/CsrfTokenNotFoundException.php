<?php

namespace App\Exceptions;

use Exception;

class CsrfTokenNotFoundException extends Exception
{
    public function __construct(string $message = 'CSRF token not found in page HTML')
    {
        parent::__construct($message);
    }
}
