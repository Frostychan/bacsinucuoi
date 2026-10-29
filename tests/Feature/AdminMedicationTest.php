<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMedicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_official_medicine_price_and_package_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $medicine = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.medicines.index'))
            ->assertSee('Danh mục thuốc và giá bán')
            ->assertSee('Paracetamol')
            ->assertSee('Chưa nhập giá');

        $this->actingAs($admin)
            ->patch(route('admin.medicines.update', $medicine), [
                'price' => '1250.50',
                'unit' => 'viên',
                'strength' => '500 mg',
                'price_source' => 'https://pharmacy.example/medicines/paracetamol',
                'price_is_estimate' => true,
            ])
            ->assertRedirect(route('admin.medicines.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'price' => '1250.50',
            'unit' => 'viên',
            'strength' => '500 mg',
            'price_is_estimate' => false,
            'price_source' => 'https://pharmacy.example/medicines/paracetamol',
        ]);
    }

    public function test_admin_medicine_prices_hide_zero_decimals_but_keep_fractional_cents(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $paracetamol = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();
        $ibuprofen = Medicine::query()->where('name', 'Ibuprofen')->firstOrFail();
        $paracetamol->update(['price' => '1250.00']);
        $ibuprofen->update(['price' => '12.50']);

        $this->actingAs($admin)
            ->get(route('admin.medicines.index'))
            ->assertSee('value="1250"', false)
            ->assertSee('1.250 ₫')
            ->assertDontSee('1.250,00 ₫')
            ->assertSee('12,50 ₫');
    }

    public function test_non_admin_cannot_change_medicine_price(): void
    {
        $patient = User::factory()->create(['role' => 'customer']);
        $medicine = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();

        $this->actingAs($patient)
            ->patch(route('admin.medicines.update', $medicine), [
                'price' => '1250.50',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'price' => null,
        ]);
    }

    public function test_admin_cannot_save_a_price_with_more_than_two_decimal_places(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $medicine = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.medicines.index'))
            ->patch(route('admin.medicines.update', $medicine), [
                'price' => '12.345',
            ])
            ->assertSessionHasErrors('price');

        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'price' => null,
        ]);
    }
}
