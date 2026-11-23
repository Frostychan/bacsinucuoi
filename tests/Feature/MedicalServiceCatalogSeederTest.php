<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\MedicalServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalServiceCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_three_hundred_services_and_is_idempotent(): void
    {
        $seeder = new MedicalServiceCatalogSeeder;
        $seeder->run();

        $this->assertSame(300, Product::query()->count());

        $existing = Product::query()->firstOrFail();
        $existing->update(['price' => '9999.00', 'stock' => 99]);

        $seeder->run();

        $this->assertSame(300, Product::query()->count());
        $this->assertDatabaseHas('products', [
            'id' => $existing->id,
            'price' => '9999.00',
            'stock' => 99,
        ]);
    }

    public function test_seeder_removes_numeric_suffixes_from_service_names(): void
    {
        Product::create([
            'name' => 'Gói khám cơ bản 231',
            'price' => 200000,
            'stock' => 10,
        ]);

        (new MedicalServiceCatalogSeeder)->run();

        $this->assertDatabaseHas('products', ['name' => 'Gói khám cơ bản']);
        $this->assertDatabaseMissing('products', ['name' => 'Gói khám cơ bản 231']);
    }
}
