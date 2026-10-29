@extends('layouts.app')
@section('title', 'Lịch khám của tôi')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Khách hàng</p><h1>Lịch khám của tôi</h1></div><a class="btn btn-primary" href="{{ route('patient.appointments.create') }}">+ Đặt lịch mới</a></div>
<div class="card table-wrap"><table><thead><tr><th>Ngày giờ</th><th>Bác sĩ</th><th>Lý do</th><th>Trạng thái</th><th></th></tr></thead><tbody>@forelse($appointments as $appointment)<tr><td>{{ $appointment->starts_at->format('d/m/Y H:i') }}</td><td class="product-name">{{ $appointment->doctor->name }}</td><td>{{ $appointment->reason }}</td><td><span class="stock">{{ ucfirst($appointment->status) }}</span></td><td><a class="btn btn-ghost" href="{{ route('patient.appointments.show', $appointment) }}">Chi tiết</a></td></tr>@empty<tr><td colspan="5" class="empty-state">Chưa có lịch hẹn.</td></tr>@endforelse</tbody></table></div><div style="margin-top:20px;">{{ $appointments->links() }}</div>
@endsection
