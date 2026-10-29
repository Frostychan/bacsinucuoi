@extends('layouts.app')

@section('title', 'Đặt lịch khám')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">Khách hàng</p>
        <h1>Đặt lịch khám</h1>
        <p class="subtitle">Chọn bác sĩ để xem các khung giờ còn trống trong ngày.</p>
    </div>
</div>

<form method="POST" action="{{ route('patient.appointments.store') }}" data-appointment-picker
    data-old-starts-at="{{ old('starts_at') }}">
    @csrf
    <div class="booking-layout">
        <section class="card doctor-picker-panel" aria-labelledby="doctor-picker-title">
            <div class="section-title"><h2 id="doctor-picker-title">Chọn bác sĩ</h2></div>
            <div class="doctor-picker-list">
                @forelse ($doctors as $doctor)
                    <label class="doctor-choice">
                        <input type="radio" name="doctor_id" value="{{ $doctor->id }}"
                            data-availability-url="{{ route('booking.doctors.availability', $doctor) }}"
                            @checked(old('doctor_id', request('doctor_id')) == $doctor->id) required>
                        <span class="doctor-choice-copy">
                            <strong>{{ $doctor->name }}</strong>
                            <small>{{ $doctor->doctorProfile?->specialty?->name ?? 'Bác sĩ đa khoa' }}</small>
                            <span class="doctor-choice-fee">{{ number_format($doctor->doctorProfile?->consultation_fee ?? 0, 0, ',', '.') }} đ / lượt</span>
                        </span>
                    </label>
                @empty
                    <p class="empty-state">Hiện chưa có bác sĩ nhận lịch.</p>
                @endforelse
            </div>
            @error('doctor_id') <p class="form-help booking-error">{{ $message }}</p> @enderror
        </section>

        <div class="booking-info-column">
            <section class="card booking-schedule-panel" aria-labelledby="booking-schedule-title">
                <div class="section-title"><h2 id="booking-schedule-title">Lịch khám của bác sĩ</h2></div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="appointment_date">Ngày khám <span class="required">*</span></label>
                        <input class="form-control" id="appointment_date" type="date" name="appointment_date"
                            value="{{ old('appointment_date') }}" min="{{ today()->format('Y-m-d') }}" required>
                        @error('appointment_date') <p class="form-help booking-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="starts_at">Khung giờ còn trống <span class="required">*</span></label>
                        <select class="form-control" id="starts_at" name="starts_at" required disabled
                            data-availability-slots data-error="{{ $errors->first('starts_at') }}">
                            <option value="">Chọn bác sĩ và ngày trước</option>
                        </select>
                        @error('starts_at') <p class="form-help booking-error">{{ $message }}</p> @enderror
                        <p class="form-help" data-availability-message aria-live="polite">Giờ khám từ 08:00 đến 17:00, theo thời lượng mỗi lượt khám của bác sĩ.</p>
                    </div>
                </div>
            </section>

            <section class="card booking-details-panel" aria-labelledby="booking-details-title">
                <div class="section-title"><h2 id="booking-details-title">Thông tin khám</h2></div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="patient_age">Tuổi bệnh nhân <span class="required">*</span></label>
                        <input class="form-control" id="patient_age" type="number" name="patient_age" min="0" max="120"
                            value="{{ old('patient_age') }}" required>
                        @error('patient_age') <p class="form-help booking-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group full">
                        <label for="reason">Triệu chứng / lý do khám <span class="required">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" maxlength="500" required>{{ old('reason') }}</textarea>
                        @error('reason') <p class="form-help booking-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group full">
                        <label for="notes">Ghi chú thêm</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes') <p class="form-help booking-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Gửi yêu cầu đặt lịch</button>
                    <a class="btn btn-ghost" href="{{ route('patient.appointments.index') }}">Hủy</a>
                </div>
            </section>
        </div>
    </div>
</form>
@endsection
