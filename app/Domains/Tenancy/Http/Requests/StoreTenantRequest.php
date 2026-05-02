<?php

namespace App\Domains\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage tenants') ?? false;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'registered_address' => ['required', 'string', 'max:2000'],
            'tin' => ['required', 'string', 'max:32', 'unique:tenants,tin'],
            'taxpayer_type' => ['required', Rule::in(['VAT', 'NON_VAT'])],
            'bir_rdo_code' => ['nullable', 'string', 'max:32'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'license_status' => ['required', Rule::in(['trial', 'active', 'grace_period', 'suspended', 'disabled'])],
            'subscription_expires_at' => ['nullable', 'date'],
            'grace_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'invoice_footer' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
