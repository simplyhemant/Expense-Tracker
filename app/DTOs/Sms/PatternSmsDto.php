<?php

namespace App\DTOs\Sms;

/**
 * Pattern SMS Data Transfer Object
 * 
 * DTO for pattern-based SMS messages
 */
class PatternSmsDto
{
    public function __construct(
        public readonly string $patternCode,
        public readonly string $recipient,
        public readonly array $variables = [],
        public readonly ?string $sender = null,
    ) {
    }

    /**
     * Create a new instance from array
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            patternCode: $data['pattern_code'] ?? $data['patternCode'],
            recipient: $data['recipient'],
            variables: $data['variables'] ?? [],
            sender: $data['sender'] ?? null,
        );
    }

    /**
     * Convert to array for API request
     *
     * @return array
     */
    public function toApiPayload(): array
    {
        $payload = [
            'pattern_code' => $this->patternCode,
            'recipient' => $this->recipient,
            'variables' => $this->variables,
        ];

        if ($this->sender !== null) {
            $payload['sender'] = $this->sender;
        }

        return $payload;
    }
}

