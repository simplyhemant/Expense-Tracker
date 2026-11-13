<?php

namespace App\Services\FarazSms;

use App\Contracts\SmsServiceInterface;
use App\DTOs\Sms\PatternSmsDto;
use App\DTOs\Sms\SmsResponseDto;
use App\Exceptions\SmsException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * FarazSMS Client
 * 
 * Production-ready FarazSMS API client using Guzzle
 */
class FarazSmsClient implements SmsServiceInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $sender;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?string $sender = null,
        protected int $timeout = 10,
        protected int $retryTimes = 2,
        protected int $retryDelay = 1000, // milliseconds
    ) {
        $this->apiKey = $apiKey ?? config('farazsms.api_key');
        $this->sender = $sender ?? config('farazsms.sender', '');
        $baseUrl = $baseUrl ?? config('farazsms.base_url', 'https://api.farazsms.com/v2/');

        if (empty($this->apiKey)) {
            throw SmsException::configError('FarazSMS API key is not configured');
        }

        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
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

            $payload = [
                'pattern_code' => $dto->patternCode,
                'recipient' => $cleanedRecipient,
                'variables' => $dto->variables,
            ];

            // Add sender if provided
            if (!empty($dto->sender)) {
                $payload['sender'] = $dto->sender;
            } elseif (!empty($this->sender)) {
                $payload['sender'] = $this->sender;
            }

            $response = $this->sendRequestWithRetry('POST', 'sms/pattern/send', [
                'json' => $payload,
            ]);

            $statusCode = $response['status_code'];
            $body = $response['body'];

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info('Pattern SMS sent successfully', [
                    'recipient' => $cleanedRecipient,
                    'pattern_code' => $dto->patternCode,
                    'variables' => $dto->variables,
                    'response' => $body,
                ]);

                return SmsResponseDto::success(
                    'پیامک با موفقیت ارسال شد',
                    $body
                );
            }

            Log::error('Failed to send pattern SMS', [
                'recipient' => $cleanedRecipient,
                'pattern_code' => $dto->patternCode,
                'variables' => $dto->variables,
                'status_code' => $statusCode,
                'response' => $body,
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $this->extractErrorMessage($body),
                $statusCode
            );

        } catch (SmsException $e) {
            Log::error('SMS Exception while sending pattern SMS', [
                'recipient' => $dto->recipient,
                'pattern_code' => $dto->patternCode,
                'error' => $e->getMessage(),
                'context' => $e->context,
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $e->getMessage(),
                $e->getCode()
            );

        } catch (\Exception $e) {
            Log::error('Exception while sending pattern SMS', [
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

            $payload = [
                'sender' => $sender ?? $this->sender,
                'recipient' => $cleanedRecipient,
                'message' => $message,
            ];

            $response = $this->sendRequestWithRetry('POST', 'sms/send', [
                'json' => $payload,
            ]);

            $statusCode = $response['status_code'];
            $body = $response['body'];

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info('Simple SMS sent successfully', [
                    'recipient' => $cleanedRecipient,
                    'response' => $body,
                ]);

                return SmsResponseDto::success(
                    'پیامک با موفقیت ارسال شد',
                    $body
                );
            }

            Log::error('Failed to send simple SMS', [
                'recipient' => $cleanedRecipient,
                'status_code' => $statusCode,
                'response' => $body,
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $this->extractErrorMessage($body),
                $statusCode
            );

        } catch (SmsException $e) {
            Log::error('SMS Exception while sending simple SMS', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return SmsResponseDto::failure(
                'خطا در ارسال پیامک',
                $e->getMessage(),
                $e->getCode()
            );

        } catch (\Exception $e) {
            Log::error('Exception while sending simple SMS', [
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
            $response = $this->sendRequestWithRetry('GET', 'account/balance');

            $statusCode = $response['status_code'];
            $body = $response['body'];

            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'message' => 'خطا در دریافت موجودی',
                'error' => $this->extractErrorMessage($body),
            ];

        } catch (\Exception $e) {
            Log::error('Exception while getting balance', [
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
            // Use account/balance endpoint as health check since FarazSMS doesn't have a ping endpoint
            $response = $this->sendRequestWithRetry('GET', 'account/balance', [], 1, 0);
            return $response['status_code'] >= 200 && $response['status_code'] < 300;
        } catch (\Exception $e) {
            Log::warning('FarazSMS health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send HTTP request with retry logic
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @param int|null $retryTimes
     * @param int|null $retryDelay
     * @return array
     * @throws SmsException
     */
    protected function sendRequestWithRetry(
        string $method,
        string $endpoint,
        array $options = [],
        ?int $retryTimes = null,
        ?int $retryDelay = null
    ): array {
        $retryTimes = $retryTimes ?? $this->retryTimes;
        $retryDelay = $retryDelay ?? $this->retryDelay;
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $retryTimes) {
            try {
                $response = $this->client->request($method, $endpoint, $options);

                return [
                    'status_code' => $response->getStatusCode(),
                    'body' => json_decode($response->getBody()->getContents(), true),
                ];

            } catch (RequestException $e) {
                $lastException = $e;
                $statusCode = $e->getResponse()?->getStatusCode();

                // Don't retry on client errors (4xx), only on server errors (5xx) or network issues
                if ($statusCode && $statusCode < 500) {
                    $body = json_decode($e->getResponse()->getBody()->getContents(), true);

                    return [
                        'status_code' => $statusCode,
                        'body' => $body,
                    ];
                }

                // Retry logic for 5xx errors or network issues
                if ($attempt < $retryTimes) {
                    $attempt++;
                    Log::warning("FarazSMS request failed, retrying (attempt {$attempt}/{$retryTimes})", [
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                    usleep($retryDelay * 1000); // Convert to microseconds
                    continue;
                }

                // All retries exhausted
                throw SmsException::networkError(
                    "Failed after {$retryTimes} retries: " . $e->getMessage(),
                    $e
                );

            } catch (GuzzleException $e) {
                $lastException = $e;

                if ($attempt < $retryTimes) {
                    $attempt++;
                    Log::warning("FarazSMS request failed, retrying (attempt {$attempt}/{$retryTimes})", [
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                    usleep($retryDelay * 1000);
                    continue;
                }

                throw SmsException::networkError(
                    "Failed after {$retryTimes} retries: " . $e->getMessage(),
                    $e
                );
            }
        }

        // This should never be reached, but just in case
        throw SmsException::networkError(
            "Request failed after all retries",
            $lastException
        );
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

    /**
     * Extract error message from API response
     *
     * @param mixed $body
     * @return string|null
     */
    protected function extractErrorMessage($body): ?string
    {
        if (is_array($body)) {
            return $body['message'] ?? $body['error'] ?? json_encode($body);
        }

        return is_string($body) ? $body : null;
    }
}

