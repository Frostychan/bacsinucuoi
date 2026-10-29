@extends('layouts.app')
@section('title', 'Quản trị hệ thống')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Quản trị</p><h1>Tổng quan MedCare</h1><p class="subtitle">Theo dõi hoạt động của hệ thống đặt lịch.</p></div></div>
<div class="stats-grid"><div class="card stat-card"><div class="stat-icon">▣</div><div><p class="stat-value">{{ $stats['appointments'] }}</p><p class="stat-label">Tổng lịch hẹn</p></div></div><div class="card stat-card"><div class="stat-icon blue">◷</div><div><p class="stat-value">{{ $stats['today'] }}</p><p class="stat-label">Lịch hẹn hôm nay</p></div></div><div class="card stat-card"><div class="stat-icon green">!</div><div><p class="stat-value">{{ $stats['pending'] }}</p><p class="stat-label">Chờ xác nhận</p></div></div></div>
<div class="card admin-quick-management" style="margin-top:24px;padding:22px;">
    <div class="section-title" style="padding:0 0 14px;"><h2>Quản lý nhanh</h2></div>
    <div class="actions">
        <a href="{{ route('admin.appointments.index') }}" class="btn btn-primary admin-appointments-link">
            Quản lý lịch hẹn
            <span class="pending-appointments-badge">{{ $stats['pending'] }} chờ duyệt</span>
        </a>
        <a href="{{ route('admin.products.index') }}" class="btn btn-primary">Quản lý dịch vụ</a>
        <a href="{{ route('admin.medicines.index') }}" class="btn btn-primary">Quản lý giá thuốc</a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Người dùng</a>
    </div>
</div>

<section class="card dashboard-pending-appointments" aria-labelledby="pending-appointments-title">
    <div class="user-role-list-heading">
        <div>
            <p class="eyebrow">Cần xử lý</p>
            <h2 id="pending-appointments-title">Lịch hẹn đang chờ duyệt</h2>
        </div>
        <a class="btn btn-ghost" href="{{ route('admin.appointments.index') }}">Xem tất cả lịch</a>
    </div>
    <div class="dashboard-pending-list">
        @forelse ($pendingAppointments as $appointment)
            <article class="dashboard-pending-item">
                <div class="dashboard-pending-time">
                    <strong>{{ $appointment->starts_at->format('d/m/Y') }}</strong>
                    <span>{{ $appointment->starts_at->format('H:i') }}</span>
                </div>
                <div class="dashboard-pending-details">
                    <strong>{{ $appointment->patient->name }}</strong>
                    <span>Bác sĩ: {{ $appointment->doctor->name }}</span>
                    <p>{{ $appointment->reason }}</p>
                </div>
                <a class="btn btn-ghost" href="{{ route('admin.appointments.edit', $appointment) }}">Xem lịch</a>
            </article>
        @empty
            <p class="empty-state dashboard-pending-empty">Hiện không có lịch hẹn nào chờ duyệt.</p>
        @endforelse
    </div>
</section>
@endsection
