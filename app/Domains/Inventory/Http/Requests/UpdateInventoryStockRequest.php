<?php

namespace App\Domains\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $stock = $this->route('inventory');

        return ($user?->can('manage inventory') ?? false)
            && ($user->hasRole('Super Admin') || (int) $stock->tenant_id === (int) $user->tenant_id);
    }

    public function rules(): array
    {
        return [
            'reorder_point' => ['required', 'numeric', 'min:0'],
        ];
    }
}
