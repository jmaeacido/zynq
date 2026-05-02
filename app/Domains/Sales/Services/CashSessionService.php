<?php

namespace App\Domains\Sales\Services;

use App\Domains\Branches\Models\Branch;
use App\Domains\Sales\Models\CashSession;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashSessionService
{
    public function open(Branch $branch, Terminal $terminal, User $user, float $openingCash): CashSession
    {
        if ((int) $terminal->tenant_id !== (int) $branch->tenant_id || (int) $terminal->branch_id !== (int) $branch->id) {
            throw new InvalidArgumentException('Terminal must belong to the selected branch.');
        }

        if (! $user->hasRole('Super Admin') && (int) $user->tenant_id !== (int) $branch->tenant_id) {
            throw new InvalidArgumentException('User cannot open a session for another tenant.');
        }

        if (! $user->hasRole('Super Admin') && $user->branch_id && (int) $user->branch_id !== (int) $branch->id) {
            throw new InvalidArgumentException('User cannot open a session for another branch.');
        }

        if ($openingCash < 0) {
            throw new InvalidArgumentException('Opening cash cannot be negative.');
        }

        return DB::transaction(function () use ($branch, $terminal, $user, $openingCash) {
            $exists = CashSession::where('tenant_id', $branch->tenant_id)
                ->where('branch_id', $branch->id)
                ->where('terminal_id', $terminal->id)
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException('This cashier already has an open session for this terminal.');
            }

            return CashSession::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'terminal_id' => $terminal->id,
                'user_id' => $user->id,
                'status' => 'open',
                'opening_cash' => $openingCash,
                'opened_at' => now(),
                'metadata' => ['phase' => 5],
            ]);
        });
    }

    public function close(CashSession $session, float $actualCash): CashSession
    {
        if ($actualCash < 0) {
            throw new InvalidArgumentException('Actual cash cannot be negative.');
        }

        return DB::transaction(function () use ($session, $actualCash) {
            $session = CashSession::lockForUpdate()->findOrFail($session->id);

            if ($session->status === 'closed') {
                throw new InvalidArgumentException('Cash session is already closed.');
            }

            $expected = $this->expectedCash($session);

            $session->update([
                'status' => 'closed',
                'expected_cash' => $expected,
                'actual_cash' => round($actualCash, 2),
                'cash_difference' => round($actualCash - $expected, 2),
                'closed_at' => now(),
            ]);

            return $session->refresh();
        });
    }

    public function openSessionForSale(Branch $branch, Terminal $terminal, User $user): ?CashSession
    {
        return CashSession::where('tenant_id', $branch->tenant_id)
            ->where('branch_id', $branch->id)
            ->where('terminal_id', $terminal->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function expectedCash(CashSession $session): float
    {
        $cashPayments = $session->sales()
            ->where('status', 'completed')
            ->whereHas('payments', fn ($query) => $query->where('payment_method', 'cash'))
            ->with('payments')
            ->get()
            ->flatMap->payments
            ->where('payment_method', 'cash')
            ->sum(fn ($payment) => (float) $payment->amount);

        return round((float) $session->opening_cash + $cashPayments, 2);
    }
}
