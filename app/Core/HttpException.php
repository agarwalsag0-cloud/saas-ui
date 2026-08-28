<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class HttpException extends RuntimeException
{
    private int $statusCode;

    public function __construct(int $statusCode = 404, string $message = '')
    {
        $this->statusCode = $statusCode;
        parent::__construct($message !== '' ? $message : self::defaultMessage($statusCode), $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    private static function defaultMessage(int $statusCode): string
    {
        $messages = [
            403 => 'You are not allowed to access this resource.',
            404 => 'The requested page was not found.',
            419 => 'Your secure form token expired. Please retry.',
            500 => 'Something went wrong.',
        ];

        return $messages[$statusCode] ?? 'Request failed.';
    }
}
