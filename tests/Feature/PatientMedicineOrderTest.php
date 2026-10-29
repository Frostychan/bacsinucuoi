<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\MedicineOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientMedicineOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_open_checkout_for_medicines_prescribed_to_them(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $appointment->medicalRecord->medications()->create([
            'medicine_name' => 'Paracetamol',
            'quantity' => 8,
        ]);

        $this->actingAs($patient)
            ->get(route('patient.appointments.show', $appointment))
            ->assertSee(route('patient.medicine-orders.checkout', $appointment), false)
            ->assertSee('Mua thuốc theo đơn');

        $this->actingAs($patient)
            ->get(route('patient.medicine-orders.checkout', $appointment))
            ->assertSee('Paracetamol')
            ->assertSee('Ibuprofen')
            ->assertSee('name="items[0][medicine_name]"', false)
            ->assertSee('type="checkbox"', false)
            ->assertSee('value="8"', false)
            ->assertSee('Bác sĩ kê: 8')
            ->assertSee('Sau khi gửi yêu cầu, bạn có thể xem giá và thanh toán trên trang thanh toán.');
    }

    public function test_patient_can_buy_medicines_saved_as_structured_prescription_items(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $appointment->medicalRecord->update(['prescription' => 'Take after meals.']);
        $appointment->medicalRecord->medications()->create([
            'medicine_name' => 'Paracetamol',
            'quantity' => 4,
        ]);

        $this->actingAs($patient)
            ->get(route('patient.appointments.show', $appointment))
            ->assertSee('Paracetamol')
            ->assertSee('Paracetamol — 4')
            ->assertSee(route('patient.medicine-orders.checkout', $appointment), false);

        $this->actingAs($patient)
            ->get(route('patient.medicine-orders.checkout', $appointment))
            ->assertSee('Paracetamol')
            ->assertSee('value="4"', false)
            ->assertSee('Bác sĩ kê: 4');
    }

    public function test_patient_can_submit_a_purchase_request_for_selected_prescribed_medicines(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();

        $response = $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'quantity' => 2, 'selected' => '1'],
                ['medicine_name' => 'Ibuprofen', 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect(route('patient.medicine-orders.checkout', $appointment));
        $response->assertSessionHas('success');
        $order = MedicineOrder::query()->where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertModelExists($order);
        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'patient_id' => $patient->id,
            'status' => 'requested',
        ]);
        $this->assertDatabaseHas('medicine_order_items', [
            'medicine_order_id' => $order->id,
            'medicine_name' => 'Paracetamol',
            'quantity' => 2,
        ]);
        $this->assertDatabaseMissing('medicine_order_items', [
            'medicine_order_id' => $order->id,
            'medicine_name' => 'Ibuprofen',
        ]);

        $this->actingAs($patient)
            ->get(route('patient.medicine-orders.checkout', $appointment))
            ->assertSee('Đã nhận yêu cầu mua thuốc')
            ->assertSee('Paracetamol')
            ->assertSee('× 2')
            ->assertDontSee('Ibuprofen');
    }

    public function test_patient_cannot_request_medicines_not_in_the_prescription(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();

        $response = $this->actingAs($patient)
            ->from(route('patient.medicine-orders.checkout', $appointment))
            ->post(route('patient.medicine-orders.store', $appointment), [
                'items' => [
                    ['medicine_name' => 'Paracetamol', 'quantity' => 1, 'selected' => '1'],
                    ['medicine_name' => 'Aspirin', 'quantity' => 1, 'selected' => '1'],
                ],
            ]);

        $response->assertSessionHasErrors('items.1.medicine_name');
        $this->assertDatabaseMissing('medicine_orders', ['appointment_id' => $appointment->id]);
        $this->assertDatabaseMissing('medicine_order_items', ['medicine_name' => 'Aspirin']);
    }

    public function test_patient_must_select_at_least_one_prescribed_medicine(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();

        $response = $this->actingAs($patient)
            ->from(route('patient.medicine-orders.checkout', $appointment))
            ->post(route('patient.medicine-orders.store', $appointment), [
                'items' => [
                    ['medicine_name' => 'Paracetamol', 'quantity' => 1],
                    ['medicine_name' => 'Ibuprofen', 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('medicine_orders', ['appointment_id' => $appointment->id]);
    }

    public function test_patient_cannot_request_more_than_one_hundred_units_of_a_medicine(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();

        $response = $this->actingAs($patient)
            ->from(route('patient.medicine-orders.checkout', $appointment))
            ->post(route('patient.medicine-orders.store', $appointment), [
                'items' => [
                    ['medicine_name' => 'Paracetamol', 'quantity' => 101, 'selected' => '1'],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.quantity');
        $this->assertDatabaseMissing('medicine_orders', ['appointment_id' => $appointment->id]);
    }

    public function test_another_patient_cannot_open_medicine_checkout(): void
    {
        [, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $otherPatient = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherPatient)
            ->get(route('patient.medicine-orders.checkout', $appointment))
            ->assertForbidden();
    }

    public function test_doctor_cannot_open_patient_medicine_checkout(): void
    {
        [, $appointment] = $this->createCompletedAppointmentWithPrescription();

        $this->actingAs($appointment->doctor)
            ->get(route('patient.medicine-orders.checkout', $appointment))
            ->assertForbidden();
    }

    public function test_patient_cannot_simulate_payment_until_every_ordered_medicine_has_a_price(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $paracetamol = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();
        $ibuprofen = Medicine::query()->where('name', 'Ibuprofen')->firstOrFail();
        $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'quantity' => 2, 'selected' => '1'],
                ['medicine_name' => 'Ibuprofen', 'quantity' => 1, 'selected' => '1'],
            ],
        ]);
        $order = MedicineOrder::query()->where('appointment_id', $appointment->id)->firstOrFail();
        $paracetamol->update(['price' => '15.00']);

        $this->actingAs($patient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertSee('Thanh toán lịch khám')
            ->assertSee('Chưa có giá thuốc')
            ->assertSee('chưa có giá chính thức')
            ->assertSee('Ibuprofen')
            ->assertDontSee('Mô phỏng thanh toán (không trừ tiền)');

        $this->actingAs($patient)
            ->post(route('patient.medicine-orders.payment.simulate', $appointment), [
                'payment_method' => 'vnpay',
            ])
            ->assertRedirect(route('patient.medicine-orders.payment', $appointment))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'status' => 'requested',
        ]);
        $this->assertDatabaseHas('medicine_order_items', [
            'medicine_order_id' => $order->id,
            'medicine_id' => $ibuprofen->id,
            'unit_price' => null,
        ]);
        $this->assertDatabaseHas('medicine_order_items', [
            'medicine_order_id' => $order->id,
            'medicine_id' => $paracetamol->id,
            'unit_price' => null,
        ]);
    }

    public function test_patient_can_simulate_payment_at_database_prices_without_marking_the_order_paid(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $medicine = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();
        $medicine->update(['price' => '12.50', 'unit' => 'viên']);

        $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'quantity' => 2, 'selected' => '1'],
            ],
        ]);
        $order = MedicineOrder::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->actingAs($patient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertSee('25 ₫')
            ->assertSee('12,50 ₫ / đơn vị')
            ->assertDontSee('12,5 ₫ / đơn vị')
            ->assertSee('Chỉ thử luồng mô phỏng (không chuyển tiền)');

        $medicine->update(['price' => '20.00']);

        $this->actingAs($patient)
            ->post(route('patient.medicine-orders.payment.simulate', $appointment), [
                'payment_method' => 'vnpay',
            ])
            ->assertSee('Đã hoàn tất mô phỏng')
            ->assertSee('Không có giao dịch thật nào được gửi')
            ->assertSee('VNPay (mô phỏng)')
            ->assertSee('25 ₫');

        $this->assertDatabaseHas('medicine_order_items', [
            'medicine_order_id' => $order->id,
            'medicine_id' => $medicine->id,
            'unit_price' => '12.50',
            'unit' => 'viên',
        ]);
        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'status' => 'requested',
        ]);
    }

    public function test_patient_sees_when_the_order_total_uses_an_estimated_price(): void
    {
        config([
            'services.sepay.bank_account' => '9876543210',
            'services.sepay.bank_code' => 'MB',
            'services.sepay.webhook_secret' => 'test-sepay-webhook-secret',
        ]);
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $medicine = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();
        $medicine->update([
            'price' => '650.00',
            'unit' => 'viên',
            'price_is_estimate' => true,
            'price_source' => 'https://pharmacy.example/paracetamol',
        ]);
        $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'quantity' => 2, 'selected' => '1'],
            ],
        ]);
        $order = MedicineOrder::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->actingAs($patient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertSee('1.300 ₫')
            ->assertSee('Giá tham khảo, cần xác nhận trước khi mua.')
            ->assertSee('Một hoặc nhiều giá trong đơn là giá tham khảo')
            ->assertDontSee('vietqr.app/img');

        $this->assertDatabaseHas('medicine_order_items', [
            'medicine_order_id' => $order->id,
            'unit_price' => '650.00',
            'unit_price_is_estimate' => true,
            'unit_price_source' => 'https://pharmacy.example/paracetamol',
        ]);
    }

    public function test_patient_cannot_open_another_patients_medicine_payment_page(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'quantity' => 1, 'selected' => '1'],
            ],
        ]);
        $otherPatient = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherPatient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertForbidden();
    }

    public function test_patient_cannot_open_payment_page_before_submitting_a_medicine_order(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();

        $this->actingAs($patient)
            ->get(route('patient.medicine-orders.payment', $appointment))
            ->assertNotFound();
    }

    public function test_patient_cannot_submit_an_unknown_simulated_payment_method(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'quantity' => 1, 'selected' => '1'],
            ],
        ]);
        $order = MedicineOrder::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $response = $this->actingAs($patient)
            ->from(route('patient.medicine-orders.payment', $appointment))
            ->post(route('patient.medicine-orders.payment.simulate', $appointment), [
                'payment_method' => 'unknown-gateway',
            ]);

        $response->assertSessionHasErrors([
            'payment_method' => 'Vui lòng chọn phương thức thanh toán mô phỏng hợp lệ.',
        ]);
        $this->assertDatabaseHas('medicine_orders', [
            'id' => $order->id,
            'status' => 'requested',
        ]);
    }

    public function test_patient_cannot_submit_duplicate_medicine_purchase_requests(): void
    {
        [$patient, $appointment] = $this->createCompletedAppointmentWithPrescription();
        $payload = [
            'items' => [
                ['medicine_name' => 'Paracetamol', 'quantity' => 1, 'selected' => '1'],
            ],
        ];

        $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), $payload)
            ->assertRedirect(route('patient.medicine-orders.checkout', $appointment));
        $this->actingAs($patient)->post(route('patient.medicine-orders.store', $appointment), $payload)
            ->assertRedirect(route('patient.medicine-orders.checkout', $appointment));

        $this->assertDatabaseCount('medicine_orders', 1);
        $this->assertDatabaseCount('medicine_order_items', 1);
    }

    /**
     * @return array{0: User, 1: Appointment}
     */
    private function createCompletedAppointmentWithPrescription(): array
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
            'prescription' => "Paracetamol\nIbuprofen\nTake after meals.",
        ]);

        return [$patient, $appointment];
    }
}
