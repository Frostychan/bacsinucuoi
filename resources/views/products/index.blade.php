@extends('layouts.app')
@section('title', 'Quản lý dịch vụ')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Quản trị</p>
            <h1>Quản lý dịch vụ</h1>
            <p class="subtitle">Thêm, sửa, xóa và cập nhật giá dịch vụ của MedCare.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">＋ Thêm dịch vụ</a>
    </div>
    <div class="card form-card" style="max-width: none; margin-bottom: 24px;">
        <h2>Nhập dịch vụ từ file JSON</h2>
        <p class="form-help">Tệp phải là danh sách JSON gồm tối đa 500 dịch vụ. Mỗi dịch vụ cần có <code>name</code> và <code>price</code>; <code>stock</code> và <code>description</code> là tùy chọn. Tối đa 2 MB.</p>
        <pre class="form-help" style="white-space: pre-wrap; color: var(--ink); background: #f7fbfc; padding: 14px; border-radius: 9px;">[
  {"name": "Khám tổng quát", "price": 500000, "stock": 10, "description": "Mô tả dịch vụ"}
]</pre>
        <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="products-json">File JSON</label>
                <input id="products-json" type="file" name="file" accept=".json,application/json" class="form-control" required>
                <x-input-error :messages="$errors->get('file')" class="form-help" />
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary">Nhập dịch vụ</button></div>
        </form>
    </div>
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Dịch vụ</th><th>Mô tả</th><th>Giá</th><th>Tồn kho</th><th></th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="product-name">{{ $product->name }}</td>
                        <td>{{ $product->description ?: '—' }}</td>
                        <td class="price">{{ number_format($product->price, 0, ',', '.') }}&nbsp;đ</td>
                        <td><span class="stock {{ $product->stock < 1 ? 'empty' : '' }}">{{ $product->stock }}</span></td>
                        <td><div class="actions"><a href="{{ route('admin.products.edit', $product) }}" class="btn btn-warning">Sửa</a><form method="POST" action="{{ route('admin.products.destroy', $product) }}">@csrf @method('DELETE')<button class="btn btn-danger" onclick="return confirm('Xóa dịch vụ này?')">Xóa</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><strong>Chưa có dịch vụ</strong><p>Hãy thêm dịch vụ đầu tiên.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
