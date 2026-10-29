<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_links_directly_to_processing_for_each_upcoming_appointment(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create(['role' => 'customer', 'name' => 'Nguyen Van An']);
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDay(),
            'status' => 'confirmed',
            'reason' => 'General checkup',
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.dashboard'));

        $response->assertOk();
        $response->assertSee('Nguyen Van An');
        $response->assertSee('Xử lý');
        $response->assertSee(route('doctor.appointments.show', $appointment), false);
    }

    public function test_newest_bookings_appear_first_on_doctor_dashboard_and_appointment_list(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $olderPatient = User::factory()->create(['role' => 'customer', 'name' => 'Older booking patient']);
        $newerPatient = User::factory()->create(['role' => 'customer', 'name' => 'Recent booking patient']);
        $olderAppointment = Appointment::create([
            'patient_id' => $olderPatient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDays(5),
            'status' => 'pending',
            'reason' => 'Older booking',
        ]);
        $newerAppointment = Appointment::create([
            'patient_id' => $newerPatient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDays(10),
            'status' => 'pending',
            'reason' => 'Recent booking',
        ]);
        $olderAppointment->forceFill(['created_at' => now()->subDays(2)])->save();
        $newerAppointment->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs($doctor)
            ->get(route('doctor.dashboard'))
            ->assertSeeTextInOrder(['Recent booking patient', 'Older booking patient']);

        $this->actingAs($doctor)
            ->get(route('doctor.appointments.index'))
            ->assertSeeTextInOrder(['Recent booking patient', 'Older booking patient']);
    }

    public function test_appointment_processing_offers_three_status_buttons_with_yellow_confirmation(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create(['role' => 'customer']);
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDay(),
            'status' => 'pending',
            'reason' => 'General checkup',
        ]);

        $this->actingAs($doctor)
            ->get(route('doctor.appointments.show', $appointment))
            ->assertSee('name="status" value="confirmed"', false)
            ->assertSee('btn-status-confirmed')
            ->assertSee('Xác nhận')
            ->assertSee('name="status" value="completed"', false)
            ->assertSee('Hoàn tất')
            ->assertSee('name="status" value="cancelled"', false)
            ->assertSee('Hủy lịch');

        $this->actingAs($doctor)
            ->patch(route('doctor.appointments.status', $appointment), [
                'doctor_notes' => 'Please bring previous test results.',
            ])
            ->assertRedirect(route('doctor.appointments.show', $appointment));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'pending',
            'doctor_notes' => 'Please bring previous test results.',
        ]);

        $this->actingAs($doctor)
            ->patch(route('doctor.appointments.status', $appointment), [
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('doctor.appointments.show', $appointment));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }
}
