<?php

namespace App\Domains\Taxation\Services;

use App\Domains\Settings\Models\TenantSetting;
use App\Domains\Tenancy\Models\Tenant;

class TaxEngine
{
    public function vatRateForTenant(Tenant $tenant): float
    {
        $setting = TenantSetting::where('tenant_id', $tenant->id)->where('key', 'vat_rate')->first();

        return (float) data_get($setting?->value, 'rate', 12);
    }

    /**
     * @param array<int, array{tax_type: string, line_total: float}> $items
     * @return array<string, float>
     */
    public function summarize(Tenant $tenant, array $items, float $discountTotal): array
    {
        $rate = $this->vatRateForTenant($tenant);
        $gross = round(array_sum(array_column($items, 'line_total')), 2);
        $net = max(0, round($gross - $discountTotal, 2));
        $summary = [
            'vat_rate' => $rate,
            'gross_sales' => $gross,
            'vatable_sales' => 0.0,
            'vat_amount' => 0.0,
            'vat_exempt_sales' => 0.0,
            'zero_rated_sales' => 0.0,
            'non_vat_sales' => 0.0,
            'discounts' => round($discountTotal, 2),
            'net_sales' => $net,
            'total_amount_due' => $net,
        ];

        foreach ($items as $item) {
            $lineNet = $gross > 0 ? round(((float) $item['line_total']) - ($discountTotal * ((float) $item['line_total'] / $gross)), 2) : 0.0;

            if ($tenant->taxpayer_type === 'NON_VAT') {
                $summary['non_vat_sales'] += $lineNet;
                continue;
            }

            if ($item['tax_type'] === 'VATABLE') {
                $this->addVatable($summary, $lineNet, $rate);
            } elseif ($item['tax_type'] === 'VAT_EXEMPT') {
                $summary['vat_exempt_sales'] += $lineNet;
            } elseif ($item['tax_type'] === 'ZERO_RATED') {
                $summary['zero_rated_sales'] += $lineNet;
            } else {
                $summary['non_vat_sales'] += $lineNet;
            }
        }

        foreach (['vatable_sales', 'vat_amount', 'vat_exempt_sales', 'zero_rated_sales', 'non_vat_sales'] as $key) {
            $summary[$key] = round($summary[$key], 2);
        }

        return $summary;
    }

    private function addVatable(array &$summary, float $lineNet, float $rate): void
    {
        $divisor = 1 + ($rate / 100);
        $vatable = round($lineNet / $divisor, 2);
        $summary['vatable_sales'] += $vatable;
        $summary['vat_amount'] += round($lineNet - $vatable, 2);
    }
}
