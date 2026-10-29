<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_legacy_test_accounts_that_can_log_in_and_is_safe_to_rerun(): void
    {
        config(['seeding.legacy_test_account_password' => '123456']);

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true]);
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true]);

        $accounts = [
            ['email' => 'admin@gmail.com', 'role' => 'admin'],
            ['email' => 'bacsi@gmail.com', 'role' => 'doctor'],
            ['email' => 'benhnhan@gmail.com', 'role' => 'customer'],
        ];

        foreach ($accounts as $account) {
            $user = User::where('email', $account['email'])->firstOrFail();

            $this->assertSame($account['role'], $user->role);
            $this->assertNotSame('123456', $user->password);

            $this->post('/login', [
                'email' => $account['email'],
                'password' => '123456',
            ])->assertRedirect(route('dashboard', absolute: false));

            $this->assertAuthenticatedAs($user);

            $this->post('/logout');
            $this->assertGuest();
        }

        $this->assertSame(3, User::whereIn('email', [
            'admin@gmail.com',
            'bacsi@gmail.com',
            'benhnhan@gmail.com',
        ])->count());
        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => User::where('email', 'bacsi@gmail.com')->value('id'),
        ]);
    }

    public function test_seeder_does_not_create_legacy_test_accounts_in_production(): void
    {
        config([
            'app.env' => 'production',
            'seeding.legacy_test_account_password' => null,
        ]);

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true]);

        $this->assertDatabaseMissing('users', ['email' => 'admin@gmail.com']);
        $this->assertDatabaseMissing('users', ['email' => 'bacsi@gmail.com']);
        $this->assertDatabaseMissing('users', ['email' => 'benhnhan@gmail.com']);
    }

    public function test_seeder_makes_five_additional_doctors_available_in_the_catalog(): void
    {
        config(['seeding.legacy_test_account_password' => '123456']);

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true]);
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true]);

        $response = $this->get(route('catalog.doctors'));

        $response->assertSee('Bác sĩ Trần Lan Anh');
        $response->assertSee('Bác sĩ Lê Minh Quân');
        $response->assertSee('Bác sĩ Phạm Thu Hà');
        $response->assertSee('Bác sĩ Võ Đức Minh');
        $response->assertSee('Bác sĩ Nguyễn Ngọc Mai');

        $customer = User::where('email', 'customer@medcare.test')->firstOrFail();

        $dashboardResponse = $this->actingAs($customer)->get(route('patient.dashboard'));
        $dashboardResponse->assertSee('Bác sĩ Trần Lan Anh');
        $dashboardResponse->assertSee('Bác sĩ Lê Minh Quân');
        $dashboardResponse->assertSee('Bác sĩ Phạm Thu Hà');
        $dashboardResponse->assertSee('Bác sĩ Võ Đức Minh');
        $dashboardResponse->assertSee('Bác sĩ Nguyễn Ngọc Mai');

        $this->assertSame(5, User::whereIn('email', [
            'bs.lananh@medcare.test',
            'bs.minhquan@medcare.test',
            'bs.thuha@medcare.test',
            'bs.ducminh@medcare.test',
            'bs.ngocmai@medcare.test',
        ])->count());
        $this->assertSame(5, DoctorProfile::whereHas('user', function ($query): void {
            $query->whereIn('email', [
                'bs.lananh@medcare.test',
                'bs.minhquan@medcare.test',
                'bs.thuha@medcare.test',
                'bs.ducminh@medcare.test',
                'bs.ngocmai@medcare.test',
            ]);
        })->where('is_accepting', true)->count());
    }

    public function test_seeder_assigns_local_portraits_to_demo_doctors_without_overwriting_uploaded_photos(): void
    {
        config(['seeding.legacy_test_account_password' => '123456']);
        $doctorWithOldDefaultPortrait = User::factory()->create([
            'email' => 'doctor@medcare.test',
            'role' => 'doctor',
        ]);
        DoctorProfile::create([
            'user_id' => $doctorWithOldDefaultPortrait->id,
            'image_path' => 'images/doctors/doctor-nguyen-an.svg',
        ]);
        $doctor = User::factory()->create(['email' => 'bs.lananh@medcare.test', 'role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'image_path' => 'doctors/custom-upload.webp',
        ]);

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true]);

        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $doctorWithOldDefaultPortrait->id,
            'image_path' => 'images/doctors/doctor-nguyen-an.png',
        ]);
        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $doctor->id,
            'image_path' => 'doctors/custom-upload.webp',
        ]);

        $portraitAssignments = [
            'doctor@medcare.test' => 'doctor-nguyen-an.png',
            'bacsi@gmail.com' => 'doctor-bacsi-thu-nghiem.png',
            'bs.lananh@medcare.test' => 'doctor-tran-lan-anh.png',
            'bs.minhquan@medcare.test' => 'doctor-le-minh-quan.png',
            'bs.thuha@medcare.test' => 'doctor-pham-thu-ha.png',
            'bs.ducminh@medcare.test' => 'doctor-vo-duc-minh.png',
            'bs.ngocmai@medcare.test' => 'doctor-nguyen-ngoc-mai.png',
        ];

        foreach ($portraitAssignments as $email => $portrait) {
            $this->assertFileExists(public_path('images/doctors/'.$portrait));

            if ($email !== 'bs.lananh@medcare.test') {
                $this->assertDatabaseHas('doctor_profiles', [
                    'user_id' => User::where('email', $email)->value('id'),
                    'image_path' => 'images/doctors/'.$portrait,
                ]);
            }
        }

        $this->get(route('catalog.doctors'))
            ->assertSee(asset('images/doctors/doctor-nguyen-an.png'), false);
    }
}
