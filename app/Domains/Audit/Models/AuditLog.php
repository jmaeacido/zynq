<?php

namespace App\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'branch_id', 'user_id', 'action', 'module', 'record_type', 'record_id', 'old_values', 'new_values', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }
}
