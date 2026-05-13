<?php

namespace App\Http\Requests;

use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class MatchReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank_transaction_id' => ['required', 'integer', 'exists:bank_transactions,id'],
            'matchable_type' => ['required', 'string', 'in:invoice,expense'],
            'matchable_id' => ['required', 'integer'],
            'matched_amount' => ['nullable', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $type = $this->input('matchable_type');
            $id = (int) $this->input('matchable_id');

            $exists = match ($type) {
                'invoice' => Invoice::where('id', $id)->exists(),
                'expense' => Expense::where('id', $id)->exists(),
                default => false,
            };

            if (! $exists) {
                $v->errors()->add('matchable_id', 'The selected invoice or expense does not exist.');
            }
        });
    }
}
