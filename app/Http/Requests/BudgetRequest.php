<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'category_id' => ['required', 'integer', 'exists:categories,id'],
                'name' => ['sometimes', 'string', 'max:255'],
                'amount' => ['required_if:type,fixed', 'nullable', 'numeric', 'min:0.01'],
                'percentage' => ['required_if:type,percentage', 'nullable', 'numeric', 'min:0.01', 'max:100'],
                'period' => ['required', 'in:monthly,quarterly,yearly'],
                'type' => ['required', 'in:fixed,percentage'],
            ];
        }

        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'percentage' => ['sometimes', 'numeric', 'min:0.01', 'max:100'],
            'period' => ['sometimes', 'in:monthly,quarterly,yearly'],
            'type' => ['sometimes', 'in:fixed,percentage'],
        ];
    }
}

