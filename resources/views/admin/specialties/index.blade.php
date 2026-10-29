@extends('layouts.app')
@section('title', 'Chuyên khoa')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Quản trị</p><h1>Chuyên khoa</h1></div></div>
<div class="card form-card"><form method="POST" action="{{ route('admin.specialties.store') }}">@csrf<div class="form-grid"><div class="form-group"><label>Tên chuyên khoa</label><input class="form-control" name="name" required></div><div class="form-group"><label>Mô tả</label><input class="form-control" name="description"></div></div><button class="btn btn-primary">Thêm chuyên khoa</button></form></div>
<div class="card table-wrap" style="margin-top:22px;"><table><thead><tr><th>Tên</th><th>Số bác sĩ</th><th>Mô tả</th></tr></thead><tbody>@foreach($specialties as $specialty)<tr><td class="product-name">{{ $specialty->name }}</td><td>{{ $specialty->doctors_count }}</td><td>{{ $specialty->description }}</td></tr>@endforeach</tbody></table></div>
@endsection
