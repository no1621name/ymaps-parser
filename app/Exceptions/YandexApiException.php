<?php

namespace App\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class YandexApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?ResponseInterface $response = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
