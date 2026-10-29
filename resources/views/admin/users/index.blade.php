@extends('layouts.app')
@section('title', 'Người dùng')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">Quản trị</p>
        <h1>Người dùng</h1>
        <p class="subtitle">Chọn một nhóm để mở danh sách và quản lý tài khoản.</p>
    </div>
</div>

<div class="user-role-grid">
    @foreach ($roleLabels as $role => $label)
        <a class="card user-role-card{{ $selectedRole === $role ? ' is-active' : '' }}"
            href="{{ route('admin.users.index', ['role' => $role]) }}"
            @if ($selectedRole === $role) aria-current="true" @endif>
            <span class="user-role-card-heading">
                <span class="user-role-card-title">{{ $label }}</span>
                <span class="user-role-card-count">{{ number_format($roleCounts[$role] ?? 0) }}</span>
            </span>
            <span class="user-role-card-hint">
                {{ $selectedRole === $role ? 'Danh sách đang mở' : 'Nhấn để xem danh sách' }}
            </span>
        </a>
    @endforeach
</div>

@if ($users)
    <section class="card user-role-list" aria-labelledby="user-role-list-title">
        <div class="user-role-list-heading">
            <div>
                <p class="eyebrow">Danh sách tài khoản</p>
                <h2 id="user-role-list-title">{{ $roleLabels[$selectedRole] }}</h2>
            </div>
            <a class="btn btn-ghost" href="{{ route('admin.users.index') }}">Thu gọn</a>
        </div>
        @if ($selectedRole === 'customer')
            <div class="patient-user-list">
                @forelse ($users as $user)
                    <details class="card patient-user-card">
                        <summary class="patient-user-summary">
                            <span class="patient-user-avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <span class="patient-user-summary-copy">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->email }}</span>
                            </span>
                            <span class="patient-user-created">Tạo ngày {{ $user->created_at->format('d/m/Y') }}</span>
                            <span class="patient-user-toggle" aria-hidden="true">+</span>
                        </summary>
                        <div class="patient-user-details">
                            <h3>Thông tin bệnh nhân</h3>
                            <dl class="patient-user-facts">
                                <div><dt>Họ tên</dt><dd>{{ $user->name }}</dd></div>
                                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                                <div><dt>Số điện thoại</dt><dd>{{ $user->phone ?: 'Chưa cung cấp' }}</dd></div>
                                <div><dt>Ngày sinh</dt><dd>{{ $user->date_of_birth?->format('d/m/Y') ?? 'Chưa cung cấp' }}</dd></div>
                                <div><dt>Giới tính</dt><dd>{{ ['female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác', 'prefer_not_to_say' => 'Không muốn tiết lộ'][$user->gender] ?? 'Chưa cung cấp' }}</dd></div>
                                <div class="patient-user-full"><dt>Địa chỉ</dt><dd>{{ $user->address ?: 'Chưa cung cấp' }}</dd></div>
                            </dl>
                            <h3>Người liên hệ khẩn cấp</h3>
                            <dl class="patient-user-facts">
                                <div><dt>Họ tên</dt><dd>{{ $user->emergency_contact_name ?: 'Chưa cung cấp' }}</dd></div>
                                <div><dt>Số điện thoại</dt><dd>{{ $user->emergency_contact_phone ?: 'Chưa cung cấp' }}</dd></div>
                                <div><dt>Mối quan hệ</dt><dd>{{ $user->emergency_contact_relationship ?: 'Chưa cung cấp' }}</dd></div>
                            </dl>
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.role', $user) }}" class="actions patient-user-role">
                                    @csrf
                                    @method('PATCH')
                                    <label for="patient-role-{{ $user->id }}">Vai trò tài khoản</label>
                                    <select class="form-control" id="patient-role-{{ $user->id }}" name="role">
                                        @foreach ($roleLabels as $role => $label)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-ghost">Lưu vai trò</button>
                                </form>
                            @endif
                        </div>
                    </details>
                @empty
                    <div class="card empty-state">Chưa có tài khoản bệnh nhân.</div>
                @endforelse
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Ngày tạo</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="product-name">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="stock">{{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</span></td>
                                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                <td>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="actions">
                                            @csrf
                                            @method('PATCH')
                                            <select class="form-control" name="role" aria-label="Vai trò mới của {{ $user->name }}"
                                                style="width:auto;padding:7px;">
                                                @foreach ($roleLabels as $role => $label)
                                                    <option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-ghost">Lưu</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state">Chưa có tài khoản thuộc nhóm này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
        <div class="user-role-pagination">{{ $users->links() }}</div>
    </section>
@endif
@endsection
