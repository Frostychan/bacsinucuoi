<?php

namespace Database\Seeders;

use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DoctorPortraitSeeder extends Seeder
{
    public function run(): void
    {
        $portraitsByEmail = [
            'doctor@medcare.test' => 'doctor-nguyen-an.png',
            'bacsi@gmail.com' => 'doctor-bacsi-thu-nghiem.png',
            'bs.lananh@medcare.test' => 'doctor-tran-lan-anh.png',
            'bs.minhquan@medcare.test' => 'doctor-le-minh-quan.png',
            'bs.thuha@medcare.test' => 'doctor-pham-thu-ha.png',
            'bs.ducminh@medcare.test' => 'doctor-vo-duc-minh.png',
            'bs.ngocmai@medcare.test' => 'doctor-nguyen-ngoc-mai.png',
        ];
        $previousDefaultPortraitsByEmail = [
            'doctor@medcare.test' => 'doctor-nguyen-an.svg',
            'bacsi@gmail.com' => 'doctor-nguyen-van-an.svg',
            'bs.lananh@medcare.test' => 'doctor-tran-lan-anh.svg',
            'bs.minhquan@medcare.test' => 'doctor-le-minh-quan.svg',
            'bs.thuha@medcare.test' => 'doctor-pham-thu-ha.svg',
            'bs.ducminh@medcare.test' => 'doctor-vo-duc-minh.svg',
            'bs.ngocmai@medcare.test' => 'doctor-nguyen-ngoc-mai.svg',
        ];

        foreach ($portraitsByEmail as $email => $portrait) {
            $doctorId = User::query()->where('email', $email)->where('role', 'doctor')->value('id');

            if ($doctorId === null) {
                continue;
            }

            DoctorProfile::query()
                ->where('user_id', $doctorId)
                ->where(function ($query) use ($previousDefaultPortraitsByEmail, $email): void {
                    $query->whereNull('image_path')->orWhere(
                        'image_path',
                        'images/doctors/'.$previousDefaultPortraitsByEmail[$email],
                    );
                })
                ->update(['image_path' => 'images/doctors/'.$portrait]);
        }
    }
}
