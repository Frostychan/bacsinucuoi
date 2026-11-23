<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\User;
use Database\Seeders\DoctorCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_hundred_doctors_and_is_idempotent(): void
    {
        $seeder = new DoctorCatalogSeeder;
        $seeder->run();

        $this->assertSame(100, User::query()->where('role', 'doctor')->count());
        $this->assertSame(100, DoctorProfile::query()->count());

        $doctor = User::where('email', 'doctor001@medcare.test')->firstOrFail();
        $doctor->update(['name' => 'Bác sĩ tên tùy chỉnh']);
        $doctor->doctorProfile->update(['image_path' => 'doctors/custom-upload.webp']);

        $seeder->run();

        $this->assertSame(100, User::query()->where('role', 'doctor')->count());
        $this->assertDatabaseHas('users', [
            'id' => $doctor->id,
            'name' => 'Bác sĩ tên tùy chỉnh',
        ]);
        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $doctor->id,
            'image_path' => 'doctors/custom-upload.webp',
        ]);
    }
}
