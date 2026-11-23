<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Services\MedicineImageService;
use Database\Seeders\IndicativeMedicationPricesSeeder;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class MedicationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'min:2', 'max:100', 'regex:/\A[\pL\pN\s.,\'&()\/-]+\z/u'],
            'catalog_search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');
        $catalogSearch = trim($validated['catalog_search'] ?? '');
        $fdaLabels = [];
        $fdaError = null;

        if (! app()->runningUnitTests() && Medicine::query()->count() < 100 && ! Cache::has('openfda.catalog.syncing')) {
            Cache::put('openfda.catalog.syncing', true, now()->addHour());
            $this->importOpenFdaRecords();
            Cache::forget('openfda.catalog.syncing');
        }

        (new IndicativeMedicationPricesSeeder)->run();

        if ($search !== '') {
            try {
                $response = Http::acceptJson()
                    ->connectTimeout(3)
                    ->timeout(10)
                    ->get('https://api.fda.gov/drug/label.json', [
                        'search' => '(openfda.brand_name:"'.$search.'" OR openfda.generic_name:"'.$search.'")',
                        'limit' => 10,
                    ]);
            } catch (ConnectionException $exception) {
                Log::warning('Không thể kết nối tới openFDA.', ['exception' => $exception::class]);
                $fdaError = 'Không thể kết nối tới openFDA lúc này. Vui lòng thử lại sau.';
            }

            if (isset($response)) {
                if ($response->status() !== 404 && ! $response->successful()) {
                    Log::warning('openFDA trả về lỗi khi tìm nhãn thuốc.', ['status' => $response->status()]);
                    $fdaError = 'openFDA đang gặp sự cố. Vui lòng thử lại sau.';
                } elseif ($response->successful()) {
                    $payload = $response->json();
                    $records = is_array($payload) ? ($payload['results'] ?? null) : null;

                    if (! is_array($records) || count(array_filter($records, 'is_array')) !== count($records)) {
                        Log::warning('openFDA trả về dữ liệu nhãn thuốc không hợp lệ.');
                        $fdaError = 'Dữ liệu nhận được từ openFDA không hợp lệ. Vui lòng thử lại sau.';
                    } else {
                        foreach ($records as $record) {
                            $openFda = is_array($record['openfda'] ?? null) ? $record['openfda'] : [];
                            $fdaLabels[] = [
                                'brand_name' => $this->firstLabelValue($openFda['brand_name'] ?? null),
                                'generic_name' => $this->firstLabelValue($openFda['generic_name'] ?? null),
                                'manufacturer_name' => $this->firstLabelValue($openFda['manufacturer_name'] ?? null),
                                'route' => $this->firstLabelValue($openFda['route'] ?? null),
                                'dosage_form' => $this->firstLabelValue($openFda['dosage_form'] ?? null),
                                'active_ingredient' => $this->firstLabelValue($record['active_ingredient'] ?? null),
                                'purpose' => $this->firstLabelValue($record['purpose'] ?? null),
                                'indications_and_usage' => $this->firstLabelValue($record['indications_and_usage'] ?? null),
                                'warnings' => $this->firstLabelValue($record['warnings'] ?? null),
                                'dosage_and_administration' => $this->firstLabelValue($record['dosage_and_administration'] ?? null),
                            ];
                        }
                    }
                }
            }
        }

        $medicines = Medicine::query()
            ->when($catalogSearch !== '', function ($query) use ($catalogSearch): void {
                $query->where('name', 'like', '%'.$catalogSearch.'%');
            })
            ->orderBy('name')
            ->get();

        return view('admin.medicines.index', compact('medicines', 'search', 'catalogSearch', 'fdaLabels', 'fdaError'));
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $data = $request->validate([
            'strength' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'price_source' => ['nullable', 'url', 'max:2048'],
            'price' => [
                'required',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $price = (string) $value;

                    if (! preg_match('/\A\d+(?:\.\d{1,2})?\z/', $price)) {
                        $fail('Giá thuốc phải có tối đa hai chữ số thập phân và không vượt quá 9.999.999.999,99 đồng.');

                        return;
                    }

                    [$wholePart] = explode('.', $price, 2);
                    $wholePart = ltrim($wholePart, '0');

                    if (strlen($wholePart) > 10) {
                        $fail('Giá thuốc không được vượt quá 9.999.999.999,99 đồng.');
                    }
                },
            ],
        ], [
            'price.required' => 'Vui lòng nhập giá chính thức của thuốc.',
        ]);

        $image = $data['image'] ?? null;
        unset($data['image']);
        $data['price_is_estimate'] = false;
        $data['price_source'] ??= null;

        if ($image) {
            if (Schema::hasColumn('medicines', 'image_path')) {
                $oldImagePath = $medicine->image_path;
                $data['image_path'] = $image->store('medicines', 'public');
                if ($oldImagePath && str_starts_with($oldImagePath, 'medicines/')) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }
        }

        $medicine->update($data);

        return redirect()->route('admin.medicines.index')
            ->with('success', 'Giá thuốc đã được cập nhật.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('medicines', 'name')],
            'strength' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $image = $data['image'] ?? null;
        unset($data['image']);

        $medicineData = [
            'name' => trim($data['name']),
            'strength' => $data['strength'] ?? null,
            'unit' => $data['unit'] ?? null,
        ];
        if (Schema::hasColumn('medicines', 'image_path')) {
            $medicineData['image_path'] = $image?->store('medicines', 'public');
        }
        $medicine = Medicine::create($medicineData);
        (new IndicativeMedicationPricesSeeder)->run();
        app(MedicineImageService::class)->sync($medicine);

        return redirect()->route('admin.medicines.index')
            ->with('success', 'Đã thêm thuốc vào danh mục.');
    }

    public function importOpenFda(): RedirectResponse
    {
        $imported = $this->importOpenFdaRecords();

        return redirect()->route('admin.medicines.index')
            ->with('success', "Đã nhập {$imported} thuốc mới từ openFDA.");
    }

    private function importOpenFdaRecords(): int
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->retry([500, 1000], 100, function (mixed $exception): bool {
                    return $exception instanceof ConnectionException;
                })
                ->get('https://api.fda.gov/drug/label.json', [
                    'limit' => 700,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Không thể kết nối tới openFDA khi nhập danh mục thuốc.', ['exception' => $exception::class]);

            return 0;
        }

        if (! $response->successful()) {
            Log::warning('openFDA trả về lỗi khi nhập danh mục thuốc.', ['status' => $response->status()]);

            return 0;
        }

        $payload = $response->json();
        $records = is_array($payload) ? ($payload['results'] ?? null) : null;

        if (! is_array($records)) {
            Log::warning('openFDA trả về dữ liệu danh mục thuốc không hợp lệ.');

            return 0;
        }

        $now = now();
        $rows = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $openFda = is_array($record['openfda'] ?? null) ? $record['openfda'] : [];
            $name = $this->firstLabelValue($openFda['brand_name'] ?? null)
                ?? $this->firstLabelValue($openFda['generic_name'] ?? null);

            if ($name === null) {
                continue;
            }

            $unit = $this->firstLabelValue($openFda['dosage_form'] ?? null)
                ?? $this->firstLabelValue($record['dosage_form'] ?? null);
            $strength = $this->firstLabelValue($openFda['strength'] ?? null)
                ?? $this->firstLabelValue($record['active_ingredient'] ?? null)
                ?? 'Theo nhãn';

            $rows[mb_strtolower($name)] = [
                'name' => mb_substr(trim($name), 0, 255),
                'unit' => mb_substr(trim($this->normalizeDosageUnit($unit)), 0, 50),
                'strength' => mb_substr(trim($strength), 0, 100),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return 0;
        }

        $before = Medicine::query()->count();
        DB::table('medicines')->insertOrIgnore(array_values($rows));
        $imported = Medicine::query()->count() - $before;
        (new IndicativeMedicationPricesSeeder)->run();
        app(MedicineImageService::class)->syncMissingImages(100);

        return $imported;
    }

    private function firstLabelValue(mixed $values): ?string
    {
        if (is_string($values)) {
            return trim($values) !== '' ? trim($values) : null;
        }

        if (! is_array($values)) {
            return null;
        }

        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function normalizeDosageUnit(?string $unit): string
    {
        $normalized = mb_strtolower(trim((string) $unit));

        return match ($normalized) {
            'tablet', 'tablets', 'tab', 'capsule', 'capsules' => 'viên',
            'powder', 'powders' => 'bột',
            'solution', 'liquid', 'oral solution', 'suspension' => 'ống hoặc chai',
            'cream', 'ointment', 'gel' => 'tuýp',
            'injection', 'injectable', 'intravenous' => 'lọ',
            'patch', 'transdermal patch' => 'miếng',
            'spray', 'aerosol' => 'chai xịt',
            'packet', 'powder for suspension' => 'gói',
            default => $unit === null || trim($unit) === '' ? 'sản phẩm' : mb_substr(trim($unit), 0, 50),
        };
    }
}
