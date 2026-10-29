@extends('layouts.app')
@section('title', 'Chỉnh sửa lịch hẹn')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Quản trị · Lịch hẹn #{{ $appointment->id }}</p><h1>Chỉnh sửa lịch hẹn</h1><p class="subtitle">Bệnh nhân: {{ $appointment->patient->name }}</p></div>
</div>

<section class="card symptom-context">
    <p class="eyebrow">Thông tin để phân công bác sĩ</p>
    <h2>Triệu chứng / lý do khám</h2>
    <p>{{ $appointment->reason }}</p>
    <h3>Ghi chú của bệnh nhân</h3>
    <p>{{ $appointment->notes ?: 'Bệnh nhân không có ghi chú thêm.' }}</p>
    <small>Admin tự đánh giá thông tin và chọn bác sĩ phù hợp; hệ thống không tự chẩn đoán triệu chứng.</small>
</section>

<section class="card appointment-edit-card">
    <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}"
        data-appointment-picker data-except-appointment="{{ $appointment->id }}"
        data-current-doctor="{{ $appointment->doctor_id }}"
        data-current-starts-at="{{ $appointment->starts_at->format('Y-m-d\TH:i') }}"
        data-old-starts-at="{{ old('starts_at', $appointment->starts_at->format('Y-m-d\TH:i')) }}">
        @csrf
        @method('PATCH')
        <div class="form-grid">
            <div class="form-group">
                <label for="doctor_id">Bác sĩ <span class="required">*</span></label>
                <select class="form-control" id="doctor_id" name="doctor_id" required>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" data-availability-url="{{ route('booking.doctors.availability', $doctor) }}"
                            @selected(old('doctor_id', $appointment->doctor_id) == $doctor->id)>
                            {{ $doctor->name }} — {{ $doctor->doctorProfile?->specialty?->name ?? 'Chưa phân chuyên khoa' }}
                            ({{ number_format($doctor->doctorProfile?->consultation_fee ?? 0, 0, ',', '.') }} đ)
                        </option>
                    @endforeach
                </select>
                @error('doctor_id') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="appointment_date">Ngày khám <span class="required">*</span></label>
                <input class="form-control" id="appointment_date" type="date" name="appointment_date"
                    value="{{ old('appointment_date', $appointment->starts_at->format('Y-m-d')) }}" required>
                @error('appointment_date') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="starts_at">Khung giờ của bác sĩ <span class="required">*</span></label>
                <select class="form-control" id="starts_at" name="starts_at" required disabled
                    data-availability-slots data-error="{{ $errors->first('starts_at') }}">
                    <option value="">Đang tải giờ trống...</option>
                </select>
                @error('starts_at') <p class="form-help booking-error">{{ $message }}</p> @enderror
                <p class="form-help" data-availability-message aria-live="polite">Giờ khám 08:00–17:00, không nghỉ trưa.</p>
            </div>
            <div class="form-group">
                <label for="patient_age">Tuổi bệnh nhân <span class="required">*</span></label>
                <input class="form-control" id="patient_age" type="number" name="patient_age" min="0" max="120" value="{{ old('patient_age', $appointment->patient_age) }}" required>
            </div>
            <div class="form-group">
                <label for="status">Trạng thái <span class="required">*</span></label>
                <select class="form-control" id="status" name="status" required>
                    @foreach (['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'completed' => 'Đã hoàn thành', 'cancelled' => 'Đã hủy'] as $status => $label)
                        <option value="{{ $status }}" @selected(old('status', $appointment->status) === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group full">
                <label for="reason">Lý do khám <span class="required">*</span></label>
                <input class="form-control" id="reason" name="reason" maxlength="500" value="{{ old('reason', $appointment->reason) }}" required>
            </div>
            <div class="form-group full">
                <label for="notes">Ghi chú</label>
                <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $appointment->notes) }}</textarea>
            </div>
            <div class="form-group full">
                <label for="cancellation_reason">Lý do hủy</label>
                <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3">{{ old('cancellation_reason', $appointment->cancellation_reason) }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary">Lưu thay đổi</button>
            <a class="btn btn-ghost" href="{{ route('admin.appointments.index') }}">Quay lại</a>
        </div>
    </form>
</section>
@endsection
