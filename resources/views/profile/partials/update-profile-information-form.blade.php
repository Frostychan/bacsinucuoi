<section class="profile-section">
    <header>
        <h2>Thông tin cá nhân</h2>
        <p>Cập nhật thông tin liên hệ để MedCare hỗ trợ bạn thuận tiện hơn.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="profile-form">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Họ và tên" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="form-error" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Địa chỉ email" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="form-error" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="form-help">
                        Email của bạn chưa được xác minh.

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Gửi lại email xác minh
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="profile-saved">
                            Đã gửi email xác minh mới.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if ($user->isRole('customer'))
            <div class="profile-subsection">
                <h3>Thông tin khách hàng</h3>
                <p>Các thông tin này được chia sẻ với bác sĩ phụ trách khi bạn có lịch hẹn với bác sĩ đó.</p>
            </div>

            <div>
                <x-input-label for="phone" value="Số điện thoại" />
                <x-text-input id="phone" name="phone" type="tel" :value="old('phone', $user->phone)" autocomplete="tel" />
                <x-input-error class="form-error" :messages="$errors->get('phone')" />
            </div>

            <div class="profile-form-grid">
                <div>
                    <x-input-label for="date_of_birth" value="Ngày sinh" />
                    <x-text-input id="date_of_birth" name="date_of_birth" type="date" :value="old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))" />
                    <x-input-error class="form-error" :messages="$errors->get('date_of_birth')" />
                </div>

                <div>
                    <x-input-label for="gender" value="Giới tính (không bắt buộc)" />
                    <select id="gender" name="gender" class="form-control">
                        <option value="">Chưa cung cấp</option>
                        <option value="female" @selected(old('gender', $user->gender) === 'female')>Nữ</option>
                        <option value="male" @selected(old('gender', $user->gender) === 'male')>Nam</option>
                        <option value="other" @selected(old('gender', $user->gender) === 'other')>Khác</option>
                        <option value="prefer_not_to_say" @selected(old('gender', $user->gender) === 'prefer_not_to_say')>Không muốn tiết lộ</option>
                    </select>
                    <x-input-error class="form-error" :messages="$errors->get('gender')" />
                </div>
            </div>

            <div>
                <x-input-label for="address" value="Địa chỉ" />
                <textarea id="address" name="address" rows="3" class="form-control" autocomplete="street-address">{{ old('address', $user->address) }}</textarea>
                <x-input-error class="form-error" :messages="$errors->get('address')" />
            </div>

            <div class="profile-subsection">
                <h3>Người liên hệ khẩn cấp</h3>
                <p>Người thân hoặc người bạn tin tưởng để liên hệ khi cần thiết.</p>
            </div>

            <div>
                <x-input-label for="emergency_contact_name" value="Họ tên người liên hệ" />
                <x-text-input id="emergency_contact_name" name="emergency_contact_name" type="text" :value="old('emergency_contact_name', $user->emergency_contact_name)" autocomplete="off" />
                <x-input-error class="form-error" :messages="$errors->get('emergency_contact_name')" />
            </div>

            <div class="profile-form-grid">
                <div>
                    <x-input-label for="emergency_contact_phone" value="Số điện thoại người liên hệ" />
                    <x-text-input id="emergency_contact_phone" name="emergency_contact_phone" type="tel" :value="old('emergency_contact_phone', $user->emergency_contact_phone)" autocomplete="off" />
                    <x-input-error class="form-error" :messages="$errors->get('emergency_contact_phone')" />
                </div>

                <div>
                    <x-input-label for="emergency_contact_relationship" value="Mối quan hệ" />
                    <x-text-input id="emergency_contact_relationship" name="emergency_contact_relationship" type="text" :value="old('emergency_contact_relationship', $user->emergency_contact_relationship)" placeholder="Ví dụ: Vợ/chồng, con, anh/chị/em" />
                    <x-input-error class="form-error" :messages="$errors->get('emergency_contact_relationship')" />
                </div>
            </div>
        @endif

        <div class="profile-actions">
            <x-primary-button>Lưu thay đổi</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="profile-saved"
                >Đã lưu thông tin.</p>
            @endif
        </div>
    </form>
</section>
