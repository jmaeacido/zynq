<?php

namespace App\Domains\Discounts\Services;

use InvalidArgumentException;

class DiscountService
{
    /**
     * @return array{rows: array<int, array<string, mixed>>, total: float}
     */
    public function calculate(array $discountRows, float $grossSales): array
    {
        $rows = [];
        $total = 0.0;

        foreach ($discountRows as $row) {
            if (empty($row['discount_type']) || (float) ($row['value'] ?? 0) <= 0) {
                continue;
            }

            $type = $row['discount_type'];
            if (! in_array($type, ['regular', 'promo', 'manual', 'senior', 'pwd', 'solo_parent'], true)) {
                throw new InvalidArgumentException('Unsupported discount type.');
            }

            $valueType = $row['value_type'] ?? 'amount';
            if (! in_array($valueType, ['amount', 'percent'], true)) {
                throw new InvalidArgumentException('Unsupported discount value type.');
            }

            if ($type === 'manual' && empty($row['approved_by_user_id'])) {
                throw new InvalidArgumentException('Manual discount requires manager/admin approval placeholder.');
            }

            $value = round((float) $row['value'], 2);
            $amount = $valueType === 'percent' ? round($grossSales * ($value / 100), 2) : $value;
            $amount = min($amount, max(0, $grossSales - $total));

            $rows[] = [
                'discount_type' => $type,
                'value_type' => $valueType,
                'value' => $value,
                'amount' => $amount,
                'reason' => $row['reason'] ?? null,
                'reference_number' => $row['reference_number'] ?? null,
                'approved_by_user_id' => $row['approved_by_user_id'] ?? null,
                'metadata' => ['phase' => 4, 'placeholder' => in_array($type, ['senior', 'pwd', 'solo_parent'], true)],
            ];

            $total = round($total + $amount, 2);
        }

        return ['rows' => $rows, 'total' => $total];
    }
}
