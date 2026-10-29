@extends('layouts.app')
@section('title', 'Chi tiết lịch hẹn')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Lịch hẹn #{{ $appointment->id }}</p><h1>Chi tiết lịch khám</h1></div></div>
<div class="card detail-card">
    <div class="detail-row"><span class="detail-label">Bác sĩ</span><strong>{{ $appointment->doctor->name }}</strong></div>
    <div class="detail-row"><span class="detail-label">Thời gian</span><strong>{{ $appointment->starts_at->format('d/m/Y H:i') }}</strong></div>
    <div class="detail-row"><span class="detail-label">Trạng thái</span><span class="stock">{{ ucfirst($appointment->status) }}</span></div>
    <div class="detail-row"><span class="detail-label">Lý do</span><strong>{{ $appointment->reason }}</strong></div>
    @if ($appointment->payment_status !== 'paid' && $appointment->status !== 'cancelled')
        <div class="form-actions">
            <a class="btn btn-primary" href="{{ route('patient.appointments.payment', $appointment) }}">Thanh toán phí đặt lịch</a>
        </div>
    @endif

    @if ($appointment->medicalRecord)
        <div class="detail-row"><span class="detail-label">Chẩn đoán</span><strong>{{ $appointment->medicalRecord->diagnosis }}</strong></div>

        @if ($appointment->medicalRecord->prescription)
            <div class="detail-row"><span class="detail-label">Đơn thuốc</span><strong class="detail-value medical-note">{{ $appointment->medicalRecord->prescription }}</strong></div>
        @endif

        @if ($appointment->medicalRecord->medications->isNotEmpty())
            <div class="detail-row">
                <span class="detail-label">Thuốc được kê</span>
                <span class="detail-value medical-note">
                    @foreach ($appointment->medicalRecord->medications as $medication)
                        {{ $medication->medicine_name }} — {{ $medication->quantity }}{{ $loop->last ? '' : "\n" }}
                    @endforeach
                </span>
            </div>
        @endif
    @endif

    @if ($appointment->doctor_notes || $appointment->medicalRecord?->notes)
        <div class="detail-row">
            <span class="detail-label">Ghi chú dành cho bệnh nhân</span>
            <span class="detail-value medical-note">{{ $appointment->doctor_notes ?: $appointment->medicalRecord?->notes }}</span>
        </div>
    @endif

    @if (auth()->user()->isRole('customer') && $appointment->status === 'completed' && ($prescribedMedicines !== [] || $appointment->medicineOrder))
        <div class="form-actions">
            @if ($appointment->medicineOrder)
                <a class="btn btn-primary" href="{{ route('patient.appointments.payment', $appointment) }}">Thanh toán đơn thuốc</a>
                <a class="btn btn-ghost" href="{{ route('patient.medicine-orders.checkout', $appointment) }}">Xem yêu cầu mua thuốc</a>
            @else
                <a class="btn btn-primary" href="{{ route('patient.medicine-orders.checkout', $appointment) }}">Mua thuốc theo đơn</a>
            @endif
        </div>
    @endif

    @if (in_array($appointment->status, ['pending', 'confirmed'], true))
        <form method="POST" action="{{ route('patient.appointments.cancel', $appointment) }}" class="form-actions">
            @csrf
            @method('PATCH')
            <input class="form-control" name="cancellation_reason" placeholder="Lý do hủy (không bắt buộc)">
            <button class="btn btn-danger">Hủy lịch hẹn</button>
        </form>
    @endif
</div>
@endsection
