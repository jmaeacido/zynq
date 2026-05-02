<?php

namespace App\Domains\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('manage settings') ?? false)
            && ($user->hasRole('Super Admin') || (int) $this->integer('tenant_id') === (int) $user->tenant_id);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'invoice_title_default' => ['required', 'string', 'max:64'],
            'compliance_contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
