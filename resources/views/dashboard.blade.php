@extends('layouts.app')
@section('title', 'Tổng quan')
@section('content')
    <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:24px;padding:28px;color:#fff;border:0;background:linear-gradient(110deg,#087e8b,#3ba7d4);">
        <div>
            <p class="eyebrow" style="color:#c9ffff;">MEDCARE HEALTH</p>
            <h1 style="margin-bottom:8px;color:#fff;">Chào mừng, {{ auth()->user()->name }}</h1>
            <p style="margin:0;color:#d9f6f7;">Quản lý thông tin cá nhân và theo dõi hành trình chăm sóc sức khỏe của bạn.</p>
        </div>
        <div style="color:rgba(255,255,255,.25);font-size:72px;line-height:1;">✚</div>
    </div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Patient portal</p>
            <h1>Tổng quan tài khoản</h1>
            <p class="subtitle">Bạn đã đăng nhập thành công vào hệ thống MedCare.</p>
        </div>
        @if (Route::has('profile.edit'))
            <a href="{{ route('profile.edit') }}" class="btn btn-primary">Cập nhật hồ sơ</a>
        @endif
    </div>
    <div class="stats-grid">
        <div class="card stat-card"><div class="stat-icon">✓</div><div><p class="stat-value">01</p><p class="stat-label">Tài khoản đang hoạt động</p></div></div>
        <div class="card stat-card"><div class="stat-icon blue">◎</div><div><p class="stat-value">24/7</p><p class="stat-label">Hỗ trợ trực tuyến</p></div></div>
        <div class="card stat-card"><div class="stat-icon green">♡</div><div><p class="stat-value">100%</p><p class="stat-label">Bảo mật thông tin</p></div></div>
    </div>
    <div class="card">
        <div class="section-title"><h2>Truy cập nhanh</h2></div>
        <div style="display:flex;flex-wrap:wrap;gap:12px;padding:18px 22px 24px;">
            @if (Route::has('profile.edit'))
                <a href="{{ route('profile.edit') }}" class="btn btn-ghost">◎ Hồ sơ cá nhân</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger">Đăng xuất</button>
            </form>
        </div>
    </div>
@endsection
