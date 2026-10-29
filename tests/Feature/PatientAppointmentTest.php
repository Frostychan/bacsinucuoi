<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_appointment_form_includes_datetime_and_patient_age_fields(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('patient.appointments.create'));

        $response->assertSee('name="appointment_date"', false);
        $response->assertSee('name="starts_at"', false);
        $response->assertSee('name="patient_age"', false);
    }

    public function test_customer_can_submit_appointment_using_form_datetime_and_age_fields(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'consultation_fee' => 300000,
            'slot_duration' => 30,
            'is_accepting' => true,
        ]);
        $startsAt = now()->addDay()->setTime(10, 0);

        $response = $this->actingAs($customer)->post(route('patient.appointments.store'), [
            'doctor_id' => $doctor->id,
            'appointment_date' => $startsAt->format('Y-m-d'),
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'patient_age' => 35,
            'reason' => 'Khám tổng quát',
        ]);

        $response->assertRedirect(route('patient.appointments.payment', Appointment::query()->latest('id')->firstOrFail()));
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $customer->id,
            'doctor_id' => $doctor->id,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'patient_age' => 35,
            'consultation_fee' => 300000,
            'reason' => 'Khám tổng quát',
            'status' => 'pending',
        ]);
    }

    public function test_customer_must_provide_datetime_and_patient_age_to_create_appointment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'is_accepting' => true,
        ]);

        $response = $this->actingAs($customer)->from(route('patient.appointments.create'))
            ->post(route('patient.appointments.store'), [
                'doctor_id' => $doctor->id,
                'reason' => 'Khám tổng quát',
            ]);

        $response->assertSessionHasErrors([
            'appointment_date' => 'Vui lòng chọn ngày khám.',
            'starts_at' => 'Vui lòng chọn khung giờ còn trống.',
            'patient_age' => 'Vui lòng nhập tuổi bệnh nhân.',
        ]);
        $this->assertSame(0, Appointment::count());
    }

    public function test_customer_can_see_available_slots_and_not_slots_already_booked(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'slot_duration' => 30,
            'is_accepting' => true,
        ]);
        $appointmentDate = now()->addDays(4)->format('Y-m-d');
        $bookedStart = now()->addDays(4)->setTime(10, 0);
        Appointment::create([
            'patient_id' => $customer->id,
            'doctor_id' => $doctor->id,
            'starts_at' => $bookedStart,
            'ends_at' => $bookedStart->copy()->addMinutes(30),
            'status' => 'confirmed',
            'reason' => 'Existing booking',
        ]);

        $response = $this->actingAs($customer)
            ->getJson(route('booking.doctors.availability', $doctor).'?date='.$appointmentDate);

        $response->assertOk();
        $slotValues = collect($response->json('slots'))->pluck('value')->all();
        $this->assertContains($appointmentDate.'T08:00', $slotValues);
        $this->assertNotContains($appointmentDate.'T10:00', $slotValues);
    }

    public function test_customer_cannot_book_outside_the_doctors_scheduled_hours(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'slot_duration' => 30,
            'is_accepting' => true,
        ]);
        $startsAt = now()->addDays(2)->setTime(7, 30);

        $response = $this->actingAs($customer)->from(route('patient.appointments.create'))
            ->post(route('patient.appointments.store'), [
                'doctor_id' => $doctor->id,
                'appointment_date' => $startsAt->format('Y-m-d'),
                'starts_at' => $startsAt->format('Y-m-d\TH:i'),
                'patient_age' => 35,
                'reason' => 'Khám tổng quát',
            ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertSame(0, Appointment::count());
    }
}
