@extends('layouts.app')
@section('title', 'Chi tiết lịch khám')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Quản trị / Dịch vụ</p><h1>{{ $product->name }}</h1><p class="subtitle">Chi tiết dịch vụ.</p></div><a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary">Sửa dịch vụ</a></div>
    <div class="card detail-card">
        <div class="detail-row"><span class="detail-label">Tên dịch vụ</span><span class="detail-value">{{ $product->name }}</span></div>
        <div class="detail-row"><span class="detail-label">Giá dịch vụ</span><span class="detail-value price">{{ number_format($product->price, 0, ',', '.') }} đ</span></div>
        <div class="detail-row"><span class="detail-label">Số lượng khả dụng</span><span class="detail-value">{{ $product->stock }}</span></div>
        <div class="detail-row"><span class="detail-label">Mô tả</span><span class="detail-value">{{ $product->description ?: 'Chưa có thông tin.' }}</span></div>
        <div class="detail-row"><span class="detail-label">Ngày cập nhật</span><span class="detail-value">{{ $product->updated_at->format('d/m/Y H:i') }}</span></div>
    </div>
    <div style="margin-top: 18px;"><a href="{{ route('admin.products.index') }}" class="btn btn-ghost">← Trở về danh sách</a></div>
@endsection
