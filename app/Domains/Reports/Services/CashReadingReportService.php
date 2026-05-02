<?php

namespace App\Domains\Reports\Services;

use App\Domains\Sales\Models\CashSession;
use App\Domains\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

class CashReadingReportService
{
    public function reading(CashSession $session): array
    {
        $sales = $session->sales()->where('status', 'completed')->with(['payments', 'taxSummary'])->get();
        $payments = $sales->flatMap->payments;
        $gross = round((float) $sales->sum('subtotal'), 2);
        $discounts = round((float) $sales->sum('discount_total'), 2);
        $net = round((float) $sales->sum('total_amount'), 2);
        $cash = round((float) $payments->where('payment_method', 'cash')->sum('amount'), 2);
        $card = round((float) $payments->where('payment_method', 'card')->sum('amount'), 2);
        $wallet = round((float) $payments->where('payment_method', 'e_wallet')->sum('amount'), 2);
        $expectedCash = round((float) $session->opening_cash + $cash, 2);

        return [
            'session' => $session,
            'sale_count' => $sales->count(),
            'gross_sales' => $gross,
            'discounts' => $discounts,
            'net_sales' => $net,
            'cash_payments' => $cash,
            'card_payments' => $card,
            'e_wallet_payments' => $wallet,
            'opening_cash' => (float) $session->opening_cash,
            'expected_cash' => $session->status === 'closed' ? (float) $session->expected_cash : $expectedCash,
            'actual_cash' => $session->actual_cash === null ? null : (float) $session->actual_cash,
            'cash_difference' => $session->cash_difference === null ? null : (float) $session->cash_difference,
            'z_valid' => $session->status === 'closed' ? round((float) $session->expected_cash, 2) === $expectedCash : null,
        ];
    }

    public function dailySales($user, ?string $date = null): array
    {
        $query = Sale::query()
            ->where('status', 'completed')
            ->when(! $user->hasRole('Super Admin'), fn (Builder $builder) => $builder->where('tenant_id', $user->tenant_id))
            ->whereDate('created_at', $date ?? now()->toDateString());

        return [
            'sale_count' => (clone $query)->count(),
            'gross_sales' => round((float) (clone $query)->sum('subtotal'), 2),
            'net_sales' => round((float) (clone $query)->sum('total_amount'), 2),
        ];
    }
}
