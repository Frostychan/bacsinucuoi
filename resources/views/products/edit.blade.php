@extends('layouts.app')
@section('title', 'Sửa dịch vụ')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Quản trị / Dịch vụ</p><h1>Sửa dịch vụ</h1><p class="subtitle">Cập nhật thông tin cho <strong>{{ $product->name }}</strong>.</p></div></div>
    <div class="card form-card">
        <form action="{{ route('admin.products.update', $product) }}" method="POST">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="form-group full"><label for="name">Tên dịch vụ <span class="required">*</span></label><input id="name" type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required></div>
                <div class="form-group"><label for="price">Giá dịch vụ <span class="required">*</span></label><input id="price" type="number" name="price" min="0" max="9999999999999.99" step="0.01" value="{{ old('price', $product->price) }}" class="form-control" required><p class="form-help">Tối đa 9.999.999.999.999,99 đồng.</p><x-input-error :messages="$errors->get('price')" class="form-help" /></div>
                <div class="form-group"><label for="stock">Số lượng khả dụng</label><input id="stock" type="number" name="stock" min="0" value="{{ old('stock', $product->stock) }}" class="form-control"></div>
                <div class="form-group full"><label for="description">Mô tả dịch vụ</label><textarea id="description" name="description" rows="5" class="form-control">{{ old('description', $product->description) }}</textarea></div>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary">Lưu thay đổi</button><a href="{{ route('admin.products.index') }}" class="btn btn-ghost">Quay lại</a></div>
        </form>
    </div>
@endsection
