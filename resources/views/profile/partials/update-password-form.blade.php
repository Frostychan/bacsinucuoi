<section class="profile-section">
    <header>
        <h2>Đổi mật khẩu</h2>
        <p>Sử dụng mật khẩu đủ mạnh để bảo vệ thông tin sức khỏe và tài khoản của bạn.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="profile-form">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Mật khẩu hiện tại" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="form-error" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Mật khẩu mới" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="form-error" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Nhập lại mật khẩu mới" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="form-error" />
        </div>

        <div class="profile-actions">
            <x-primary-button>Lưu mật khẩu</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="profile-saved"
                >Đã cập nhật mật khẩu.</p>
            @endif
        </div>
    </form>
</section>
