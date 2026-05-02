<?php

namespace App\Domains\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('cash_session');
        $user = $this->user();

        return ($user?->can('create sales') ?? false)
            && ($user->hasRole('Super Admin') || (int) $session->tenant_id === (int) $user->tenant_id);
    }

    public function rules(): array
    {
        return [
            'actual_cash' => ['required', 'numeric', 'min:0'],
        ];
    }
}
