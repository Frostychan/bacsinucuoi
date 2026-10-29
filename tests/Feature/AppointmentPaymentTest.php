<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_sees_booking_fee_and_virtual_qr_on_payment_page(): void
    {
        [$patient, $appointment] = $this->createAppointment();

        $this->actingAs($patient)
            ->get(route('patient.appointments.payment', $appointment))
            ->assertSee('Phí đặt lịch bác sĩ')
            ->assertSee('300.000 ₫')
            ->assertSee('Mã QR thanh toán ảo')
            ->assertSee('không thể dùng để thanh toán')
            ->assertSee('Mô phỏng thanh toán phí đặt lịch');
    }

    public function test_patient_sees_real_booking_qr_when_sepay_is_configured(): void
    {
        config([
            'services.sepay.bank_account' => '9876543210',
            'services.sepay.bank_code' => 'MB',
            'services.sepay.webhook_secret' => 'test-sepay-webhook-secret',
        ]);
        [$patient, $appointment] = $this->createAppointment();

        $this->actingAs($patient)
            ->get(route('patient.appointments.payment', $appointment))
            ->assertSee('APPT-'.$appointment->id)
            ->assertSee('vietqr.app/img');
    }

    public function test_patient_can_simulate_payment_of_booking_fee(): void
    {
        [$patient, $appointment] = $this->createAppointment();

        $this->actingAs($patient)
            ->post(route('patient.appointments.payment.sepay-sandbox', $appointment), [
                'bill' => 'appointment',
                'scenario' => 'paid',
            ])
            ->assertRedirect(route('patient.appointments.payment', $appointment))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'payment_status' => 'simulated_paid',
        ]);
        $this->assertDatabaseHas('medicine_payments', [
            'appointment_id' => $appointment->id,
            'reference' => 'APPT-'.$appointment->id,
            'transfer_amount' => 300000,
            'status' => 'simulated_paid',
            'is_simulated' => true,
        ]);
    }

    public function test_wrong_booking_fee_amount_does_not_mark_appointment_paid(): void
    {
        [$patient, $appointment] = $this->createAppointment();

        $this->actingAs($patient)
            ->post(route('patient.appointments.payment.sepay-sandbox', $appointment), [
                'bill' => 'appointment',
                'scenario' => 'wrong_amount',
            ])
            ->assertRedirect(route('patient.appointments.payment', $appointment))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('medicine_payments', [
            'appointment_id' => $appointment->id,
            'status' => 'amount_mismatch',
            'is_simulated' => true,
        ]);
    }

    public function test_sepay_webhook_marks_matching_booking_fee_paid(): void
    {
        config([
            'services.sepay.bank_account' => '9876543210',
            'services.sepay.bank_code' => 'MB',
            'services.sepay.webhook_secret' => 'test-sepay-webhook-secret',
            'services.sepay.signature_tolerance_seconds' => 300,
        ]);
        [, $appointment] = $this->createAppointment();
        $payload = [
            'id' => 123456,
            'accountNumber' => '9876543210',
            'transferType' => 'in',
            'transferAmount' => 300000,
            'content' => 'APPT-'.$appointment->id,
            'code' => null,
            'referenceCode' => 'BANKREF123',
        ];
        $timestamp = now()->timestamp;
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, 'test-sepay-webhook-secret');

        $response = $this->call('POST', '/api/webhooks/sepay', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SEPAY_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SEPAY_SIGNATURE' => $signature,
        ], $body);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('medicine_payments', [
            'appointment_id' => $appointment->id,
            'provider_transaction_id' => '123456',
            'status' => 'paid',
            'is_simulated' => false,
        ]);
    }

    public function test_another_patient_cannot_view_or_pay_booking_fee(): void
    {
        [, $appointment] = $this->createAppointment();
        $otherPatient = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherPatient)
            ->get(route('patient.appointments.payment', $appointment))
            ->assertForbidden();

        $this->actingAs($otherPatient)
            ->post(route('patient.appointments.payment.sepay-sandbox', $appointment), [
                'bill' => 'appointment',
                'scenario' => 'paid',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'payment_status' => 'pending',
        ]);
    }

    /**
     * @return array{0: User, 1: Appointment}
     */
    private function createAppointment(): array
    {
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDay(),
            'consultation_fee' => 300000,
            'status' => 'pending',
            'reason' => 'Consultation',
        ]);

        return [$patient, $appointment];
    }
}
