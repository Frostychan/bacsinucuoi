<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicationController extends Controller
{
    public function index(): View
    {
        $medicines = Medicine::query()->orderBy('name')->get();

        return view('admin.medicines.index', compact('medicines'));
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $data = $request->validate([
            'strength' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
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

        $data['price_is_estimate'] = false;
        $data['price_source'] ??= null;
        $medicine->update($data);

        return redirect()->route('admin.medicines.index')
            ->with('success', 'Giá thuốc đã được cập nhật.');
    }
}
