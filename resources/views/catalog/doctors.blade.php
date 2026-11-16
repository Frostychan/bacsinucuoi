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
        <details class="card catalog-doctor-card">
            <summary class="catalog-doctor-summary" aria-label="Xem thông tin bác sĩ {{ $doctor->user->name }}">
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
                    <span class="catalog-doctor-summary-hint">
                        Xem thông tin bác sĩ
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </span>
                </div>
            </summary>
            <div class="catalog-doctor-details">
                <dl class="catalog-doctor-facts">
                    <div>
                        <dt>Chuyên khoa</dt>
                        <dd>{{ $doctor->specialty?->name ?: 'Chưa cập nhật' }}</dd>
                    </div>
                    <div>
                        <dt>Số giấy phép hành nghề</dt>
                        <dd>{{ $doctor->license_number ?: 'Chưa cập nhật' }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><a href="mailto:{{ $doctor->user->email }}">{{ $doctor->user->email }}</a></dd>
                    </div>
                    <div>
                        <dt>Số điện thoại</dt>
                        <dd>
                            @if ($doctor->user->phone)
                                <a href="tel:{{ $doctor->user->phone }}">{{ $doctor->user->phone }}</a>
                            @else
                                Chưa cập nhật
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Thời lượng khám</dt>
                        <dd>{{ $doctor->slot_duration }} phút</dd>
                    </div>
                </dl>
                <p class="catalog-doctor-bio">{{ $doctor->bio ?: 'Tận tâm đồng hành cùng người bệnh.' }}</p>
                <div class="catalog-doctor-footer">
                    <span class="stock">Đang nhận lịch</span>
                    <strong class="price">{{ number_format($doctor->consultation_fee, 0, ',', '.') }} đ / lượt</strong>
                </div>
                <a class="btn btn-primary catalog-doctor-booking" href="{{ route('patient.appointments.create', ['doctor_id' => $doctor->user_id]) }}">
                    Đặt lịch với bác sĩ
                </a>
            </div>
        </details>
    @empty
        <div class="card empty-state">Chưa có bác sĩ đang nhận lịch.</div>
    @endforelse
</div>
@endsection
