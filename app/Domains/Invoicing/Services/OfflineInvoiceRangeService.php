<?php

namespace App\Domains\Invoicing\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Branches\Models\Branch;
use App\Domains\Invoicing\Models\InvoiceSequence;
use App\Domains\Invoicing\Models\OfflineInvoiceRange;
use App\Domains\Sales\Models\CashSession;
use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OfflineInvoiceRangeService
{
    public function __construct(
        private readonly TerminalComplianceService $terminalCompliance,
        private readonly AuditService $audit,
    ) {
    }

    public function enabled(): bool
    {
        return (bool) config('offline.invoice_range_enabled', false);
    }

    public function reserve(User $user, Branch $branch, Terminal $terminal, string $documentType = 'sales_invoice'): OfflineInvoiceRange
    {
        if (! $this->enabled()) {
            throw new InvalidArgumentException('Reserved offline invoice ranges are disabled.');
        }

        return DB::transaction(function () use ($user, $branch, $terminal, $documentType): OfflineInvoiceRange {
            $this->assertScope($user, $branch, $terminal);

            if (! $this->terminalCompliance->isSaleReady($terminal)) {
                throw new InvalidArgumentException('Terminal compliance setup is incomplete.');
            }

            $openSession = CashSession::where('tenant_id', $branch->tenant_id)
                ->where('branch_id', $branch->id)
                ->where('terminal_id', $terminal->id)
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->exists();

            if (! $openSession) {
                throw new InvalidArgumentException('An open cash session is required before reserving offline invoice ranges.');
            }

            $sequence = InvoiceSequence::where('tenant_id', $branch->tenant_id)
                ->where('branch_id', $branch->id)
                ->where('terminal_id', $terminal->id)
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            $start = (int) (($sequence?->current_number ?? 0) + 1);
            $end = $start + max(1, (int) config('offline.invoice_range_size', 25)) - 1;

            $overlap = OfflineInvoiceRange::where('tenant_id', $branch->tenant_id)
                ->where('branch_id', $branch->id)
                ->where('terminal_id', $terminal->id)
                ->where('document_type', $documentType)
                ->whereIn('status', ['active', 'reserved'])
                ->where('expires_at', '>', now())
                ->where('range_start', '<=', $end)
                ->where('range_end', '>=', $start)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw new InvalidArgumentException('Requested offline invoice range overlaps an active reserved range.');
            }

            $range = OfflineInvoiceRange::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'terminal_id' => $terminal->id,
                'document_type' => $documentType,
                'range_start' => $start,
                'range_end' => $end,
                'next_number' => $start,
                'status' => 'active',
                'reserved_by_user_id' => $user->id,
                'reserved_at' => now(),
                'expires_at' => now()->addHours((int) config('offline.invoice_range_ttl_hours', 24)),
            ]);

            $this->audit->record($user, 'offline_invoice_range_reserved', 'offline_sync', 'offline_invoice_range', $range->id, [
                'range_start' => $range->range_start,
                'range_end' => $range->range_end,
                'terminal_id' => $terminal->id,
            ], tenantId: $branch->tenant_id, branchId: $branch->id);

            return $range;
        });
    }

    public function activeFor(Branch $branch, Terminal $terminal, string $documentType = 'sales_invoice')
    {
        return OfflineInvoiceRange::where('tenant_id', $branch->tenant_id)
            ->where('branch_id', $branch->id)
            ->where('terminal_id', $terminal->id)
            ->where('document_type', $documentType)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('range_start')
            ->get();
    }

    public function validateSubmittedNumber(array $payload): void
    {
        if (! $this->enabled() || blank(data_get($payload, 'sale.offline_invoice_number'))) {
            return;
        }

        $number = (int) data_get($payload, 'sale.offline_invoice_number');
        $range = OfflineInvoiceRange::where('tenant_id', (int) $payload['tenant_id'])
            ->where('branch_id', (int) $payload['branch_id'])
            ->where('terminal_id', (int) $payload['terminal_id'])
            ->where('document_type', 'sales_invoice')
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->where('range_start', '<=', $number)
            ->where('range_end', '>=', $number)
            ->lockForUpdate()
            ->first();

        if (! $range) {
            throw new InvalidArgumentException('Offline invoice number is outside the active reserved range.');
        }
    }

    private function assertScope(User $user, Branch $branch, Terminal $terminal): void
    {
        if ((int) $terminal->tenant_id !== (int) $branch->tenant_id || (int) $terminal->branch_id !== (int) $branch->id) {
            throw new InvalidArgumentException('Terminal must belong to the selected branch and tenant.');
        }

        if (! $user->hasRole('Super Admin') && (int) $user->tenant_id !== (int) $branch->tenant_id) {
            throw new InvalidArgumentException('User cannot reserve ranges for another tenant.');
        }

        if (! $user->hasRole('Super Admin') && $user->branch_id && (int) $user->branch_id !== (int) $branch->id) {
            throw new InvalidArgumentException('User cannot reserve ranges for another branch.');
        }
    }
}
