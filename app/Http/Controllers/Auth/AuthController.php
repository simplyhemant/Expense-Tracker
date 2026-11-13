<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Services\FarazSms\FarazSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected FarazSmsService $farazSmsService;

    public function __construct(FarazSmsService $farazSmsService)
    {
        $this->farazSmsService = $farazSmsService;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'message' => 'User registered successfully'
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'message' => 'Logged in successfully'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        return new UserResource($request->user());
    }

    public function registerWithPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:11|max:11|unique:users,phone',
            'name' => 'required|string|max:255',
        ], [
            'phone.required' => 'شماره تلفن الزامی است',
            'phone.min' => 'شماره تلفن باید 11 رقم باشد',
            'phone.max' => 'شماره تلفن باید 11 رقم باشد',
            'phone.unique' => 'این شماره تلفن قبلاً ثبت شده است',
            'name.required' => 'نام الزامی است',
            'name.max' => 'نام نمی‌تواند بیش از 255 کاراکتر باشد',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->phone . '@phone.local',
            'password' => Hash::make(str()->random(32)), // Random password for phone users
            'phone' => $request->phone,
        ]);

        $welcomeVariables = array_filter([
            'name' => $user->name,
            'app' => config('app.name'),
        ]);

        $welcomeResult = $this->farazSmsService->sendWelcomeMessage($user->phone, $welcomeVariables);

        if (!$welcomeResult['success']) {
            Log::warning('Failed to send welcome SMS after signup', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'message' => $welcomeResult['message'] ?? null,
                'error' => $welcomeResult['error'] ?? null,
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ثبت‌نام موفقیت‌آمیز',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ]
        ], 201);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
            'currency' => 'sometimes|string|in:toman,rial',
        ]);

        $user = $request->user();
        
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('currency')) {
            $user->currency = $request->currency;
        }
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user)
        ]);
    }
}
