<?php

namespace App\Exceptions;

use Exception;

class BusinessIdNotFoundException extends Exception
{
    public function __construct(string $message = 'Business ID not found in URL')
    {
        parent::__construct($message);
    }
}
