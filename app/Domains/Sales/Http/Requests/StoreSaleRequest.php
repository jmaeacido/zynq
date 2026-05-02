<?php

namespace App\Domains\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $discounts = collect($this->input('discounts', []))
            ->filter(fn (array $discount): bool => filled($discount['discount_type'] ?? null) && (float) ($discount['value'] ?? 0) > 0)
            ->values()
            ->all();

        $payments = collect($this->input('payments', []))
            ->filter(fn (array $payment): bool => filled($payment['payment_method'] ?? null) && (float) ($payment['amount'] ?? 0) > 0)
            ->values()
            ->all();

        $this->merge(['discounts' => $discounts, 'payments' => $payments]);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('create sales') ?? false)
            || ($user?->can('manage inventory') ?? false)
            || ($user?->hasRole('Super Admin') ?? false);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'terminal_id' => ['required', 'exists:terminals,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', 'in:cash,card,e_wallet'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:255'],
            'discounts' => ['nullable', 'array'],
            'discounts.*.discount_type' => ['required', 'in:regular,promo,manual,senior,pwd,solo_parent'],
            'discounts.*.value_type' => ['required', 'in:amount,percent'],
            'discounts.*.value' => ['required', 'numeric', 'gt:0'],
            'discounts.*.reason' => ['nullable', 'string', 'max:255'],
            'discounts.*.reference_number' => ['nullable', 'string', 'max:255'],
            'discounts.*.approved_by_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
