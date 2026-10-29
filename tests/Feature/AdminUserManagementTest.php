<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_three_collapsed_user_role_cards_with_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'customer']);
        User::factory()->create(['role' => 'doctor']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertSee('Bệnh nhân')
            ->assertSee('Bác sĩ')
            ->assertSee('Admin')
            ->assertSee('Nhấn để xem danh sách')
            ->assertDontSee('Danh sách tài khoản');
    }

    public function test_admin_opens_basic_role_table_without_doctor_profile_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'name' => 'Patient Role List',
            'email' => 'patient-list@example.test',
            'role' => 'customer',
        ]);
        $doctor = User::factory()->create([
            'name' => 'Doctor Role List',
            'email' => 'doctor-list@example.test',
            'phone' => '0900123456',
            'role' => 'doctor',
        ]);
        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'doctor']));

        $response->assertSee('Doctor Role List')
            ->assertSee('Bác sĩ')
            ->assertSee('doctor-list@example.test')
            ->assertSee('Thu gọn')
            ->assertSee('Vai trò')
            ->assertDontSee('Thông tin bác sĩ')
            ->assertDontSee('Patient Role List');
    }

    public function test_admin_can_expand_a_patient_to_view_personal_and_emergency_contact_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $patient = User::factory()->create([
            'name' => 'Patient Full Details',
            'email' => 'patient-details@example.test',
            'phone' => '0900555666',
            'date_of_birth' => '1990-06-15',
            'gender' => 'female',
            'address' => '15 Đường Lê Lợi, TP. HCM',
            'emergency_contact_name' => '<script>alert("xss")</script>',
            'emergency_contact_phone' => '0900777888',
            'emergency_contact_relationship' => 'Chị gái',
            'role' => 'customer',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'customer']));

        $response->assertSee('details class="card patient-user-card"', false)
            ->assertSee('Patient Full Details')
            ->assertSee('patient-details@example.test')
            ->assertSee('0900555666')
            ->assertSee('15/06/1990')
            ->assertSee('Nữ')
            ->assertSee('15 Đường Lê Lợi, TP. HCM')
            ->assertSee('0900777888')
            ->assertSee('Chị gái')
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertDontSee('details class="card patient-user-card" open', false);
    }

    public function test_admin_user_list_rejects_an_unknown_role_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'unknown']));

        $response->assertSessionHasErrors('role');
    }
}
