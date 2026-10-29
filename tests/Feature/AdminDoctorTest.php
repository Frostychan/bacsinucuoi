<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDoctorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_doctor_page_shows_collapsed_photo_cards_with_full_doctor_information(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doctor = User::factory()->create([
            'name' => 'Dr. Mai Nguyen',
            'email' => 'mai.nguyen@example.test',
            'phone' => '0900111222',
            'date_of_birth' => '1985-04-12',
            'gender' => 'Nữ',
            'address' => '12 Nguyễn Huệ, TP. HCM',
            'role' => 'doctor',
        ]);
        $specialty = Specialty::create([
            'name' => 'Nội tổng quát',
            'is_active' => true,
        ]);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'GP-98765',
            'bio' => 'Bác sĩ có kinh nghiệm khám nội khoa.',
            'image_path' => 'doctors/mai-nguyen.webp',
            'consultation_fee' => 400000,
            'slot_duration' => 45,
            'is_accepting' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.doctors.index'));

        $response->assertSee('details class="card doctor-admin-card"', false)
            ->assertSee(route('admin.doctors.create'), false)
            ->assertSee('Ảnh bác sĩ Dr. Mai Nguyen')
            ->assertSee('Dr. Mai Nguyen')
            ->assertSee('mai.nguyen@example.test')
            ->assertSee('0900111222')
            ->assertSee('12 Nguyễn Huệ, TP. HCM')
            ->assertSee('12/04/1985')
            ->assertSee('Nữ')
            ->assertSee('Nội tổng quát')
            ->assertSee('GP-98765')
            ->assertSee('Bác sĩ có kinh nghiệm khám nội khoa.')
            ->assertSee('400.000 đ')
            ->assertSee('45 phút')
            ->assertSee('Đang nhận lịch')
            ->assertSee('doctor-admin-grid')
            ->assertDontSee('details class="card doctor-admin-card" open', false);
    }

    public function test_admin_can_create_a_doctor_account_and_profile(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $specialty = Specialty::create([
            'name' => 'Nhi khoa',
            'is_active' => true,
        ]);
        $image = UploadedFile::fake()->create('doctor.jpg', 100, 'image/jpeg');

        $this->actingAs($admin)
            ->get(route('admin.doctors.create'))
            ->assertOk()
            ->assertSee('Tạo tài khoản đăng nhập và hồ sơ chuyên môn');

        $response = $this->actingAs($admin)->post(route('admin.doctors.store'), [
            'name' => 'Dr. New Doctor',
            'email' => 'new.doctor@example.test',
            'phone' => '0900999888',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'specialty_id' => $specialty->id,
            'license_number' => 'GP-NEW-123',
            'bio' => 'Bác sĩ chuyên khoa nhi.',
            'consultation_fee' => 275000,
            'slot_duration' => 45,
            'is_accepting' => '1',
            'image' => $image,
        ]);

        $response->assertRedirect(route('admin.doctors.index'))
            ->assertSessionHas('success', 'Đã tạo tài khoản và hồ sơ bác sĩ.');
        $doctor = User::query()->where('email', 'new.doctor@example.test')->firstOrFail();
        $this->assertSame('doctor', $doctor->role);
        $this->assertTrue(Hash::check('StrongPassword123!', $doctor->password));
        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'GP-NEW-123',
            'bio' => 'Bác sĩ chuyên khoa nhi.',
            'consultation_fee' => 275000,
            'slot_duration' => 45,
            'is_accepting' => true,
        ]);
        Storage::disk('public')->assertExists($doctor->doctorProfile->image_path);
    }

    public function test_admin_cannot_create_a_doctor_with_duplicate_email_or_unconfirmed_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'existing@example.test']);

        $response = $this->actingAs($admin)->from(route('admin.doctors.create'))
            ->post(route('admin.doctors.store'), [
                'name' => 'Duplicate Doctor',
                'email' => 'existing@example.test',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
                'consultation_fee' => 200000,
                'slot_duration' => 30,
            ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseMissing('users', ['name' => 'Duplicate Doctor']);
    }

    public function test_non_admin_cannot_open_or_submit_the_add_doctor_form(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.doctors.create'))
            ->assertForbidden();
        $this->actingAs($customer)
            ->post(route('admin.doctors.store'), [])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['role' => 'doctor']);
        Storage::disk('public')->assertDirectoryEmpty('doctors');
    }

    public function test_admin_can_upload_a_doctor_photo_for_the_patient_dashboard(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'consultation_fee' => 250000,
            'slot_duration' => 30,
            'is_accepting' => true,
        ]);
        $image = UploadedFile::fake()->create('doctor.jpg', 100, 'image/jpeg');

        $this->actingAs($admin)
            ->get(route('admin.doctors.index'))
            ->assertOk()
            ->assertSee(route('admin.doctors.update', $doctor), false)
            ->assertSee('enctype="multipart/form-data"', false);

        $response = $this->actingAs($admin)->patch(route('admin.doctors.update', $doctor), [
            'consultation_fee' => 250000,
            'slot_duration' => 30,
            'is_accepting' => '1',
            'image' => $image,
        ]);

        $response->assertRedirect();
        $doctorProfile = DoctorProfile::where('user_id', $doctor->id)->firstOrFail();
        $this->assertNotNull($doctorProfile->image_path);
        Storage::disk('public')->assertExists($doctorProfile->image_path);
    }

    public function test_non_admin_cannot_upload_a_doctor_photo(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $image = UploadedFile::fake()->create('doctor.jpg', 100, 'image/jpeg');

        $this->actingAs($customer)->patch(route('admin.doctors.update', $doctor), [
            'consultation_fee' => 250000,
            'slot_duration' => 30,
            'is_accepting' => '1',
            'image' => $image,
        ])->assertForbidden();

        Storage::disk('public')->assertDirectoryEmpty('doctors');
    }
}
