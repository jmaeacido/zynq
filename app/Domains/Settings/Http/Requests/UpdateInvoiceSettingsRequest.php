<?php

namespace App\Domains\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
