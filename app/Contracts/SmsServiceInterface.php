<?php

namespace App\Contracts;

use App\DTOs\Sms\PatternSmsDto;
use App\DTOs\Sms\SmsResponseDto;

/**
 * SMS Service Contract
 * 
 * Interface for SMS service implementations
 */
interface SmsServiceInterface
{
    /**
     * Send a pattern-based SMS message
     *
     * @param PatternSmsDto $dto
     * @return SmsResponseDto
     */
    public function sendPattern(PatternSmsDto $dto): SmsResponseDto;

    /**
     * Send a simple SMS message
     *
     * @param string $recipient
     * @param string $message
     * @param string|null $sender
     * @return SmsResponseDto
     */
    public function sendSimple(string $recipient, string $message, ?string $sender = null): SmsResponseDto;

    /**
     * Get account balance
     *
     * @return array
     */
    public function getBalance(): array;

    /**
     * Check service health
     *
     * @return bool
     */
    public function isHealthy(): bool;
}

