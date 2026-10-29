<x-guest-layout>
    <form class="flex flex-col gap-4" method="POST" action="{{ route('register') }}">
        @csrf

        <div class="flex flex-col gap-2">
            <label for="name" class="text-sm font-medium text-slate-700">Họ và tên</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Nguyễn Văn An" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
            <x-input-error :messages="$errors->get('name')" class="text-sm text-red-600" />
        </div>

        <div class="flex flex-col gap-2">
            <label for="email" class="text-sm font-medium text-slate-700">Địa chỉ email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="ban@email.com" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
            <x-input-error :messages="$errors->get('email')" class="text-sm text-red-600" />
        </div>

        <div class="flex flex-col gap-2">
            <label for="password" class="text-sm font-medium text-slate-700">Mật khẩu</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Tạo mật khẩu" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
            <x-input-error :messages="$errors->get('password')" class="text-sm text-red-600" />
        </div>

        <div class="flex flex-col gap-2">
            <label for="password_confirmation" class="text-sm font-medium text-slate-700">Xác nhận mật khẩu</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Nhập lại mật khẩu" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
            <x-input-error :messages="$errors->get('password_confirmation')" class="text-sm text-red-600" />
        </div>

        <button type="submit" class="mt-1 inline-flex w-full items-center justify-center rounded-xl bg-teal-700 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-700/20 transition hover:bg-teal-800 focus:outline-none focus:ring-4 focus:ring-teal-600/25">
            Tạo tài khoản
        </button>

        <p class="text-center text-sm text-slate-500">
            Bạn đã có tài khoản?
            <a href="{{ route('login') }}" class="font-semibold text-teal-700 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Đăng nhập</a>
        </p>
    </form>
</x-guest-layout>
