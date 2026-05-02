<?php

namespace App\Domains\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBirInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'registered_address' => ['required', 'string', 'max:500'],
            'tin' => ['required', 'string', 'max:50'],
            'taxpayer_type' => ['required', Rule::in(['VAT', 'NON_VAT'])],
            'bir_rdo_code' => ['required', 'string', 'max:20'],
            'bir_registration_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
