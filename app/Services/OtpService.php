<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use App\Services\FarazSms\FarazSmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected $farazSmsService;
    protected $otpLength;
    protected $otpExpiry;

    public function __construct(FarazSmsService $farazSmsService)
    {
        $this->farazSmsService = $farazSmsService;
        $this->otpLength = config('farazsms.otp_length', 6);
        $this->otpExpiry = config('farazsms.otp_expiry', 120); // seconds
    }

    /**
     * Send OTP to phone number
     *
     * @param string $phone
     * @return array
     */
    public function sendOtp(string $phone): array
    {
        try {
            // Clean phone number
            $phone = $this->cleanPhoneNumber($phone);

            // Validate phone number
            if (!$this->isValidIranianPhone($phone)) {
                return [
                    'success' => false,
                    'message' => 'شماره تلفن نامعتبر است',
                ];
            }

            // Check rate limiting (max 3 OTPs in 10 minutes)
            if ($this->isRateLimited($phone)) {
                return [
                    'success' => false,
                    'message' => 'لطفاً چند دقیقه صبر کنید و دوباره تلاش کنید',
                ];
            }

            // Generate OTP code
            $code = $this->generateOtpCode();

            // Save OTP to database
            $otp = Otp::create([
                'phone' => $phone,
                'code' => $code,
                'expires_at' => Carbon::now()->addSeconds($this->otpExpiry),
                'verified' => false,
                'attempts' => 0,
            ]);

            // Development mode - skip SMS sending
            if (config('farazsms.dev_mode', false)) {
                Log::info('OTP created (DEV MODE - SMS skipped)', [
                    'phone' => $phone,
                    'code' => $code,
                    'otp_id' => $otp->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'کد تایید: ' . $code . ' (حالت توسعه)',
                    'expires_in' => $this->otpExpiry,
                    'dev_code' => $code, // Include code in response for dev mode
                ];
            }

            // Production mode - send SMS
            $smsResult = $this->farazSmsService->sendOtp($phone, $code);

            if ($smsResult['success']) {
                Log::info('OTP created and sent', [
                    'phone' => $phone,
                    'otp_id' => $otp->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'کد تایید به شماره شما ارسال شد',
                    'expires_in' => $this->otpExpiry,
                ];
            } else {
                // Delete OTP if SMS failed
                $otp->delete();

                Log::error('Error sending OTP', [
                    'phone' => $phone,
                    'result' => $smsResult,
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در ارسال پیامک. لطفاً دوباره تلاش کنید',
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending OTP', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطای سیستمی رخ داده است',
            ];
        }
    }

    /**
     * Verify OTP code
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    public function verifyOtp(string $phone, string $code): array
    {
        try {
            $phone = $this->cleanPhoneNumber($phone);

            // Development mode - accept any code
            if (config('farazsms.dev_mode', false)) {
                Log::info('OTP verification (DEV MODE - any code accepted)', [
                    'phone' => $phone,
                    'code' => $code,
                ]);

                // Find existing user (don't create new one)
                $user = User::where('phone', $phone)->first();
                
                // If not found by phone, try by email (for backward compatibility)
                if (!$user) {
                    $user = User::where('email', $phone . '@phone.local')->first();
                }

                if ($user) {
                    // Update phone number if it was missing (for existing users)
                    if (empty($user->phone)) {
                        $user->update(['phone' => $phone]);
                    }

                    // Generate token
                    $token = $user->createToken('mobile-app')->plainTextToken;

                    return [
                        'success' => true,
                        'message' => 'ورود موفقیت‌آمیز (حالت توسعه)',
                        'user' => $user,
                        'token' => $token,
                    ];
                } else {
                    // User doesn't exist - return success but no user data
                    return [
                        'success' => true,
                        'message' => 'کد تایید صحیح است. لطفاً اطلاعات خود را تکمیل کنید',
                        'user' => null,
                        'token' => null,
                    ];
                }
            }

            // Production mode - verify actual OTP
            // Find valid OTP
            $otp = Otp::validForPhone($phone)->first();

            if (!$otp) {
                return [
                    'success' => false,
                    'message' => 'کد تایید نامعتبر یا منقضی شده است',
                ];
            }

            // Check max attempts
            if ($otp->attempts >= 5) {
                return [
                    'success' => false,
                    'message' => 'تعداد تلاش‌ها بیش از حد مجاز است. لطفاً کد جدید دریافت کنید',
                ];
            }

            // Verify code
            if ($otp->code !== $code) {
                $otp->incrementAttempts();

                return [
                    'success' => false,
                    'message' => 'کد تایید اشتباه است',
                    'attempts_left' => 5 - $otp->attempts - 1,
                ];
            }

            // Mark as verified
            $otp->markAsVerified();

            // Find existing user (don't create new one)
            $user = User::where('phone', $phone)->first();
            
            // If not found by phone, try by email (for backward compatibility)
            if (!$user) {
                $user = User::where('email', $phone . '@phone.local')->first();
            }

            if ($user) {
                // Update phone number if it was missing (for existing users)
                if (empty($user->phone)) {
                    $user->update(['phone' => $phone]);
                }

                // Generate token
                $token = $user->createToken('mobile-app')->plainTextToken;

                Log::info('OTP verified successfully - existing user', [
                    'phone' => $phone,
                    'user_id' => $user->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'ورود موفقیت‌آمیز',
                    'user' => $user,
                    'token' => $token,
                ];
            } else {
                // User doesn't exist - return success but no user data
                Log::info('OTP verified successfully - new user needs registration', [
                    'phone' => $phone,
                ]);

                return [
                    'success' => true,
                    'message' => 'کد تایید صحیح است. لطفاً اطلاعات خود را تکمیل کنید',
                    'user' => null,
                    'token' => null,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error verifying OTP', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطای سیستمی رخ داده است',
            ];
        }
    }

    /**
     * Find or create user by phone
     *
     * @param string $phone
     * @return User
     */
    private function findOrCreateUser(string $phone): User
    {
        // First try to find by phone number
            $user = User::where('phone', $phone)->first();
            
            // If not found by phone, try by email (for backward compatibility)
            if (!$user) {
                $user = User::where('email', $phone . '@phone.local')->first();
            }

        if (!$user) {
            $user = User::create([
                'name' => 'کاربر ' . substr($phone, -4),
                'email' => $phone . '@phone.local', // Temporary email
                'password' => bcrypt(str()->random(32)), // Random password
                'phone' => $phone,
            ]);
        } else {
            // Update phone number if it was missing (for existing users)
            if (empty($user->phone)) {
                $user->update(['phone' => $phone]);
            }
        }

        return $user;
    }

    /**
     * Generate random OTP code
     *
     * @return string
     */
    private function generateOtpCode(): string
    {
        $min = pow(10, $this->otpLength - 1);
        $max = pow(10, $this->otpLength) - 1;

        return (string) random_int($min, $max);
    }

    /**
     * Clean phone number
     *
     * @param string $phone
     * @return string
     */
    private function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ensure it starts with 0 for Iranian numbers
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '9') {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    /**
     * Validate Iranian phone number
     *
     * @param string $phone
     * @return bool
     */
    private function isValidIranianPhone(string $phone): bool
    {
        // Iranian mobile numbers: 09xxxxxxxxx (11 digits)
        return preg_match('/^09\d{9}$/', $phone);
    }

    /**
     * Check if phone number is rate limited
     *
     * @param string $phone
     * @return bool
     */
    private function isRateLimited(string $phone): bool
    {
        $recentOtps = Otp::where('phone', $phone)
            ->where('created_at', '>', Carbon::now()->subMinutes(10))
            ->count();

        return $recentOtps >= 3;
    }

    /**
     * Clean up expired OTPs (can be called via scheduled task)
     *
     * @return int
     */
    public function cleanupExpiredOtps(): int
    {
        return Otp::where('expires_at', '<', Carbon::now())->delete();
    }
}

