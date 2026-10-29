<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPatientOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_a_read_only_patient_overview_from_the_header(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doctor = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Preview']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'is_accepting' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.patient-overview'));

        $response->assertOk();
        $response->assertSee(route('admin.patient-overview'), false);
        $response->assertSee('Xem trang bệnh nhân');
        $response->assertSee('Giới thiệu MedCare');
        $response->assertSee('Dr. Preview');
        $response->assertDontSee('Đặt lịch khám');
        $response->assertDontSee('Đặt lịch với bác sĩ');
        $response->assertDontSee('Lịch hẹn gần đây');
    }

    public function test_patient_cannot_open_the_admin_patient_overview(): void
    {
        $patient = User::factory()->create(['role' => 'customer']);

        $this->actingAs($patient)
            ->get(route('admin.patient-overview'))
            ->assertForbidden();
    }
}
