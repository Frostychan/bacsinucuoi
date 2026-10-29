<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class IndicativeMedicationPricesSeeder extends Seeder
{
    public function run(): void
    {
        $referencePrices = [
            'Paracetamol' => ['strength' => '500 mg', 'unit' => 'viên', 'price' => '650.00', 'source' => null],
            'Ibuprofen' => ['strength' => '400 mg', 'unit' => 'viên', 'price' => '2000.00', 'source' => 'https://www.pharmart.vn/thuoc-giam-dau-ha-sot/thuoc-i-pain-400mg-8881.html'],
            'Amoxicillin' => ['strength' => '500 mg', 'unit' => 'viên nang', 'price' => '3500.00', 'source' => 'https://www.pharmart.vn/thuoc-khang-sinh/thuoc-amoxicillin-500-mg-domesco-1483.html'],
            'Azithromycin' => ['strength' => '500 mg', 'unit' => 'viên', 'price' => '10500.00', 'source' => 'https://nhathuocngocanh.com/san-pham/pymeazi-500/'],
            'Cefuroxime' => ['strength' => '500 mg', 'unit' => 'viên', 'price' => '14000.00', 'source' => 'https://www.pharmart.vn/thuoc-khang-sinh/thuoc-arme-cefu-500-10105.html'],
            'Metronidazole' => ['strength' => '250 mg', 'unit' => 'viên', 'price' => '1000.00', 'source' => null],
            'Omeprazole' => ['strength' => '20 mg', 'unit' => 'viên nang', 'price' => '2000.00', 'source' => 'https://nhathuocngocanh.com/san-pham/omeprazol-20mg-tvp/'],
            'Esomeprazole' => ['strength' => '20 mg', 'unit' => 'viên nang', 'price' => '6500.00', 'source' => null],
            'Loratadine' => ['strength' => '10 mg', 'unit' => 'viên', 'price' => '1250.00', 'source' => null],
            'Cetirizine' => ['strength' => '10 mg', 'unit' => 'viên', 'price' => '1250.00', 'source' => null],
            'Amlodipine' => ['strength' => '5 mg', 'unit' => 'viên', 'price' => '1250.00', 'source' => null],
            'Losartan' => ['strength' => '50 mg', 'unit' => 'viên', 'price' => '2500.00', 'source' => null],
            'Metformin' => ['strength' => '500 mg, giải phóng tức thì', 'unit' => 'viên', 'price' => '1000.00', 'source' => null],
            'Atorvastatin' => ['strength' => '10 mg', 'unit' => 'viên', 'price' => '2500.00', 'source' => null],
            'Aspirin' => ['strength' => '81 mg, bao tan ở ruột', 'unit' => 'viên', 'price' => '1250.00', 'source' => null],
            'Vitamin C' => ['strength' => '500 mg', 'unit' => 'viên', 'price' => '1250.00', 'source' => null],
            'Calcium carbonate' => ['strength' => '500 mg', 'unit' => 'viên', 'price' => '2500.00', 'source' => null],
            'Oresol (ORS)' => ['strength' => 'Gói bù nước điện giải tiêu chuẩn', 'unit' => 'gói', 'price' => '5500.00', 'source' => null],
            'Salbutamol' => ['strength' => '2 mg', 'unit' => 'viên', 'price' => '1000.00', 'source' => null],
            'Guaifenesin' => ['strength' => '100 mg', 'unit' => 'viên', 'price' => '2000.00', 'source' => null],
        ];

        $medicines = Medicine::query()->get()->keyBy('name');

        foreach ($referencePrices as $name => $referencePrice) {
            $medicine = $medicines->get($name);

            if ($medicine === null || ($medicine->price !== null && ! $medicine->price_is_estimate)) {
                continue;
            }

            $medicine->update([
                'strength' => $referencePrice['strength'],
                'unit' => $referencePrice['unit'],
                'price' => $referencePrice['price'],
                'price_is_estimate' => true,
                'price_source' => $referencePrice['source'],
            ]);
        }
    }
}
