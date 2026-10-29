<?php

namespace App\Support;

use App\Models\Appointment;

class AppointmentPaymentPricing
{
    public static function totalInWholeVnd(Appointment $appointment): ?int
    {
        if ($appointment->consultation_fee === null) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $appointment->consultation_fee, 2), 2, '0');

        if (trim($fraction, '0') !== '') {
            return null;
        }

        $amount = (int) $whole;

        return $amount > 0 ? $amount : null;
    }
}
