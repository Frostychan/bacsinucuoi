<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorMedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_open_completed_appointment_medical_record_form(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'completed');

        $response = $this->actingAs($doctor)->get(route('doctor.appointments.show', $appointment));

        $response->assertOk();
        $response->assertSee(route('doctor.medical-records.create', $appointment), false);
        $this->actingAs($doctor)->get(route('doctor.medical-records.create', $appointment))
            ->assertOk()
            ->assertSee(route('doctor.medical-records.store', $appointment), false);
    }

    public function test_doctor_can_choose_from_twenty_medicines_when_creating_a_medical_record(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'completed');

        $response = $this->actingAs($doctor)->get(route('doctor.medical-records.create', $appointment));

        $response->assertSee('id="medicine-search"', false)
            ->assertSee('id="selected-medications"', false)
            ->assertSee('name="prescription"', false)
            ->assertSee('Số lượng', false);
        $this->assertSame(20, substr_count($response->getContent(), 'data-medication-option data-medication='));
    }

    public function test_doctor_can_save_a_medical_record_for_a_completed_appointment(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'completed');

        $response = $this->actingAs($doctor)->post(route('doctor.medical-records.store', $appointment), [
            'diagnosis' => 'Seasonal allergy',
            'prescription' => 'Take after meals.',
            'medications' => [
                ['name' => 'Paracetamol', 'quantity' => 12],
                ['name' => 'Ibuprofen', 'quantity' => 6],
            ],
            'notes' => 'Follow up in one week.',
        ]);

        $response->assertRedirect(route('doctor.appointments.show', $appointment));
        $this->assertDatabaseHas('medical_records', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Seasonal allergy',
            'prescription' => 'Take after meals.',
            'notes' => 'Follow up in one week.',
        ]);
        $medicalRecord = MedicalRecord::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertDatabaseHas('medical_record_medications', [
            'medical_record_id' => $medicalRecord->id,
            'medicine_id' => Medicine::query()->where('name', 'Paracetamol')->value('id'),
            'medicine_name' => 'Paracetamol',
            'quantity' => 12,
        ]);
        $this->assertDatabaseHas('medical_record_medications', [
            'medical_record_id' => $medicalRecord->id,
            'medicine_name' => 'Ibuprofen',
            'quantity' => 6,
        ]);
        $this->assertModelExists(MedicalRecord::where('appointment_id', $appointment->id)->firstOrFail());
    }

    public function test_doctor_cannot_save_a_prescription_quantity_above_one_hundred(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'completed');

        $response = $this->actingAs($doctor)
            ->from(route('doctor.medical-records.create', $appointment))
            ->post(route('doctor.medical-records.store', $appointment), [
                'diagnosis' => 'Seasonal allergy',
                'medications' => [
                    ['name' => 'Paracetamol', 'quantity' => 101],
                ],
            ]);

        $response->assertSessionHasErrors('medications.0.quantity');
        $this->assertDatabaseMissing('medical_records', ['appointment_id' => $appointment->id]);
        $this->assertDatabaseMissing('medical_record_medications', ['medicine_name' => 'Paracetamol']);
    }

    public function test_patient_can_view_the_doctors_note_for_their_completed_appointment(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'completed');
        MedicalRecord::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Seasonal allergy',
            'prescription' => 'Antihistamine',
            'notes' => 'Please return if symptoms continue.',
        ]);

        $response = $this->actingAs($appointment->patient)->get(route('patient.appointments.show', $appointment));

        $response->assertSee('Chẩn đoán');
        $response->assertSee('Seasonal allergy');
        $response->assertSee('Đơn thuốc');
        $response->assertSee('Antihistamine');
        $response->assertSee('Ghi chú dành cho bệnh nhân');
        $response->assertSee('Please return if symptoms continue.');
    }

    public function test_patient_can_view_doctor_notes_saved_from_the_appointment_processing_page(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'confirmed');
        $appointment->update(['notes' => 'Patient booking note.']);

        $this->actingAs($doctor)
            ->from(route('doctor.appointments.show', $appointment))
            ->patch(route('doctor.appointments.status', $appointment), [
                'status' => 'confirmed',
                'doctor_notes' => 'Please bring your previous test results.',
            ])
            ->assertRedirect(route('doctor.appointments.show', $appointment));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_notes' => 'Please bring your previous test results.',
            'notes' => 'Patient booking note.',
        ]);

        $this->actingAs($appointment->patient)
            ->get(route('patient.appointments.show', $appointment))
            ->assertSee('Ghi chú dành cho bệnh nhân')
            ->assertSee('Please bring your previous test results.');
    }

    public function test_another_doctor_cannot_save_patient_notes_to_the_appointment(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $otherDoctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'confirmed');

        $response = $this->actingAs($otherDoctor)
            ->patch(route('doctor.appointments.status', $appointment), [
                'status' => 'confirmed',
                'doctor_notes' => 'Unauthorized note.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_notes' => null,
        ]);
    }

    public function test_patient_cannot_view_another_patients_doctor_note(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'completed');
        $otherPatient = User::factory()->create(['role' => 'customer']);
        MedicalRecord::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Seasonal allergy',
            'notes' => 'Private medical note.',
        ]);

        $response = $this->actingAs($otherPatient)->get(route('patient.appointments.show', $appointment));

        $response->assertForbidden();
    }

    public function test_doctor_cannot_save_a_medical_record_before_appointment_is_completed(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($doctor, 'confirmed');

        $response = $this->actingAs($doctor)->post(route('doctor.medical-records.store', $appointment), [
            'diagnosis' => 'Seasonal allergy',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('medical_records', ['appointment_id' => $appointment->id]);
    }

    private function createAppointment(User $doctor, string $status): Appointment
    {
        $patient = User::factory()->create(['role' => 'customer']);

        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDay(),
            'status' => $status,
            'reason' => 'Consultation',
        ]);
    }
}
