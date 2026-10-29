<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\MedicineOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SepayMedicinePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_incoming_transfer_marks_the_matching_order_paid(): void
    {
        $this->configureSePay();
        [$appointment, $order] = $this->createMedicineOrder();
        $payload = $this->incomingTransfer($order->id, 3000);

        $this->postSignedWebhook($payload)->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('medicine_payments', [
            'medicine_order_id' => $order->id,
            'provider_transaction_id' => '987654',
            'transfer_amount' => 3000,
            'status' => 'paid',
        ]);

        $this->actingAs($appointment->patient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertSee('Đã nhận thanh toán');
    }

    public function test_invalid_signature_does_not_create_a_payment_or_mark_the_order_paid(): void
    {
        $this->configureSePay();
        [, $order] = $this->createMedicineOrder();
        $payload = $this->incomingTransfer($order->id, 3000);

        $this->postSignedWebhook($payload, 'sha256=invalid')
            ->assertUnauthorized();

        $this->assertDatabaseCount('medicine_payments', 0);
        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_old_signed_webhook_is_rejected(): void
    {
        $this->configureSePay();
        [, $order] = $this->createMedicineOrder();
        $payload = $this->incomingTransfer($order->id, 3000);

        $this->postSignedWebhook($payload, null, now()->subSeconds(301)->timestamp)
            ->assertUnauthorized();

        $this->assertDatabaseCount('medicine_payments', 0);
    }

    public function test_amount_mismatch_is_recorded_but_does_not_mark_order_paid(): void
    {
        $this->configureSePay();
        [, $order] = $this->createMedicineOrder();
        $payload = $this->incomingTransfer($order->id, 2999);

        $this->postSignedWebhook($payload)->assertOk();

        $this->assertDatabaseHas('medicine_payments', [
            'medicine_order_id' => $order->id,
            'provider_transaction_id' => '987654',
            'status' => 'amount_mismatch',
        ]);
        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_duplicate_sepay_transaction_is_processed_only_once(): void
    {
        $this->configureSePay();
        [, $order] = $this->createMedicineOrder();
        $payload = $this->incomingTransfer($order->id, 3000);

        $this->postSignedWebhook($payload)->assertOk();
        $this->postSignedWebhook($payload)->assertOk();

        $this->assertDatabaseCount('medicine_payments', 1);
        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_outgoing_transfer_is_acknowledged_without_confirming_payment(): void
    {
        $this->configureSePay();
        [, $order] = $this->createMedicineOrder();
        $payload = $this->incomingTransfer($order->id, 3000);
        $payload['transferType'] = 'out';

        $this->postSignedWebhook($payload)->assertOk();

        $this->assertDatabaseCount('medicine_payments', 0);
        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_estimated_price_cannot_be_confirmed_by_a_real_payment_webhook(): void
    {
        $this->configureSePay();
        [, $order] = $this->createMedicineOrder();
        $order->items()->where('medicine_name', 'Paracetamol')->update([
            'unit_price_is_estimate' => true,
        ]);

        $this->postSignedWebhook($this->incomingTransfer($order->id, 3000))->assertOk();

        $this->assertDatabaseHas('medicine_payments', [
            'medicine_order_id' => $order->id,
            'status' => 'price_not_confirmed',
        ]);
        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_patient_sees_real_qr_only_when_sepay_is_configured_and_prices_are_confirmed(): void
    {
        $this->configureSePay();
        [$appointment] = $this->createMedicineOrder();

        $this->actingAs($appointment->patient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertSee('Chuyển khoản ngân hàng qua SePay')
            ->assertSee('MEDCARE-')
            ->assertSee('9876543210')
            ->assertSee('vietqr.app/img');
    }

    public function test_patient_can_complete_a_virtual_sepay_transfer_without_recording_real_payment(): void
    {
        [$appointment, $order] = $this->createMedicineOrder();

        $this->actingAs($appointment->patient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertSee('SePay ảo — chỉ môi trường thử nghiệm')
            ->assertSee('Thử giao dịch SePay đúng số tiền');

        $this->actingAs($appointment->patient)
            ->post(route('patient.medicine-orders.payment.sepay-sandbox', $appointment), [
                'scenario' => 'paid',
            ])
            ->assertRedirect(route('patient.appointments.payment', $appointment))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'simulated_paid',
        ]);
        $this->assertDatabaseHas('medicine_payments', [
            'medicine_order_id' => $order->id,
            'status' => 'simulated_paid',
            'is_simulated' => true,
            'receiving_account' => 'TEST-ACCOUNT',
        ]);

        $this->actingAs($appointment->patient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertSee('Đây là giao dịch ảo')
            ->assertSee('đơn chưa được xác nhận thanh toán thật');
    }

    public function test_virtual_wrong_amount_is_recorded_without_marking_the_order_paid(): void
    {
        [$appointment, $order] = $this->createMedicineOrder();

        $this->actingAs($appointment->patient)
            ->post(route('patient.medicine-orders.payment.sepay-sandbox', $appointment), [
                'scenario' => 'wrong_amount',
            ])
            ->assertRedirect(route('patient.appointments.payment', $appointment))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('medicine_payments', [
            'medicine_order_id' => $order->id,
            'status' => 'amount_mismatch',
            'is_simulated' => true,
        ]);
    }

    public function test_virtual_sepay_is_not_available_in_production(): void
    {
        [$appointment, $order] = $this->createMedicineOrder();
        $this->app['env'] = 'production';

        $this->withSession(['_token' => 'sandbox-test-csrf'])
            ->actingAs($appointment->patient)
            ->post(route('patient.medicine-orders.payment.sepay-sandbox', $appointment), [
                '_token' => 'sandbox-test-csrf',
                'scenario' => 'paid',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseCount('medicine_payments', 0);
    }

    public function test_another_patient_cannot_use_virtual_sepay_for_an_order(): void
    {
        [$appointment, $order] = $this->createMedicineOrder();
        $otherPatient = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherPatient)
            ->post(route('patient.medicine-orders.payment.sepay-sandbox', $appointment), [
                'scenario' => 'paid',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseCount('medicine_payments', 0);
    }

    /**
     * @return array{0: Appointment, 1: MedicineOrder}
     */
    private function createMedicineOrder(): array
    {
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->subDay(),
            'status' => 'completed',
            'reason' => 'Consultation',
        ]);
        MedicalRecord::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Test diagnosis',
            'prescription' => "Paracetamol\nIbuprofen",
        ]);
        Medicine::query()->whereIn('name', ['Paracetamol', 'Ibuprofen'])->update([
            'price' => '1000.00',
            'unit' => 'viên',
            'price_is_estimate' => false,
        ]);

        $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'quantity' => 2, 'selected' => '1'],
                ['medicine_name' => 'Ibuprofen', 'quantity' => 1, 'selected' => '1'],
            ],
        ])->assertRedirect(route('patient.medicine-orders.checkout', $appointment));

        return [$appointment, MedicineOrder::query()->where('appointment_id', $appointment->id)->firstOrFail()];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function incomingTransfer(int $orderId, int $amount): array
    {
        return [
            'id' => 987654,
            'accountNumber' => '9876543210',
            'transferType' => 'in',
            'transferAmount' => $amount,
            'content' => 'MEDCARE-'.$orderId,
            'code' => null,
            'referenceCode' => 'BANKREF123',
        ];
    }

    private function postSignedWebhook(array $payload, ?string $signatureOverride = null, ?int $timestamp = null)
    {
        $timestamp ??= now()->timestamp;
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = $signatureOverride
            ?? 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, 'test-sepay-webhook-secret');

        return $this->call('POST', '/api/webhooks/sepay', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SEPAY_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SEPAY_SIGNATURE' => $signature,
        ], $rawBody);
    }

    private function configureSePay(): void
    {
        config([
            'services.sepay.bank_account' => '9876543210',
            'services.sepay.bank_code' => 'MB',
            'services.sepay.webhook_secret' => 'test-sepay-webhook-secret',
            'services.sepay.signature_tolerance_seconds' => 300,
        ]);
    }
}
