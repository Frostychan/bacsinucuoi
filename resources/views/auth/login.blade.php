<x-guest-layout>
    <x-auth-session-status class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <form class="flex flex-col gap-5" method="POST" action="{{ route('login') }}">
        @csrf

        <div class="flex flex-col gap-2">
            <label for="email" class="text-sm font-medium text-slate-700">Địa chỉ email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="ban@email.com" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
            <x-input-error :messages="$errors->get('email')" class="text-sm text-red-600" />
        </div>

        <div class="flex flex-col gap-2">
            <label for="password" class="text-sm font-medium text-slate-700">Mật khẩu</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Nhập mật khẩu" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
            <x-input-error :messages="$errors->get('password')" class="text-sm text-red-600" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="flex items-center gap-2 text-sm text-slate-600">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-teal-700 focus:ring-teal-600" name="remember">
                Ghi nhớ đăng nhập
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-teal-700 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2" href="{{ route('password.request') }}">
                    Quên mật khẩu?
                </a>
            @endif
        </div>

        <button type="submit" class="mt-1 inline-flex w-full items-center justify-center rounded-xl bg-teal-700 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-teal-700/20 transition hover:bg-teal-800 focus:outline-none focus:ring-4 focus:ring-teal-600/25">
            Đăng nhập
        </button>

        <p class="text-center text-sm text-slate-500">
            Chưa có tài khoản?
            <a href="{{ route('register') }}" class="font-semibold text-teal-700 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Đăng ký ngay</a>
        </p>
    </form>
</x-guest-layout>
