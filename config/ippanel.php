<?php

return [
    // API key - supports both IPPANEL_API and FARAZSMS_API_KEY for backward compatibility
    'api_key' => env('IPPANEL_API', env('FARAZSMS_API_KEY', '')),
    
    // Default sender number
    'default_sender' => env('FARAZSMS_SENDER', env('IPPANEL_SENDER', '')),
];