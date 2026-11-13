<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
	/**
	 * Show the admin login form.
	 */
	public function showLoginForm()
	{
		if (Auth::guard('admin')->check()) {
			return redirect()->route('admin.dashboard');
		}

		return view('admin.login');
	}

	/**
	 * Handle admin login.
	 */
	public function login(Request $request)
	{
		$credentials = $request->validate([
			'email' => ['required', 'email'],
			'password' => ['required', 'string'],
		]);

		$remember = (bool) $request->boolean('remember');

		if (Auth::guard('admin')->attempt($credentials, $remember)) {
			$request->session()->regenerate();
			return redirect()->intended(route('admin.dashboard'));
		}

		return back()->withErrors([
			'email' => 'The provided credentials do not match our records.',
		])->onlyInput('email');
	}

	/**
	 * Log the admin out.
	 */
	public function logout(Request $request)
	{
		Auth::guard('admin')->logout();
		$request->session()->invalidate();
		$request->session()->regenerateToken();
		return redirect()->route('admin.login');
	}

	/**
	 * Admin dashboard.
	 */
	public function dashboard()
	{
		try {
			$usersCount = \App\Models\User::count();
			$transactionsCount = \App\Models\Transaction::count();
			$categoriesCount = \App\Models\Category::count();
			$budgetsCount = \App\Models\Budget::count();

			// Total income and expenses
			$totalIncome = (int) \App\Models\Transaction::where('type', 'income')->sum('amount');
			$totalExpense = (int) \App\Models\Transaction::where('type', 'expense')->sum('amount');

			// Recent users
			$recentUsers = \App\Models\User::latest()->take(10)->get();

			// Recent transactions
			$recentTransactions = \App\Models\Transaction::with(['user', 'category'])
				->latest()
				->take(10)
				->get();

			// Monthly transactions data for chart (last 6 months)
			$monthlyData = [];
			for ($i = 5; $i >= 0; $i--) {
				$date = now()->subMonths($i);
				$monthlyData[] = [
					'month' => $date->format('M Y'),
					'income' => (int) \App\Models\Transaction::where('type', 'income')
						->whereYear('date', $date->year)
						->whereMonth('date', $date->month)
						->sum('amount'),
					'expense' => (int) \App\Models\Transaction::where('type', 'expense')
						->whereYear('date', $date->year)
						->whereMonth('date', $date->month)
						->sum('amount'),
				];
			}

			// Category-wise expenses
			$categoryExpenses = \App\Models\Transaction::where('type', 'expense')
				->whereNotNull('category_id')
				->selectRaw('category_id, SUM(amount) as total')
				->groupBy('category_id')
				->with('category')
				->get()
				->map(function ($item) {
					return [
						'name' => $item->category->name ?? 'بدون دسته',
						'total' => (int) $item->total,
					];
				})
				->take(10);

			return view('admin.dashboard', compact(
				'usersCount',
				'transactionsCount',
				'categoriesCount',
				'budgetsCount',
				'totalIncome',
				'totalExpense',
				'recentUsers',
				'recentTransactions',
				'monthlyData',
				'categoryExpenses'
			));
		} catch (\Exception $e) {
			// Fallback with empty data
			return view('admin.dashboard', [
				'usersCount' => 0,
				'transactionsCount' => 0,
				'categoriesCount' => 0,
				'budgetsCount' => 0,
				'totalIncome' => 0,
				'totalExpense' => 0,
				'recentUsers' => collect(),
				'recentTransactions' => collect(),
				'monthlyData' => [],
				'categoryExpenses' => collect(),
			]);
		}
	}

	/**
	 * Show individual user details with all their data.
	 */
	public function showUser(\App\Models\User $user)
	{
		// Load relationships
		$user->load(['transactions', 'transactions.category']);

		// Get user's transactions
		$transactions = $user->transactions()
			->with('category')
			->latest()
			->paginate(20);

		// Get user's categories
		$categories = \App\Models\Category::where('user_id', $user->id)->get();

		// Get user's budgets
		$budgets = \App\Models\Budget::where('user_id', $user->id)
			->with('category')
			->latest()
			->get();

		// Calculate statistics
		$totalIncome = $user->transactions()->where('type', 'income')->sum('amount');
		$totalExpense = $user->transactions()->where('type', 'expense')->sum('amount');
		$transactionsCount = $user->transactions()->count();
		$categoriesCount = $categories->count();
		$budgetsCount = $budgets->count();

		// Category breakdown
		$categoryBreakdown = $user->transactions()
			->where('type', 'expense')
			->whereNotNull('category_id')
			->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
			->groupBy('category_id')
			->with('category')
			->get()
			->map(function ($item) {
				return [
					'name' => $item->category->name ?? 'بدون دسته',
					'total' => (int) $item->total,
					'count' => $item->count,
				];
			});

		return view('admin.user-detail', compact(
			'user',
			'transactions',
			'categories',
			'budgets',
			'totalIncome',
			'totalExpense',
			'transactionsCount',
			'categoriesCount',
			'budgetsCount',
			'categoryBreakdown'
		));
	}
}


