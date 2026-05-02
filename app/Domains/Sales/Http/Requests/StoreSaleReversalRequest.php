<?php

namespace App\Domains\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReversalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match ($this->input('type')) {
            'void' => $this->user()?->can('approve voids') ?? false,
            'refund', 'partial_refund' => $this->user()?->can('approve refunds') ?? false,
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:void,refund,partial_refund'],
            'reason' => ['required', 'string', 'max:2000'],
            'return_to_stock' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.sale_item_id' => ['required_with:items', 'exists:sale_items,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
        ];
    }
}
