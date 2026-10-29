<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Support\MedicationCatalog;
use Illuminate\Database\Seeder;

class MedicationCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (MedicationCatalog::all() as $name) {
            Medicine::firstOrCreate(
                ['name' => $name],
                ['price' => null, 'unit' => null, 'strength' => null],
            );
        }
    }
}
