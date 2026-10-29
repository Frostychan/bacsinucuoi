<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#073b4c">
    <title>@yield('title', 'MedCare') · MedCare</title>
    <style>
        :root {
            --ink: #16324f; --muted: #678096; --line: #dceaf1; --surface: #fff;
            --canvas: #eef6f8; --primary: #087e8b; --primary-dark: #05606c;
            --success: #14866d; --danger: #c84b5c; --warning: #a66a12;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; color: var(--ink); background: var(--canvas); }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        .app-shell { display: flex; min-height: 100vh; flex-direction: column; }
        .brand { display: flex; align-items: center; gap: 11px; color: #fff; font-size: 20px; font-weight: 800; letter-spacing: -.03em; white-space: nowrap; }
        .brand-mark { display: grid; width: 36px; height: 36px; place-items: center; border-radius: 11px; color: #087e8b; background: #dff7f7; font-size: 18px; }
        .nav-link { display: inline-flex; align-items: center; gap: 7px; padding: 9px 10px; border-radius: 9px; color: #c4dce5; font-size: 13px; font-weight: 600; transition: .2s ease; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { color: #fff; background: #0c5366; }
        .nav-link.active { box-shadow: inset 0 -2px 0 #66d8d4; }
        .nav-icon { width: 20px; text-align: center; }
        .main { display: flex; min-width: 0; flex: 1; flex-direction: column; }
        .topbar { position: relative; z-index: 5; display: flex; align-items: center; gap: 22px; min-height: 76px; padding: 12px 5.5%; color: #c4dce5; background: linear-gradient(110deg, #063746, #075365); box-shadow: 0 8px 24px rgba(7,59,76,.14); }
        .topbar .topnav { display: flex; align-items: center; gap: 2px; min-width: 0; flex: 1; flex-wrap: wrap; }
        .topbar .topnav .nav-link > span:not(.nav-icon) { display: inline-block; max-width: 180px; overflow: hidden; opacity: 1; transition: max-width .22s ease, opacity .16s ease; }
        .topbar .user-chip { flex: 0 0 auto; color: #c4dce5; }
        .role-badge { display: inline-flex; align-items: center; padding: 4px 8px; border: 1px solid rgba(255,255,255,.16); border-radius: 999px; color: #d9f2f1; background: rgba(255,255,255,.08); font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .topbar .user-chip .avatar { color: #087e8b; background: #dff7f7; }
        .topbar .user-name { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .customer-profile-link:hover, .customer-profile-link:focus-visible { color: #fff; text-decoration: underline; text-underline-offset: 3px; }
        .topbar .logout-button { padding: 8px 0 8px 8px; border: 0; color: #a6c3cc; background: transparent; cursor: pointer; font-size: 12px; }
        .topbar .logout-button:hover { color: #fff; }
        .breadcrumb { color: var(--muted); font-size: 13px; }
        .breadcrumb strong { color: var(--ink); }
        .user-chip { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 13px; font-weight: 600; }
        .avatar { display: grid; width: 35px; height: 35px; place-items: center; border-radius: 50%; color: #fff; background: var(--primary); font-size: 12px; }
        .content { width: min(1180px, 89%); margin: 0 auto; padding: 46px 0 70px; flex: 1; }
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 28px; }
        .eyebrow { margin: 0 0 8px; color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { margin-bottom: 8px; font-size: clamp(25px, 3vw, 34px); letter-spacing: -.045em; }
        .subtitle { margin: 0; color: var(--muted); font-size: 14px; }
        .card { border: 1px solid var(--line); border-radius: 18px; background: rgba(255,255,255,.94); box-shadow: 0 14px 40px rgba(15, 23, 42, .055); }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 680px; }
        th { padding: 15px 22px; color: #86a0af; background: #fbfcfe; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-align: left; text-transform: uppercase; }
        td { padding: 18px 22px; border-top: 1px solid var(--line); color: #475d70; font-size: 14px; vertical-align: middle; }
        tr:hover td { background: #fcfeff; }
        .product-name, .appointment-name { color: var(--ink); font-weight: 700; }
        .product-id { color: #8ca5b3; font-size: 12px; }
        .price { color: var(--primary-dark); font-weight: 800; }
        .stock, .status { display: inline-flex; padding: 5px 10px; border-radius: 20px; color: var(--success); background: #e5f7f0; font-size: 12px; font-weight: 700; }
        .stock.empty, .status.pending { color: var(--warning); background: #fff5dd; }
        .actions { display: flex; align-items: center; gap: 7px; white-space: nowrap; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px; padding: 0 14px; border: 0; border-radius: 9px; cursor: pointer; font-size: 13px; font-weight: 700; transition: .2s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { color: #fff; background: var(--primary); box-shadow: 0 5px 12px rgba(8,126,139,.2); }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-ghost { color: #475d70; background: #edf4f7; }
        .btn-warning { color: var(--warning); background: #fff7df; }
        .btn-danger { color: var(--danger); background: #fff0f2; }
        .empty-state { padding: 64px 20px; color: var(--muted); text-align: center; }
        .empty-icon { display: grid; width: 52px; height: 52px; margin: 0 auto 14px; place-items: center; border-radius: 16px; color: var(--primary); background: #e2f6f6; font-size: 22px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 30px; }
        .catalog-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
        .catalog-card { display: flex; align-items: flex-start; gap: 16px; padding: 22px; }
        .catalog-card h2 { margin: 0 0 8px; color: var(--ink); font-size: 17px; }
        .catalog-card p { margin: 0 0 12px; color: var(--muted); font-size: 13px; line-height: 1.6; }
        .catalog-icon, .doctor-avatar { display: grid; width: 44px; height: 44px; flex: 0 0 44px; place-items: center; border-radius: 13px; color: var(--primary); background: #e2f6f6; font-weight: 800; }
        .doctor-avatar { border-radius: 50%; color: #fff; background: var(--primary); }
        .patient-hero { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(250px, .7fr); align-items: center; gap: 30px; min-height: 330px; margin-bottom: 58px; padding: clamp(28px, 5vw, 58px); overflow: hidden; border-radius: 24px; color: #fff; background: linear-gradient(120deg, #073b4c, #087e8b); box-shadow: 0 20px 44px rgba(7, 59, 76, .18); }
        .patient-hero .eyebrow { color: #a8e5df; }
        .patient-hero h1 { max-width: 680px; margin: 0 0 16px; font-size: clamp(30px, 4vw, 46px); line-height: 1.12; }
        .patient-hero-description { max-width: 620px; margin: 0; color: #d1e7e9; font-size: 15px; line-height: 1.8; }
        .patient-hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
        .patient-hero .btn-primary { color: #075365; background: #fff; box-shadow: none; }
        .patient-hero .btn-primary:hover { background: #e9fbf9; }
        .btn-hero-ghost { border: 1px solid rgba(255,255,255,.3); color: #fff; background: rgba(255,255,255,.08); }
        .btn-hero-ghost:hover { background: rgba(255,255,255,.16); }
        .patient-hero-art { position: relative; display: grid; min-height: 220px; place-items: center; }
        .patient-hero-art::before, .patient-hero-art::after { position: absolute; width: 220px; height: 220px; border: 1px solid rgba(255,255,255,.18); border-radius: 50%; content: ""; }
        .patient-hero-art::after { width: 165px; height: 165px; }
        .hero-cross { position: relative; z-index: 1; display: grid; width: 112px; height: 112px; place-items: center; border: 1px solid rgba(255,255,255,.3); border-radius: 32px; color: #c4f4ec; background: rgba(255,255,255,.1); font-size: 62px; box-shadow: 0 18px 42px rgba(1,36,48,.18); }
        .hero-note { position: absolute; right: -2px; bottom: 6px; z-index: 2; display: flex; align-items: center; gap: 11px; padding: 13px 16px; border: 1px solid rgba(255,255,255,.3); border-radius: 14px; color: var(--ink); background: #fff; box-shadow: 0 10px 24px rgba(1,36,48,.18); }
        .hero-note-icon { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 50%; color: #087e8b; background: #e2f6f6; }
        .hero-note strong, .hero-note small { display: block; }
        .hero-note strong { font-size: 12px; }
        .hero-note small { margin-top: 3px; color: var(--muted); font-size: 10px; }
        .about-section, .doctor-section, .recent-appointments { margin-bottom: 58px; }
        .section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
        .section-heading .eyebrow { margin-bottom: 7px; }
        .section-heading h2 { margin: 0; color: var(--ink); font-size: clamp(22px, 3vw, 29px); letter-spacing: -.035em; }
        .section-heading > p { max-width: 460px; margin: 0; color: var(--muted); font-size: 13px; line-height: 1.7; }
        .benefit-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .benefit-card { padding: 23px; }
        .benefit-icon { display: grid; width: 43px; height: 43px; margin-bottom: 17px; place-items: center; border-radius: 14px; color: var(--primary); background: #e2f6f6; font-size: 21px; }
        .benefit-card h3 { margin: 0 0 9px; color: var(--ink); font-size: 16px; }
        .benefit-card p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.7; }
        .doctor-showcase, .doctor-catalog-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
        .doctor-showcase-card, .catalog-doctor-card { min-width: 0; }
        .doctor-showcase-card { overflow: hidden; }
        .doctor-photo-wrap { position: relative; height: 210px; overflow: hidden; background: linear-gradient(145deg, #d9eff0, #eff7f7); }
        .doctor-photo { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center 30%; }
        .doctor-photo-placeholder { display: grid; place-items: center; color: #087e8b; font-size: 62px; font-weight: 700; }
        .doctor-availability { position: absolute; top: 14px; left: 14px; padding: 6px 10px; border: 1px solid rgba(255,255,255,.7); border-radius: 999px; color: #087252; background: rgba(255,255,255,.92); font-size: 10px; font-weight: 800; }
        .doctor-showcase-info { padding: 19px; }
        .doctor-specialty { margin: 0 0 6px; color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .doctor-showcase-info h3 { margin: 0 0 9px; font-size: 18px; }
        .doctor-showcase-info .doctor-bio { min-height: 62px; margin: 0 0 14px; color: var(--muted); font-size: 12px; line-height: 1.65; }
        .doctor-fee { display: flex; justify-content: space-between; gap: 10px; margin: 0 0 14px; color: var(--muted); font-size: 12px; }
        .doctor-fee strong { color: var(--ink); }
        .doctor-book-link { width: 100%; }
        .doctor-showcase-more { display: flex; justify-content: center; margin-top: 24px; }
        .patient-booking-banner { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 58px; padding: 27px 30px; border: 1px solid #d1eceb; border-radius: 18px; background: linear-gradient(110deg, #e4f7f5, #f6fbfb); }
        .patient-booking-banner .eyebrow { margin-bottom: 7px; }
        .patient-booking-banner h2 { margin: 0 0 7px; font-size: 20px; }
        .patient-booking-banner p:last-child { margin: 0; color: var(--muted); font-size: 13px; }
        .recent-appointments-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .recent-appointment-card { display: flex; align-items: flex-start; gap: 16px; min-width: 0; padding: 20px; transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
        .recent-appointment-card:hover { transform: translateY(-2px); border-color: #b8dadd; box-shadow: 0 16px 34px rgba(15, 65, 75, .09); }
        .appointment-date-badge { display: flex; width: 58px; min-width: 58px; min-height: 64px; flex-direction: column; align-items: center; justify-content: center; gap: 2px; border: 1px solid #d6eeee; border-radius: 15px; color: var(--primary-dark); background: #effafa; }
        .appointment-date-badge > span { font-size: 22px; font-weight: 800; line-height: 1; }
        .appointment-date-badge small { color: var(--muted); font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .recent-appointment-details { display: flex; min-width: 0; flex: 1; flex-direction: column; gap: 7px; }
        .recent-appointment-topline, .recent-appointment-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .recent-appointment-time { margin: 0; color: var(--muted); font-size: 11px; font-weight: 600; }
        .recent-appointment-details h3 { margin: 0; overflow: hidden; color: var(--ink); font-size: 15px; text-overflow: ellipsis; white-space: nowrap; }
        .recent-appointment-reason { margin: 0; overflow: hidden; color: var(--muted); font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .recent-appointment-footer { margin-top: 2px; padding-top: 9px; border-top: 1px solid #edf2f4; }
        .recent-appointment-fee { color: var(--ink); font-size: 12px; font-weight: 800; }
        .recent-appointment-link { color: var(--primary); font-size: 11px; font-weight: 800; white-space: nowrap; }
        .recent-appointment-link:hover { color: var(--primary-dark); }
        .appointment-status { display: inline-flex; flex: 0 0 auto; align-items: center; padding: 5px 9px; border-radius: 999px; color: #765c16; background: #fff5dc; font-size: 10px; font-weight: 800; white-space: nowrap; }
        .appointment-status-confirmed { color: #765c16; background: #fff0bc; }
        .appointment-status-completed { color: #285e9a; background: #eaf2ff; }
        .appointment-status-cancelled { color: #a13d4d; background: #fff0f1; }
        .appointment-status-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
        .btn-status-confirmed { color: #765c16; background: #ffe08a; }
        .btn-status-confirmed:hover { background: #ffd45e; }
        .btn-status-completed { color: #fff; background: #14866d; }
        .btn-status-completed:hover { background: #0f705b; }
        .btn-status-cancelled { color: #a13d4d; background: #fff0f1; }
        .btn-status-cancelled:hover { background: #ffe0e4; }
        .appointment-edit-card { max-width: 850px; padding: 28px; }
        .doctor-admin-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; margin-bottom: 20px; }
        .doctor-admin-card { min-width: 0; overflow: hidden; }
        .doctor-admin-summary { display: grid; grid-template-columns: 76px minmax(0, 1fr) 24px; align-items: center; gap: 13px; padding: 16px; cursor: pointer; list-style: none; }
        .doctor-admin-summary::-webkit-details-marker { display: none; }
        .doctor-admin-photo { display: grid; width: 76px; height: 76px; place-items: center; overflow: hidden; border-radius: 16px; background: #e2f6f6; object-fit: cover; }
        .doctor-admin-placeholder { color: var(--primary); font-size: 30px; font-weight: 800; }
        .doctor-admin-summary-copy { display: grid; min-width: 0; gap: 5px; }
        .doctor-admin-summary-copy strong { overflow: hidden; color: var(--ink); font-size: 14px; text-overflow: ellipsis; }
        .doctor-admin-summary-copy span, .doctor-admin-summary-copy small { overflow: hidden; color: var(--muted); font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .doctor-admin-toggle { display: grid; width: 24px; height: 24px; place-items: center; border-radius: 50%; color: var(--primary); background: #e2f6f6; font-size: 18px; font-weight: 700; }
        .doctor-admin-card[open] .doctor-admin-toggle { transform: rotate(45deg); }
        .doctor-admin-details { padding: 0 16px 18px; border-top: 1px solid var(--line); }
        .doctor-admin-details h2, .doctor-admin-details h3 { margin: 16px 0 12px; color: var(--ink); font-size: 14px; }
        .doctor-admin-facts { display: grid; gap: 11px; margin: 0; }
        .doctor-admin-facts > div { display: grid; gap: 3px; }
        .doctor-admin-facts dt { color: var(--muted); font-size: 11px; }
        .doctor-admin-facts dd { margin: 0; color: var(--ink); font-size: 12px; font-weight: 600; overflow-wrap: anywhere; }
        .doctor-admin-facts .doctor-admin-bio dd { font-weight: 400; line-height: 1.6; white-space: pre-wrap; }
        .doctor-catalog-grid { align-items: stretch; }
        .catalog-doctor-card { overflow: hidden; }
        .catalog-doctor-photo-wrap { height: 210px; overflow: hidden; background: linear-gradient(145deg, #d9eff0, #eff7f7); }
        .catalog-doctor-photo { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center 30%; }
        .catalog-doctor-placeholder { display: grid; place-items: center; color: var(--primary); font-size: 62px; font-weight: 700; }
        .catalog-doctor-info { padding: 20px; }
        .catalog-doctor-info h2 { margin: 0 0 9px; color: var(--ink); font-size: 18px; }
        .catalog-doctor-info .doctor-specialty { margin: 0 0 6px; color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .catalog-doctor-info .catalog-doctor-bio { min-height: 42px; margin: 0 0 16px; color: var(--muted); font-size: 13px; line-height: 1.65; }
        .catalog-doctor-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .user-role-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 22px; }
        .user-role-card { display: grid; gap: 9px; padding: 18px; border: 1px solid transparent; color: inherit; text-decoration: none; transition: .18s ease; }
        .user-role-card:hover, .user-role-card.is-active { border-color: var(--primary); background: #f1fbfb; box-shadow: 0 8px 20px rgba(8,126,139,.1); transform: translateY(-2px); }
        .user-role-card-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .user-role-card-title { color: var(--ink); font-size: 15px; font-weight: 800; }
        .user-role-card-count { color: var(--primary-dark); font-size: 20px; font-weight: 800; }
        .user-role-card-hint { color: var(--muted); font-size: 12px; }
        .user-role-list { overflow: hidden; }
        .user-role-list-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 22px; }
        .user-role-list-heading .eyebrow { margin: 0 0 5px; }
        .user-role-list-heading h2 { margin: 0; color: var(--ink); font-size: 18px; }
        .user-role-pagination { padding: 18px 22px; }
        .admin-quick-management { margin-top: 24px; padding: 22px; }
        .admin-appointments-link { gap: 10px; }
        .pending-appointments-badge { padding: 4px 8px; border-radius: 999px; color: #8a4b08; background: #fff1cb; font-size: 11px; font-weight: 800; }
        .dashboard-pending-appointments { margin-top: 20px; overflow: hidden; }
        .dashboard-pending-appointments .user-role-list-heading { padding-bottom: 16px; }
        .dashboard-pending-list { display: grid; }
        .dashboard-pending-item { display: grid; grid-template-columns: 105px minmax(0, 1fr) auto; align-items: center; gap: 18px; padding: 16px 22px; border-top: 1px solid var(--line); }
        .dashboard-pending-time { display: grid; gap: 4px; color: var(--ink); }
        .dashboard-pending-time strong { font-size: 13px; }
        .dashboard-pending-time span, .dashboard-pending-details span { color: var(--muted); font-size: 12px; }
        .dashboard-pending-details { min-width: 0; }
        .dashboard-pending-details > strong { color: var(--ink); font-size: 14px; }
        .dashboard-pending-details p { margin: 5px 0 0; color: var(--muted); font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dashboard-pending-empty { padding: 24px 18px; }
        .patient-user-list { display: grid; gap: 12px; padding: 0 22px 22px; }
        .patient-user-card { overflow: hidden; }
        .patient-user-summary { display: grid; grid-template-columns: 44px minmax(0, 1fr) auto 24px; align-items: center; gap: 14px; padding: 15px 18px; cursor: pointer; list-style: none; }
        .patient-user-summary::-webkit-details-marker { display: none; }
        .patient-user-avatar { display: grid; width: 44px; height: 44px; place-items: center; border-radius: 50%; color: #fff; background: var(--primary); font-weight: 800; }
        .patient-user-summary-copy { display: grid; min-width: 0; gap: 4px; }
        .patient-user-summary-copy strong { overflow: hidden; color: var(--ink); text-overflow: ellipsis; white-space: nowrap; }
        .patient-user-summary-copy span, .patient-user-created { color: var(--muted); font-size: 12px; }
        .patient-user-summary-copy span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .patient-user-toggle { display: grid; width: 24px; height: 24px; place-items: center; border-radius: 50%; color: var(--primary); background: #e2f6f6; font-size: 18px; font-weight: 700; }
        .patient-user-card[open] .patient-user-toggle { transform: rotate(45deg); }
        .patient-user-details { padding: 0 18px 18px; border-top: 1px solid var(--line); }
        .patient-user-details h3 { margin: 16px 0 11px; color: var(--ink); font-size: 14px; }
        .patient-user-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 18px; margin: 0; }
        .patient-user-facts > div { display: grid; gap: 3px; min-width: 0; }
        .patient-user-facts dt { color: var(--muted); font-size: 11px; }
        .patient-user-facts dd { margin: 0; color: var(--ink); font-size: 13px; overflow-wrap: anywhere; }
        .patient-user-facts .patient-user-full { grid-column: 1 / -1; }
        .patient-user-role { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: end; gap: 8px; margin-top: 16px; }
        .patient-user-role label { grid-column: 1 / -1; margin: 0; }
        .patient-user-role .form-control { width: auto; padding: 8px 10px; font-size: 12px; }
        .booking-card { max-width: none; margin-bottom: 28px; padding: 22px; }
        .booking-card .section-title { padding: 0 0 20px; }
        .booking-card .section-link { color: var(--muted); font-weight: 600; }
        .booking-layout { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(260px, .8fr); gap: 20px; align-items: start; }
        .booking-info-column { display: grid; grid-column: 1; grid-row: 1; gap: 20px; }
        .doctor-picker-panel, .booking-schedule-panel, .booking-details-panel { padding: 22px; }
        .doctor-picker-panel { grid-column: 2; grid-row: 1; }
        .doctor-picker-panel .section-title, .booking-schedule-panel .section-title, .booking-details-panel .section-title { padding: 0 0 16px; }
        .doctor-picker-list { display: grid; gap: 10px; max-height: 680px; overflow-y: auto; }
        .doctor-choice { display: flex; align-items: flex-start; gap: 12px; padding: 15px; border: 1px solid var(--line); border-radius: 12px; cursor: pointer; transition: .18s ease; }
        .doctor-choice:hover, .doctor-choice.selected { border-color: var(--primary); background: #f1fbfb; box-shadow: 0 0 0 2px rgba(8,126,139,.08); }
        .doctor-choice input { margin-top: 4px; accent-color: var(--primary); }
        .doctor-choice-copy { display: grid; gap: 5px; }
        .doctor-choice-copy strong { color: var(--ink); font-size: 14px; }
        .doctor-choice-copy small { color: var(--muted); }
        .doctor-choice-fee { color: var(--primary-dark); font-size: 12px; font-weight: 800; }
        .booking-error { color: var(--danger); }
        .symptom-context { margin-bottom: 20px; padding: 22px; border-left: 4px solid var(--primary); }
        .symptom-context h2, .symptom-context h3 { margin: 0 0 8px; font-size: 15px; }
        .symptom-context p { margin: 0 0 16px; color: #475d70; white-space: pre-wrap; }
        .symptom-context small { color: var(--muted); }
        .stat-card { display: flex; align-items: center; gap: 14px; padding: 20px; }
        .stat-icon { display: grid; width: 44px; height: 44px; place-items: center; border-radius: 13px; color: #fff; background: var(--primary); }
        .stat-icon.blue { background: #3b9bc6; } .stat-icon.green { background: #42a889; }
        .stat-value { margin: 0 0 3px; color: var(--ink); font-size: 23px; font-weight: 800; }
        .stat-label { margin: 0; color: var(--muted); font-size: 12px; }
        .section-title { display: flex; align-items: center; justify-content: space-between; padding: 20px 22px 4px; }
        .section-title h2 { margin: 0; font-size: 16px; }
        .section-link { color: var(--primary); font-size: 12px; font-weight: 700; }
        .detail-card, .form-card { max-width: 760px; padding: 30px; }
        .detail-row { display: flex; justify-content: space-between; gap: 20px; padding: 17px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .detail-card > .detail-row:first-child { padding-top: 0; }
        .detail-card > .detail-row:last-child { border-bottom: 0; }
        .detail-label { color: var(--muted); } .detail-value { max-width: 60%; color: var(--ink); font-weight: 700; text-align: right; }
        .detail-value.medical-note { text-align: left; white-space: pre-line; overflow-wrap: anywhere; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; } .form-group.full { grid-column: 1 / -1; }
        .form-help { margin: 7px 0 0; color: var(--muted); font-size: 12px; }
        label { display: block; margin-bottom: 8px; color: #334d61; font-size: 13px; font-weight: 700; }
        .required { color: var(--danger); }
        .form-control { display: block; width: 100%; padding: 12px 13px; border: 1px solid #d3e2e9; border-radius: 9px; outline: none; color: var(--ink); background: #fff; font-size: 14px; }
        .form-control:focus { border-color: #48bfc0; box-shadow: 0 0 0 3px #d8f4f3; }
        textarea.form-control { resize: vertical; } .form-help { margin: 7px 0 0; color: #8da4b1; font-size: 12px; }
        .medicine-selection { display: flex; min-height: 38px; flex-wrap: wrap; gap: 8px; }
        .medicine-chip { display: inline-flex; align-items: center; gap: 7px; padding: 5px 7px 5px 11px; border: 1px solid #b8e5df; border-radius: 999px; color: #075f62; background: #e9f8f5; font-size: 12px; font-weight: 700; }
        .selected-medicine-row { display: flex; min-width: 0; flex: 1 1 100%; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 10px; background: #fbfefe; }
        .selected-medicine-name { min-width: 0; flex: 1; color: var(--ink); font-size: 13px; font-weight: 700; overflow-wrap: anywhere; }
        .selected-medicine-quantity-label { display: flex; flex: 0 0 auto; align-items: center; gap: 8px; margin: 0; color: var(--muted); font-size: 12px; white-space: nowrap; }
        .selected-medicine-quantity { width: 88px; padding: 8px 9px; }
        .medicine-remove { display: grid; width: 22px; height: 22px; place-items: center; padding: 0; border: 0; border-radius: 50%; color: #9b3747; background: #fff; cursor: pointer; font-size: 17px; line-height: 1; }
        .medicine-remove:hover { color: #fff; background: var(--danger); }
        .medicine-table-wrap { max-height: 360px; margin: -5px 0 20px; overflow: auto; border: 1px solid var(--line); border-radius: 10px; }
        .medicine-table { min-width: 0; }
        .medicine-table th { position: sticky; top: 0; z-index: 1; }
        .medicine-table td { padding: 0; }
        .medicine-option { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 22px; border: 0; color: var(--ink); background: #fff; cursor: pointer; font-size: 13px; text-align: left; }
        .medicine-option:hover:not(:disabled), .medicine-option:focus-visible { outline: none; background: #eef9f8; }
        .medicine-option:disabled { color: #8da4b1; background: #fbfcfe; cursor: default; }
        .medicine-option-action { color: var(--primary); font-size: 12px; font-weight: 700; }
        .medicine-option:disabled .medicine-option-action { color: var(--success); }
        .medicine-table-wrap > .form-help { padding: 0 15px 12px; }
        .medicine-order-notice { margin-bottom: 20px; padding: 14px 16px; border-radius: 10px; color: #75520c; background: #fff7df; font-size: 13px; line-height: 1.6; }
        .medicine-order-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 16px 0; border-bottom: 1px solid var(--line); }
        .medicine-select-label { display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer; }
        .medicine-select-label input { width: 17px; height: 17px; accent-color: var(--primary); }
        .medicine-quantity-label { display: flex; align-items: center; gap: 10px; margin: 0; white-space: nowrap; }
        .medicine-quantity { width: 100px; padding: 8px 10px; }
        .medicine-order-confirmation h2 { margin-bottom: 8px; color: var(--success); font-size: 19px; }
        .medicine-order-confirmation p, .medicine-order-confirmation li { color: var(--muted); font-size: 14px; line-height: 1.7; }
        .medicine-order-confirmation ul { padding-left: 20px; }
        .payment-simulation-notice { margin-bottom: 22px; padding: 14px 16px; border-radius: 10px; color: #75520c; background: #fff7df; font-size: 13px; line-height: 1.6; }
        .payment-simulation-result { margin-bottom: 22px; padding: 18px; border: 1px solid #b8e5df; border-radius: 12px; color: #075f62; background: #e9f8f5; line-height: 1.6; }
        .payment-simulation-result h2 { margin: 0 0 8px; font-size: 18px; }
        .payment-simulation-result p { margin: 0 0 8px; font-size: 13px; }
        .payment-simulation-result p:last-child { margin-bottom: 0; }
        .payment-order-summary { margin-bottom: 22px; }
        .payment-order-summary h2 { margin-bottom: 12px; font-size: 17px; }
        .payment-order-summary ul { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
        .payment-order-summary li, .payment-total { display: flex; justify-content: space-between; gap: 14px; padding: 10px 0; border-bottom: 1px solid var(--line); color: var(--ink); font-size: 13px; }
        .payment-total { margin-top: 10px; padding-top: 16px; font-size: 14px; }
        .payment-methods { display: grid; gap: 10px; margin: 0 0 20px; padding: 0; border: 0; }
        .payment-methods legend { margin-bottom: 10px; color: #334d61; font-size: 13px; font-weight: 700; }
        .payment-method { display: flex; align-items: center; gap: 10px; margin: 0; padding: 13px; border: 1px solid var(--line); border-radius: 9px; background: #fff; cursor: pointer; }
        .payment-method input { width: 17px; height: 17px; accent-color: var(--primary); }
        .virtual-qr-panel { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 26px; padding: 20px; border: 1px dashed #d9b654; border-radius: 14px; background: #fffaf0; }
        .virtual-qr-panel h2 { margin: 0 0 8px; color: var(--ink); font-size: 19px; }
        .virtual-qr-panel p:last-child { margin-bottom: 0; color: var(--muted); font-size: 13px; line-height: 1.6; }
        .virtual-qr { width: 128px; height: 128px; flex: 0 0 128px; padding: 8px; border: 1px solid #e8e1ce; border-radius: 8px; background: #fff; }
        .form-actions { display: flex; gap: 10px; margin-top: 8px; padding-top: 22px; border-top: 1px solid var(--line); }
        .profile-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr); gap: 20px; align-items: start; }
        .profile-card { padding: 26px; }
        .profile-card.wide { grid-row: span 2; }
        .profile-section header { padding-bottom: 18px; border-bottom: 1px solid var(--line); }
        .profile-section h2 { margin: 0 0 7px; color: var(--ink); font-size: 17px; }
        .profile-section header p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.6; }
        .profile-subsection { grid-column: 1 / -1; padding-top: 18px; border-top: 1px solid var(--line); }
        .profile-subsection h3 { margin: 0 0 6px; color: var(--ink); font-size: 15px; }
        .profile-subsection p { margin: 0; color: var(--muted); font-size: 12px; line-height: 1.6; }
        .profile-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .profile-form select, .profile-form textarea { display: block; width: 100%; padding: 11px 12px; border: 1px solid #d3e2e9; border-radius: 9px; outline: none; color: var(--ink); background: #fff; font-size: 14px; }
        .profile-form select:focus, .profile-form textarea:focus { border-color: #48bfc0; box-shadow: 0 0 0 3px #d8f4f3; }
        .patient-contact-card { max-width: 760px; margin-bottom: 20px; padding: 22px; }
        .patient-contact-card .section-title { padding: 0 0 18px; }
        .patient-contact-card .section-title h2 { margin: 0 0 5px; font-size: 17px; }
        .patient-contact-card .section-title p { margin: 0; color: var(--muted); font-size: 12px; }
        .patient-contact-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 22px; }
        .patient-contact-grid > div { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
        .patient-contact-grid strong { color: var(--ink); font-size: 13px; line-height: 1.6; overflow-wrap: anywhere; }
        .patient-contact-full { grid-column: 1 / -1; }
        .emergency-contact { display: grid; gap: 14px; margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--line); }
        .emergency-contact h3 { margin: 0; font-size: 14px; }
        .profile-form { margin-top: 22px; }
        .profile-form > div { margin-bottom: 18px; }
        .profile-form label { margin-bottom: 7px; }
        .profile-form input { display: block; width: 100%; padding: 11px 12px; border: 1px solid #d3e2e9; border-radius: 9px; outline: none; color: var(--ink); background: #fff; font-size: 14px; }
        .profile-form input:focus { border-color: #48bfc0; box-shadow: 0 0 0 3px #d8f4f3; }
        .profile-form .form-error { margin: 6px 0 0; color: var(--danger); font-size: 12px; }
        .profile-actions { display: flex; align-items: center; gap: 12px; padding-top: 4px; }
        .profile-saved { color: var(--success); font-size: 13px; font-weight: 700; }
        .danger-card { border-color: #f3d9de; }
        .danger-card .profile-section h2 { color: var(--danger); }
        .danger-card .profile-section header p { color: #8f6670; }
        .danger-button { display: inline-flex; align-items: center; min-height: 38px; padding: 0 14px; border: 0; border-radius: 9px; color: #fff; background: var(--danger); cursor: pointer; font-size: 13px; font-weight: 700; }
        .alert { margin-bottom: 20px; padding: 13px 16px; border-radius: 10px; color: #a2273e; background: #fff0f2; font-size: 13px; }
        footer { padding: 20px 5.5%; color: #8aa1ae; border-top: 1px solid var(--line); background: rgba(255,255,255,.45); font-size: 12px; }
        @media (min-width: 981px) {
            .topbar:not(:hover):not(:focus-within) .topnav .nav-link > span:not(.nav-icon) { max-width: 0; opacity: 0; }
            .topbar:not(:hover):not(:focus-within) .topnav .nav-link { gap: 0; }
        }
        @media (max-width: 980px) {
            .topbar { align-items: flex-start; flex-wrap: wrap; gap: 12px 18px; padding: 14px 5%; }
            .topbar .topnav { order: 3; flex-basis: 100%; }
            .topbar .user-chip { margin-left: auto; }
        }
        @media (max-width: 760px) {
            .virtual-qr-panel { align-items: flex-start; flex-direction: column-reverse; }
            .brand { font-size: 18px; } .brand-mark { width: 32px; height: 32px; }
            .topbar .user-name:not(.customer-profile-link), .topbar .logout-button, .role-badge { display: none; }
            .topbar .customer-profile-link { display: inline-flex; max-width: 112px; font-size: 11px; }
            .nav-link { padding: 8px 9px; font-size: 12px; }
            .content { width: 90%; padding-top: 30px; } .page-heading { align-items: flex-start; flex-direction: column; }
            .stats-grid { grid-template-columns: 1fr; } .form-grid { display: block; }
            .user-role-grid { grid-template-columns: 1fr; gap: 10px; }
            .user-role-card { padding: 15px 17px; }
            .patient-user-list { padding: 0 14px 14px; }
            .patient-user-summary { grid-template-columns: 40px minmax(0, 1fr) 24px; gap: 10px; padding: 13px; }
            .patient-user-avatar { width: 40px; height: 40px; }
            .patient-user-created { display: none; }
            .patient-user-facts { grid-template-columns: 1fr; }
            .doctor-admin-grid { grid-template-columns: 1fr; }
            .dashboard-pending-item { grid-template-columns: 1fr auto; gap: 10px; padding: 14px; }
            .dashboard-pending-time { grid-column: 1 / -1; grid-template-columns: auto auto; justify-content: start; gap: 8px; }
            .dashboard-pending-item > .btn { grid-column: 2; grid-row: 2; }
            .booking-layout { grid-template-columns: 1fr; }
            .booking-info-column, .doctor-picker-panel { grid-column: 1; grid-row: auto; }
            .doctor-picker-list { max-height: 360px; }
            .catalog-grid:not(.doctor-catalog-grid) { grid-template-columns: 1fr; }
            .patient-hero { grid-template-columns: 1fr; gap: 10px; }
            .medicine-order-row { align-items: flex-start; flex-direction: column; gap: 12px; }
            .patient-hero-art { min-height: 190px; }
            .hero-note { right: 0; }
            .benefit-grid, .recent-appointments-grid { grid-template-columns: 1fr; }
            .doctor-showcase, .doctor-catalog-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .recent-appointment-card { padding: 16px; }
            .recent-appointment-topline { align-items: flex-start; flex-direction: column; gap: 6px; }
            .profile-form-grid, .patient-contact-grid { grid-template-columns: 1fr; }
            .section-heading, .patient-booking-banner { align-items: flex-start; flex-direction: column; }
            .patient-booking-banner { padding: 22px; }
            .selected-medicine-row { flex-wrap: wrap; }
            .doctor-photo-wrap { height: 250px; }
            .form-card, .detail-card, .profile-card { padding: 22px; } .profile-grid { display: block; }
            .profile-card { margin-bottom: 18px; } .user-name { display: none; }
        }
        @media (max-width: 520px) {
            .doctor-showcase, .doctor-catalog-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <div class="main">
            <header class="topbar">
                <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">✚</span><span>MedCare</span></a>
                @php($role = auth()->user()->role ?? 'guest')
                <nav class="topnav" aria-label="Điều hướng chính">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard', '*.dashboard') ? 'active' : '' }}"><span class="nav-icon">⌂</span><span>Tổng quan</span></a>
                    <a href="{{ route('contact.create') }}" class="nav-link {{ request()->routeIs('contact.*') ? 'active' : '' }}"><span class="nav-icon">✉</span><span>Liên hệ</span></a>
                    @if (auth()->user()?->isRole('admin') && Route::has('admin.patient-overview'))
                        <a href="{{ route('admin.patient-overview') }}" class="nav-link {{ request()->routeIs('admin.patient-overview') ? 'active' : '' }}"><span class="nav-icon">⌕</span><span>Xem trang bệnh nhân</span></a>
                    @endif
                    @if (auth()->user()?->isRole('customer'))
                        <a href="{{ route('catalog.specialties') }}" class="nav-link {{ request()->routeIs('catalog.specialties') ? 'active' : '' }}"><span class="nav-icon">▦</span><span>Chuyên khoa</span></a>
                        <a href="{{ route('catalog.doctors') }}" class="nav-link {{ request()->routeIs('catalog.doctors') ? 'active' : '' }}"><span class="nav-icon">✚</span><span>Bác sĩ</span></a>
                        <a href="{{ route('patient.appointments.create') }}" class="nav-link {{ request()->routeIs('patient.appointments.create') ? 'active' : '' }}"><span class="nav-icon">＋</span><span>Đặt lịch khám</span></a>
                        <a href="{{ route('patient.appointments.index') }}" class="nav-link {{ request()->routeIs('patient.appointments.index') ? 'active' : '' }}"><span class="nav-icon">◷</span><span>Lịch khám của tôi</span></a>
                        <a href="{{ route('catalog.services') }}" class="nav-link {{ request()->routeIs('catalog.services') ? 'active' : '' }}"><span class="nav-icon">▤</span><span>Dịch vụ</span></a>
                    @elseif (! auth()->user()?->isRole('admin'))
                        <a href="{{ route('catalog.specialties') }}" class="nav-link {{ request()->routeIs('catalog.specialties') ? 'active' : '' }}"><span class="nav-icon">▦</span><span>Chuyên khoa</span></a>
                        <a href="{{ route('catalog.doctors') }}" class="nav-link {{ request()->routeIs('catalog.doctors') ? 'active' : '' }}"><span class="nav-icon">✚</span><span>Bác sĩ</span></a>
                        <a href="{{ route('catalog.services') }}" class="nav-link {{ request()->routeIs('catalog.services') ? 'active' : '' }}"><span class="nav-icon">▤</span><span>Dịch vụ</span></a>
                    @endif
                    @if (auth()->user()?->isRole('doctor') && Route::has('doctor.appointments.index'))
                        <a href="{{ route('doctor.appointments.index') }}" class="nav-link {{ request()->routeIs('doctor.appointments.*') ? 'active' : '' }}"><span class="nav-icon">◷</span><span>Lịch bệnh nhân</span></a>
                    @endif
                    @if (auth()->user()?->isRole('admin') && Route::has('admin.appointments.index'))
                        <a href="{{ route('admin.appointments.index') }}" class="nav-link {{ request()->routeIs('admin.appointments.*') ? 'active' : '' }}"><span class="nav-icon">◷</span><span>Tất cả lịch hẹn</span></a>
                    @endif
                    @if (auth()->user()?->isRole('admin') && Route::has('admin.products.index'))
                        <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><span class="nav-icon">▤</span><span>Quản lý dịch vụ</span></a>
                    @endif
                    @if (auth()->user()?->isRole('admin') && Route::has('admin.medicines.index'))
                        <a href="{{ route('admin.medicines.index') }}" class="nav-link {{ request()->routeIs('admin.medicines.*') ? 'active' : '' }}"><span class="nav-icon">✚</span><span>Giá thuốc</span></a>
                    @endif
                    @if (auth()->user()?->isRole('admin') && Route::has('admin.contact-messages.index'))
                        <a href="{{ route('admin.contact-messages.index') }}" class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}"><span class="nav-icon">✉</span><span>Liên hệ mới</span></a>
                    @endif
                    @if (auth()->user()?->isRole('admin') && Route::has('admin.users.index'))
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><span class="nav-icon">♙</span><span>Người dùng</span></a>
                    @endif
                    @if (auth()->user()?->isRole('admin') && Route::has('admin.doctors.index'))
                        <a href="{{ route('admin.doctors.index') }}" class="nav-link {{ request()->routeIs('admin.doctors.*') ? 'active' : '' }}"><span class="nav-icon">✚</span><span>Bác sĩ</span></a>
                    @endif
                    @if (Route::has('admin.specialties.index'))
                        <a href="{{ route('admin.specialties.index') }}" class="nav-link {{ request()->routeIs('admin.specialties.*') ? 'active' : '' }}"><span class="nav-icon">▦</span><span>Chuyên khoa</span></a>
                    @endif
                    @if (Route::has('admin.reports.index'))
                        <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"><span class="nav-icon">▤</span><span>Báo cáo</span></a>
                    @endif
                    @if (Route::has('profile.edit') && ! auth()->user()?->isRole('customer'))
                        <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}"><span class="nav-icon">◎</span><span>Hồ sơ</span></a>
                    @endif
                </nav>
                <div class="user-chip">
                    @if (auth()->user()?->isRole('customer') && Route::has('profile.edit'))
                        <a class="user-name customer-profile-link" href="{{ route('profile.edit') }}" aria-label="Hồ sơ khách hàng: {{ auth()->user()->name }}">{{ auth()->user()->name }}</a>
                    @else
                        <span class="user-name">{{ auth()->user()->name ?? 'Khách truy cập' }}</span>
                    @endif
                    @auth
                        <span class="role-badge">{{ match (auth()->user()->role) { 'admin' => 'Quản trị', 'doctor' => 'Bác sĩ', default => 'Khách hàng' } }}</span>
                    @endauth
                    <span class="avatar">{{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 1)) : 'M' }}</span>
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="logout-button" type="submit">Đăng xuất</button>
                        </form>
                    @endauth
                </div>
            </header>
            <main class="content">
                @if (session('success')) <div class="alert" style="color:#15785f;background:#e5f7f0;">{{ session('success') }}</div> @endif
                @if ($errors->any())
                    <div class="alert"><strong>Vui lòng kiểm tra lại:</strong><ul style="margin:7px 0 0 18px;padding:0;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @yield('content')
            </main>
            <footer>© 2026 MedCare · Nền tảng đặt lịch khám và chăm sóc sức khỏe</footer>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-appointment-picker]').forEach((form) => {
            const doctorInputs = form.querySelectorAll('[name="doctor_id"]');
            const dateInput = form.querySelector('[name="appointment_date"]');
            const slotSelect = form.querySelector('[data-availability-slots]');
            const message = form.querySelector('[data-availability-message]');
            const oldStartsAt = form.dataset.oldStartsAt || '';
            let requestNumber = 0;

            const selectedDoctor = () => {
                if (doctorInputs[0]?.type === 'radio') {
                    return form.querySelector('[name="doctor_id"]:checked');
                }

                return doctorInputs[0]?.selectedOptions?.[0] ?? null;
            };

            const refreshSlots = async () => {
                const currentRequest = ++requestNumber;
                const doctor = selectedDoctor();
                const date = dateInput?.value;
                slotSelect.replaceChildren(new Option('Chọn khung giờ', ''));
                slotSelect.disabled = true;

                if (!doctor?.dataset.availabilityUrl || !date) {
                    message.textContent = 'Chọn bác sĩ và ngày khám để xem giờ còn trống.';
                    return;
                }

                message.textContent = 'Đang tải giờ khám còn trống...';

                try {
                    const url = new URL(doctor.dataset.availabilityUrl, window.location.origin);
                    url.searchParams.set('date', date);
                    if (form.dataset.exceptAppointment) {
                        url.searchParams.set('except_appointment', form.dataset.exceptAppointment);
                    }

                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!response.ok) {
                        throw new Error(`Không thể tải lịch trống (HTTP ${response.status}).`);
                    }

                    const data = await response.json();
                    if (currentRequest !== requestNumber) {
                        return;
                    }

                    let slots = Array.isArray(data.slots) ? data.slots : [];
                    const currentStartsAt = form.dataset.currentStartsAt || '';
                    const currentDoctor = form.dataset.currentDoctor || '';
                    if (currentStartsAt.startsWith(`${date}T`)
                        && currentDoctor === doctor.value
                        && !slots.some((slot) => slot.value === currentStartsAt)) {
                        slots = [...slots, {
                            value: currentStartsAt,
                            label: `${currentStartsAt.slice(11)} · lịch hiện tại`,
                        }];
                    }

                    slots.forEach((slot) => {
                        slotSelect.add(new Option(slot.label, slot.value));
                    });
                    slotSelect.disabled = slots.length === 0;
                    const requestedSlot = oldStartsAt.startsWith(`${date}T`)
                        ? oldStartsAt
                        : currentStartsAt;
                    if (requestedSlot && [...slotSelect.options].some((option) => option.value === requestedSlot)) {
                        slotSelect.value = requestedSlot;
                    }

                    message.textContent = slots.length
                        ? 'Chọn một giờ còn trống. Khung khám hoạt động từ 08:00 đến 17:00.'
                        : 'Ngày này không còn khung giờ trống cho bác sĩ đã chọn.';
                } catch (error) {
                    if (currentRequest === requestNumber) {
                        message.textContent = 'Không tải được lịch trống. Vui lòng thử lại.';
                        console.error(error);
                    }
                }
            };

            doctorInputs.forEach((input) => input.addEventListener('change', () => {
                form.querySelectorAll('.doctor-choice').forEach((choice) => {
                    choice.classList.toggle('selected', choice.contains(input) && input.checked);
                });
                refreshSlots();
            }));
            const selectedRadio = form.querySelector('[name="doctor_id"]:checked');
            if (selectedRadio) {
                selectedRadio.closest('.doctor-choice')?.classList.add('selected');
            }
            dateInput?.addEventListener('change', refreshSlots);
            if (dateInput?.value && selectedDoctor()) {
                refreshSlots();
            }
        });
    </script>
</body>
</html>
