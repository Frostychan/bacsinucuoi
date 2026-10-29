@extends('layouts.app')
@section('title', 'Liên hệ mới')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Quản trị</p>
            <h1>Thông tin liên hệ</h1>
            <p class="subtitle">Danh sách lời nhắn được gửi qua biểu mẫu liên hệ.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Nội dung</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($messages as $contactMessage)
                    <tr>
                        <td>{{ $contactMessage->created_at->format('d/m/Y H:i') }}</td>
                        <td class="product-name">{{ $contactMessage->name }}</td>
                        <td><a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></td>
                        <td class="medical-note">{{ $contactMessage->message }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">Chưa có liên hệ nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:20px;">{{ $messages->links() }}</div>
@endsection
