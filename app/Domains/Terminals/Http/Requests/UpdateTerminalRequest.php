<?php

namespace App\Domains\Terminals\Http\Requests;

use App\Domains\Branches\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('manage terminals') ?? false)
            && ($user->hasRole('Super Admin') || (int) $this->integer('tenant_id') === (int) $user->tenant_id);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'terminal_name' => ['required', 'string', 'max:255'],
            'terminal_code' => ['required', 'string', 'max:32', Rule::unique('terminals')->where('tenant_id', $this->integer('tenant_id'))->where('branch_id', $this->integer('branch_id'))->ignore($this->terminal)],
            'machine_identification_number' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255'],
            'permit_to_use_number' => ['nullable', 'string', 'max:255'],
            'accreditation_number' => ['nullable', 'string', 'max:255'],
            'software_version' => ['required', 'string', 'max:64'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $branch = Branch::find($this->integer('branch_id'));

                if ($branch && (int) $branch->tenant_id !== $this->integer('tenant_id')) {
                    $validator->errors()->add('branch_id', 'The branch must belong to the selected tenant.');
                }

                if ($branch && $this->user()?->branch_id && ! $this->user()?->hasRole('Super Admin') && (int) $branch->id !== (int) $this->user()->branch_id) {
                    $validator->errors()->add('branch_id', 'You do not have access to this branch.');
                }
            },
        ];
    }
}
