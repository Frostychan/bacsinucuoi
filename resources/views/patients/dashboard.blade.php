@extends('layouts.app')
@section('title', 'Chăm sóc sức khỏe cùng MedCare')
@section('content')
<section class="patient-hero">
    <div class="patient-hero-copy">
        <p class="eyebrow">Chăm sóc sức khỏe, bắt đầu từ hôm nay</p>
        <h1>
            @if ($adminPreview ?? false)
                Giới thiệu MedCare
            @else
                Xin chào, {{ auth()->user()->name }}. MedCare luôn đồng hành cùng bạn.
            @endif
        </h1>
        <p class="patient-hero-description">Tìm bác sĩ phù hợp, khám phá các chuyên khoa và chủ động đặt lịch khám trong một không gian chăm sóc sức khỏe đáng tin cậy.</p>
        @if ($adminPreview ?? false)
            <p class="patient-hero-description">Đây là bản xem trước trang tổng quan dành cho bệnh nhân.</p>
        @else
            <div class="patient-hero-actions">
                <a class="btn btn-primary" href="{{ route('patient.appointments.create') }}">Đặt lịch khám</a>
                <a class="btn btn-hero-ghost" href="#doctors">Tìm hiểu đội ngũ bác sĩ</a>
            </div>
        @endif
    </div>
    <div class="patient-hero-art" aria-hidden="true">
        <div class="hero-cross">✚</div>
        <div class="hero-note"><span class="hero-note-icon">✓</span><span><strong>Chăm sóc tận tâm</strong><small>Kết nối bạn với đúng chuyên gia</small></span></div>
    </div>
</section>

<section class="about-section" aria-labelledby="about-title">
    <div class="section-heading">
        <div><p class="eyebrow">Về MedCare</p><h2 id="about-title">Chăm sóc thuận tiện, an tâm hơn</h2></div>
        <p>MedCare giúp bạn chủ động tìm kiếm dịch vụ y tế và theo dõi lịch khám của mình dễ dàng.</p>
    </div>
    <div class="benefit-grid">
        <article class="card benefit-card"><span class="benefit-icon">⌕</span><h3>Tìm đúng chuyên gia</h3><p>Khám phá đội ngũ bác sĩ và lựa chọn người phù hợp với nhu cầu chăm sóc của bạn.</p></article>
        <article class="card benefit-card"><span class="benefit-icon">◷</span><h3>Đặt lịch thuận tiện</h3><p>Gửi yêu cầu đặt lịch trực tuyến và xem thông tin lịch hẹn ngay trong tài khoản.</p></article>
        <article class="card benefit-card"><span class="benefit-icon">♡</span><h3>Đồng hành tận tâm</h3><p>Thông tin rõ ràng giúp bạn an tâm hơn trong từng bước chăm sóc sức khỏe.</p></article>
    </div>
</section>

