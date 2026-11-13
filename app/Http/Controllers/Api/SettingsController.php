<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get app settings including minimum required version
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'min_app_version' => config('app.min_app_version', '1.0.0'),
                'bazar_url' => config('app.bazar_url', 'https://cafebazaar.ir/app/ir.bessoon.bedebestoon'),
            ]
        ]);
    }
}

