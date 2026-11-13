<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Category;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService)
    {
    }

    public function index(Request $request)
    {
        $query = Transaction::with(['category', 'bankAccount'])
            ->where('user_id', $request->user()->id);
        
        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by bank account if provided
        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }
        
        // Filter by date range if provided
        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        
        $transactions = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'bank_account_id' => [
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where(
                    'user_id',
                    $request->user()->id
                )),
            ],
        ]);

        // Verify category exists and is accessible (user's own or global)
        if ($request->filled('category_id')) {
        $category = Category::where('id', $request->category_id)
                ->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)
                          ->orWhereNull('user_id'); // Allow global categories
                })
            ->first();
            
        if (!$category) {
                return response()->json([
                    'success' => false,
                    'error' => 'Category not found or not accessible'
                ], 404);
            }
        }

        $type = $this->normalizeType($request->type);
        $date = Carbon::parse($request->date);
        $time = $this->resolveTime($request);
        $transaction = Transaction::create([
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'category_id' => $request->category_id,
            'bank_account_id' => $request->bank_account_id,
            'type' => $type,
            'date' => $date,
            'time' => $time,
            'user_id' => $request->user()->id,
        ]);

        $transaction->load(['category', 'bankAccount']);
        $this->recalculateBudgetsAfterChange($transaction);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction
        ], 201);
    }

    public function show(Request $request, Transaction $transaction)
    {
        // Check if transaction belongs to default user
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction->load('category', 'bankAccount')
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        // Check if transaction belongs to default user
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'sometimes|in:income,expense',
            'date' => 'sometimes|date',
            'time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'bank_account_id' => [
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where(
                    'user_id',
                    $request->user()->id
                )),
            ],
        ]);

        if ($request->filled('category_id')) {
            // Verify category exists and is accessible (user's own or global)
            $category = Category::where('id', $request->category_id)
                ->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)
                          ->orWhereNull('user_id'); // Allow global categories
                })
                ->first();
                
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'error' => 'Category not found or not accessible'
                ], 404);
            }
        }

        $transaction->loadMissing('category');
        $originalDate = $transaction->date?->copy();
        $originalType = $this->normalizeType($transaction->type);
        $originalCategoryId = $transaction->category_id;

        $newType = $this->normalizeType($request->input('type', $transaction->type));
        $newDate = $request->filled('date')
            ? Carbon::parse($request->date)
            : $transaction->date;
        $newTime = $this->resolveTime($request, $transaction->time);

        $updates = $request->only(['title', 'description', 'amount', 'category_id', 'bank_account_id', 'type']);
        $updates['type'] = $newType;
        $updates['date'] = $newDate;
        $updates['time'] = $newTime;

        $transaction->update($updates);
        $transaction->refresh()->load('category', 'bankAccount');

        $this->recalculateBudgetsAfterChange($transaction, $originalType, $originalCategoryId, $originalDate);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $transaction
        ]);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        // Check if transaction belongs to default user
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $transaction->loadMissing('category');
        $originalDate = $transaction->date?->copy();
        $originalType = $this->normalizeType($transaction->type);
        $originalCategoryId = $transaction->category_id;

        $transaction->delete();

        if ($originalType === 'income') {
            if ($originalDate) {
                $this->budgetService->recalculateForIncome($transaction->user_id, $originalDate);
            }
        } elseif ($originalCategoryId !== null && $originalDate) {
            $this->budgetService->recalculateForExpense($transaction->user_id, $originalCategoryId, $originalDate);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully'
        ]);
    }

    // Financial analytics endpoints
    public function summary(Request $request)
    {
        $userId = $request->user()->id;
        
        $totalIncome = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->sum('amount');
            
        $totalExpenses = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->sum('amount');
            
        $balance = $totalIncome - $totalExpenses;
        
        // Debug logging
        \Log::debug('Transaction Summary', [
            'user_id' => $userId,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'balance' => $balance,
            'income_count' => Transaction::where('user_id', $userId)->where('type', 'income')->count(),
            'expense_count' => Transaction::where('user_id', $userId)->where('type', 'expense')->count(),
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'balance' => $balance
            ]
        ]);
    }

    public function monthlySummary(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $monthlyIncome = Transaction::where('user_id', $request->user()->id)
            ->where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        $monthlyExpenses = Transaction::where('user_id', $request->user()->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        $monthlyBalance = $monthlyIncome - $monthlyExpenses;
        
        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'income' => $monthlyIncome,
                'expenses' => $monthlyExpenses,
                'balance' => $monthlyBalance
            ]
        ]);
    }

    public function expensesByCategory(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $expensesByCategory = Transaction::select('category_id', 'categories.name', DB::raw('SUM(amount) as total'))
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $request->user()->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->groupBy('category_id', 'categories.name')
            ->orderBy('total', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $expensesByCategory
        ]);
    }

    public function recentTransactions(Request $request)
    {
        $transactions = Transaction::with(['category', 'bankAccount'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    private function recalculateBudgetsAfterChange(
        Transaction $transaction,
        ?string $originalType = null,
        ?int $originalCategoryId = null,
        ?CarbonInterface $originalDate = null
    ): void {
        $currentType = $this->normalizeType($transaction->type);
        $currentDate = $transaction->date instanceof CarbonInterface
            ? $transaction->date
            : Carbon::parse($transaction->date);

        if ($currentType === 'income') {
            $this->budgetService->recalculateForIncome($transaction->user_id, $currentDate);
        } else {
            $this->budgetService->recalculateForExpense($transaction->user_id, $transaction->category_id, $currentDate);
        }

        if ($originalType === null) {
            return;
        }

        if ($originalType === 'income') {
            if ($originalDate && ($currentType !== 'income' || !$originalDate->isSameDay($currentDate))) {
                $this->budgetService->recalculateForIncome($transaction->user_id, $originalDate);
            }
            return;
        }

        if ($originalCategoryId === null) {
            return;
        }

        $shouldRecalculateOriginal = $currentType !== 'expense'
            || $originalCategoryId !== $transaction->category_id
            || ($originalDate && !$originalDate->isSameDay($currentDate));

        if ($shouldRecalculateOriginal) {
            $referenceDate = $originalDate ?? $currentDate;
            $this->budgetService->recalculateForExpense($transaction->user_id, $originalCategoryId, $referenceDate);
        }
    }

    private function normalizeType(string $type): string
    {
        return str_contains($type, '.')
            ? substr(strrchr($type, '.'), 1)
            : $type;
    }

    private function resolveTime(Request $request, ?string $fallback = null): string
    {
        $timeInput = $request->input('time');
        if (!empty($timeInput)) {
            return $this->normalizeTimeValue($timeInput);
        }

        $dateInput = $request->input('date');
        if (!empty($dateInput)) {
            $extracted = $this->extractTimeFromDate($dateInput);
            if ($extracted !== null) {
                return $extracted;
            }
        }

        return $fallback ?? '00:00:00';
    }

    private function extractTimeFromDate(string $dateValue): ?string
    {
        if (!str_contains($dateValue, ' ')) {
            return null;
        }

        $segments = preg_split('/\s+/', trim($dateValue));
        if (!$segments || count($segments) < 2) {
            return null;
        }

        return $this->normalizeTimeValue($segments[1]);
    }

    private function normalizeTimeValue(string $timeValue): string
    {
        $trimmed = trim($timeValue);
        $format = strlen($trimmed) === 5 ? 'H:i' : 'H:i:s';

        return Carbon::createFromFormat($format, $trimmed)->format('H:i:s');
    }
}
