<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_doctor_can_view_patient_contact_details_from_appointment(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create([
            'role' => 'customer',
            'phone' => '0901234567',
            'date_of_birth' => '1990-05-20',
            'gender' => 'female',
            'address' => '12 Nguyen Trai',
            'emergency_contact_name' => 'Nguyen Van An',
            'emergency_contact_phone' => '0912345678',
            'emergency_contact_relationship' => 'Brother',
        ]);
        $appointment = $this->createAppointment($doctor, $patient);

        $response = $this->actingAs($doctor)->get(route('doctor.appointments.show', $appointment));

        $response->assertOk();
        $response->assertSee('0901234567');
        $response->assertSee('20/05/1990');
        $response->assertSee('Nữ');
        $response->assertSee('12 Nguyen Trai');
        $response->assertSee('Nguyen Van An');
        $response->assertSee('0912345678');
        $response->assertSee('Brother');
    }

    public function test_doctor_cannot_view_patient_information_for_another_doctors_appointment(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $otherDoctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create([
            'role' => 'customer',
            'phone' => '0901234567',
            'emergency_contact_name' => 'Private Contact',
        ]);
        $appointment = $this->createAppointment($otherDoctor, $patient);

        $response = $this->actingAs($doctor)->get(route('doctor.appointments.show', $appointment));

        $response->assertForbidden();
        $response->assertDontSee('0901234567');
        $response->assertDontSee('Private Contact');
    }

    private function createAppointment(User $doctor, User $patient): Appointment
    {
        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDay(),
            'status' => 'confirmed',
            'reason' => 'Consultation',
        ]);
    }
}
