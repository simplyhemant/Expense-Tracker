<?php

namespace App\Console\Commands;

use App\DTOs\Sms\PatternSmsDto;
use App\Exceptions\SmsException;
use App\Services\FarazSms\IPPanelAdapter;
use App\Services\FarazSms\FarazSmsService;
use Illuminate\Console\Command;

class TestFarazSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'farazsms:test 
                            {phone : Phone number to send test SMS}
                            {--pattern= : Pattern code to use (default: OTP pattern)}
                            {--balance : Check account balance}
                            {--health : Check service health}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FarazSMS integration (pattern SMS, balance, health check)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Testing FarazSMS Integration');
        $this->newLine();

        // First, check configuration
        $this->info('🔧 Checking configuration...');
        $apiKey = config('ippanel.api_key');
        $sender = config('farazsms.sender');
        
        if (empty($apiKey)) {
            $this->error('❌ API key is not configured!');
            $this->line('Please set IPPANEL_API or FARAZSMS_API_KEY in your .env file');
            return self::FAILURE;
        }
        
        $this->line('✅ API Key: ' . substr($apiKey, 0, 10) . '...' . substr($apiKey, -4));
        $this->line('✅ Using: IPPanel (Official Package)');
        $this->line('✅ Sender: ' . ($sender ?: 'Not configured'));
        $this->newLine();

        // Try to instantiate the client
        try {
            $client = app(IPPanelAdapter::class);
            $service = app(FarazSmsService::class);
        } catch (SmsException $e) {
            $this->error('❌ Failed to initialize IPPanel client');
            $this->line('Error: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Check health
        if ($this->option('health') || !$this->option('balance')) {
            $this->info('📡 Checking service health...');
            $isHealthy = $client->isHealthy();
            
            if ($isHealthy) {
                $this->info('✅ FarazSMS service is healthy');
            } else {
                $this->error('❌ FarazSMS service is not responding');
                $this->warn('This could mean:');
                $this->line('  - Invalid API key');
                $this->line('  - Network connectivity issues');
                $this->line('  - FarazSMS service is down');
                $this->line('  - Check logs: tail -f storage/logs/laravel.log');
                return self::FAILURE;
            }
            $this->newLine();
        }

        // Check balance
        if ($this->option('balance')) {
            $this->info('💰 Checking account balance...');
            $balanceResult = $client->getBalance();
            
            if ($balanceResult['success']) {
                $this->info('✅ Balance retrieved successfully');
                $this->line(json_encode($balanceResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error('❌ Failed to retrieve balance');
                $this->line($balanceResult['error'] ?? $balanceResult['message']);
            }
            $this->newLine();
        }

        // Send test SMS
        $phone = $this->argument('phone');
        $patternCode = $this->option('pattern') ?? config('farazsms.otp_pattern_code');

        if (empty($patternCode)) {
            $this->warn('⚠️  No pattern code provided. Skipping SMS send test.');
            $this->line('Use --pattern=your-pattern-code or configure FARAZSMS_OTP_PATTERN_CODE');
            return self::SUCCESS;
        }

        if (empty($sender)) {
            $this->warn('⚠️  No sender number configured. Skipping SMS send test.');
            $this->line('Set FARAZSMS_SENDER in your .env file to test SMS sending');
            return self::SUCCESS;
        }

        $this->info("📤 Sending test pattern SMS to {$phone}...");
        $this->line("Pattern code: {$patternCode}");

        // Create DTO
        $dto = new PatternSmsDto(
            patternCode: $patternCode,
            recipient: $phone,
            variables: [
                'otp' => '123456',
                'name' => 'تست',
                'app' => config('app.name'),
            ]
        );

        // Send via client
        $response = $client->sendPattern($dto);

        if ($response->success) {
            $this->info('✅ SMS sent successfully!');
            $this->line('Message: ' . $response->message);
            
            if ($response->data) {
                $this->newLine();
                $this->line('Response data:');
                $this->line(json_encode($response->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } else {
            $this->error('❌ Failed to send SMS');
            $this->line('Message: ' . $response->message);
            
            if ($response->error) {
                $this->line('Error: ' . $response->error);
            }
            
            if ($response->statusCode) {
                $this->line('Status Code: ' . $response->statusCode);
            }
            
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('🎉 FarazSMS test completed!');

        return self::SUCCESS;
    }
}

