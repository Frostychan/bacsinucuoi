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
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Dịch vụ</th><th>Mô tả</th><th>Giá</th><th>Tồn kho</th><th></th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="product-name">{{ $product->name }}</td>
                        <td>{{ $product->description ?: '—' }}</td>
                        <td class="price">{{ number_format($product->price, 0, ',', '.') }} đ</td>
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
