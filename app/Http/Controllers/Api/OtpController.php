<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP to phone number
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:11|max:11',
        ], [
            'phone.required' => 'شماره تلفن الزامی است',
            'phone.min' => 'شماره تلفن باید 11 رقم باشد',
            'phone.max' => 'شماره تلفن باید 11 رقم باشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->otpService->sendOtp($request->phone);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Verify OTP code
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:11|max:11',
            'code' => 'required|string|min:4|max:6',
        ], [
            'phone.required' => 'شماره تلفن الزامی است',
            'code.required' => 'کد تایید الزامی است',
            'code.min' => 'کد تایید باید حداقل 4 رقم باشد',
            'code.max' => 'کد تایید باید حداکثر 6 رقم باشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->otpService->verifyOtp($request->phone, $request->code);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Resend OTP (same as sendOtp)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendOtp(Request $request)
    {
        return $this->sendOtp($request);
    }
}
