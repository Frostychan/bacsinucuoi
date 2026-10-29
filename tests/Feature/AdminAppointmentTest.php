<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_pending_appointment_count_and_quick_review_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doctor = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Dashboard']);
        $pendingPatient = User::factory()->create(['role' => 'customer', 'name' => 'Pending Dashboard Patient']);
        $confirmedPatient = User::factory()->create(['role' => 'customer', 'name' => 'Confirmed Dashboard Patient']);
        $pendingAppointment = Appointment::create([
            'patient_id' => $pendingPatient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDays(2)->setTime(9, 30),
            'status' => 'pending',
            'reason' => 'Headache consultation',
        ]);
        Appointment::create([
            'patient_id' => $confirmedPatient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDays(3)->setTime(11, 0),
            'status' => 'confirmed',
            'reason' => 'Already confirmed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('1 chờ duyệt')
            ->assertSee('Lịch hẹn đang chờ duyệt')
            ->assertSee('Pending Dashboard Patient')
            ->assertSee('Dr. Dashboard')
            ->assertSee('Headache consultation')
            ->assertSee(route('admin.appointments.edit', $pendingAppointment), false)
            ->assertDontSee('Confirmed Dashboard Patient');
    }

    public function test_admin_can_edit_an_appointment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'consultation_fee' => 300000,
            'slot_duration' => 45,
            'is_accepting' => true,
        ]);
        $appointment = $this->createAppointment($patient, $doctor);
        $startsAt = now()->addDays(4)->setTime(10, 15);
        $startsAtInput = $startsAt->format('Y-m-d\TH:i');

        $this->actingAs($admin)
            ->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee(route('admin.appointments.edit', $appointment), false)
            ->assertSee(route('admin.appointments.destroy', $appointment), false);
        $this->actingAs($admin)
            ->get(route('admin.appointments.edit', $appointment))
            ->assertOk()
            ->assertSee('Chỉnh sửa lịch hẹn');

        $response = $this->actingAs($admin)->patch(route('admin.appointments.update', $appointment), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $startsAt->format('Y-m-d'),
            'starts_at' => $startsAtInput,
            'patient_age' => 42,
            'reason' => 'Annual checkup',
            'status' => 'confirmed',
            'notes' => 'Updated appointment details.',
            'cancellation_reason' => '',
        ]);

        $response->assertRedirect(route('admin.appointments.index'));
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $startsAt->copy()->addMinutes(45)->format('Y-m-d H:i:s'),
            'patient_age' => 42,
            'consultation_fee' => 300000,
            'reason' => 'Annual checkup',
            'status' => 'confirmed',
        ]);
    }

    public function test_admin_cannot_assign_an_appointment_to_a_non_doctor_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $otherPatient = User::factory()->create(['role' => 'customer']);
        $appointment = $this->createAppointment($patient, $doctor);

        $response = $this->actingAs($admin)->from(route('admin.appointments.edit', $appointment))
            ->patch(route('admin.appointments.update', $appointment), [
                'doctor_id' => $otherPatient->id,
                'appointment_date' => now()->addDays(4)->format('Y-m-d'),
                'starts_at' => now()->addDays(4)->format('Y-m-d\TH:i'),
                'patient_age' => 42,
                'reason' => 'Annual checkup',
                'status' => 'confirmed',
            ]);

        $response->assertSessionHasErrors('doctor_id');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_cannot_reassign_an_appointment_to_an_occupied_slot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'slot_duration' => 30,
            'is_accepting' => true,
        ]);
        $appointment = $this->createAppointment($patient, $doctor);
        $originalStartsAt = $appointment->starts_at->format('Y-m-d H:i:s');
        $occupiedStart = now()->addDays(5)->setTime(10, 0);
        Appointment::create([
            'patient_id' => User::factory()->create(['role' => 'customer'])->id,
            'doctor_id' => $doctor->id,
            'starts_at' => $occupiedStart,
            'ends_at' => $occupiedStart->copy()->addMinutes(30),
            'status' => 'confirmed',
            'reason' => 'Already booked',
        ]);

        $response = $this->actingAs($admin)->from(route('admin.appointments.edit', $appointment))
            ->patch(route('admin.appointments.update', $appointment), [
                'doctor_id' => $doctor->id,
                'appointment_date' => $occupiedStart->format('Y-m-d'),
                'starts_at' => $occupiedStart->format('Y-m-d\TH:i'),
                'patient_age' => 42,
                'reason' => 'Annual checkup',
                'status' => 'confirmed',
            ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertSame(
            $originalStartsAt,
            $appointment->fresh()->starts_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_admin_can_update_details_of_an_appointment_in_the_past_without_rescheduling_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'slot_duration' => 30,
            'is_accepting' => true,
        ]);
        $appointment = $this->createAppointment($patient, $doctor);
        $pastStart = now()->subDays(3)->setTime(10, 0);
        $appointment->forceFill([
            'starts_at' => $pastStart,
            'ends_at' => $pastStart->copy()->addMinutes(30),
        ])->save();

        $response = $this->actingAs($admin)->patch(route('admin.appointments.update', $appointment), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $pastStart->format('Y-m-d'),
            'starts_at' => $pastStart->format('Y-m-d\TH:i'),
            'patient_age' => 42,
            'reason' => 'Updated historical appointment',
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('admin.appointments.index'));
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'starts_at' => $pastStart->format('Y-m-d H:i:s'),
            'reason' => 'Updated historical appointment',
            'status' => 'completed',
        ]);
    }

    public function test_non_admin_cannot_edit_or_delete_an_appointment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($patient, $doctor);

        $this->actingAs($customer)
            ->get(route('admin.appointments.edit', $appointment))
            ->assertForbidden();
        $this->actingAs($customer)
            ->delete(route('admin.appointments.destroy', $appointment))
            ->assertForbidden();

        $this->assertModelExists($appointment);
    }

    public function test_admin_can_delete_an_appointment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = $this->createAppointment($patient, $doctor);

        $response = $this->actingAs($admin)->delete(route('admin.appointments.destroy', $appointment));

        $response->assertRedirect(route('admin.appointments.index'));
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_admin_appointment_list_shows_newest_bookings_first(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $olderPatient = User::factory()->create(['role' => 'customer', 'name' => 'Older booking patient']);
        $newerPatient = User::factory()->create(['role' => 'customer', 'name' => 'Recent booking patient']);
        $olderAppointment = $this->createAppointment($olderPatient, $doctor);
        $newerAppointment = $this->createAppointment($newerPatient, $doctor);
        $olderAppointment->forceFill(['created_at' => now()->subDays(2)])->save();
        $newerAppointment->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs($admin)
            ->get(route('admin.appointments.index'))
            ->assertSeeTextInOrder(['Recent booking patient', 'Older booking patient']);
    }

    private function createAppointment(User $patient, User $doctor): Appointment
    {
        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDay(),
            'status' => 'pending',
            'reason' => 'Initial consultation',
        ]);
    }
}
