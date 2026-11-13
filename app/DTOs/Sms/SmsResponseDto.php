<?php

namespace App\DTOs\Sms;

/**
 * SMS Response Data Transfer Object
 * 
 * Standardized response for SMS operations
 */
class SmsResponseDto
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?array $data = null,
        public readonly ?string $error = null,
        public readonly ?int $statusCode = null,
    ) {
    }

    /**
     * Create a successful response
     *
     * @param string $message
     * @param array|null $data
     * @return static
     */
    public static function success(string $message, ?array $data = null): static
    {
        return new static(
            success: true,
            message: $message,
            data: $data,
            statusCode: 200,
        );
    }

    /**
     * Create a failed response
     *
     * @param string $message
     * @param string|null $error
     * @param int|null $statusCode
     * @return static
     */
    public static function failure(string $message, ?string $error = null, ?int $statusCode = null): static
    {
        return new static(
            success: false,
            message: $message,
            error: $error,
            statusCode: $statusCode,
        );
    }

    /**
     * Convert to array (for backward compatibility)
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $result['data'] = $this->data;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        if ($this->statusCode !== null) {
            $result['status_code'] = $this->statusCode;
        }

        return $result;
    }
}

