@extends('layouts.app')

@section('title', 'Chuyên khoa')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">Chăm sóc chuyên sâu</p>
        <h1>Chuyên khoa</h1>
        <p class="subtitle">Tìm đúng chuyên khoa cho nhu cầu chăm sóc sức khỏe của bạn.</p>
    </div>
</div>

<div class="catalog-grid">
    @forelse ($specialties as $specialty)
        <article class="card catalog-card">
            <div class="catalog-icon">✚</div>
            <div>
                <h2>{{ $specialty->name }}</h2>
                <p>{{ $specialty->description ?: 'Đội ngũ bác sĩ sẵn sàng đồng hành cùng bạn.' }}</p>
                <span class="stock">{{ $specialty->doctors_count }} bác sĩ</span>
            </div>
        </article>
    @empty
        <div class="card empty-state">Chưa có chuyên khoa đang hoạt động.</div>
    @endforelse
</div>
@endsection
