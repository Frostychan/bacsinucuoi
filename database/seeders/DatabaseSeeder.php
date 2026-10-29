<?php

namespace Database\Seeders;

use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $specialty = Specialty::updateOrCreate(
            ['name' => 'Nội tổng quát'],
            ['description' => 'Khám và điều trị các bệnh lý nội khoa thường gặp.'],
        );

        $accounts = [
            [
                'name' => 'Quản trị viên',
                'email' => 'admin@medcare.test',
                'role' => 'admin',
                'password' => 'password',
            ],
            [
                'name' => 'Bác sĩ Nguyễn An',
                'email' => 'doctor@medcare.test',
                'role' => 'doctor',
                'password' => 'password',
                'license_number' => 'MED-DEMO-001',
            ],
            [
                'name' => 'Khách hàng Demo',
                'email' => 'customer@medcare.test',
                'role' => 'customer',
                'password' => 'password',
            ],
        ];

        if (in_array(config('app.env'), ['local', 'testing'], true)) {
            $legacyTestPassword = $this->legacyTestPassword();

            $accounts = [
                ...$accounts,
                [
                    'name' => 'Quản trị viên thử nghiệm',
                    'email' => 'admin@gmail.com',
                    'role' => 'admin',
                    'password' => $legacyTestPassword,
                ],
                [
                    'name' => 'Bác sĩ thử nghiệm',
                    'email' => 'bacsi@gmail.com',
                    'role' => 'doctor',
                    'password' => $legacyTestPassword,
                    'license_number' => 'MED-LEGACY-001',
                ],
                [
                    'name' => 'Bệnh nhân thử nghiệm',
                    'email' => 'benhnhan@gmail.com',
                    'role' => 'customer',
                    'password' => $legacyTestPassword,
                ],
                [
                    'name' => 'Bác sĩ Trần Lan Anh',
                    'email' => 'bs.lananh@medcare.test',
                    'role' => 'doctor',
                    'password' => 'password',
                    'license_number' => 'MED-DEMO-002',
                    'specialty' => 'Nhi khoa',
                    'bio' => 'Bác sĩ Nhi khoa tận tâm, đồng hành cùng sức khỏe trẻ em.',
                    'consultation_fee' => 350000,
                ],
                [
                    'name' => 'Bác sĩ Lê Minh Quân',
                    'email' => 'bs.minhquan@medcare.test',
                    'role' => 'doctor',
                    'password' => 'password',
                    'license_number' => 'MED-DEMO-003',
                    'specialty' => 'Tim mạch',
                    'bio' => 'Bác sĩ Tim mạch tư vấn và chăm sóc sức khỏe tim mạch.',
                    'consultation_fee' => 450000,
                ],
                [
                    'name' => 'Bác sĩ Phạm Thu Hà',
                    'email' => 'bs.thuha@medcare.test',
                    'role' => 'doctor',
                    'password' => 'password',
                    'license_number' => 'MED-DEMO-004',
                    'specialty' => 'Da liễu',
                    'bio' => 'Bác sĩ Da liễu tư vấn chăm sóc và điều trị các vấn đề về da.',
                    'consultation_fee' => 320000,
                ],
                [
                    'name' => 'Bác sĩ Võ Đức Minh',
                    'email' => 'bs.ducminh@medcare.test',
                    'role' => 'doctor',
                    'password' => 'password',
                    'license_number' => 'MED-DEMO-005',
                    'specialty' => 'Sản phụ khoa',
                    'bio' => 'Bác sĩ Sản phụ khoa đồng hành trong chăm sóc sức khỏe phụ nữ.',
                    'consultation_fee' => 400000,
                ],
                [
                    'name' => 'Bác sĩ Nguyễn Ngọc Mai',
                    'email' => 'bs.ngocmai@medcare.test',
                    'role' => 'doctor',
                    'password' => 'password',
                    'license_number' => 'MED-DEMO-006',
                    'specialty' => 'Tai mũi họng',
                    'bio' => 'Bác sĩ Tai mũi họng tận tâm tư vấn và điều trị.',
                    'consultation_fee' => 330000,
                ],
            ];
        }

        foreach ($accounts as $account) {
            $password = $account['password'];
            $licenseNumber = $account['license_number'] ?? null;
            $specialtyName = $account['specialty'] ?? null;
            $bio = $account['bio'] ?? 'Bác sĩ giàu kinh nghiệm, tận tâm với người bệnh.';
            $consultationFee = $account['consultation_fee'] ?? 300000;
            unset($account['password'], $account['license_number'], $account['specialty'], $account['bio'], $account['consultation_fee']);

            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [...$account, 'password' => Hash::make($password)],
            );

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if ($user->isRole('doctor')) {
                $doctorSpecialty = $specialtyName
                    ? Specialty::updateOrCreate(
                        ['name' => $specialtyName],
                        ['description' => 'Khám và tư vấn chuyên khoa '.$specialtyName.'.'],
                    )
                    : $specialty;

                DoctorProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'specialty_id' => $doctorSpecialty->id,
                        'license_number' => $licenseNumber,
                        'bio' => $bio,
                        'consultation_fee' => $consultationFee,
                        'slot_duration' => 30,
                        'is_accepting' => true,
                    ],
                );
            }
        }

        $this->call([
            DoctorPortraitSeeder::class,
            MedicationCatalogSeeder::class,
        ]);

        User::whereNull('role')->update(['role' => 'customer']);
    }

    private function legacyTestPassword(): string
    {
        $password = config('seeding.legacy_test_account_password');

        if (! is_string($password) || $password === '') {
            throw new \RuntimeException('Set LEGACY_TEST_ACCOUNT_PASSWORD before seeding legacy test accounts.');
        }

        return $password;
    }
}
