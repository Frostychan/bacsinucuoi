<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\MedicinePayment;
use App\Support\AppointmentPaymentPricing;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AppointmentPaymentProcessor
{
    /**
     * @param  array{id: int|string, accountNumber: string, transferType: string, transferAmount: int}  $data
     */
    public function process(int $appointmentId, array $data, bool $isSimulated = false): string
    {
        $transactionId = (string) $data['id'];

        if (MedicinePayment::query()->where('provider_transaction_id', $transactionId)->exists()) {
            return 'duplicate';
        }

        if ($data['transferType'] !== 'in') {
            return 'ignored';
        }

        try {
            return DB::transaction(function () use ($appointmentId, $data, $transactionId, $isSimulated): string {
                $appointment = Appointment::query()->whereKey($appointmentId)->lockForUpdate()->first();
                $status = 'unmatched_reference';

                if ($appointment !== null) {
                    $expectedAmount = AppointmentPaymentPricing::totalInWholeVnd($appointment);

                    if ($expectedAmount === null) {
                        $status = 'price_not_confirmed';
                    } elseif ($appointment->status === 'cancelled') {
                        $status = 'appointment_cancelled';
                    } elseif ((int) $data['transferAmount'] !== $expectedAmount) {
                        $status = 'amount_mismatch';
                    } elseif ($appointment->payment_status === 'paid') {
                        $status = 'already_paid';
                    } else {
                        $status = $isSimulated ? 'simulated_paid' : 'paid';
                        $appointment->update([
                            'payment_status' => $status,
                            'paid_at' => now(),
                        ]);
                    }
                }

                MedicinePayment::create([
                    'appointment_id' => $appointment?->id,
                    'provider_transaction_id' => $transactionId,
                    'reference' => 'APPT-'.$appointmentId,
                    'transfer_amount' => (int) $data['transferAmount'],
                    'receiving_account' => $data['accountNumber'],
                    'status' => $status,
                    'is_simulated' => $isSimulated,
                    'received_at' => now(),
                ]);

                return $status;
            });
        } catch (QueryException $exception) {
            if (MedicinePayment::query()->where('provider_transaction_id', $transactionId)->exists()) {
                return 'duplicate';
            }

            throw $exception;
        }
    }
}
