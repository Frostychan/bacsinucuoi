<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_service_with_a_large_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Premium service',
            'price' => '88888881111',
            'stock' => 1,
            'description' => 'Premium description.',
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Premium service',
            'price' => '88888881111.00',
            'stock' => 1,
        ]);
    }

    public function test_admin_receives_validation_error_for_a_price_above_the_database_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->from(route('admin.products.create'))
            ->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'Too expensive service',
                'price' => '10000000000000',
                'stock' => 1,
                'description' => 'Price exceeds decimal column capacity.',
            ]);

        $response->assertSessionHasErrors([
            'price' => 'Giá dịch vụ không được vượt quá 9.999.999.999.999,99 đồng.',
        ]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_can_update_a_service_with_a_large_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Existing service',
            'price' => 250000,
            'stock' => 1,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'Updated service',
            'price' => '88888881111',
            'stock' => 1,
            'description' => 'Updated description.',
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => '88888881111.00',
        ]);
    }
}
