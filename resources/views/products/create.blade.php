@extends('layouts.app')
@section('title', 'Thêm dịch vụ')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Quản trị / Dịch vụ</p><h1>Thêm dịch vụ</h1><p class="subtitle">Tạo dịch vụ mới cho hệ thống MedCare.</p></div></div>
    <div class="card form-card">
        <form action="{{ route('admin.products.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group full"><label for="name">Tên dịch vụ <span class="required">*</span></label><input id="name" type="text" name="name" class="form-control" placeholder="Khám tổng quát" value="{{ old('name') }}" required></div>
                <div class="form-group"><label for="price">Giá dịch vụ <span class="required">*</span></label><input id="price" type="number" name="price" min="0" max="9999999999999.99" step="0.01" class="form-control" placeholder="0" value="{{ old('price') }}" required><p class="form-help">Đơn vị: Việt Nam đồng. Tối đa 9.999.999.999.999,99 đồng.</p><x-input-error :messages="$errors->get('price')" class="form-help" /></div>
                <div class="form-group"><label for="stock">Số lượng khả dụng</label><input id="stock" type="number" name="stock" min="0" class="form-control" value="{{ old('stock', 0) }}"></div>
                <div class="form-group full"><label for="description">Mô tả dịch vụ</label><textarea id="description" name="description" rows="5" class="form-control" placeholder="Mô tả dịch vụ...">{{ old('description') }}</textarea></div>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary">Lưu dịch vụ</button><a href="{{ route('admin.products.index') }}" class="btn btn-ghost">Hủy bỏ</a></div>
        </form>
    </div>
@endsection
