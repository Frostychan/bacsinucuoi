<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\User;
use App\Services\MedicineImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_can_search_openfda_labels_by_brand_or_generic_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warning = 'FDA label warning <script>alert("unsafe")</script>';

        Http::preventStrayRequests();
        Http::fake([
            'api.fda.gov/drug/label.json*' => Http::response([
                'results' => [[
                    'openfda' => [
                        'brand_name' => ['TYLENOL'],
                        'generic_name' => ['acetaminophen'],
                        'manufacturer_name' => ['Example Pharma'],
                        'route' => ['ORAL'],
                        'dosage_form' => ['TABLET'],
                    ],
                    'active_ingredient' => ['Acetaminophen 500 mg'],
                    'purpose' => ['Pain reliever'],
                    'indications_and_usage' => ['Temporary relief of minor aches and pains.'],
                    'warnings' => [$warning],
                    'dosage_and_administration' => ['Follow the package label.'],
                ]],
            ]),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.medicines.index', ['search' => 'Tylenol']));

        $response->assertOk()
            ->assertSee('Tra cứu nhãn thuốc openFDA')
            ->assertSee('TYLENOL')
            ->assertSee('acetaminophen')
            ->assertSee('Example Pharma')
            ->assertSee('Acetaminophen 500 mg')
            ->assertSee('Temporary relief of minor aches and pains.')
            ->assertSee('Cảnh báo trên nhãn')
            ->assertSee('Hướng dẫn sử dụng trên nhãn')
            ->assertSee('Follow the package label.')
            ->assertSee('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);

        Http::assertSent(function (ClientRequest $request): bool {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && parse_url($request->url(), PHP_URL_HOST) === 'api.fda.gov'
                && parse_url($request->url(), PHP_URL_PATH) === '/drug/label.json'
                && ($query['search'] ?? null) === '(openfda.brand_name:"Tylenol" OR openfda.generic_name:"Tylenol")'
                && ($query['limit'] ?? null) === '10';
        });
    }

    public function test_admin_medicine_page_does_not_call_openfda_without_a_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Http::preventStrayRequests();

        $this->actingAs($admin)
            ->get(route('admin.medicines.index'))
            ->assertOk()
            ->assertSee('Tra cứu nhãn thuốc openFDA')
            ->assertSee('Paracetamol');

        Http::assertNothingSent();
    }

    public function test_openfda_no_matches_are_shown_as_an_empty_search_result(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Http::preventStrayRequests();
        Http::fake([
            'api.fda.gov/drug/label.json*' => Http::response([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'No matches found!'],
            ], 404),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.medicines.index', ['search' => 'UnknownDrug']))
            ->assertOk()
            ->assertSee('Không tìm thấy nhãn thuốc phù hợp');
    }

    public function test_openfda_service_failure_is_shown_to_the_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Http::preventStrayRequests();
        Http::fake([
            'api.fda.gov/drug/label.json*' => Http::response(['error' => 'Unavailable'], 503),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.medicines.index', ['search' => 'Tylenol']))
            ->assertOk()
            ->assertSee('openFDA đang gặp sự cố. Vui lòng thử lại sau.');
    }

    public function test_openfda_connection_failure_is_shown_to_the_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Http::preventStrayRequests();
        Http::fake([
            'api.fda.gov/drug/label.json*' => Http::failedConnection(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.medicines.index', ['search' => 'Tylenol']))
            ->assertOk()
            ->assertSee('Không thể kết nối tới openFDA lúc này. Vui lòng thử lại sau.');
    }

    public function test_openfda_search_rejects_query_syntax_instead_of_sending_it_to_the_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Http::preventStrayRequests();

        $this->actingAs($admin)
            ->get(route('admin.medicines.index', ['search' => 'Tylenol" OR *']))
            ->assertRedirect()
            ->assertSessionHasErrors('search');

        Http::assertNothingSent();
    }

    public function test_non_admin_cannot_search_openfda_labels(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        Http::preventStrayRequests();

        $this->actingAs($customer)
            ->get(route('admin.medicines.index', ['search' => 'Tylenol']))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_admin_can_import_openfda_medicines_without_overwriting_existing_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $paracetamol = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();
        $paracetamol->update(['price' => '1250.50', 'unit' => 'viên']);

        Http::preventStrayRequests();
        Http::fake([
            'api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'openfda' => [
                            'brand_name' => ['Paracetamol'],
                            'dosage_form' => ['TABLET'],
                        ],
                    ],
                    [
                        'openfda' => [
                            'brand_name' => ['Tylenol'],
                            'generic_name' => ['Acetaminophen'],
                            'dosage_form' => ['TABLET'],
                        ],
                    ],
                    [
                        'openfda' => [
                            'generic_name' => ['Metformin'],
                            'dosage_form' => ['TABLET'],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.medicines.import-openfda'))
            ->assertRedirect(route('admin.medicines.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medicines', [
            'name' => 'Tylenol',
            'unit' => 'viên',
        ]);
        $this->assertDatabaseHas('medicines', [
            'name' => 'Metformin',
            'unit' => 'viên',
        ]);
        $this->assertDatabaseHas('medicines', [
            'id' => $paracetamol->id,
            'name' => 'Paracetamol',
            'price' => '1250.50',
            'unit' => 'viên',
        ]);

        Http::assertSent(function (ClientRequest $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return parse_url($request->url(), PHP_URL_HOST) === 'api.fda.gov'
                && parse_url($request->url(), PHP_URL_PATH) === '/drug/label.json'
                && ($query['limit'] ?? null) === '700';
        });
    }

    public function test_non_admin_cannot_import_openfda_medicines(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        Http::preventStrayRequests();

        $this->actingAs($customer)
            ->post(route('admin.medicines.import-openfda'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_admin_can_add_medicine_to_catalog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.medicines.store'), [
                'name' => 'Efferalgan',
                'strength' => '500 mg',
                'unit' => 'viên',
            ])
            ->assertRedirect(route('admin.medicines.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medicines', [
            'name' => 'Efferalgan',
            'strength' => '500 mg',
            'unit' => 'viên',
        ]);
    }

    public function test_admin_can_upload_medicine_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $medicine = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.medicines.update', $medicine), [
                'strength' => '500 mg',
                'unit' => 'viên',
                'price' => '650',
                'image' => UploadedFile::fake()->image('paracetamol.jpg'),
            ])
            ->assertRedirect(route('admin.medicines.index'))
            ->assertSessionHas('success');

        $medicine->refresh();
        $this->assertNotNull($medicine->image_path);
        Storage::disk('public')->assertExists($medicine->image_path);
    }

    public function test_rximage_sync_saves_medicine_image_and_preserves_existing_image(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'rximage.nlm.nih.gov/api/rximage/1/rxnav*' => Http::response([
                'nlmRxImages' => [
                    ['imageUrl' => 'https://rximage.nlm.nih.gov/example/paracetamol.jpg'],
                ],
            ]),
            'rximage.nlm.nih.gov/example/paracetamol.jpg' => Http::response(
                "fake-image",
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);

        $medicine = Medicine::query()->where('name', 'Paracetamol')->firstOrFail();
        $service = app(MedicineImageService::class);

        $this->assertTrue($service->sync($medicine));
        $medicine->refresh();
        $this->assertNotNull($medicine->image_path);
        Storage::disk('public')->assertExists($medicine->image_path);

        $existingPath = $medicine->image_path;
        $this->assertFalse($service->sync($medicine));
        $this->assertSame($existingPath, $medicine->fresh()->image_path);
    }

    public function test_admin_medicine_catalog_rejects_duplicate_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.medicines.store'), [
                'name' => 'Paracetamol',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('medicines', 20);
    }
}
