<?php

namespace App\Domains\Products\Http\Requests;

use App\Domains\Products\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
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
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products')->where('tenant_id', $this->integer('tenant_id'))->ignore($this->product)],
            'barcode' => ['nullable', 'string', 'max:128', Rule::unique('products')->where('tenant_id', $this->integer('tenant_id'))->ignore($this->product)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', 'string', 'max:32'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'tax_type' => ['required', Rule::in(['VATABLE', 'VAT_EXEMPT', 'ZERO_RATED', 'NON_VAT'])],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->integer('category_id') ? ProductCategory::find($this->integer('category_id')) : null;

                if ($category && (int) $category->tenant_id !== $this->integer('tenant_id')) {
                    $validator->errors()->add('category_id', 'The category must belong to the selected tenant.');
                }
            },
        ];
    }
}
