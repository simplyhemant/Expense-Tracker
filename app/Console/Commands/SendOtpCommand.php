<?php

namespace App\Console\Commands;

use App\Services\OtpService;
use Illuminate\Console\Command;

class SendOtpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:send {phone : Iranian phone number (11 digits starting with 09)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send OTP SMS to the provided phone number via FarazSMS';

    /**
     * Execute the console command.
     */
    public function handle(OtpService $otpService): int
    {
        $phone = (string) $this->argument('phone');

        $this->info(sprintf('Sending OTP to %s ...', $phone));

        $result = $otpService->sendOtp($phone);

        if (!($result['success'] ?? false)) {
            $this->error($result['message'] ?? 'خطا در ارسال کد تایید');

            if (!empty($result['error'])) {
                $this->line($result['error']);
            }

            return self::FAILURE;
        }

        $this->info($result['message'] ?? 'کد تایید با موفقیت ارسال شد');

        if (!empty($result['dev_code'])) {
            $this->line(sprintf('DEV MODE OTP: %s', $result['dev_code']));
        }

        return self::SUCCESS;
    }
}















