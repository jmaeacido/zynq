<?php

namespace App\Domains\Licensing\Http\Requests;

use App\Domains\Licensing\Services\LicenseService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage licenses') ?? false;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['nullable', 'string', 'max:64', Rule::unique('tenants', 'license_key')->ignore($this->route('tenant'))],
            'license_status' => ['required', Rule::in(LicenseService::STATUSES)],
            'subscription_expires_at' => ['nullable', 'date'],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
