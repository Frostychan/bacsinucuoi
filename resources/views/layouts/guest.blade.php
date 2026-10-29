<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ request()->routeIs('register') ? 'Tạo tài khoản' : (request()->routeIs('login') ? 'Đăng nhập' : 'Xác thực tài khoản') }} · MedCare</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        <aside class="relative hidden overflow-hidden bg-[#073b4c] px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-16">
            <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full border border-white/10"></div>
            <div class="absolute -bottom-48 -left-40 h-[34rem] w-[34rem] rounded-full border border-white/10"></div>

            <a href="{{ route('login') }}" class="relative flex w-fit items-center gap-3 text-xl font-bold tracking-tight text-white">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white/10 text-2xl text-teal-200" aria-hidden="true">✚</span>
                MedCare
            </a>

            <div class="relative max-w-xl py-16">
                <p class="mb-5 text-sm font-semibold uppercase tracking-[0.2em] text-teal-200">Chăm sóc sức khỏe tận tâm</p>
                <h1 class="text-4xl font-semibold leading-tight xl:text-5xl">Sức khỏe của bạn, ưu tiên của chúng tôi.</h1>
                <p class="mt-6 max-w-lg text-base leading-7 text-slate-200">
                    Đặt lịch khám và kết nối với đội ngũ bác sĩ chuyên nghiệp một cách thuận tiện, an toàn.
                </p>

                <div class="mt-10 flex flex-col gap-4 text-sm text-slate-100">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-teal-300/15 text-teal-200" aria-hidden="true">✓</span>
                        Quản lý lịch hẹn dễ dàng
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-teal-300/15 text-teal-200" aria-hidden="true">✓</span>
                        Kết nối cùng bác sĩ đáng tin cậy
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-slate-300">Đồng hành cùng bạn trên hành trình sống khỏe.</p>
        </aside>

        <main class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
            <div class="w-full max-w-md">
                <a href="{{ route('login') }}" class="mb-8 flex items-center justify-center gap-3 text-xl font-bold tracking-tight text-[#073b4c] lg:hidden">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-teal-50 text-2xl text-teal-700" aria-hidden="true">✚</span>
                    MedCare
                </a>

                <div class="mb-7">
                    <p class="text-sm font-semibold text-teal-700">Chào mừng bạn</p>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                        {{ request()->routeIs('register') ? 'Tạo tài khoản' : (request()->routeIs('login') ? 'Đăng nhập' : 'Xác thực tài khoản') }}
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        {{ request()->routeIs('register') ? 'Đăng ký để bắt đầu sử dụng dịch vụ của MedCare.' : 'Đăng nhập để tiếp tục sử dụng dịch vụ của MedCare.' }}
                    </p>
                </div>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
                    {{ $slot }}
                </section>

                <p class="mt-6 text-center text-xs text-slate-400">Thông tin của bạn luôn được bảo mật.</p>
            </div>
        </main>
    </div>
</body>
</html>
