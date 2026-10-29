@extends('layouts.app')
@section('title', 'Bảng điều khiển bác sĩ')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Không gian bác sĩ</p><h1>Xin chào, {{ auth()->user()->name }}</h1><p class="subtitle">Danh sách các lịch hẹn sắp tới.</p></div><a class="btn btn-primary" href="{{ route('doctor.appointments.index') }}">Quản lý lịch hẹn</a></div>
<div class="card table-wrap">
    <table>
        <thead>
            <tr><th>Ngày giờ</th><th>Bệnh nhân</th><th>Lý do</th><th>Trạng thái</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($appointments as $appointment)
                <tr>
                    <td>{{ $appointment->starts_at->format('d/m/Y H:i') }}</td>
                    <td class="product-name">{{ $appointment->patient->name }}</td>
                    <td>{{ $appointment->reason }}</td>
                    <td><span class="stock">{{ ucfirst($appointment->status) }}</span></td>
                    <td><a class="btn btn-ghost" href="{{ route('doctor.appointments.show', $appointment) }}">Xử lý</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">Không có lịch hẹn sắp tới.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
