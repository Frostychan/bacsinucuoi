<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Database\Seeders\MedicalServiceCatalogSeeder;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class ProductController extends Controller
{
    public function index(): View
    {
        if (Product::query()->count() < 300) {
            (new MedicalServiceCatalogSeeder)->run();
        }

        $products = Product::query()->orderBy('name')->get();

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

    public function import(Request $request): RedirectResponse
    {
        $validatedFile = $request->validate([
            'file' => [
                'required',
                'file',
                'mimetypes:application/json,application/x-json,text/json,text/plain',
                'extensions:json',
                'max:2048',
            ],
        ], [
            'file.required' => 'Vui lòng chọn tệp JSON cần nhập.',
            'file.file' => 'Tệp được tải lên không hợp lệ.',
            'file.extensions' => 'Chỉ chấp nhận tệp có phần mở rộng .json.',
            'file.max' => 'Dung lượng tệp JSON không được vượt quá 2 MB.',
        ]);

        try {
            $products = json_decode($validatedFile['file']->get(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'file' => 'Tệp được tải lên không chứa JSON hợp lệ.',
            ]);
        }

        if (! is_array($products) || ! array_is_list($products)) {
            throw ValidationException::withMessages([
                'file' => 'JSON phải có dạng danh sách sản phẩm.',
            ]);
        }

        $validatedProducts = Validator::make(
            ['products' => $products],
            [
                'products' => ['required', 'array', 'min:1', 'max:500'],
                'products.*' => ['required', 'array:name,price,stock,description'],
                ...$this->productRules('products.*.'),
                'products.*.stock' => ['sometimes', 'integer', 'min:0', 'max:2147483647'],
            ],
            [
                'products.min' => 'Tệp JSON phải có ít nhất một sản phẩm.',
                'products.max' => 'Chỉ được nhập tối đa 500 sản phẩm trong một lần.',
                'products.*.array' => 'Mỗi sản phẩm chỉ được có các trường name, price, stock và description.',
                'products.*.stock.max' => 'Số lượng mỗi sản phẩm không được vượt quá 2.147.483.647.',
            ],
        )->validate()['products'];

        DB::transaction(function () use ($validatedProducts): void {
            foreach ($validatedProducts as $product) {
                Product::create($product);
            }
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã nhập '.count($validatedProducts).' dịch vụ từ tệp JSON.');
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
     * @return array<string, array<int, Closure|string>>
     */
    private function productRules(string $prefix = ''): array
    {
        return [
            $prefix.'name' => ['required', 'string', 'max:255'],
            $prefix.'price' => [
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
            $prefix.'stock' => ['nullable', 'integer', 'min:0'],
            $prefix.'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
