<?php

namespace Tests\Feature;

use App\Models\Medicine;
use Database\Seeders\IndicativeMedicationPricesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicativeMedicationPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_seeder_fills_catalog_with_estimates_and_preserves_admin_prices(): void
    {
        $seeder = new IndicativeMedicationPricesSeeder;
        $seeder->run();
        $this->assertSame(20, Medicine::query()->whereNotNull('price')->where('price_is_estimate', true)->count());
        $this->assertSame(5, Medicine::query()->whereNotNull('price_source')->where('price_is_estimate', true)->count());

        $adminEnteredMedicine = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();
        $adminEnteredMedicine->update([
            'price' => '9999.00',
            'price_is_estimate' => false,
            'price_source' => 'https://clinic.example/prices',
        ]);
        $this->assertDatabaseHas('medicines', [
            'name' => 'Paracetamol',
            'price' => '9999.00',
            'price_is_estimate' => false,
            'price_source' => 'https://clinic.example/prices',
        ]);
        $seeder->run();

        $this->assertDatabaseHas('medicines', [
            'name' => 'Paracetamol',
            'price' => '9999.00',
            'price_is_estimate' => false,
            'price_source' => 'https://clinic.example/prices',
        ]);
        $this->assertSame(19, Medicine::query()->whereNotNull('price')->where('price_is_estimate', true)->count());
    }
}
