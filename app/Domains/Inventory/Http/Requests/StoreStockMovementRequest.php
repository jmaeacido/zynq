<?php

namespace App\Domains\Inventory\Http\Requests;

use App\Domains\Branches\Models\Branch;
use App\Domains\Products\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('manage inventory') ?? false)
            && ($user->hasRole('Super Admin') || (int) $this->integer('tenant_id') === (int) $user->tenant_id);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'product_id' => ['required', 'exists:products,id'],
            'movement_type' => ['required', Rule::in(['stock_in', 'stock_out', 'adjustment'])],
            'quantity' => ['required_unless:movement_type,adjustment', 'nullable', 'numeric', 'gt:0'],
            'target_quantity' => ['required_if:movement_type,adjustment', 'nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $branch = Branch::find($this->integer('branch_id'));
                $product = Product::find($this->integer('product_id'));

                if ($branch && (int) $branch->tenant_id !== $this->integer('tenant_id')) {
                    $validator->errors()->add('branch_id', 'The branch must belong to the selected tenant.');
                }

                if ($product && (int) $product->tenant_id !== $this->integer('tenant_id')) {
                    $validator->errors()->add('product_id', 'The product must belong to the selected tenant.');
                }
            },
        ];
    }
}
