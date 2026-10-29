<?php

namespace App\Support;

use App\Models\MedicineOrder;

class MedicineOrderPricing
{
    public static function totalInWholeVnd(MedicineOrder $order): ?int
    {
        $totalCents = 0;

        foreach ($order->items as $item) {
            if ($item->unit_price === null || $item->unit_price_is_estimate) {
                return null;
            }

            [$whole, $fraction] = array_pad(explode('.', $item->unit_price, 2), 2, '0');
            $totalCents += (((int) $whole * 100) + (int) str_pad($fraction, 2, '0')) * $item->quantity;
        }

        if ($totalCents === 0 || $totalCents % 100 !== 0) {
            return null;
        }

        return intdiv($totalCents, 100);
    }
}
