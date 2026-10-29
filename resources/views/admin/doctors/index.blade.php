@extends('layouts.app')
@section('title', 'Quản lý bác sĩ')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">Quản trị</p>
        <h1>Quản lý bác sĩ</h1>
        <p class="subtitle">Chọn hồ sơ để xem đầy đủ thông tin và cập nhật hồ sơ bác sĩ.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.doctors.create') }}">Thêm bác sĩ</a>
</div>

<div class="doctor-admin-grid">
    @forelse ($doctors as $doctor)
        @php($profile = $doctor->doctorProfile)
        <details class="card doctor-admin-card">
            <summary class="doctor-admin-summary">
                @if ($profile?->image_path)
                    <img class="doctor-admin-photo"
                        src="{{ str_starts_with($profile->image_path, 'images/') ? asset($profile->image_path) : asset('storage/'.$profile->image_path) }}"
                        alt="Ảnh bác sĩ {{ $doctor->name }}" loading="lazy">
                @else
                    <span class="doctor-admin-photo doctor-admin-placeholder" role="img"
                        aria-label="Chưa có ảnh bác sĩ {{ $doctor->name }}">{{ strtoupper(substr($doctor->name, 0, 1)) }}</span>
                @endif
                <span class="doctor-admin-summary-copy">
                    <strong>{{ $doctor->name }}</strong>
                    <span>{{ $profile?->specialty?->name ?? 'Chưa phân chuyên khoa' }}</span>
                    <small>{{ $doctor->email }}</small>
                </span>
                <span class="doctor-admin-toggle" aria-hidden="true">+</span>
            </summary>

            <div class="doctor-admin-details">
                <h2>Thông tin bác sĩ</h2>
                <dl class="doctor-admin-facts">
                    <div><dt>Email</dt><dd>{{ $doctor->email }}</dd></div>
                    <div><dt>Số điện thoại</dt><dd>{{ $doctor->phone ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Ngày sinh</dt><dd>{{ $doctor->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Giới tính</dt><dd>{{ $doctor->gender ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Địa chỉ</dt><dd>{{ $doctor->address ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Chuyên khoa</dt><dd>{{ $profile?->specialty?->name ?? 'Chưa phân chuyên khoa' }}</dd></div>
                    <div><dt>Số giấy phép</dt><dd>{{ $profile?->license_number ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Phí khám</dt><dd>{{ number_format($profile?->consultation_fee ?? 0, 0, ',', '.') }} đ</dd></div>
                    <div><dt>Thời lượng khám</dt><dd>{{ $profile?->slot_duration ?? 30 }} phút</dd></div>
                    <div><dt>Trạng thái nhận lịch</dt><dd>{{ $profile?->is_accepting ? 'Đang nhận lịch' : 'Tạm ngưng nhận lịch' }}</dd></div>
                    <div><dt>Ngày tạo tài khoản</dt><dd>{{ $doctor->created_at->format('d/m/Y') }}</dd></div>
                    <div class="doctor-admin-bio"><dt>Giới thiệu</dt><dd>{{ $profile?->bio ?: 'Bác sĩ chưa cập nhật phần giới thiệu.' }}</dd></div>
                </dl>

                <h3>Cập nhật thông tin chuyên môn</h3>
                <form method="POST" action="{{ route('admin.doctors.update', $doctor) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="specialty-{{ $doctor->id }}">Chuyên khoa</label>
                            <select class="form-control" id="specialty-{{ $doctor->id }}" name="specialty_id">
                                <option value="">Chưa phân loại</option>
                                @foreach ($specialties as $specialty)
                                    <option value="{{ $specialty->id }}" @selected($profile?->specialty_id === $specialty->id)>{{ $specialty->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="license-{{ $doctor->id }}">Số giấy phép</label>
                            <input class="form-control" id="license-{{ $doctor->id }}" name="license_number" value="{{ $profile?->license_number }}">
                        </div>
                        <div class="form-group">
                            <label for="fee-{{ $doctor->id }}">Phí khám</label>
                            <input class="form-control" id="fee-{{ $doctor->id }}" type="number" min="0" step="1000" name="consultation_fee" value="{{ $profile?->consultation_fee ?? 0 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="duration-{{ $doctor->id }}">Thời lượng khám (phút)</label>
                            <input class="form-control" id="duration-{{ $doctor->id }}" type="number" min="15" max="180" name="slot_duration" value="{{ $profile?->slot_duration ?? 30 }}" required>
                        </div>
                        <div class="form-group full">
                            <label for="bio-{{ $doctor->id }}">Giới thiệu bác sĩ</label>
                            <textarea class="form-control" id="bio-{{ $doctor->id }}" name="bio" rows="3">{{ $profile?->bio }}</textarea>
                        </div>
                        <div class="form-group full">
                            <label for="image-{{ $doctor->id }}">Ảnh bác sĩ</label>
                            <input class="form-control" id="image-{{ $doctor->id }}" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                            <p class="form-help">Chấp nhận JPG, PNG hoặc WEBP, dung lượng tối đa 5 MB.</p>
                        </div>
                    </div>
                    <label><input type="checkbox" name="is_accepting" value="1" @checked($profile?->is_accepting ?? true)> Đang nhận lịch</label>
                    <div class="form-actions"><button class="btn btn-primary">Lưu thông tin bác sĩ</button></div>
                </form>
            </div>
        </details>
    @empty
        <div class="card empty-state">Chưa có tài khoản bác sĩ để quản lý.</div>
    @endforelse
</div>

<div>{{ $doctors->links() }}</div>
@endsection
