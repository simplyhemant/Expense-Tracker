<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $query = Debt::where('user_id', $request->user()->id)
            ->with('payments')
            ->orderByDesc('created_at');

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['debt', 'credit'])) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'paid':
                    $query->where('is_paid', true);
                    break;
                case 'unpaid':
                    $query->where('is_paid', false);
                    break;
                case 'overdue':
                    $query->where('is_paid', false)
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', Carbon::today());
                    break;
                case 'upcoming':
                    $query->where('is_paid', false)
                        ->whereNotNull('due_date')
                        ->where('due_date', '>=', Carbon::today())
                        ->where('due_date', '<=', Carbon::today()->addDays(7));
                    break;
            }
        }

        $debts = $query->get();

        // Recalculate remaining amounts based on payments
        foreach ($debts as $debt) {
            $debt->calculateRemainingAmount();
            $debt->save();
        }

        return response()->json([
            'success' => true,
            'data' => $debts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:debt,credit',
            'person_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'reminder_date' => 'nullable|date',
        ]);

        $debt = Debt::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'person_name' => $validated['person_name'],
            'amount' => $validated['amount'],
            'paid_amount' => 0,
            'remaining_amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'reminder_date' => $validated['reminder_date'] ?? null,
            'is_paid' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $debt->load('payments')
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $debt = Debt::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('payments')
            ->first();

        if (!$debt) {
            return response()->json([
                'success' => false,
                'error' => 'Debt not found'
            ], 404);
        }

        // Recalculate remaining amount
        $debt->calculateRemainingAmount();
        $debt->save();

        return response()->json([
            'success' => true,
            'data' => $debt
        ]);
    }

    public function update(Request $request, $id)
    {
        $debt = Debt::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$debt) {
            return response()->json([
                'success' => false,
                'error' => 'Debt not found'
            ], 404);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:debt,credit',
            'person_name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'reminder_date' => 'nullable|date',
        ]);

        $debt->fill($validated);
        
        // Recalculate remaining amount if amount changed
        if (isset($validated['amount'])) {
            $debt->remaining_amount = $debt->amount - $debt->paid_amount;
            $debt->is_paid = $debt->remaining_amount <= 0;
        }
        
        $debt->save();

        return response()->json([
            'success' => true,
            'data' => $debt->load('payments')
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $debt = Debt::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$debt) {
            return response()->json([
                'success' => false,
                'error' => 'Debt not found'
            ], 404);
        }

        $debt->delete();

        return response()->json([
            'success' => true,
            'message' => 'Debt deleted successfully'
        ]);
    }

    public function addPayment(Request $request, $id)
    {
        $debt = Debt::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$debt) {
            return response()->json([
                'success' => false,
                'error' => 'Debt not found'
            ], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        // Check if payment amount exceeds remaining amount
        if ($validated['amount'] > $debt->remaining_amount) {
            return response()->json([
                'success' => false,
                'error' => 'Payment amount cannot exceed remaining amount'
            ], 422);
        }

        DB::transaction(function () use ($debt, $validated) {
            $payment = DebtPayment::create([
                'debt_id' => $debt->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'description' => $validated['description'] ?? null,
            ]);

            // Recalculate debt amounts
            $debt->calculateRemainingAmount();
            $debt->save();

            return $payment;
        });

        return response()->json([
            'success' => true,
            'data' => $debt->load('payments')
        ], 201);
    }

    public function updatePayment(Request $request, $id, $paymentId)
    {
        $debt = Debt::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$debt) {
            return response()->json([
                'success' => false,
                'error' => 'Debt not found'
            ], 404);
        }

        $payment = DebtPayment::where('id', $paymentId)
            ->where('debt_id', $debt->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'error' => 'Payment not found'
            ], 404);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'payment_date' => 'sometimes|date',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($payment, $debt, $validated) {
            $payment->fill($validated);
            $payment->save();

            // Recalculate debt amounts
            $debt->calculateRemainingAmount();
            $debt->save();
        });

        return response()->json([
            'success' => true,
            'data' => $debt->load('payments')
        ]);
    }

    public function deletePayment(Request $request, $id, $paymentId)
    {
        $debt = Debt::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$debt) {
            return response()->json([
                'success' => false,
                'error' => 'Debt not found'
            ], 404);
        }

        $payment = DebtPayment::where('id', $paymentId)
            ->where('debt_id', $debt->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'error' => 'Payment not found'
            ], 404);
        }

        DB::transaction(function () use ($payment, $debt) {
            $payment->delete();

            // Recalculate debt amounts
            $debt->calculateRemainingAmount();
            $debt->save();
        });

        return response()->json([
            'success' => true,
            'data' => $debt->load('payments')
        ]);
    }
}

