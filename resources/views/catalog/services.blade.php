@extends('layouts.app')

@section('title', 'Dịch vụ')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">MedCare</p>
        <h1>Dịch vụ</h1>
        <p class="subtitle">Các sản phẩm và dịch vụ hiện đang được cung cấp.</p>
    </div>
</div>

<div class="catalog-grid">
    @forelse ($services as $service)
        <article class="card catalog-card">
            <div class="catalog-icon">▤</div>
            <div>
                <h2>{{ $service->name }}</h2>
                <p>{{ $service->description ?: 'Thông tin dịch vụ đang được cập nhật.' }}</p>
                <strong class="price">{{ number_format($service->price, 0, ',', '.') }} đ</strong>
            </div>
        </article>
    @empty
        <div class="card empty-state">Chưa có dịch vụ khả dụng.</div>
    @endforelse
</div>
@endsection
