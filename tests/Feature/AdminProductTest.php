<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_products_page_automatically_shows_medical_service_catalog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = Product::create([
            'name' => 'Khám nội tổng quát - Tư vấn ban đầu 01',
            'price' => 9999,
            'stock' => 99,
            'description' => 'Giá tùy chỉnh.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Quản lý dịch vụ')
            ->assertSee('Khám nội tổng quát - Tư vấn ban đầu 01');

        $this->assertDatabaseCount('products', 300);
        $this->assertDatabaseHas('products', [
            'id' => $existing->id,
            'price' => '9999.00',
            'stock' => 99,
            'description' => 'Giá tùy chỉnh.',
        ]);
    }

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

    public function test_admin_can_import_services_from_a_json_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->createWithContent('services.json', json_encode([
            [
                'name' => 'Khám tổng quát',
                'price' => 500000,
                'stock' => 10,
                'description' => 'Khám sức khỏe định kỳ.',
            ],
            [
                'name' => 'Tư vấn dinh dưỡng',
                'price' => 250000,
            ],
        ], JSON_THROW_ON_ERROR));

        $response = $this->actingAs($admin)->post(route('admin.products.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Đã nhập 2 dịch vụ từ tệp JSON.');
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', [
            'name' => 'Khám tổng quát',
            'price' => '500000.00',
            'stock' => 10,
            'description' => 'Khám sức khỏe định kỳ.',
        ]);
        $this->assertDatabaseHas('products', [
            'name' => 'Tư vấn dinh dưỡng',
            'price' => '250000.00',
        ]);
    }

    public function test_admin_product_page_displays_the_json_import_form_and_format(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertSee(route('admin.products.import'))
            ->assertSee('multipart/form-data', false)
            ->assertSee('Nhập dịch vụ từ file JSON')
            ->assertSee('"name": "Khám tổng quát"');
    }

    public function test_invalid_json_does_not_create_any_services(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->createWithContent('services.json', '[{"name":');

        $response = $this->from(route('admin.products.index'))
            ->actingAs($admin)
            ->post(route('admin.products.import'), ['file' => $file]);

        $response->assertSessionHasErrors([
            'file' => 'Tệp được tải lên không chứa JSON hợp lệ.',
        ]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_invalid_product_data_rejects_the_entire_json_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->createWithContent('services.json', json_encode([
            ['name' => 'Valid service', 'price' => 500000],
            ['price' => 250000],
        ], JSON_THROW_ON_ERROR));

        $response = $this->from(route('admin.products.index'))
            ->actingAs($admin)
            ->post(route('admin.products.import'), ['file' => $file]);

        $response->assertSessionHasErrors('products.1.name');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_json_import_rejects_unexpected_product_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->createWithContent('services.json', json_encode([
            [
                'name' => 'Service',
                'price' => 500000,
                'is_admin' => true,
            ],
        ], JSON_THROW_ON_ERROR));

        $response = $this->from(route('admin.products.index'))
            ->actingAs($admin)
            ->post(route('admin.products.import'), ['file' => $file]);

        $response->assertSessionHasErrors('products.0');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_json_import_rejects_files_with_a_non_json_extension(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->createWithContent('services.txt', json_encode([
            ['name' => 'Service', 'price' => 500000],
        ], JSON_THROW_ON_ERROR));

        $response = $this->from(route('admin.products.index'))
            ->actingAs($admin)
            ->post(route('admin.products.import'), ['file' => $file]);

        $response->assertSessionHasErrors([
            'file' => 'Chỉ chấp nhận tệp có phần mở rộng .json.',
        ]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_non_admin_cannot_import_services_from_json(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $file = UploadedFile::fake()->createWithContent('services.json', json_encode([
            ['name' => 'Unauthorized service', 'price' => 500000],
        ], JSON_THROW_ON_ERROR));

        $this->actingAs($customer)
            ->post(route('admin.products.import'), ['file' => $file])
            ->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }
}
