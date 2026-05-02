<?php

namespace App\Domains\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', Rule::unique('product_categories')->where('tenant_id', $this->integer('tenant_id'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
