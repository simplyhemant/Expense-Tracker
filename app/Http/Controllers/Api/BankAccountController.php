<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = BankAccount::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $account = BankAccount::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bank account created successfully',
            'data' => $account,
        ], 201);
    }

    public function show(Request $request, BankAccount $bankAccount)
    {
        if ($bankAccount->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bank account not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $bankAccount,
        ]);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        if ($bankAccount->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bank account not found'], 404);
        }

        $validated = $this->validatePayload($request, partial: true);

        $bankAccount->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bank account updated successfully',
            'data' => $bankAccount->refresh(),
        ]);
    }

    public function destroy(Request $request, BankAccount $bankAccount)
    {
        if ($bankAccount->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bank account not found'], 404);
        }

        Transaction::where('bank_account_id', $bankAccount->id)
            ->update(['bank_account_id' => null]);

        $bankAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bank account deleted successfully',
        ]);
    }

    private function validatePayload(Request $request, bool $partial = false): array
    {
        $nameRule = $partial ? 'nullable|string|max:255' : 'required|string|max:255';
        $bankNameRule = 'nullable|string|max:255';

        $validated = $request->validate([
            'name' => $nameRule,
            'bank_name' => $bankNameRule,
            'card_number' => 'nullable|string|max:32',
            'account_number' => 'nullable|string|max:32',
        ]);

        if (array_key_exists('name', $validated)) {
            $validated['name'] = trim($validated['name']);
        }

        if (array_key_exists('bank_name', $validated)) {
            $validated['bank_name'] = trim($validated['bank_name'] ?? '') ?: null;
        }

        if ((!array_key_exists('bank_name', $validated) || $validated['bank_name'] === null)
            && array_key_exists('name', $validated)) {
            $validated['bank_name'] = $validated['name'];
        }

        if (array_key_exists('card_number', $validated)) {
            $validated['card_number'] = $this->stripNonDigits($validated['card_number']);
        }

        if (array_key_exists('account_number', $validated)) {
            $validated['account_number'] = $this->stripNonDigits($validated['account_number'], allowLetters: true);
        }

        return $validated;
    }

    private function stripNonDigits(?string $value, bool $allowLetters = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $pattern = $allowLetters ? '/[^A-Za-z0-9]/' : '/\D/';
        $cleanValue = preg_replace($pattern, '', $value) ?? '';

        return $cleanValue === '' ? null : $cleanValue;
    }
}

