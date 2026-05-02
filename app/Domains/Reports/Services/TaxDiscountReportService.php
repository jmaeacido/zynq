<?php

namespace App\Domains\Reports\Services;

use App\Domains\Sales\Models\SaleDiscount;
use App\Domains\Sales\Models\SaleTax;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TaxDiscountReportService
{
    public function vatSales(User $user, ?string $from = null, ?string $to = null): array
    {
        return $this->taxTotals($this->taxQuery($user, $from, $to)->where('vatable_sales', '>', 0));
    }

    public function nonVatSales(User $user, ?string $from = null, ?string $to = null): array
    {
        return $this->taxTotals($this->taxQuery($user, $from, $to)->where('non_vat_sales', '>', 0));
    }

    public function discounts(User $user, ?string $from = null, ?string $to = null)
    {
        return $this->discountQuery($user, $from, $to)
            ->selectRaw('discount_type, count(*) as count, sum(amount) as total_amount')
            ->groupBy('discount_type')
            ->orderBy('discount_type')
            ->get();
    }

    private function taxQuery(User $user, ?string $from, ?string $to): Builder
    {
        return SaleTax::query()
            ->when(! $user->hasRole('Super Admin'), fn (Builder $query) => $query->where('tenant_id', $user->tenant_id))
            ->when($from, fn (Builder $query) => $query->whereDate('created_at', '>=', Carbon::parse($from)->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate('created_at', '<=', Carbon::parse($to)->toDateString()));
    }

    private function discountQuery(User $user, ?string $from, ?string $to): Builder
    {
        return SaleDiscount::query()
            ->when(! $user->hasRole('Super Admin'), fn (Builder $query) => $query->where('tenant_id', $user->tenant_id))
            ->when($from, fn (Builder $query) => $query->whereDate('created_at', '>=', Carbon::parse($from)->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate('created_at', '<=', Carbon::parse($to)->toDateString()));
    }

    private function taxTotals(Builder $query): array
    {
        $row = $query->selectRaw('
            sum(gross_sales) as gross_sales,
            sum(vatable_sales) as vatable_sales,
            sum(vat_amount) as vat_amount,
            sum(vat_exempt_sales) as vat_exempt_sales,
            sum(zero_rated_sales) as zero_rated_sales,
            sum(non_vat_sales) as non_vat_sales,
            sum(discounts) as discounts,
            sum(net_sales) as net_sales,
            sum(total_amount_due) as total_amount_due
        ')->first();

        return collect($row?->getAttributes() ?? [])->map(fn ($value) => round((float) $value, 2))->all();
    }
}
