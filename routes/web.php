<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;

Route::prefix('admin')->group(function () {
	// Guest routes for admin
	Route::middleware('guest:admin')->group(function () {
		Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
		Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
	});

	// Authenticated admin routes
	Route::middleware('auth:admin')->group(function () {
		Route::get('/', function () {
			return redirect()->route('admin.dashboard');
		});
		Route::get('dashboard', [AdminAuthController::class, 'dashboard'])->name('admin.dashboard');
		Route::get('users/{user}', [AdminAuthController::class, 'showUser'])->name('admin.users.show');
		Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
	});
});

