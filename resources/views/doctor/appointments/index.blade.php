@extends('layouts.app')
@section('title', 'Lịch hẹn bệnh nhân')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Bác sĩ</p><h1>Lịch hẹn bệnh nhân</h1></div></div>
<div class="card table-wrap"><table><thead><tr><th>Ngày giờ</th><th>Bệnh nhân</th><th>Lý do</th><th>Trạng thái</th><th></th></tr></thead><tbody>@forelse($appointments as $appointment)<tr><td>{{ $appointment->starts_at->format('d/m/Y H:i') }}</td><td class="product-name">{{ $appointment->patient->name }}<br><small>{{ $appointment->patient->email }}</small></td><td>{{ $appointment->reason }}</td><td><span class="stock">{{ ucfirst($appointment->status) }}</span></td><td><a class="btn btn-ghost" href="{{ route('doctor.appointments.show', $appointment) }}">Xử lý</a></td></tr>@empty<tr><td colspan="5" class="empty-state">Không có lịch hẹn.</td></tr>@endforelse</tbody></table></div><div style="margin-top:20px;">{{ $appointments->links() }}</div>
@endsection
