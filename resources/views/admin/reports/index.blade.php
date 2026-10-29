@extends('layouts.app')
@section('title', 'Báo cáo')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Quản trị</p><h1>Báo cáo lịch hẹn</h1></div></div>
<div class="card table-wrap"><table><thead><tr><th>Trạng thái</th><th>Số lượng</th></tr></thead><tbody>@forelse($reports as $status => $total)<tr><td class="product-name">{{ ucfirst($status) }}</td><td>{{ $total }}</td></tr>@empty<tr><td colspan="2" class="empty-state">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div>
@endsection
