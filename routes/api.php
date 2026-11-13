<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\SettingsController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/phone', [AuthController::class, 'registerWithPhone']);
Route::post('/login', [AuthController::class, 'login']);

// OTP Authentication
Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/otp/resend', [OtpController::class, 'resendOtp']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/update', [AuthController::class, 'updateProfile']);
    
    // User management
    Route::apiResource('users', UserController::class);
    
    // Financial Analytics
    Route::get('/transactions/summary', [TransactionController::class, 'summary']);
    Route::get('/transactions/monthly-summary', [TransactionController::class, 'monthlySummary']);
    Route::get('/transactions/expenses-by-category', [TransactionController::class, 'expensesByCategory']);
    Route::get('/transactions/recent', [TransactionController::class, 'recentTransactions']);

    Route::get('budgets/summary', [BudgetController::class, 'summary']);
    Route::post('budgets/refresh', [BudgetController::class, 'refresh']);
    Route::apiResource('budgets', BudgetController::class);
    
    // Categories
    Route::apiResource('categories', CategoryController::class);
    Route::get('/categories/type/{type}', [CategoryController::class, 'index']);
    Route::post('/categories/seed-default', [CategoryController::class, 'seedDefaultCategories']);

    // Bank Accounts
    Route::apiResource('bank-accounts', BankAccountController::class);
    
    // Transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::get('/transactions/type/{type}', [TransactionController::class, 'index']);
    
    // Debts and Credits
    Route::apiResource('debts', DebtController::class);
    Route::post('/debts/{id}/payments', [DebtController::class, 'addPayment']);
    Route::put('/debts/{id}/payments/{paymentId}', [DebtController::class, 'updatePayment']);
    Route::delete('/debts/{id}/payments/{paymentId}', [DebtController::class, 'deletePayment']);
});

// Public settings endpoint (for version check)
Route::get('/settings', [SettingsController::class, 'index']);

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});
