<?php

namespace App\Services;

use App\Models\MedicineOrder;
use App\Models\MedicinePayment;
use App\Support\MedicineOrderPricing;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SepayPaymentProcessor
{
    /**
     * @param  array{id: int|string, accountNumber: string, transferType: string, transferAmount: int, content?: ?string, code?: ?string, referenceCode?: ?string}  $data
     */
    public function process(array $data, bool $isSimulated = false): string
    {
        $transactionId = (string) $data['id'];
        $transactionExists = MedicinePayment::query()
            ->where('provider_transaction_id', $transactionId)
            ->exists();

        if ($transactionExists) {
            return 'duplicate';
        }

        if ($data['transferType'] !== 'in') {
            return 'ignored';
        }

        $referenceText = implode(' ', array_filter([
            $data['content'] ?? null,
            $data['code'] ?? null,
            $data['referenceCode'] ?? null,
        ], fn (?string $value): bool => $value !== null && $value !== ''));
        preg_match('/(?<![A-Z0-9])MEDCARE-(\d+)(?!\d)/i', $referenceText, $matches);
        $orderId = isset($matches[1]) ? (int) $matches[1] : null;

        try {
            return DB::transaction(function () use ($data, $orderId, $transactionId, $isSimulated): string {
                $order = $orderId === null
                    ? null
                    : MedicineOrder::query()
                        ->whereKey($orderId)
                        ->with('items')
                        ->lockForUpdate()
                        ->first();

                $status = 'unmatched_reference';

                if ($order !== null) {
                    $expectedAmount = MedicineOrderPricing::totalInWholeVnd($order);

                    if ($expectedAmount === null) {
                        $status = 'price_not_confirmed';
                    } elseif ((int) $data['transferAmount'] !== $expectedAmount) {
                        $status = 'amount_mismatch';
                    } elseif ($order->payment_status === 'paid') {
                        $status = 'already_paid';
                    } else {
                        $status = $isSimulated ? 'simulated_paid' : 'paid';
                        $order->update([
                            'payment_status' => $status,
                            'paid_at' => now(),
                        ]);
                    }
                }

                MedicinePayment::create([
                    'medicine_order_id' => $order?->id,
                    'provider_transaction_id' => $transactionId,
                    'reference' => $orderId === null ? null : 'MEDCARE-'.$orderId,
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
