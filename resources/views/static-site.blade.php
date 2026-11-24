<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#073b4c">
    <title>MedCare - Đặt lịch khám dễ dàng</title>
    <link rel="icon" href="./images/brand/medcare-mark.png">
    <style>
        :root { --navy:#073b4c; --teal:#087e8b; --mint:#e7f7f5; --ink:#16324f; --muted:#667d8a; --line:#dde8ec; }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { margin:0; color:var(--ink); background:#f7fafb; font-family:Arial,sans-serif; }
        a { color:inherit; text-decoration:none; }
        .topbar { display:flex; align-items:center; justify-content:space-between; min-height:76px; padding:12px 6%; background:var(--navy); color:#fff; }
        .brand { display:flex; align-items:center; gap:10px; font-size:22px; font-weight:800; }
        .brand img { width:54px; height:54px; object-fit:contain; }
        .nav { display:flex; gap:22px; font-size:14px; font-weight:700; }
        .nav a:hover { color:#73ddd7; }
        .hero { display:grid; grid-template-columns:1fr 1fr; align-items:center; min-height:520px; padding:56px 6%; background:linear-gradient(120deg,#073b4c,#087e8b); color:#fff; }
        .hero h1 { max-width:620px; margin:12px 0 20px; font-size:52px; line-height:1.08; letter-spacing:-2px; }
        .hero p { max-width:590px; color:#d8ecef; font-size:17px; line-height:1.8; }
        .eyebrow { color:#82ddd6; font-size:12px; font-weight:800; letter-spacing:2px; text-transform:uppercase; }
        .actions { display:flex; gap:12px; margin-top:28px; }
        .button { display:inline-flex; min-height:46px; align-items:center; justify-content:center; padding:0 18px; border-radius:10px; background:#fff; color:var(--navy); font-weight:800; }
        .button-outline { border:1px solid rgba(255,255,255,.35); background:rgba(255,255,255,.08); color:#fff; }
        .hero-art { display:grid; place-items:center; }
        .hero-art img { width:min(360px,90%); border-radius:28px; box-shadow:0 24px 60px rgba(0,0,0,.22); }
        .section { max-width:1180px; margin:auto; padding:68px 24px; }
        .section-head { display:flex; align-items:end; justify-content:space-between; gap:20px; margin-bottom:24px; }
        .section h2 { margin:8px 0 0; font-size:32px; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .card { overflow:hidden; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:0 14px 34px rgba(15,51,69,.07); }
        .card-body { padding:20px; }
        .card h3 { margin:0 0 8px; font-size:18px; }
        .card p { margin:0; color:var(--muted); font-size:14px; line-height:1.6; }
        .doctor { text-align:center; }
        .doctor img { width:100%; height:220px; object-fit:cover; object-position:center 20%; }
        .specialty { min-height:150px; display:grid; place-items:center; text-align:center; background:linear-gradient(145deg,#effaf8,#fff); }
        .specialty strong { font-size:18px; }
        footer { padding:28px 6%; color:#b9cfd6; background:#073b4c; text-align:center; font-size:13px; }
        @media (max-width:760px) {
            .topbar { flex-wrap:wrap; gap:10px; }
            .nav { width:100%; gap:14px; overflow-x:auto; padding-bottom:4px; }
            .hero { grid-template-columns:1fr; padding:42px 6%; }
            .hero h1 { font-size:38px; }
            .hero-art { margin-top:36px; }
            .grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="#top" aria-label="MedCare">
            <img src="./images/brand/medcare-mark.png" alt="Logo MedCare">
            <span>MedCare</span>
        </a>
        <nav class="nav" aria-label="Điều hướng">
            <a href="#doctors">Bác sĩ</a>
            <a href="#specialties">Chuyên khoa</a>
            <a href="#services">Dịch vụ</a>
        </nav>
    </header>

    <main id="top">
        <section class="hero">
            <div>
                <p class="eyebrow">Chăm sóc sức khỏe tiện lợi</p>
                <h1>Đặt lịch khám, kết nối bác sĩ trong vài bước.</h1>
                <p>MedCare giúp bạn chủ động lựa chọn bác sĩ, chuyên khoa và dịch vụ y tế phù hợp.</p>
                <div class="actions">
                    <a class="button" href="#doctors">Tìm bác sĩ</a>
                    <a class="button button-outline" href="#services">Xem dịch vụ</a>
                </div>
            </div>
            <div class="hero-art">
                <img src="./images/brand/medcare-logo.png" alt="MedCare">
            </div>
        </section>

        <section class="section" id="doctors">
            <div class="section-head">
                <div><p class="eyebrow">Đội ngũ chuyên môn</p><h2>Bác sĩ tiêu biểu</h2></div>
            </div>
            <div class="grid">
                <article class="card doctor">
                    <img src="./images/doctors/doctor-le-minh-quan.png" alt="Bác sĩ Lê Minh Quân">
                    <div class="card-body"><h3>Bác sĩ Lê Minh Quân</h3><p>Tim mạch · Đang nhận lịch</p></div>
                </article>
                <article class="card doctor">
                    <img src="./images/doctors/doctor-tran-lan-anh.png" alt="Bác sĩ Trần Lan Anh">
                    <div class="card-body"><h3>Bác sĩ Trần Lan Anh</h3><p>Nhi khoa · Đang nhận lịch</p></div>
                </article>
                <article class="card doctor">
                    <img src="./images/doctors/doctor-nguyen-ngoc-mai.png" alt="Bác sĩ Nguyễn Ngọc Mai">
                    <div class="card-body"><h3>Bác sĩ Nguyễn Ngọc Mai</h3><p>Tai mũi họng · Đang nhận lịch</p></div>
                </article>
            </div>
        </section>

        <section class="section" id="specialties">
            <div class="section-head"><div><p class="eyebrow">Chuyên khoa</p><h2>Chăm sóc đa dạng</h2></div></div>
            <div class="grid">
                <article class="card specialty"><strong>Nội tổng quát</strong></article>
                <article class="card specialty"><strong>Tim mạch</strong></article>
                <article class="card specialty"><strong>Nhi khoa</strong></article>
            </div>
        </section>

        <section class="section" id="services">
            <div class="section-head"><div><p class="eyebrow">Dịch vụ y tế</p><h2>Khám và tư vấn</h2></div></div>
            <div class="grid">
                <article class="card"><div class="card-body"><h3>Khám tổng quát</h3><p>Khám sức khỏe định kỳ và tư vấn cận lâm sàng.</p></div></article>
                <article class="card"><div class="card-body"><h3>Khám chuyên khoa</h3><p>Khám và theo dõi theo từng chuyên khoa.</p></div></article>
                <article class="card"><div class="card-body"><h3>Tư vấn dinh dưỡng</h3><p>Đánh giá cân nặng và xây dựng chế độ ăn phù hợp.</p></div></article>
            </div>
        </section>
    </main>

    <footer>© 2026 MedCare · Nền tảng đặt lịch khám và chăm sóc sức khỏe</footer>
</body>
</html>
