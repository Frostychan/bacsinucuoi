@extends('layouts.app')

@section('title', 'Bác sĩ')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">Đội ngũ chuyên môn</p>
        <h1>Bác sĩ</h1>
        <p class="subtitle">Khám phá đội ngũ bác sĩ đang tiếp nhận lịch hẹn.</p>
    </div>
</div>

<div class="catalog-grid doctor-catalog-grid" role="region" aria-label="Danh sách bác sĩ">
    @forelse ($doctors as $doctor)
        <article class="card catalog-doctor-card">
            <div class="catalog-doctor-photo-wrap">
                @if ($doctor->image_path)
                    <img class="catalog-doctor-photo" src="{{ str_starts_with($doctor->image_path, 'images/') ? asset($doctor->image_path) : asset('storage/'.$doctor->image_path) }}" alt="Ảnh minh họa bác sĩ {{ $doctor->user->name }}" loading="lazy">
                @else
                    <div class="catalog-doctor-photo catalog-doctor-placeholder" role="img" aria-label="Chưa có ảnh bác sĩ {{ $doctor->user->name }}">{{ strtoupper(substr($doctor->user->name, 0, 1)) }}</div>
                @endif
            </div>
            <div class="catalog-doctor-info">
                <p class="doctor-specialty">{{ $doctor->specialty?->name ?: 'Bác sĩ chuyên khoa' }}</p>
                <h2>{{ $doctor->user->name }}</h2>
                <p class="catalog-doctor-bio">{{ $doctor->bio ?: 'Tận tâm đồng hành cùng người bệnh.' }}</p>
                <div class="catalog-doctor-footer"><span class="stock">Đang nhận lịch</span><strong class="price">{{ number_format($doctor->consultation_fee, 0, ',', '.') }} đ</strong></div>
            </div>
        </article>
    @empty
        <div class="card empty-state">Chưa có bác sĩ đang nhận lịch.</div>
    @endforelse
</div>
@endsection
