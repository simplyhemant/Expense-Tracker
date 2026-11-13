<?php

namespace App\Providers;

use App\Contracts\SmsServiceInterface;
use App\Services\FarazSms\FarazSmsClient;
use App\Services\FarazSms\FarazSmsService;
use App\Services\FarazSms\IPPanelAdapter;
use Illuminate\Support\ServiceProvider;

/**
 * SMS Service Provider
 * 
 * Registers SMS-related services
 */
class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register IPPanelAdapter as singleton (primary implementation)
        $this->app->singleton(IPPanelAdapter::class, function ($app) {
            return new IPPanelAdapter(
                sender: config('farazsms.sender')
            );
        });

        // Keep FarazSmsClient for backward compatibility if needed
        $this->app->singleton(FarazSmsClient::class, function ($app) {
            // If IPPanel is configured, use adapter. Otherwise, use direct client
            if (!empty(config('ippanel.api_key'))) {
                return $app->make(IPPanelAdapter::class);
            }
            
            return new FarazSmsClient(
                apiKey: config('farazsms.api_key'),
                baseUrl: config('farazsms.base_url'),
                sender: config('farazsms.sender'),
                timeout: config('farazsms.timeout', 10),
                retryTimes: config('farazsms.retry_times', 2),
                retryDelay: config('farazsms.retry_delay', 1000),
            );
        });

        // Register FarazSmsService using IPPanel adapter
        $this->app->singleton(FarazSmsService::class, function ($app) {
            return new FarazSmsService(
                client: $app->make(IPPanelAdapter::class)
            );
        });

        // Bind interface to IPPanel adapter implementation
        $this->app->bind(SmsServiceInterface::class, IPPanelAdapter::class);

        // Alias for backward compatibility
        $this->app->alias(FarazSmsService::class, 'farazsms');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

