@extends('layouts.app')
@section('title', 'Liên hệ')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">MedCare</p>
            <h1>Liên hệ với chúng tôi</h1>
            <p class="subtitle">Gửi câu hỏi hoặc lời nhắn, đội ngũ MedCare sẽ phản hồi bạn.</p>
        </div>
    </div>

    <div class="card form-card">
        <form method="POST" action="{{ route('contact.store') }}">
            @csrf

            <div class="form-group">
                <label for="name">Họ tên <span class="required">*</span></label>
                <input class="form-control" id="name" name="name" type="text" value="{{ old('name') }}" maxlength="100" autocomplete="name" required>
                @error('name') <p class="form-help">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                @error('email') <p class="form-help">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="message">Nội dung <span class="required">*</span></label>
                <textarea class="form-control" id="message" name="message" rows="6" maxlength="5000" required>{{ old('message') }}</textarea>
                @error('message') <p class="form-help">{{ $message }}</p> @enderror
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Gửi liên hệ</button>
            </div>
        </form>
    </div>
@endsection
