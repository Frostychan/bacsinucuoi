<?php

namespace Database\Seeders;

use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $specialtyNames = [
            'Nội tổng quát',
            'Tim mạch',
            'Nhi khoa',
            'Da liễu',
            'Tai mũi họng',
            'Tiêu hóa',
            'Tiết niệu',
            'Sản phụ khoa',
            'Cơ xương khớp',
            'Thần kinh',
            'Mắt',
            'Nội tiết',
            'Hô hấp',
            'Dinh dưỡng',
            'Ung bướu',
            'Tâm thần',
            'Ngoại tổng quát',
            'Y học cổ truyền',
            'Lão khoa',
            'Chẩn đoán hình ảnh',
        ];

        $specialties = [];
        foreach ($specialtyNames as $index => $name) {
            $specialties[$index] = Specialty::firstOrCreate(
                ['name' => $name],
                ['description' => 'Khoa '.$name.' và tư vấn sức khỏe liên quan.', 'is_active' => true],
            );
        }

        $familyNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Võ', 'Đặng', 'Bùi', 'Đỗ'];
        $middleNames = ['Anh', 'Minh', 'Thu', 'Hải', 'Quốc', 'Ngọc', 'Thanh', 'Đức', 'Gia', 'Khánh'];
        $givenNames = ['An', 'Bình', 'Chi', 'Dung', 'Giang', 'Hà', 'Hùng', 'Lan', 'Long', 'Mai', 'Nam', 'Phúc', 'Quân', 'Trang', 'Vy'];

        for ($index = 1; $index <= 100; $index++) {
            $family = $familyNames[($index - 1) % count($familyNames)];
            $middle = $middleNames[(int) floor(($index - 1) / count($familyNames)) % count($middleNames)];
            $given = $givenNames[($index * 7) % count($givenNames)];
            $name = 'BS. '.$family.' '.$middle.' '.$given;
            $email = 'doctor'.str_pad((string) $index, 3, '0', STR_PAD_LEFT).'@medcare.test';

            $doctor = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => 'doctor',
                    'email_verified_at' => now(),
                ],
            );

            if (! $doctor->isRole('doctor')) {
                $doctor->update(['role' => 'doctor']);
            }

            if ($doctor->email_verified_at === null) {
                $doctor->forceFill(['email_verified_at' => now()])->save();
            }

            $specialtyIndex = ($index - 1) % count($specialties);
            $profile = DoctorProfile::firstOrCreate(
                ['user_id' => $doctor->id],
                [
                    'specialty_id' => $specialties[$specialtyIndex]->id,
                    'license_number' => 'MED-CATALOG-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'bio' => 'Bác sĩ '.$name.' hiện công tác chuyên khoa '.$specialties[$specialtyIndex]->name.'. Tận tâm tư vấn và theo dõi sức khỏe người bệnh.',
                    'consultation_fee' => 180000 + (($index % 10) * 30000),
                    'slot_duration' => $index % 4 === 0 ? 45 : 30,
                    'is_accepting' => true,
                ],
            );

            if ($index <= 30
                && ($profile->image_path === null
                    || str_starts_with((string) $profile->image_path, 'images/doctors/catalog/'))) {
                $profile->update([
                    'image_path' => 'images/doctors/catalog/doctor-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT).'.png',
                ]);
            } elseif (str_starts_with((string) $profile->image_path, 'images/doctors/catalog/')) {
                $profile->update(['image_path' => null]);
            }
        }
    }
}
