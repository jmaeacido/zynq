<?php

namespace App\Domains\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create sales') ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'terminal_id' => ['required', 'exists:terminals,id'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ];
    }
}
