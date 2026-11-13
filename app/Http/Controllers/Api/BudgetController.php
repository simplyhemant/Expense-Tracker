<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $budgets = Budget::query()
            ->with('category')
            ->where('user_id', $request->user()->id)
            ->orderBy('start_date')
            ->get();

        \Log::info("Returning {$budgets->count()} budgets for user {$request->user()->id}");
        foreach ($budgets as $budget) {
            $categoryName = $budget->category ? $budget->category->name : 'N/A';
            \Log::info(
                "Budget #{$budget->id}: Category={$categoryName}, " .
                "Amount={$budget->amount}, Spent={$budget->spent}, " .
                "Remaining=" . ($budget->amount - $budget->spent)
            );
        }

        return response()->json([
            'success' => true,
            'data' => $budgets,
        ]);
    }

    public function store(BudgetRequest $request): JsonResponse
    {
        $category = Category::query()
            ->where('id', $request->input('category_id'))
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                      ->orWhereNull('user_id'); // Allow global categories
            })
            ->first();

        abort_if(! $category, 404, 'Category not found');

        // Budgets are calculated on-the-fly for the current Jalali month
        // Store current month dates for reference, but calculations use current month dynamically
        $startDate = Budget::getCurrentJalaliMonthStart();
        $endDate = Budget::getCurrentJalaliMonthEnd();

        $type = $request->input('type');

        $budget = Budget::create([
            'user_id' => $request->user()->id,
            'category_id' => $category->id,
            'name' => $request->input('name', $category->name),
            'amount' => $type === 'fixed' ? $request->input('amount') : 0,
            'spent' => 0,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period' => $request->input('period', 'monthly'), // Default to monthly
            'type' => $type,
            'percentage' => $type === 'percentage' ? $request->input('percentage') : null,
        ]);

        $budget->load('category');

        $this->budgetService->refreshBudget($budget);

        return response()->json([
            'success' => true,
            'message' => 'Budget created successfully',
            'data' => $budget->fresh(['category']),
        ], 201);
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        return response()->json([
            'success' => true,
            'data' => $budget->load('category'),
        ]);
    }

    public function update(BudgetRequest $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        $validated = $request->validated();

        if (array_key_exists('category_id', $validated)) {
            $category = Category::query()
                ->where('id', $validated['category_id'])
                ->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)
                          ->orWhereNull('user_id'); // Allow global categories
                })
                ->first();

            abort_if(! $category, 404, 'Category not found');

            $budget->category_id = $category->id;
            $budget->name = $request->input('name', $category->name);
        }

        if (array_key_exists('name', $validated)) {
            $budget->name = $validated['name'];
        }

        if (array_key_exists('type', $validated)) {
            $budget->type = $validated['type'];
        }

        if (array_key_exists('period', $validated)) {
            $budget->period = $validated['period'];
            // Update dates to current Jalali month (for reference, calculations are on-the-fly)
            $budget->start_date = Budget::getCurrentJalaliMonthStart();
            $budget->end_date = Budget::getCurrentJalaliMonthEnd();
        }

        if ($budget->type === 'fixed') {
            if (array_key_exists('amount', $validated)) {
                $budget->amount = $validated['amount'];
            }
            $budget->percentage = null;
        } else {
            if (array_key_exists('percentage', $validated)) {
                $budget->percentage = $validated['percentage'];
            }
            $budget->amount = 0;
        }

        $budget->save();

        $budget->load('category');
        $this->budgetService->refreshBudget($budget);

        return response()->json([
            'success' => true,
            'message' => 'Budget updated successfully',
            'data' => $budget->fresh(['category']),
        ]);
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);
        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted successfully',
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $budgets = Budget::query()
            ->where('user_id', $request->user()->id)
            ->get();

        $totalPlanned = $budgets->sum('amount');
        $totalSpent = $budgets->sum('spent');

        return response()->json([
            'success' => true,
            'data' => [
                'total_planned' => $totalPlanned,
                'total_spent' => $totalSpent,
                'total_remaining' => max($totalPlanned - $totalSpent, 0),
            ],
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $budgets = Budget::query()
            ->where('user_id', $request->user()->id)
            ->get();

        \Log::info("Refreshing {$budgets->count()} budgets for user {$request->user()->id}");

        foreach ($budgets as $budget) {
            $previousSpent = $budget->spent;
            $this->budgetService->refreshBudget($budget);
            $budget->refresh();
            
            $categoryName = $budget->category ? $budget->category->name : 'N/A';
            \Log::info(
                "Budget #{$budget->id} ({$categoryName}): " .
                "Spent updated from {$previousSpent} to {$budget->spent}"
            );
        }

        $refreshedBudgets = $budgets->fresh()->map->load('category');

        return response()->json([
            'success' => true,
            'message' => 'All budgets refreshed successfully',
            'data' => $refreshedBudgets,
        ]);
    }

    private function authorizeBudget(Request $request, Budget $budget): void
    {
        abort_if($budget->user_id !== $request->user()->id, 404, 'Budget not found');
    }

    private function calculateEndDate(Carbon $startDate, string $period): Carbon
    {
        $anchor = $startDate->copy()->startOfDay();

        return match ($period) {
            'quarterly' => $anchor->copy()->startOfMonth()->addMonthsNoOverflow(3)->subDay()->endOfDay(),
            'yearly' => $anchor->copy()->startOfMonth()->addYear()->subDay()->endOfDay(),
            default => $anchor->copy()->endOfMonth(),
        };
    }

    private function firstDayOfCurrentJalaliMonth(): Carbon
    {
        $now = Carbon::now();
        [$jy, $jm, $jd] = $this->gregorianToJalali($now->year, $now->month, $now->day);
        [$gy, $gm, $gd] = $this->jalaliToGregorian($jy, $jm, 1);

        return Carbon::create($gy, $gm, $gd)->startOfDay();
    }

    private function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gDays = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) + $gd + $gDays[$gm - 1];

        $jy = -1595 + (33 * (int)($days / 12053));
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));

        return [$jy, $jm, $jd];
    }

    private function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (int)(($jy / 33) * 8) + (int)(((($jy % 33) + 3) / 4));
        $days += ($jm < 7) ? (($jm - 1) * 31) : (((($jm - 7) * 30) + 186));
        $days += $jd - 1;

        $gy = 400 * (int)($days / 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100;
            $days -= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * (int)($days / 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $months = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $leap = ($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0);

        if ($leap) {
            $months[1] = 29;
        }

        $gm = 0;
        while ($gm < 12 && $gd > $months[$gm]) {
            $gd -= $months[$gm];
            $gm++;
        }

        return [$gy, $gm + 1, $gd];
    }
}

