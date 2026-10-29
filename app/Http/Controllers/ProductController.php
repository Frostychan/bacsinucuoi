<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::all();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->productRules(), [
            'price.max' => 'Giá dịch vụ không được vượt quá 9.999.999.999.999,99 đồng.',
        ]);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Dịch vụ đã được tạo.');
    }

    public function show(string $id): View
    {
        $product = Product::findOrFail($id);

        return view('products.show', compact('product'));
    }

    public function edit(string $id): View
    {
        $product = Product::findOrFail($id);

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate($this->productRules(), [
            'price.max' => 'Giá dịch vụ không được vượt quá 9.999.999.999.999,99 đồng.',
        ]);
        $product = Product::findOrFail($id);
        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Dịch vụ đã được cập nhật.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Dịch vụ đã được xóa.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => [
                'required',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $price = (string) $value;

                    if (! preg_match('/\A\d+(?:\.\d{1,2})?\z/', $price)) {
                        $fail('Giá dịch vụ phải có tối đa hai chữ số thập phân và không vượt quá 9.999.999.999.999,99 đồng.');

                        return;
                    }

                    [$wholePart] = explode('.', $price, 2);
                    $wholePart = ltrim($wholePart, '0');

                    if (strlen($wholePart) > 13) {
                        $fail('Giá dịch vụ không được vượt quá 9.999.999.999.999,99 đồng.');
                    }
                },
            ],
            'stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