<section id="doctors" class="doctor-section" aria-labelledby="doctors-title">
    <div class="section-heading">
        <div><p class="eyebrow">Đội ngũ chuyên môn</p><h2 id="doctors-title">Bác sĩ đang nhận lịch</h2></div>
    </div>
    <div class="doctor-showcase" role="region" aria-label="Danh sách bác sĩ đang nhận lịch">
        @forelse ($doctors as $doctor)
            <article class="card doctor-showcase-card">
                <div class="doctor-photo-wrap">
                    @if ($doctor->doctorProfile?->image_path)
                        <img class="doctor-photo" src="{{ str_starts_with($doctor->doctorProfile->image_path, 'images/') ? asset($doctor->doctorProfile->image_path) : asset('storage/'.$doctor->doctorProfile->image_path) }}" alt="Ảnh minh họa bác sĩ {{ $doctor->name }}" loading="lazy">
                    @else
                        <div class="doctor-photo doctor-photo-placeholder" role="img" aria-label="Chưa có ảnh bác sĩ {{ $doctor->name }}">{{ strtoupper(substr($doctor->name, 0, 1)) }}</div>
                    @endif
                    <span class="doctor-availability">Đang nhận lịch</span>
                </div>
                <div class="doctor-showcase-info">
                    <p class="doctor-specialty">{{ $doctor->doctorProfile?->specialty?->name ?? 'Bác sĩ đa khoa' }}</p>
                    <h3>{{ $doctor->name }}</h3>
                    <p class="doctor-bio">{{ $doctor->doctorProfile?->bio ?: 'Tận tâm lắng nghe và đồng hành cùng bạn trong hành trình chăm sóc sức khỏe.' }}</p>
                    <p class="doctor-fee">Phí khám <strong>{{ number_format($doctor->doctorProfile?->consultation_fee ?? 0, 0, ',', '.') }} đ</strong></p>
                    @unless ($adminPreview ?? false)
                        <a class="btn btn-ghost doctor-book-link" href="{{ route('patient.appointments.create', ['doctor_id' => $doctor->id]) }}">Đặt lịch với bác sĩ</a>
                    @endunless
                </div>
            </article>
        @empty
            <div class="card empty-state">Hiện chưa có bác sĩ nhận lịch. Vui lòng quay lại sau.</div>
        @endforelse
    </div>
    @if ($hasMoreDoctors ?? false)
        <div class="doctor-showcase-more">
            <a class="btn btn-ghost" href="{{ route('catalog.doctors') }}">Xem thêm bác sĩ <span aria-hidden="true">→</span></a>
        </div>
    @endif
</section>

@unless ($adminPreview ?? false)
    <section class="patient-booking-banner">
        <div><p class="eyebrow">Bạn đã sẵn sàng?</p><h2>Chọn thời gian khám phù hợp với bạn</h2><p>Gửi yêu cầu đặt lịch trực tuyến chỉ với vài bước đơn giản.</p></div>
        <a class="btn btn-primary" href="{{ route('patient.appointments.create') }}">Bắt đầu đặt lịch <span aria-hidden="true">→</span></a>
    </section>
@endunless

@unless ($adminPreview ?? false)
    <section class="recent-appointments">
    <div class="section-heading">
        <div><p class="eyebrow">Theo dõi sức khỏe</p><h2>Lịch hẹn gần đây</h2></div>
        <a class="section-link" href="{{ route('patient.appointments.index') }}">Xem tất cả <span aria-hidden="true">→</span></a>
    </div>
    <div class="recent-appointments-grid">
        @php
            $statusLabels = [
                'pending' => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận',
                'completed' => 'Đã hoàn thành',
                'cancelled' => 'Đã hủy',
            ];
        @endphp
        @forelse ($appointments as $appointment)
            <article class="card recent-appointment-card">
                <div class="appointment-date-badge">
                    <span>{{ $appointment->starts_at->format('d') }}</span>
                    <small>{{ $appointment->starts_at->translatedFormat('M') }}</small>
                </div>
                <div class="recent-appointment-details">
                    <div class="recent-appointment-topline">
                        <p class="recent-appointment-time">{{ $appointment->starts_at->format('H:i') }} · Lịch khám</p>
                        <span class="appointment-status appointment-status-{{ $appointment->status }}">{{ $statusLabels[$appointment->status] ?? 'Đang cập nhật' }}</span>
                    </div>
                    <h3>{{ $appointment->doctor->name }}</h3>
                    <p class="recent-appointment-reason">{{ $appointment->reason }}</p>
                    <div class="recent-appointment-footer">
                        <span class="recent-appointment-fee">{{ number_format($appointment->consultation_fee ?? 0, 0, ',', '.') }} đ</span>
                        <a href="{{ route('patient.appointments.show', $appointment) }}" class="recent-appointment-link" aria-label="Xem lịch hẹn với {{ $appointment->doctor->name }}">Chi tiết <span aria-hidden="true">→</span></a>
                    </div>
                </div>
            </article>
        @empty
            <div class="card empty-state">Bạn chưa có lịch hẹn nào. Hãy đặt lịch khi đã sẵn sàng.</div>
        @endforelse
    </div>
    </section>
@endunless
@endsection
