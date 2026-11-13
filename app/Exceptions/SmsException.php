<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * SMS Exception
 * 
 * Custom exception for SMS-related errors
 */
class SmsException extends Exception
{
    public function __construct(
        string $message = "SMS operation failed",
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?array $context = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for API errors
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $context
     * @return static
     */
    public static function apiError(string $message, int $statusCode, ?array $context = null): static
    {
        return new static(
            message: $message,
            code: $statusCode,
            context: $context,
        );
    }

    /**
     * Create exception for network errors
     *
     * @param string $message
     * @param Throwable|null $previous
     * @return static
     */
    public static function networkError(string $message, ?Throwable $previous = null): static
    {
        return new static(
            message: "Network error: {$message}",
            code: 0,
            previous: $previous,
        );
    }

    /**
     * Create exception for configuration errors
     *
     * @param string $message
     * @return static
     */
    public static function configError(string $message): static
    {
        return new static(
            message: "Configuration error: {$message}",
            code: 500,
        );
    }
}

