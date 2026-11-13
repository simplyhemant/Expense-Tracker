<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FarazSMS API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for FarazSMS API integration
    |
    */

    'api_key' => env('FARAZSMS_API_KEY', ''),
    'base_url' => env('FARAZSMS_BASE_URL', 'https://api.farazsms.com/v2/'),
    'sender' => env('FARAZSMS_SENDER', ''),
    'otp_pattern_code' => env('FARAZSMS_OTP_PATTERN_CODE'),
    'welcome_pattern_code' => env('FARAZSMS_WELCOME_PATTERN_CODE'),
    
    // OTP Settings
    'otp_length' => 6,
    'otp_expiry' => 120, // seconds (2 minutes)
    'otp_template' => 'کد تایید شما: {{code}}', // SMS template (fallback when pattern code missing)
    
    // HTTP Client Settings
    'timeout' => env('FARAZSMS_TIMEOUT', 10), // seconds
    'retry_times' => env('FARAZSMS_RETRY_TIMES', 2), // number of retries
    'retry_delay' => env('FARAZSMS_RETRY_DELAY', 1000), // milliseconds
    
    // Development Mode
    // When enabled, no SMS is sent and any code works for verification
    // Priority: FARAZSMS_DEV_MODE > APP_ENV check
    // In production, explicitly set FARAZSMS_DEV_MODE=false or ensure APP_ENV=production
    'dev_mode' => env('FARAZSMS_DEV_MODE') !== null
        ? filter_var(env('FARAZSMS_DEV_MODE'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false
        : (env('APP_ENV', 'production') == 'local')
];

