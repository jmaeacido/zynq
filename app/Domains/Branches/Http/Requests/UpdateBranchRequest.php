<?php

namespace App\Domains\Branches\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('manage branches') ?? false)
            && ($user->hasRole('Super Admin') || (int) $this->integer('tenant_id') === (int) $user->tenant_id);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_code' => ['required', 'string', 'max:32', Rule::unique('branches')->where('tenant_id', $this->integer('tenant_id'))->ignore($this->branch)],
            'address' => ['required', 'string', 'max:2000'],
            'bir_registered_address' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
