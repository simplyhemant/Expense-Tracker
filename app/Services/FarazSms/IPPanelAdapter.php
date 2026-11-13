<?php

namespace App\Services\FarazSms;

use App\Contracts\SmsServiceInterface;
use App\DTOs\Sms\PatternSmsDto;
use App\DTOs\Sms\SmsResponseDto;
use App\Exceptions\SmsException;
use Illuminate\Support\Facades\Log;
use SmartRaya\IPPanelLaravel\Errors\Error;
use SmartRaya\IPPanelLaravel\Errors\HttpException;
use SmartRaya\IPPanelLaravel\Facades\IPPanel;

/**
 * IPPanel Adapter for FarazSMS
 * 
 * Adapter that uses the official IPPanel Laravel package
 * while maintaining our FarazSMS service interface
 */
class IPPanelAdapter implements SmsServiceInterface
{
    protected string $sender;

    public function __construct(?string $sender = null)
    {
        $this->sender = $sender ?? config('farazsms.sender', config('ippanel.default_sender', ''));
        
        // Validate API key is configured
        $apiKey = config('ippanel.api_key');
        if (empty($apiKey)) {
            throw SmsException::configError('IPPanel API key is not configured');
        }
    }

    /**
     * Send a pattern-based SMS message
     *
     * @param PatternSmsDto $dto
     * @return SmsResponseDto
     */
    public function sendPattern(PatternSmsDto $dto): SmsResponseDto
    {
        if (empty($dto->patternCode)) {
            Log::warning('Attempted to send pattern message without pattern code', [
                'recipient' => $dto->recipient,
            ]);

            return SmsResponseDto::failure('کد الگوی پیامک تنظیم نشده است');
        }

        try {
            // Clean phone number
            $cleanedRecipient = $this->cleanPhoneNumber($dto->recipient);
            
            // Determine sender
            $sender = $dto->sender ?? $this->sender;

            // Send pattern SMS using IPPanel
            $bulkId = IPPanel::sendPattern(
                $dto->patternCode,
                $sender,
                $cleanedRecipient,
                $dto->variables
            );

            Log::info('Pattern SMS sent successfully via IPPanel', [
                'recipient' => $cleanedRecipient,
                'pattern_code' => $dto->patternCode,
                'variables' => $dto->variables,
                'bulk_id' => $bulkId,
            ]);

            return SmsResponseDto::success(
                'پیامک با موفقیت ارسال شد',
                ['bulk_id' => $bulkId]
            );

        } catch (Error $e) {
            Log::error('IPPanel API error while sending pattern SMS', [
                'recipient' => $dto->recipient,
                'pattern_code' => $dto->patternCode,
                'error' => $e->getMessage(),
                'error_data' => $e->unwrap(),
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $e->getMessage(),
                $e->getCode()
            );

        } catch (HttpException $e) {
            Log::error('HTTP error while sending pattern SMS via IPPanel', [
                'recipient' => $dto->recipient,
                'pattern_code' => $dto->patternCode,
                'error' => $e->getMessage(),
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $e->getMessage(),
                $e->getCode()
            );

        } catch (\Exception $e) {
            Log::error('Exception while sending pattern SMS via IPPanel', [
                'recipient' => $dto->recipient,
                'pattern_code' => $dto->patternCode,
                'error' => $e->getMessage(),
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $e->getMessage()
            );
        }
    }

    /**
     * Send a simple SMS message
     *
     * @param string $recipient
     * @param string $message
     * @param string|null $sender
     * @return SmsResponseDto
     */
    public function sendSimple(string $recipient, string $message, ?string $sender = null): SmsResponseDto
    {
        try {
            // Clean phone number
            $cleanedRecipient = $this->cleanPhoneNumber($recipient);
            
            // Determine sender
            $sender = $sender ?? $this->sender;

            // Send SMS using IPPanel (one-to-many)
            $bulkId = IPPanel::send(
                $sender,
                [$cleanedRecipient],
                $message
            );

            Log::info('Simple SMS sent successfully via IPPanel', [
                'recipient' => $cleanedRecipient,
                'bulk_id' => $bulkId,
            ]);

            return SmsResponseDto::success(
                'پیامک با موفقیت ارسال شد',
                ['bulk_id' => $bulkId]
            );

        } catch (Error $e) {
            Log::error('IPPanel API error while sending simple SMS', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $e->getMessage(),
                $e->getCode()
            );

        } catch (HttpException $e) {
            Log::error('HTTP error while sending simple SMS via IPPanel', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $e->getMessage(),
                $e->getCode()
            );

        } catch (\Exception $e) {
            Log::error('Exception while sending simple SMS via IPPanel', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $e->getMessage()
            );
        }
    }

    /**
     * Get account balance
     *
     * @return array
     */
    public function getBalance(): array
    {
        try {
            $credit = IPPanel::getCredit();

            return [
                'success' => true,
                'data' => [
                    'credit' => $credit,
                ],
            ];

        } catch (Error $e) {
            Log::error('IPPanel API error while getting balance', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت موجودی',
                'error' => $e->getMessage(),
            ];

        } catch (HttpException $e) {
            Log::error('HTTP error while getting balance via IPPanel', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت موجودی',
                'error' => $e->getMessage(),
            ];

        } catch (\Exception $e) {
            Log::error('Exception while getting balance via IPPanel', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت موجودی',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check service health
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        try {
            // Try to get credit as health check
            IPPanel::getCredit();
            return true;
        } catch (\Exception $e) {
            Log::warning('IPPanel health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Clean phone number to Iranian format
     *
     * @param string $phone
     * @return string
     */
    protected function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to standard Iranian format (10 digits starting with 9)
        if (strlen($phone) == 10 && str_starts_with($phone, '9')) {
            $phone = '0' . $phone;
        }

        // Remove leading zero and add 98 country code
        if (str_starts_with($phone, '0')) {
            $phone = '98' . substr($phone, 1);
        }

        return $phone;
    }
}

