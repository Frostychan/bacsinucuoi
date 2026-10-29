<section class="profile-section">
    <header>
        <h2>Xóa tài khoản</h2>
        <p>Thao tác này không thể hoàn tác. Tất cả dữ liệu tài khoản sẽ bị xóa vĩnh viễn.</p>
    </header>

    <x-danger-button
        class="danger-button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Xóa tài khoản</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">Bạn chắc chắn muốn xóa tài khoản?</h2>

            <p class="mt-1 text-sm text-gray-600">Nhập mật khẩu để xác nhận việc xóa tài khoản vĩnh viễn.</p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Mật khẩu hiện tại"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Hủy
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Xác nhận xóa
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
