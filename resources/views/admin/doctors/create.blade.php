@extends('layouts.app')
@section('title', 'Thêm bác sĩ')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">Quản trị · Bác sĩ</p>
        <h1>Thêm bác sĩ</h1>
        <p class="subtitle">Tạo tài khoản đăng nhập và hồ sơ chuyên môn cho bác sĩ mới.</p>
    </div>
</div>

<section class="card form-card">
    <form method="POST" action="{{ route('admin.doctors.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Họ và tên <span class="required">*</span></label>
                <input class="form-control" id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
                @error('name') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="email">Email đăng nhập <span class="required">*</span></label>
                <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" required>
                @error('email') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input class="form-control" id="phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="30">
                @error('phone') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="specialty_id">Chuyên khoa</label>
                <select class="form-control" id="specialty_id" name="specialty_id">
                    <option value="">Chưa phân loại</option>
                    @foreach ($specialties as $specialty)
                        <option value="{{ $specialty->id }}" @selected(old('specialty_id') == $specialty->id)>{{ $specialty->name }}</option>
                    @endforeach
                </select>
                @error('specialty_id') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu tạm thời <span class="required">*</span></label>
                <input class="form-control" id="password" type="password" name="password" autocomplete="new-password" required>
                @error('password') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation">Xác nhận mật khẩu <span class="required">*</span></label>
                <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label for="license_number">Số giấy phép</label>
                <input class="form-control" id="license_number" name="license_number" value="{{ old('license_number') }}" maxlength="255">
                @error('license_number') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="consultation_fee">Phí khám (đồng) <span class="required">*</span></label>
                <input class="form-control" id="consultation_fee" type="number" name="consultation_fee" min="0" step="1000" value="{{ old('consultation_fee', 0) }}" required>
                @error('consultation_fee') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label for="slot_duration">Thời lượng khám (phút) <span class="required">*</span></label>
                <input class="form-control" id="slot_duration" type="number" name="slot_duration" min="15" max="180" value="{{ old('slot_duration', 30) }}" required>
                @error('slot_duration') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group full">
                <label for="bio">Giới thiệu bác sĩ</label>
                <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="2000">{{ old('bio') }}</textarea>
                @error('bio') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group full">
                <label for="image">Ảnh bác sĩ</label>
                <input class="form-control" id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                <p class="form-help">Chấp nhận JPG, PNG hoặc WEBP, dung lượng tối đa 5 MB.</p>
                @error('image') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <label><input type="checkbox" name="is_accepting" value="1" @checked(old('is_accepting', '1') === '1')> Đang nhận lịch khám</label>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Tạo bác sĩ</button>
            <a class="btn btn-ghost" href="{{ route('admin.doctors.index') }}">Hủy</a>
        </div>
    </form>
</section>
@endsection
