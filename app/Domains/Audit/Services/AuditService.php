<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Models\AuditLog;
use App\Models\User;

class AuditService
{
    public function record(?User $user, string $action, string $module, string $recordType, int|string $recordId, array $newValues = [], array $oldValues = [], ?int $tenantId = null, ?int $branchId = null): AuditLog
    {
        return AuditLog::create([
            'tenant_id' => $tenantId ?? $user?->tenant_id,
            'branch_id' => $branchId ?? $user?->branch_id,
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'record_type' => $recordType,
            'record_id' => (string) $recordId,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
