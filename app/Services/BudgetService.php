<?php

namespace App\Services;

use App\Events\BudgetThresholdReached;
use App\Models\Budget;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BudgetService
{
    public function refreshBudget(Budget $budget): void
    {
        $previousSpent = $budget->spent;

        $budget->refreshSpent();
        $budget->refresh();

        if ($budget->type === 'percentage' && $budget->percentage !== null) {
            $income = $this->calculateIncomeTotal($budget);
            $calculatedAmount = round($income * ($budget->percentage / 100), 2);
            $budget->amount = max($calculatedAmount, 0);
            $budget->save();
            $budget->refresh();
        }

        if ($budget->amount <= 0) {
            return;
        }

        if ($budget->spent >= $budget->amount) {
            BudgetThresholdReached::dispatch($budget, BudgetThresholdReached::STATUS_EXCEEDED);
            return;
        }

        $thresholdValue = $budget->amount * 0.8;
        if ($previousSpent < $thresholdValue && $budget->spent >= $thresholdValue) {
            BudgetThresholdReached::dispatch($budget, BudgetThresholdReached::STATUS_THRESHOLD);
        }
    }

    public function recalculateForIncome(int $userId, CarbonInterface $date): void
    {
        // Only recalculate budgets if the transaction date is in the current Jalali month
        $startDate = Budget::getCurrentJalaliMonthStart();
        $endDate = Budget::getCurrentJalaliMonthEnd();
        
        if (!$date->betweenIncluded($startDate, $endDate)) {
            return;
        }

        $this->matchingBudgets($userId, null)
            ->each(fn (Budget $budget) => $this->refreshBudget($budget));
    }

    public function recalculateForExpense(int $userId, ?int $categoryId, CarbonInterface $date): void
    {
        // Only recalculate if category_id is provided
        // Transactions without categories don't affect budgets
        if ($categoryId === null) {
            return;
        }

        // Only recalculate budgets if the transaction date is in the current Jalali month
        $startDate = Budget::getCurrentJalaliMonthStart();
        $endDate = Budget::getCurrentJalaliMonthEnd();
        
        if (!$date->betweenIncluded($startDate, $endDate)) {
            return;
        }

        $this->matchingBudgets($userId, $categoryId)
            ->each(fn (Budget $budget) => $this->refreshBudget($budget));
    }

    /**
     * @return Collection<int, Budget>
     */
    private function matchingBudgets(int $userId, ?int $categoryId = null): Collection
    {
        $query = Budget::query()
            ->where('user_id', $userId);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->get();
    }

    private function calculateIncomeTotal(Budget $budget): float
    {
        $startDate = Budget::getCurrentJalaliMonthStart();
        $endDate = Budget::getCurrentJalaliMonthEnd();
        
        return (float) Transaction::query()
            ->where('user_id', $budget->user_id)
            ->where('type', 'income')
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->sum('amount');
    }
}

