<?php

namespace App\Services\FarazSms;

use App\Contracts\SmsServiceInterface;
use App\DTOs\Sms\PatternSmsDto;
use App\DTOs\Sms\SmsResponseDto;
use Illuminate\Support\Facades\Log;

/**
 * FarazSMS Service
 * 
 * High-level service for sending SMS messages via FarazSMS
 * Maintains backward compatibility while using IPPanel package
 */
class FarazSmsService
{
    public function __construct(
        protected SmsServiceInterface $client
    ) {
    }

    /**
     * Send OTP via SMS
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    public function sendOtp(string $phone, string $code): array
    {
        $patternCode = config('farazsms.otp_pattern_code');

        if (!empty($patternCode)) {
            return $this->sendPatternMessage(
                $patternCode,
                $phone,
                ['otp' => $code],
                'کد تایید با موفقیت ارسال شد'
            );
        }

        // Fallback to simple SMS if pattern code is not configured
        Log::warning('OTP pattern code not configured, falling back to simple SMS', [
            'phone' => $phone,
        ]);

        $message = str_replace('{{code}}', $code, config('farazsms.otp_template'));
        $response = $this->client->sendSimple($phone, $message);

        return $response->toArray();
    }

    /**
     * Send welcome message via pattern
     *
     * @param string $phone
     * @param array $variables
     * @return array
     */
    public function sendWelcomeMessage(string $phone, array $variables = []): array
    {
        $patternCode = config('farazsms.welcome_pattern_code');

        if (empty($patternCode)) {
            Log::warning('Welcome pattern code is not configured', [
                'phone' => $phone,
            ]);

            return [
                'success' => false,
                'message' => 'الگوی پیامک خوش‌آمدگویی تنظیم نشده است',
            ];
        }

        return $this->sendPatternMessage(
            $patternCode,
            $phone,
            $variables,
            'پیام خوش‌آمدگویی با موفقیت ارسال شد'
        );
    }

    /**
     * Generic helper to send pattern-based SMS
     *
     * @param string $patternCode
     * @param string $phone
     * @param array $variables
     * @param string $successMessage
     * @return array
     */
    public function sendPatternMessage(
        string $patternCode,
        string $phone,
        array $variables = [],
        string $successMessage = 'پیامک با موفقیت ارسال شد'
    ): array {
        if (empty($patternCode)) {
            Log::warning('Attempted to send pattern message without pattern code', [
                'phone' => $phone,
            ]);

            return [
                'success' => false,
                'message' => 'کد الگوی پیامک تنظیم نشده است',
            ];
        }

        $dto = new PatternSmsDto(
            patternCode: $patternCode,
            recipient: $phone,
            variables: $variables,
        );

        $response = $this->client->sendPattern($dto);

        // Override success message if provided
        if ($response->success && $successMessage !== 'پیامک با موفقیت ارسال شد') {
            return [
                'success' => true,
                'message' => $successMessage,
                'data' => $response->data,
            ];
        }

        return $response->toArray();
    }

    /**
     * Check account balance
     *
     * @return array
     */
    public function getBalance(): array
    {
        return $this->client->getBalance();
    }

    /**
     * Check if service is healthy
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->client->isHealthy();
    }

    /**
     * Send simple SMS message
     *
     * @param string $phone
     * @param string $message
     * @return array
     */
    public function sendSimple(string $phone, string $message): array
    {
        $response = $this->client->sendSimple($phone, $message);
        return $response->toArray();
    }
}

