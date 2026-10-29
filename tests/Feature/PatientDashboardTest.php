<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_introduces_medcare_and_displays_available_doctor_profiles(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Lan Nguyen']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'bio' => 'Experienced family physician.',
            'image_path' => 'doctors/lan-nguyen.jpg',
            'is_accepting' => true,
            'consultation_fee' => 250000,
        ]);

        $response = $this->actingAs($customer)->get(route('patient.dashboard'));

        $response->assertOk();
        $response->assertSee('Chăm sóc thuận tiện, an tâm hơn');
        $response->assertSee('Dr. Lan Nguyen');
        $response->assertSee('doctor-showcase', false);
        $response->assertSee('aria-label="Danh sách bác sĩ đang nhận lịch"', false);
        $response->assertSee(route('catalog.doctors'), false);
        $response->assertSee('>Bác sĩ</span>', false);
        $response->assertDontSee('Xem tất cả bác sĩ');
        $response->assertDontSee('horizontal-scroll-hint', false);
        $response->assertSee('.topbar:not(:hover):not(:focus-within)', false);
        $response->assertSee('storage/doctors/lan-nguyen.jpg');
        $response->assertSee(route('patient.appointments.create', ['doctor_id' => $doctor->id]), false);
    }

    public function test_dashboard_shows_only_six_doctors_and_places_more_link_below_when_more_are_available(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        foreach (range(1, 7) as $number) {
            $doctor = User::factory()->create([
                'role' => 'doctor',
                'name' => 'Dr. Available '.$number,
            ]);
            DoctorProfile::create([
                'user_id' => $doctor->id,
                'is_accepting' => true,
            ]);
        }

        $response = $this->actingAs($customer)->get(route('patient.dashboard'));

        $content = $response->getContent();
        $response->assertSee('Dr. Available 1');
        $response->assertSee('Dr. Available 6');
        $response->assertDontSee('Dr. Available 7');
        $response->assertSee('Xem thêm bác sĩ');
        $this->assertGreaterThan(
            strpos($content, 'class="doctor-showcase"'),
            strpos($content, 'class="doctor-showcase-more"'),
        );
        $response->assertSee(route('catalog.doctors'), false);
    }

    public function test_dashboard_hides_more_doctors_link_when_six_or_fewer_are_available(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        foreach (range(1, 6) as $number) {
            $doctor = User::factory()->create(['role' => 'doctor']);
            DoctorProfile::create([
                'user_id' => $doctor->id,
                'is_accepting' => true,
            ]);
        }

        $response = $this->actingAs($customer)->get(route('patient.dashboard'));

        $response->assertDontSee('Xem thêm bác sĩ');
    }

    public function test_dashboard_does_not_advertise_doctors_who_are_not_accepting_appointments(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor', 'name' => 'Unavailable Doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'is_accepting' => false,
        ]);

        $response = $this->actingAs($customer)->get(route('patient.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Unavailable Doctor');
    }

    public function test_doctor_catalog_displays_uploaded_doctor_photos(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Minh Tran']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'image_path' => 'doctors/minh-tran.jpg',
            'is_accepting' => true,
        ]);
        $secondDoctor = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Lan Nguyen']);
        DoctorProfile::create([
            'user_id' => $secondDoctor->id,
            'is_accepting' => true,
        ]);

        $response = $this->get(route('catalog.doctors'));

        $response->assertOk();
        $response->assertSee('Dr. Minh Tran');
        $response->assertSee('doctor-catalog-grid', false);
        $response->assertSee('aria-label="Danh sách bác sĩ"', false);
        $response->assertDontSee('horizontal-scroll-hint', false);
        $response->assertSee('storage/doctors/minh-tran.jpg');
    }

    public function test_recent_appointments_use_compact_cards_and_localized_statuses(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Mai']);
        $appointment = Appointment::create([
            'patient_id' => $customer->id,
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addDay(),
            'status' => 'confirmed',
            'reason' => 'Khám tổng quát',
            'consultation_fee' => 300000,
        ]);

        $response = $this->actingAs($customer)->get(route('patient.dashboard'));

        $response->assertOk();
        $response->assertSee('recent-appointments-grid', false);
        $response->assertSee('recent-appointment-card', false);
        $response->assertSee('Đã xác nhận');
        $response->assertSee('Khám tổng quát');
        $response->assertSee(route('patient.appointments.show', $appointment), false);
    }
}
