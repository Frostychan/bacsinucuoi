@extends('layouts.app')
@section('title', 'Hồ sơ cá nhân')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Account settings</p>
            <h1>Hồ sơ cá nhân</h1>
            <p class="subtitle">Cập nhật thông tin liên hệ và bảo mật tài khoản MedCare.</p>
        </div>
    </div>
    <div class="profile-grid">
        <div class="card profile-card wide">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="card profile-card">
            @include('profile.partials.update-password-form')
        </div>
        <div class="card profile-card danger-card">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection
