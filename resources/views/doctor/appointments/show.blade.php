@extends('layouts.app')
@section('title', 'Xử lý lịch hẹn')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Lịch hẹn #{{ $appointment->id }}</p><h1>{{ $appointment->patient->name }}</h1><p class="subtitle">{{ $appointment->starts_at->format('d/m/Y H:i') }} · {{ $appointment->reason }}</p></div></div>
<section class="card patient-contact-card">
    <div class="section-title"><div><h2>Thông tin khách hàng</h2><p>Thông tin do khách hàng cung cấp trong hồ sơ cá nhân.</p></div></div>
    <div class="patient-contact-grid">
        <div><span class="detail-label">Số điện thoại</span><strong>{{ $appointment->patient->phone ?: 'Chưa cung cấp' }}</strong></div>
        <div><span class="detail-label">Ngày sinh</span><strong>{{ $appointment->patient->date_of_birth?->format('d/m/Y') ?? 'Chưa cung cấp' }}</strong></div>
        <div><span class="detail-label">Giới tính</span><strong>{{ ['female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác', 'prefer_not_to_say' => 'Không muốn tiết lộ'][$appointment->patient->gender] ?? 'Chưa cung cấp' }}</strong></div>
        <div class="patient-contact-full"><span class="detail-label">Địa chỉ</span><strong>{{ $appointment->patient->address ?: 'Chưa cung cấp' }}</strong></div>
    </div>
    <div class="emergency-contact">
        <h3>Người liên hệ khẩn cấp</h3>
        <div class="patient-contact-grid">
            <div><span class="detail-label">Họ tên</span><strong>{{ $appointment->patient->emergency_contact_name ?: 'Chưa cung cấp' }}</strong></div>
            <div><span class="detail-label">Số điện thoại</span><strong>{{ $appointment->patient->emergency_contact_phone ?: 'Chưa cung cấp' }}</strong></div>
            <div><span class="detail-label">Mối quan hệ</span><strong>{{ $appointment->patient->emergency_contact_relationship ?: 'Chưa cung cấp' }}</strong></div>
        </div>
    </div>
</section>
<div class="card form-card">
    <form method="POST" action="{{ route('doctor.appointments.status', $appointment) }}">
        @csrf
        @method('PATCH')

        <div class="form-group">
            <span class="form-label">Cập nhật trạng thái</span>
            <div class="appointment-status-actions" role="group" aria-label="Cập nhật trạng thái lịch hẹn">
                <button class="btn btn-status-confirmed" type="submit" name="status" value="confirmed">Xác nhận</button>
                <button class="btn btn-status-completed" type="submit" name="status" value="completed">Hoàn tất</button>
                <button class="btn btn-status-cancelled" type="submit" name="status" value="cancelled">Hủy lịch</button>
            </div>
        </div>

        <div class="form-group">
            <label for="doctor_notes">Ghi chú gửi bệnh nhân</label>
            <textarea class="form-control" id="doctor_notes" name="doctor_notes" rows="4" maxlength="5000">{{ old('doctor_notes', $appointment->doctor_notes ?? $appointment->medicalRecord?->notes) }}</textarea>
            <p class="form-help">Bệnh nhân có thể xem ghi chú này trong chi tiết lịch khám.</p>
        </div>

        <div class="form-group">
            <label for="cancellation_reason">Lý do hủy (nếu có)</label>
            <textarea class="form-control" id="cancellation_reason" name="cancellation_reason">{{ old('cancellation_reason', $appointment->cancellation_reason) }}</textarea>
        </div>

        <div class="form-actions">
            <button class="btn btn-ghost" type="submit">Lưu ghi chú</button>
        </div>
    </form>

    @if ($appointment->status === 'completed')
        <div class="form-actions">
            <a class="btn btn-ghost" href="{{ route('doctor.medical-records.create', $appointment) }}">Lập hồ sơ y tế</a>
        </div>
    @endif
</div>
@endsection
