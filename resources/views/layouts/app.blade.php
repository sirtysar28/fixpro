<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Preconnect untuk mempercepat & menstabilkan loading font dari CDN --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></noscript>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #14b8a6;
            --primary-bg: rgba(13,148,136,.08);
            --accent: #f59e0b;
            --accent-dark: #d97706;
            --danger: #dc2626;
            --success: #16a34a;
            --warning: #f59e0b;
            --info: #2563eb;
            --sidebar-w: 260px;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; display: flex; min-height: 100vh; }
        h1, h2, h3, h4, h5 { font-weight: 700; }
        
        /* Sidebar */
        .sidebar { width: var(--sidebar-w); background: #fff; border-right: 1px solid #e2e8f0; position: fixed; height: 100vh; left: 0; top: 0; display: flex; flex-direction: column; z-index: 100; transition: transform .3s; }
        .sidebar-brand { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; }
        .sidebar-brand .logo { width: 40px; height: 40px; border-radius: 10px; overflow: hidden; border: 2px solid var(--primary-bg); }
        .sidebar-brand .logo img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-brand h1 { font-size: 1.2rem; margin: 0; }
        .sidebar-brand h1 span { color: var(--primary); }
        .sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 11px 20px; color: #64748b; text-decoration: none; font-size: .875rem; font-weight: 500; transition: all .2s; border-left: 3px solid transparent; }
        .sidebar-nav a:hover { background: var(--primary-bg); color: var(--primary-dark); }
        .sidebar-nav a.active { background: var(--primary-bg); color: var(--primary-dark); border-left-color: var(--primary); font-weight: 600; }
        .sidebar-nav a i { width: 20px; text-align: center; font-size: .9rem; }
        .sidebar-nav .nav-label { padding: 10px 20px 4px; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
        .sidebar-footer { padding: 16px; border-top: 1px solid #e2e8f0; }
        .sidebar-footer .user-info { display: flex; align-items: center; gap: 10px; }
        .sidebar-footer .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-bg); display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 700; font-size: .8rem; }
        .sidebar-footer .user-name { font-weight: 600; font-size: .85rem; }
        .sidebar-footer .user-role { font-size: .72rem; color: #94a3b8; }
        .role-badge { font-size: .65rem; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
        .role-admin { background: #fee2e2; color: #dc2626; }
        .role-staff { background: #dbeafe; color: #2563eb; }
        .role-user { background: #f0fdf4; color: #16a34a; }
        .role-teknisi { background: #fef3c7; color: #92400e; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-w); flex: 1; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; position: sticky; top: 0; z-index: 500; }
        .topbar h2 { font-size: 1.1rem; margin: 0; }
        .topbar-actions { display: flex; align-items: center; gap: 6px; }
        .topbar-icon-btn { width: 38px; height: 38px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; position: relative; font-size: .9rem; color: #64748b; }
        .topbar-icon-btn:hover { background: var(--primary-bg); border-color: var(--primary); color: var(--primary); }
        .topbar-icon-btn .badge-dot { position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; border-radius: 50%; background: var(--danger); border: 2px solid #fff; }
        .topbar-user { display: flex; align-items: center; gap: 10px; padding: 4px 14px 4px 4px; border-radius: 12px; border: 1.5px solid #e2e8f0; cursor: default; transition: all .2s; }
        .topbar-user:hover { border-color: var(--primary); background: var(--primary-bg); }
        .topbar-avatar { width: 34px; height: 34px; border-radius: 8px; background: var(--primary-bg); display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 700; font-size: .72rem; }
        .topbar-user-info { line-height: 1.25; }
        .topbar-user-name { font-size: .78rem; font-weight: 700; color: #1e293b; }
        .topbar-user-status { font-size: .58rem; color: #94a3b8; display: flex; align-items: center; gap: 3px; }
        .topbar-divider { width: 1px; height: 28px; background: #e2e8f0; margin: 0 2px; }
        .online-dot { width: 7px; height: 7px; border-radius: 50%; background: #16a34a; display: inline-block; animation: pulse-dot 2s infinite; }
        @keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
        .trial-badge-top { font-size: .56rem; padding: 1px 6px; border-radius: 5px; font-weight: 700; white-space: nowrap; }
        .trial-active { background: #fef3c7; color: #92400e; }
        .trial-expired { background: #fee2e2; color: #991b1b; }
        .trial-permanent { background: #dcfce7; color: #166534; }
        .notif-dropdown { position: absolute; top: 46px; right: 0; width: 340px; max-height: 400px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,.12); z-index: 9999; display: none; overflow: hidden; }
        .notif-dropdown.show { display: block; }
        .page-content { padding: 24px; flex: 1; }

        /* ========== Fitur #9 — BOTTOM NAVIGATION MOBILE ========== */
        .bottom-nav { display: none; }
        .bottom-nav-sheet-overlay { display: none; }
        .bottom-nav-sheet { display: none; }
        @media (max-width: 768px) {
            body { padding-bottom: 64px; }
            .bottom-nav {
                display: flex; position: fixed; bottom: 0; left: 0; right: 0;
                height: 60px; background: #fff; border-top: 1px solid #e2e8f0;
                box-shadow: 0 -4px 20px rgba(0,0,0,.06); z-index: 998;
                padding-bottom: env(safe-area-inset-bottom);
            }
            .bottom-nav-item {
                flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
                gap: 3px; color: #94a3b8; text-decoration: none; font-size: .58rem; font-weight: 600;
                transition: color .15s; position: relative; padding: 6px 2px;
            }
            .bottom-nav-item i { font-size: 1.15rem; }
            .bottom-nav-item.active { color: var(--primary); }
            .bottom-nav-item.active::before {
                content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
                width: 28px; height: 3px; background: var(--primary); border-radius: 0 0 4px 4px;
            }
            .bottom-nav-item .bn-badge {
                position: absolute; top: 4px; right: 50%; margin-right: -22px;
                background: var(--danger); color: #fff; font-size: .54rem; font-weight: 700;
                min-width: 15px; height: 15px; padding: 0 4px; border-radius: 8px;
                display: flex; align-items: center; justify-content: center; line-height: 1;
            }
            .bottom-nav-more.active { color: var(--primary); }
            .bottom-nav-sheet-overlay {
                display: block; position: fixed; inset: 0; background: rgba(0,0,0,.4);
                z-index: 999; opacity: 0; pointer-events: none; transition: opacity .25s;
            }
            .bottom-nav-sheet-overlay.show { opacity: 1; pointer-events: auto; }
            .bottom-nav-sheet {
                display: block; position: fixed; bottom: 0; left: 0; right: 0; background: #fff;
                border-radius: 18px 18px 0 0; z-index: 1000; padding: 8px 16px 24px;
                transform: translateY(100%); transition: transform .3s ease; max-height: 70vh; overflow-y: auto;
                padding-bottom: calc(24px + env(safe-area-inset-bottom));
            }
            .bottom-nav-sheet.show { transform: translateY(0); }
            .bottom-nav-sheet .sheet-handle { width: 40px; height: 4px; background: #cbd5e1; border-radius: 2px; margin: 8px auto 14px; }
            .bottom-nav-sheet .sheet-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
            .bottom-nav-sheet .sheet-link {
                display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 6px;
                text-decoration: none; color: #475569; font-size: .64rem; font-weight: 600;
                background: #f8fafc; border-radius: 12px; transition: all .15s;
            }
            .bottom-nav-sheet .sheet-link i { font-size: 1.3rem; color: var(--primary); }
            .bottom-nav-sheet .sheet-link:active { background: var(--primary-bg); transform: scale(.96); }
            body.dark .bottom-nav { background: #1e293b; border-top-color: #334155; }
            body.dark .bottom-nav-item { color: #64748b; }
            body.dark .bottom-nav-item.active { color: #2dd4bf; }
            body.dark .bottom-nav-item.active::before { background: #2dd4bf; }
            body.dark .bottom-nav-sheet { background: #1e293b; }
            body.dark .bottom-nav-sheet .sheet-link { background: #0f172a; color: #cbd5e1; }
            body.dark .bottom-nav-sheet .sheet-link i { color: #2dd4bf; }
            body.dark .bottom-nav-sheet .sheet-handle { background: #475569; }
            /* Geser chat widget ke atas agar tidak ketutup bottom nav */
            #chatWidget, #chatPanel { bottom: 76px !important; }
        }

        /* Cards */
        .card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px; }
        .card-header h3 { font-size: .95rem; margin: 0; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; transition: transform .2s, box-shadow .2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,.08); }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; margin-bottom: 12px; }
        .stat-label { font-size: .8rem; color: #64748b; margin-bottom: 4px; font-weight: 500; }
        .stat-value { font-size: 1.6rem; font-weight: 800; }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 10px 12px; font-size: .72rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
        table td { padding: 10px 12px; font-size: .84rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        table tr:hover td { background: #f8fafc; }

        /* Badges */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .7rem; font-weight: 600; white-space: nowrap; }
        .badge-masuk { background: #dbeafe; color: #1e40af; }
        .badge-proses { background: #fef3c7; color: #92400e; }
        .badge-pending { background: #fee2e2; color: #991b1b; }
        .badge-selesai { background: #dcfce7; color: #166534; }
        .badge-urgent { background: #fee2e2; color: #991b1b; }
        .badge-normal { background: #f1f5f9; color: #475569; }
        .badge-masuk-kas { background: #dcfce7; color: #166534; }
        .badge-keluar { background: #fee2e2; color: #991b1b; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: none; border-radius: 8px; font-family: inherit; font-size: .84rem; font-weight: 600; cursor: pointer; transition: all .2s; text-decoration: none; }
        .btn:hover { opacity: .9; transform: translateY(-1px); }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-warning { background: var(--accent); color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-sm { padding: 5px 12px; font-size: .76rem; }
        .btn-xs { padding: 3px 8px; font-size: .7rem; border-radius: 6px; }

        /* Forms */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 600; color: #374151; }
        .form-input { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: .85rem; background: #fff; color: #1e293b; transition: border-color .2s, box-shadow .2s; outline: none; }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-bg); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        select.form-input { cursor: pointer; }

        /* Alert */
        .alert { padding: 12px 16px; border-radius: 8px; font-size: .84rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

        /* Grid layouts */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }

        /* Chart */
        .chart-container { position: relative; height: 200px; }

        /* Saldo tracker */
        .saldo-tracker { display: flex; align-items: center; gap: 16px; padding: 18px 24px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 12px; color: #fff; margin-bottom: 20px; }
        .saldo-tracker .saldo-label { font-size: .85rem; opacity: .8; }
        .saldo-tracker .saldo-value { font-size: 1.8rem; font-weight: 800; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
            .form-row { grid-template-columns: 1fr; }
            .grid-2 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .mobile-toggle { display: block !important; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
        .mobile-toggle { display: none; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #374151; }

        /* Pagination */
        .pagination { display: flex; gap: 4px; list-style: none; padding: 0; margin: 12px 0 0; flex-wrap: wrap; }
        .pagination li { list-style: none; }
        .pagination li span, .pagination li a {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px; padding: 0 8px;
            border-radius: 8px; font-size: .78rem; font-weight: 600;
            border: 1px solid #e2e8f0; background: #fff; color: #64748b;
            cursor: pointer; transition: all .15s; text-decoration: none;
        }
        .pagination li a:hover { background: var(--primary-bg); color: var(--primary); border-color: var(--primary); }
        .pagination li.active span, .pagination li.active a {
            background: var(--primary); color: #fff; border-color: var(--primary); }
        .pagination li.disabled span, .pagination li.disabled a {
            opacity: .4; cursor: default; pointer-events: none;
        }
        .pagination li span, .pagination li a { white-space: nowrap; }
        body.dark .pagination li span, body.dark .pagination li a { background: #1e293b; border-color: #334155; color: #94a3b8; }
        body.dark .pagination li a:hover { background: rgba(13,148,136,.15); color: #2dd4bf; border-color: #2dd4bf; }
        body.dark .pagination li.active span, body.dark .pagination li.active a { background: var(--primary); color: #fff; }

        /* Print */
        @media print {
            .sidebar, .topbar, .bottom-nav, .bottom-nav-sheet, .bottom-nav-sheet-overlay { display: none !important; }
            .main-content { margin-left: 0; }
            body { padding-bottom: 0; }
        }

        /* Overlay */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 99; }
        .sidebar-overlay.show { display: block; }

        /* Tooltip */
        .text-muted { color: #64748b; }
        .text-sm { font-size: .8rem; }
        .text-xs { font-size: .72rem; }
        .font-bold { font-weight: 700; }
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
        .mt-4 { margin-top: 16px; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }

        /* ========== DARK MODE ========== */
        body.dark { background: #0f172a; color: #e2e8f0; }
        body.dark .sidebar { background: #1e293b; border-right-color: #334155; }
        body.dark .sidebar-brand { border-bottom-color: #334155; }
        body.dark .sidebar-brand h1 span { color: #2dd4bf; }
        body.dark .sidebar-nav a { color: #94a3b8; }
        body.dark .sidebar-nav a:hover { background: rgba(13,148,136,.15); color: #5eead4; }
        body.dark .sidebar-nav a.active { background: rgba(13,148,136,.15); color: #2dd4bf; border-left-color: #2dd4bf; }
        body.dark .sidebar-nav .nav-label { color: #64748b; }
        body.dark .sidebar-footer { border-top-color: #334155; }
        body.dark .sidebar-footer .user-name { color: #e2e8f0; }
        body.dark .sidebar-footer .user-role { color: #64748b; }
        body.dark .user-avatar { background: rgba(13,148,136,.2); color: #2dd4bf; }
        body.dark .topbar { background: #1e293b; border-bottom-color: #334155; }
        body.dark .topbar h2 { color: #e2e8f0; }
        body.dark .mobile-toggle { color: #e2e8f0; }
        body.dark .topbar-user-name { color: #e2e8f0; }
        body.dark .topbar-icon-btn { background: #0f172a; border-color: #334155; color: #94a3b8; }
        body.dark .topbar-icon-btn:hover { background: rgba(13,148,136,.15); border-color: #2dd4bf; color: #2dd4bf; }
        body.dark .topbar-user { border-color: #334155; background: transparent; }
        body.dark .topbar-user:hover { border-color: #2dd4bf; background: rgba(13,148,136,.1); }
        body.dark .topbar-avatar { background: rgba(13,148,136,.2); color: #2dd4bf; }
        body.dark .topbar-divider { background: #334155; }
        body.dark .notif-dropdown { background: #1e293b; border-color: #334155; }
        body.dark .card { background: #1e293b; border-color: #334155; }
        body.dark .stat-card { background: #1e293b; border-color: #334155; }
        body.dark .stat-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,.3); }
        body.dark .stat-label { color: #94a3b8; }
        body.dark .stat-value { color: #e2e8f0; }
        body.dark table th { background: #0f172a; color: #94a3b8; border-bottom-color: #334155; }
        body.dark table td { border-bottom-color: #1e293b; color: #e2e8f0; }
        body.dark table tr:hover td { background: #0f172a; }
        body.dark .form-input { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        body.dark .form-input:focus { border-color: #2dd4bf; box-shadow: 0 0 0 3px rgba(13,148,136,.15); }
        body.dark .form-group label { color: #94a3b8; }
        body.dark .btn-secondary { background: #334155; color: #e2e8f0; border-color: #475569; }
        body.dark .btn-secondary:hover { background: #475569; }
        body.dark .alert-success { background: #052e16; border-color: #166534; color: #4ade80; }
        body.dark .alert-error { background: #450a0a; border-color: #991b1b; color: #fca5a5; }
        body.dark .alert-warning { background: #451a03; border-color: #92400e; color: #fbbf24; }
        body.dark .text-muted { color: #94a3b8; }
        body.dark .sidebar-overlay { background: rgba(0,0,0,.6); }
        body.dark #darkModeIcon { color: #94a3b8; }
        body.dark #darkModeLabel { color: #94a3b8; }
        body.dark select.form-input { background: #0f172a; }
        body.dark .badge-masuk { background: #1e3a5f; color: #93c5fd; }
        body.dark .badge-proses { background: #451a03; color: #fbbf24; }
        body.dark .badge-pending { background: #450a0a; color: #fca5a5; }
        body.dark .badge-selesai { background: #052e16; color: #4ade80; }
        body.dark .badge-normal { background: #334155; color: #e2e8f0; }
        body.dark .saldo-tracker { background: linear-gradient(135deg, #115e59, #134e4a); }
        /* Dark mode clock area */
        body.dark div[style*="background:#f8fafc"] { background: #0f172a !important; border-bottom-color: #334155 !important; }
        body.dark #clockTime { color: #e2e8f0 !important; }
        body.dark #clockDate { color: #94a3b8 !important; }
        body.dark div[style*="background:#f0fdf4"] { background: #052e16 !important; }
        /* Dark mode branch selector */
        body.dark div[style*="background:#f0fdf4"] span[style*="color:var(--primary)"] { color: #2dd4bf !important; }

        /* ===== Sembunyikan widget & banner Google Translate ===== */
        .goog-te-banner-frame.skiptranslate, iframe.goog-te-banner-frame { display:none !important; }
        .goog-te-gadget, .goog-te-gadget-icon, .goog-logo-link, .goog-te-gadget span { display:none !important; height:0; }
        body { top:0 !important; }
        .skiptranslate { min-height:0 !important; }
        .goog-tooltip, .goog-tooltip:hover { display:none !important; }
        .goog-text-highlight { background:none !important; box-shadow:none !important; }
        #google_translate_element { display:none !important; }

        /* ===== GLOBAL LOADING SPINNER (HIJAU) ===== */
        .loading-overlay {
            position: fixed; inset: 0;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
            z-index: 99999;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .loading-overlay.show { opacity: 1; pointer-events: auto; }
        body.dark .loading-overlay { background: rgba(15, 23, 42, 0.85); }
        .loading-spinner {
            width: 56px; height: 56px;
            border: 6px solid #e2e8f0;
            border-top-color: #16a34a; /* WARNA HIJAU (--success) */
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        body.dark .loading-spinner {
            border: 6px solid #334155;
            border-top-color: #4ade80; /* Hijau terang untuk Dark Mode */
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .no-loading, .no-loading * { pointer-events: auto !important; }
    </style>
</head>
<body>
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><img src="{{ asset('logo-fixpro.jpg') }}" alt="FIXPRO"></div>
            <div>
                <h1 style="margin:0;font-size:1.1rem">Fix<span>Pro</span></h1>
                <div style="font-size:.65rem;color:#94a3b8;font-weight:500;margin-top:1px">efisiensi, akurasi, dan produktivitas.</div>
            </div>
        </div>
        <!-- Jam & Tanggal -->
        <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;background:#f8fafc">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="font-size:1.8rem;line-height:1">🇮🇩</div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <span style="font-size:.64rem;font-weight:600;color:#94a3b8;letter-spacing:.5px">WIB</span>
                        <span style="font-size:.64rem;font-weight:700;color:#64748b;letter-spacing:.3px">JAKARTA</span>
                    </div>
                    <div id="clockTime" style="font-size:1.35rem;font-weight:800;color:#0f172a;letter-spacing:1px;margin:2px 0">--:--:--</div>
                    <div id="clockDate" style="font-size:.68rem;color:#64748b;font-weight:500">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Branch selector -->
        {{-- Branch selector: Super Admin & Enterprise Admin can switch --}}
        @php
            $currentUser = auth()->user();
            $canSwitchBranch = $currentUser->isSuperAdmin() || ($currentUser->isEnterprise() && $currentUser->isAdmin());
        @endphp
        @if($canSwitchBranch)
        <div style="padding:10px 16px;border-bottom:1px solid #e2e8f0">
            <div style="font-size:.68rem;font-weight:600;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px"><i class="fas fa-store"></i> Cabang Aktif</div>
            <form method="POST" action="{{ route('cabang.set') }}" id="branchForm">
                @csrf
                <select name="cabang_id" class="form-input" style="padding:7px 10px;font-size:.78rem;border-radius:6px" onchange="this.form.submit()">
                    @if($currentUser->isSuperAdmin())
                    <option value="all" {{ session('cabang_id') === 'all' ? 'selected' : '' }}>🌐 Semua Cabang</option>
                    @endif
                    @if($currentUser->isSuperAdmin())
                        {{-- Super Admin: show ALL cabang with hierarchy --}}
                        @php
                            $allCabangs = \App\Models\Cabang::whereNull('parent_cabang_id')->orderBy('nama')->get();
                        @endphp
                        @foreach($allCabangs as $root)
                        <option value="{{ $root->id }}" {{ ((int) session('cabang_id')) === $root->id ? 'selected' : '' }}>{{ !$root->aktif ? '⏸️' : '🏢' }} {{ $root->nama }} (Pusat){{ !$root->aktif ? ' (Nonaktif)' : '' }}</option>
                            @php $children = \App\Models\Cabang::where('parent_cabang_id', $root->id)->orderBy('nama')->get(); @endphp
                            @foreach($children as $child)
                            <option value="{{ $child->id }}" {{ ((int) session('cabang_id')) === $child->id ? 'selected' : '' }}>&nbsp;&nbsp;&nbsp;├─ 📍 {{ $child->nama }}{{ !$child->aktif ? ' (Nonaktif)' : '' }}</option>
                            @endforeach
                            @if($children->count() > 0)
                            <option disabled style="color:#94a3b8;font-size:.7rem">&nbsp;&nbsp;&nbsp;└─────────────────</option>
                            @endif
                        @endforeach
                    @else
                        {{-- Enterprise Admin: show only their group with hierarchy --}}
                        @php
                            $allowedIds = $currentUser->getAllowedCabangIds();
                            $myCabangs = \App\Models\Cabang::whereIn('id', $allowedIds)->orderBy('parent_cabang_id')->orderBy('nama')->get();
                            $parentCabang = $myCabangs->first(fn($c) => $c->parent_cabang_id === null);
                            $childCabangs = $myCabangs->whereNotNull('parent_cabang_id');
                        @endphp
                        @if($parentCabang)
                        <option value="{{ $parentCabang->id }}" {{ ((int) session('cabang_id')) === $parentCabang->id ? 'selected' : '' }}>🏢 {{ $parentCabang->nama }} (Pusat)</option>
                            @foreach($childCabangs->where('parent_cabang_id', $parentCabang->id) as $child)
                            <option value="{{ $child->id }}" {{ ((int) session('cabang_id')) === $child->id ? 'selected' : '' }}>&nbsp;&nbsp;&nbsp;├─ 📍 {{ $child->nama }}</option>
                            @endforeach
                            @if($childCabangs->where('parent_cabang_id', $parentCabang->id)->count() > 0)
                            <option disabled style="color:#94a3b8;font-size:.7rem">&nbsp;&nbsp;&nbsp;└─────────────────</option>
                            @endif
                        @else
                            @foreach($myCabangs as $cab)
                            <option value="{{ $cab->id }}" {{ ((int) session('cabang_id')) === $cab->id ? 'selected' : '' }}>📍 {{ $cab->nama }}</option>
                            @endforeach
                        @endif
                    @endif
                </select>
            </form>
        </div>
        @else
        <div style="padding:10px 16px;border-bottom:1px solid #e2e8f0;background:#f0fdf4">
            <div style="font-size:.68rem;font-weight:600;color:#94a3b8;margin-bottom:2px;text-transform:uppercase;letter-spacing:.5px"><i class="fas fa-store"></i> Cabang Anda</div>
            <div style="font-size:.84rem;font-weight:700;color:var(--primary)">{{ auth()->user()->cabang?->nama ?? 'Pusat' }}</div>
        </div>
        @endif
        <nav class="sidebar-nav">
            @if(auth()->user()->isTeknisi())
                {{-- ==================== TEKNISI: MENU TERBATAS ==================== --}}
                <div class="nav-label">Menu Utama</div>
                <a href="{{ route('teknisi-dashboard.index') }}" class="{{ request()->routeIs('teknisi-dashboard.*') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Saya
                </a>
                <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class="fas fa-user-edit"></i> Profil Saya
                </a>
            @else
            <div class="nav-label">{{ t('menu.main_menu','Menu Utama') }}</div>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> {{ t('menu.dashboard','Dashboard') }}
            </a>

            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
            <a href="{{ route('servis.create') }}" class="{{ request()->routeIs('servis.create') ? 'active' : '' }}">
                <i class="fas fa-plus-circle"></i> {{ t('menu.input_service','Input Servis') }}
            </a>
            <a href="{{ route('servis.index') }}" class="{{ request()->routeIs('servis.index') ? 'active' : '' }}">
                <i class="fas fa-list-alt"></i> {{ t('menu.service_list','Daftar Servis') }}
            </a>
            <a href="{{ route('arsip-servis.index') }}" class="{{ request()->routeIs('arsip-servis.*') ? 'active' : '' }}">
                <i class="fas fa-archive"></i> {{ t('menu.service_archive','Arsip & Lacak Servis') }}
            </a>
            <a href="{{ route('laporan-keuangan.index') }}" class="{{ request()->routeIs('laporan-keuangan.*') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> {{ t('menu.finance_report','Laporan Keuangan') }}
            </a>
            @endif

            {{-- ==================== ADMIN CABANG ANAK (enterprise): kelola sparepart sesuai cabangnya ==================== --}}
            @if(auth()->user()->isAdminCabangAnak())
            <div class="nav-label" style="margin-top: 8px;">Sparepart Cabang</div>
            <a href="{{ route('stok.index') }}" class="{{ request()->routeIs('stok.*') ? 'active' : '' }}">
                <i class="fas fa-boxes"></i> Stok Barang
            </a>
            <a href="{{ route('pembelian.index') }}" class="{{ request()->routeIs('pembelian.*') ? 'active' : '' }}">
                <i class="fas fa-truck-loading"></i> Pembelian Supplier
            </a>
            <a href="{{ route('pembelian.hutang') }}" class="{{ request()->routeIs('pembelian.hutang') ? 'active' : '' }}">
                <i class="fas fa-hand-holding-usd"></i> Hutang Supplier
            </a>
            <a href="{{ route('aktivitas-sparepart.index') }}" class="{{ request()->routeIs('aktivitas-sparepart.*') ? 'active' : '' }}">
                <i class="fas fa-exchange-alt"></i> Aktivitas Sparepart
            </a>
            <div class="nav-label" style="margin-top: 8px;">Penjualan Grosir</div>
            <a href="{{ route('grosir.dashboard') }}" class="{{ request()->routeIs('grosir.dashboard') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard Grosir
            </a>
            <a href="{{ route('grosir.penjualan.create') }}" class="{{ request()->routeIs('grosir.penjualan.create', 'grosir.penjualan.store') ? 'active' : '' }}">
                <i class="fas fa-cash-register"></i> Transaksi Grosir
            </a>
            <a href="{{ route('grosir.pesanan.index') }}" class="{{ request()->routeIs('grosir.pesanan.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i> Pesanan Grosir
            </a>
            <a href="{{ route('grosir.pelanggan.index') }}" class="{{ request()->routeIs('grosir.pelanggan.*') ? 'active' : '' }}">
                <i class="fas fa-user-friends"></i> Pelanggan Grosir
            </a>
            <a href="{{ route('grosir.harga.index') }}" class="{{ request()->routeIs('grosir.harga.index', 'grosir.harga.khusus*') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Harga Grosir
            </a>
            <a href="{{ route('grosir.stok.index') }}" class="{{ request()->routeIs('grosir.stok.*') ? 'active' : '' }}">
                <i class="fas fa-boxes"></i> Stok Grosir
            </a>
            <a href="{{ route('grosir.piutang.index') }}" class="{{ request()->routeIs('grosir.piutang.*') ? 'active' : '' }}">
                <i class="fas fa-hand-holding-usd"></i> Piutang Grosir
            </a>
            <a href="{{ route('grosir.retur.index') }}" class="{{ request()->routeIs('grosir.retur.*') ? 'active' : '' }}">
                <i class="fas fa-undo"></i> Retur Grosir
            </a>
            <a href="{{ route('grosir.laporan.index') }}" class="{{ request()->routeIs('grosir.laporan.*') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Laporan Grosir
            </a>
            @endif

            @if(auth()->user()->isAdmin())
            <div class="nav-label" style="margin-top: 8px;">{{ t('menu.management','Manajemen') }}</div>
            <a href="{{ route('pelanggan.index') }}" class="{{ request()->routeIs('pelanggan.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> {{ t('menu.customer','Pelanggan') }}
            </a>
            <a href="{{ route('teknisi.index') }}" class="{{ request()->routeIs('teknisi.*') ? 'active' : '' }}">
                <i class="fas fa-wrench"></i> Teknisi
            </a>
            <a href="{{ route('tipe-hp.index') }}" class="{{ request()->routeIs('tipe-hp.*') ? 'active' : '' }}">
                <i class="fas fa-mobile-alt"></i> Master Tipe HP
            </a>
            <a href="{{ route('stok.index') }}" class="{{ request()->routeIs('stok.*') ? 'active' : '' }}">
                <i class="fas fa-boxes"></i> Stok Barang
            </a>
            <a href="{{ route('service-prices.index') }}" class="{{ request()->routeIs('service-prices.*') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Daftar Harga Service
            </a>
            <a href="{{ route('barcode.index') }}" class="{{ request()->routeIs('barcode.*') ? 'active' : '' }}">
                <i class="fas fa-barcode"></i> Generate Barcode
            </a>
            <a href="{{ route('penjualan-sparepart.index') }}" class="{{ request()->routeIs('penjualan-sparepart.*') ? 'active' : '' }}">
                <i class="fas fa-shopping-cart"></i> Penjualan Sparepart
            </a>
            <div class="nav-label" style="margin-top: 8px;">Penjualan Grosir</div>
            <a href="{{ route('grosir.dashboard') }}" class="{{ request()->routeIs('grosir.dashboard') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard Grosir
            </a>
            <a href="{{ route('grosir.penjualan.create') }}" class="{{ request()->routeIs('grosir.penjualan.create', 'grosir.penjualan.store') ? 'active' : '' }}">
                <i class="fas fa-cash-register"></i> Transaksi Grosir
            </a>
            <a href="{{ route('grosir.penjualan.index') }}" class="{{ request()->routeIs('grosir.penjualan.index', 'grosir.penjualan.show', 'grosir.penjualan.*') ? 'active' : '' }}">
                <i class="fas fa-receipt"></i> Riwayat Grosir
            </a>
            <a href="{{ route('grosir.pesanan.index') }}" class="{{ request()->routeIs('grosir.pesanan.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i> Pesanan Grosir
            </a>
            <a href="{{ route('grosir.pelanggan.index') }}" class="{{ request()->routeIs('grosir.pelanggan.*') ? 'active' : '' }}">
                <i class="fas fa-user-friends"></i> Pelanggan Grosir
            </a>
            <a href="{{ route('grosir.harga.index') }}" class="{{ request()->routeIs('grosir.harga.index', 'grosir.harga.khusus*') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Harga Grosir
            </a>
            <a href="{{ route('grosir.stok.index') }}" class="{{ request()->routeIs('grosir.stok.*') ? 'active' : '' }}">
                <i class="fas fa-boxes"></i> Stok Grosir
            </a>
            <a href="{{ route('grosir.piutang.index') }}" class="{{ request()->routeIs('grosir.piutang.*') ? 'active' : '' }}">
                <i class="fas fa-hand-holding-usd"></i> Piutang Grosir
            </a>
            <a href="{{ route('grosir.retur.index') }}" class="{{ request()->routeIs('grosir.retur.*') ? 'active' : '' }}">
                <i class="fas fa-undo"></i> Retur Grosir
            </a>
            <a href="{{ route('grosir.laporan.index') }}" class="{{ request()->routeIs('grosir.laporan.*') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Laporan Grosir
            </a>
            <a href="{{ route('tagihan-sparepart.index') }}" class="{{ request()->routeIs('tagihan-sparepart.*') ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i> Tagihan Sparepart
            </a>
            <a href="{{ route('jualbeli.index') }}" class="{{ request()->routeIs('jualbeli.*') ? 'active' : '' }}">
                <i class="fas fa-mobile-alt"></i> Jual Beli HP
            </a>
            <a href="{{ route('pembelian.index') }}" class="{{ request()->routeIs('pembelian.*') ? 'active' : '' }}">
                <i class="fas fa-truck-loading"></i> Pembelian Supplier
            </a>
            <a href="{{ route('aktivitas-sparepart.index') }}" class="{{ request()->routeIs('aktivitas-sparepart.*') ? 'active' : '' }}">
                <i class="fas fa-exchange-alt"></i> Aktivitas Sparepart
            </a>
            <a href="{{ route('kas.index') }}" class="{{ request()->routeIs('kas.*') ? 'active' : '' }}">
                <i class="fas fa-cash-register"></i> Kas Harian
            </a>
            <a href="{{ route('payment.select') }}" class="{{ request()->routeIs('payment.*') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i> {{ t('menu.online_payment','Pembayaran Online') }}
            </a>
            <a href="{{ route('subscription.index') }}" class="{{ request()->routeIs('subscription.*') ? 'active' : '' }}">
                <i class="fas fa-star"></i> {{ t('subscription.subscription','Paket Berlangganan') }}
                @php $ss = auth()->user()->subscriptionSummary(); @endphp
                @if(!auth()->user()->isSuperAdmin() && $ss && ($ss['days_left'] ?? null) !== null && ($ss['days_left'] ?? 99) <= 30)
                <span style="background:var(--warning);color:#fff;font-size:.6rem;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:auto">!</span>
                @endif
            </a>
            <a href="{{ route('sync.index') }}" class="{{ request()->routeIs('sync.*') ? 'active' : '' }}">
                <i class="fas fa-sync-alt"></i> Riwayat Sinkronisasi
            </a>
            <a href="{{ route('whatsapp.index') }}" class="{{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                <i class="fab fa-whatsapp"></i> {{ t('menu.whatsapp_web','WhatsApp Web') }}
            </a>

            {{-- Sistem: Admin Cabang hanya Pengaturan, Kelola Akun, Multi Cabang --}}
            {{-- Sistem: Super Admin mendapat semua menu --}}
            <div class="nav-label" style="margin-top: 8px;">{{ t('menu.system','Sistem') }}</div>
            <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="fas fa-cog"></i> {{ t('menu.settings','Pengaturan') }}
            </a>
            @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdmin() && !auth()->user()->isEnterprise()))
            <a href="{{ route('user-management.index') }}" class="{{ request()->routeIs('user-management.*') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i> Kelola Akun
            </a>
            @endif

            {{-- Request Aktivasi (hanya Admin Cabang standar trial/non-permanen) --}}
            @if(auth()->user()->isAdminCabang() && !auth()->user()->isEnterprise() && !auth()->user()->is_permanent)
            <a href="{{ route('activation-request.index') }}" class="{{ request()->routeIs('activation-request.*') ? 'active' : '' }}" style="color:var(--warning)">
                <i class="fas fa-key"></i> Aktivasi Lisensi
            </a>
            @endif

            {{-- SUPER ADMIN ONLY --}}
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.website.index') }}" class="{{ request()->routeIs('admin.website.*') ? 'active' : '' }}">
                <i class="fas fa-globe"></i> Kelola Website
            </a>
            <a href="{{ route('banner-iklan.index') }}" class="{{ request()->routeIs('banner-iklan.*') ? 'active' : '' }}">
                <i class="fas fa-ad"></i> Banner Iklan
            </a>
            @endif

            {{-- Multi Cabang: Super Admin & Enterprise Admin --}}
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isEnterprise())
            <a href="{{ route('cabang.index') }}" class="{{ request()->routeIs('cabang.*') ? 'active' : '' }}">
                <i class="fas fa-store"></i> Multi Cabang
            </a>
            @if(auth()->user()->isEnterprise())
            <a href="{{ route('user-management.index') }}" class="{{ request()->routeIs('user-management.*') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i> Kelola Akun
            </a>
            @endif
            @endif

            {{-- SUPER ADMIN ONLY: CONTROL (Request Aktivasi, Kode, Status, Paket, User, Role, Audit) --}}
            @if(auth()->user()->isSuperAdmin())
            <div class="nav-label" style="margin-top: 8px;">Invoice Sparepart (Pusat)</div>
            <a href="{{ route('invoice.create') }}" class="{{ request()->routeIs('invoice.create') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar"></i> Buat Invoice
            </a>
            <a href="{{ route('invoice.riwayat') }}" class="{{ request()->routeIs('invoice.riwayat', 'invoice.show', 'invoice.pdf', 'invoice.thermal') ? 'active' : '' }}">
                <i class="fas fa-history"></i> Riwayat Invoice
            </a>
            <a href="{{ route('invoice.pembayaran') }}" class="{{ request()->routeIs('invoice.pembayaran') ? 'active' : '' }}">
                <i class="fas fa-money-bill-wave"></i> Pembayaran
            </a>
            <a href="{{ route('invoice.piutang') }}" class="{{ request()->routeIs('invoice.piutang') ? 'active' : '' }}">
                <i class="fas fa-hand-holding-usd"></i> Piutang
            </a>
            <a href="{{ route('invoice.retur') }}" class="{{ request()->routeIs('invoice.retur*') ? 'active' : '' }}">
                <i class="fas fa-undo"></i> Retur
            </a>
            <div class="nav-label" style="margin-top: 8px;">Control</div>
            @php
                $pendingActivation = \App\Models\ActivationRequest::whereIn('status', ['pending','processing'])->count();
            @endphp
            <a href="{{ route('admin.activation-requests.index') }}" class="{{ request()->routeIs('admin.activation-requests.*') ? 'active' : '' }}">
                <i class="fas fa-user-clock"></i> Request Aktivasi Cabang
                @if($pendingActivation > 0)
                <span style="background:var(--danger);color:#fff;font-size:.6rem;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:auto">{{ $pendingActivation }}</span>
                @endif
            </a>
            <a href="{{ route('activation-code.index') }}" class="{{ request()->routeIs('activation-code.*') ? 'active' : '' }}">
                <i class="fas fa-ticket-alt"></i> Kode Aktivasi
            </a>
            <a href="{{ route('activation.status') }}" class="{{ request()->routeIs('activation.status') ? 'active' : '' }}">
                <i class="fas fa-shield-alt"></i> Status Aktivasi
            </a>
            <a href="{{ route('subscription.index') }}" class="{{ request()->routeIs('subscription.*') ? 'active' : '' }}">
                <i class="fas fa-star"></i> Paket / Langganan
            </a>
            <a href="{{ route('user-management.index') }}" class="{{ request()->routeIs('user-management.*') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i> Kelola User
            </a>
            <a href="{{ route('control.roles') }}" class="{{ request()->routeIs('control.roles') ? 'active' : '' }}">
                <i class="fas fa-user-shield"></i> Role & Permission
            </a>
            <a href="{{ route('audit-log.index') }}" class="{{ request()->routeIs('audit-log.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i> {{ t('menu.audit_log','Audit Log') }}
            </a>
            <a href="{{ route('serial-number.index') }}" class="{{ request()->routeIs('serial-number.*') ? 'active' : '' }}">
                <i class="fas fa-key"></i> Aktivasi & Lisensi
            </a>
            <a href="{{ route('bank-accounts.index') }}" class="{{ request()->routeIs('bank-accounts.*') ? 'active' : '' }}">
                <i class="fas fa-university"></i> Rekening Bank
            </a>
            @php
                $failedSync = \App\Models\SyncQueue::whereIn('status', ['failed','conflict'])->count();
            @endphp
            <a href="{{ route('sync.index') }}" class="{{ request()->routeIs('sync.*') ? 'active' : '' }}">
                <i class="fas fa-sync-alt"></i> Riwayat Sinkronisasi
                @if($failedSync > 0)
                <span style="background:var(--danger);color:#fff;font-size:.6rem;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:auto">{{ $failedSync }}</span>
                @endif
            </a>
            <a href="{{ route('admin.languages.index') }}" class="{{ request()->routeIs('admin.languages.*') ? 'active' : '' }}">
                <i class="fas fa-language"></i> {{ t('menu.multi_language','Multi Bahasa') }}
            </a>
            @endif
            @endif

            @if(auth()->user()->isUser() && !auth()->user()->isAdmin() && !auth()->user()->isStaff() && !auth()->user()->isTeknisi())
            {{-- ==================== USER BIASA ==================== --}}
            <div class="nav-label" style="margin-top: 8px;">Layanan Servis</div>
            <a href="{{ route('my-service.create') }}" class="{{ request()->routeIs('my-service.create') ? 'active' : '' }}">
                <i class="fas fa-plus-circle"></i> Daftar Servis HP
            </a>
            <a href="{{ route('my-service.index') }}" class="{{ request()->routeIs('my-service.index') ? 'active' : '' }}">
                <i class="fas fa-history"></i> Riwayat Servis Saya
            </a>
            <a href="{{ route('arsip-servis.index') }}" class="{{ request()->routeIs('arsip-servis.*') ? 'active' : '' }}">
                <i class="fas fa-search-location"></i> Lacak Servis
            </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
            {{-- ==================== ADMIN / STAFF - Layanan --}}
            <div class="nav-label" style="margin-top: 8px;">Layanan</div>
            <a href="{{ route('my-service.create') }}" class="{{ request()->routeIs('my-service.create') ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i> Daftar Servis (User)
            </a>
            <a href="{{ route('my-service.index') }}" class="{{ request()->routeIs('my-service.index') ? 'active' : '' }}">
                <i class="fas fa-history"></i> Riwayat Servis
            </a>
            <a href="{{ route('arsip-servis.index') }}" class="{{ request()->routeIs('arsip-servis.*') ? 'active' : '' }}">
                <i class="fas fa-archive"></i> Arsip & Lacak
            </a>
            @endif
            @endif
        </nav>
        <div class="sidebar-footer">
            <!-- Dark Mode Toggle -->
            <div style="padding:10px 0;border-bottom:1px solid #e2e8f0;margin-bottom:10px">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:0 4px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <i class="fas fa-moon" id="darkModeIcon" style="color:#64748b;font-size:.85rem"></i>
                        <span style="font-size:.8rem;font-weight:600;color:#64748b" id="darkModeLabel">Dark Mode</span>
                    </div>
                    <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer">
                        <input type="checkbox" id="darkModeToggle" style="opacity:0;width:0;height:0" onchange="toggleDarkMode()">
                        <span style="position:absolute;cursor:pointer;inset:0;background:#cbd5e1;border-radius:24px;transition:.3s" id="darkModeSlider"></span>
                        <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s" id="darkModeDot"></span>
                    </label>
                </div>
            </div>
            <div class="user-info">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div>
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">
                        @if(auth()->user()->isSuperAdmin())
                            <span class="role-badge" style="background:#fef3c7;color:#92400e"><i class="fas fa-crown" style="margin-right:2px"></i> Super Admin</span>
                        @else
                            <span class="role-badge role-{{ strtolower(auth()->user()->role?->name ?? 'user') }}">{{ auth()->user()->role?->name ?? 'User' }}</span>
                            @if(auth()->user()->isAdmin())
                            <span class="role-badge" style="background:{{ auth()->user()->isEnterprise() ? '#fef3c7' : '#eff6ff' }};color:{{ auth()->user()->isEnterprise() ? '#92400e' : '#1e40af' }};margin-top:2px;display:inline-block">
                                <i class="fas fa-{{ auth()->user()->isEnterprise() ? 'building' : 'box' }}" style="margin-right:2px"></i> {{ ucfirst(auth()->user()->paket ?? 'standar') }}
                            </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-content">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 12px;">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2>@yield('title', 'Dashboard')</h2>
            </div>
            <div class="topbar-actions">
                {{-- Universal Search --}}
                @php
                    $user = auth()->user();
                    $isPermanent = $user->is_permanent;
                    $daysLeft = $user->daysUntilExpiry();
                    $expiresAt = $user->login_expires_at;
                    $showTrial = !$isPermanent && !$user->is_super_admin && $user->daysUntilExpiry() !== null;
                    $activeCabangId = $user->getEffectiveCabangId();
                    $qrisImage = \App\Models\Setting::get("qris_image_{$activeCabangId}");
                    $subSummary = $user->subscriptionSummary();
                    // ===== Google Translate — deteksi dari cookie =====
                    $gtCookie = $_COOKIE['googtrans'] ?? '';
                    $gtCurrent = 'id';
                    foreach (['en','hi'] as $gtLangCode) {
                        if (str_contains($gtCookie, '/' . $gtLangCode)) { $gtCurrent = $gtLangCode; break; }
                    }
                    $gtLangs = [
                        'id' => ['flag' => '🇮🇩', 'native' => 'Bahasa Indonesia', 'name' => 'Indonesian'],
                        'en' => ['flag' => '🇬🇧', 'native' => 'English',         'name' => 'English'],
                        'hi' => ['flag' => '🇮🇳', 'native' => 'हिन्दी',           'name' => 'Hindi (India)'],
                    ];
                    $gtActive = $gtLangs[$gtCurrent];
                @endphp
                @if(auth()->user()->isAdmin() || auth()->user()->isStaff() || auth()->user()->isSuperAdmin())
                <div style="position:relative">
                    <input type="text" id="universalSearch" placeholder="Cari servis, pelanggan, IMEI..." style="padding:7px 14px 7px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.78rem;width:220px;background:#f8fafc;outline:none;transition:border .2s" onfocus="this.style.borderColor='var(--primary)';this.style.boxShadow='0 0 0 3px var(--primary-bg)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'" onkeydown="if(event.key==='Enter')doUniversalSearch()">
                    <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.76rem"></i>
                </div>
                @endif

                {{-- Divider --}}
                <div class="topbar-divider"></div>

                {{-- Notifikasi --}}
                <div style="position:relative" id="notifWrap">
                    <button class="topbar-icon-btn" onclick="toggleNotif(event)" title="Notifikasi">
                        <i class="fas fa-bell"></i>
                        @php
                            $notifCabangId = $user->getActiveCabangId();
                            $notifServisQuery = \App\Models\Servis::with(['pelanggan', 'cabang']);
                            if ($notifCabangId !== null) $notifServisQuery->where('cabang_id', $notifCabangId);
                            $unreadChat = \App\Models\Chat::query();
                            if ($notifCabangId !== null) $unreadChat->where('cabang_id', $notifCabangId);
                            $unreadChat = $unreadChat->where('sender_id', '!=', $user->id)->where('is_read', false)->count();
                            $recentServis = (clone $notifServisQuery)->orderBy('created_at','desc')->take(10)->get();
                        @endphp
                        @if($unreadChat > 0)
                        <span class="badge-dot"></span>
                        @endif
                    </button>
                    <div class="notif-dropdown" id="notifDropdown" onclick="event.stopPropagation()">
                        <div style="padding:14px 18px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center">
                            <span style="font-weight:700;font-size:.88rem">Notifikasi</span>
                            <span style="font-size:.68rem;color:#94a3b8">{{ $unreadChat }} chat baru</span>
                        </div>
                        <div id="notifList" style="max-height:350px;overflow-y:auto">
                            @foreach($recentServis as $ns)
                            <a href="{{ route('servis.index', ['search' => $ns->kode]) }}" style="display:block;padding:12px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;transition:background .15s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <span style="font-size:.78rem;font-weight:600;color:var(--primary)">{{ $ns->kode }}</span>
                                    <span class="badge badge-{{ strtolower($ns->status) }}" style="font-size:.6rem">{{ $ns->status }}</span>
                                </div>
                                <div style="font-size:.72rem;color:#64748b;margin-top:2px">{{ $ns->perangkat }} — {{ $ns->pelanggan?->nama ?? '-' }}</div>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:2px">
                                    <span style="font-size:.62rem;color:#94a3b8">{{ $ns->created_at?->diffForHumans() }}</span>
                                    <span style="font-size:.62rem;color:#94a3b8">{{ $ns->cabang?->nama ?? '-' }}</span>
                                </div>
                            </a>
                            @endforeach
                            @if($recentServis->count() === 0)
                            <div style="padding:24px 18px;text-align:center;color:#94a3b8;font-size:.82rem">
                                <i class="fas fa-bell-slash" style="font-size:1.4rem;margin-bottom:6px;display:block;opacity:.4"></i>
                                Belum ada notifikasi
                            </div>
                            @endif
                            @if($unreadChat > 0)
                            <a href="{{ auth()->user()->isAdmin() || auth()->user()->isStaff() ? 'javascript:toggleAdminChat()' : 'javascript:toggleChat()' }}" style="display:block;padding:12px 18px;border-bottom:1px solid #f1f5f9;background:#fef3c7;text-decoration:none;color:#92400e;transition:background .15s" onmouseover="this.style.background='#fde68a'" onmouseout="this.style.background='#fef3c7'">
                                <div style="font-size:.78rem;font-weight:600"><i class="fas fa-comments"></i> {{ $unreadChat }} pesan chat belum dibaca <i class="fas fa-arrow-right" style="font-size:.65rem;margin-left:4px"></i></div>
                            </a>
                            @endif
                        </div>
                        <div style="padding:10px 18px;border-top:1px solid #e2e8f0;text-align:center">
                            <a href="{{ route('servis.index') }}" style="font-size:.72rem;color:var(--primary);font-weight:600;text-decoration:none">Lihat Semua Servis →</a>
                        </div>
                    </div>
                </div>

                {{-- Kalkulator --}}
                <button class="topbar-icon-btn" onclick="toggleCalculator()" title="Kalkulator">
                    <i class="fas fa-calculator"></i>
                </button>

                {{-- QRIS (selalu tampil untuk semua role) --}}
                <div style="position:relative" id="qrisWrap">
                    <button class="topbar-icon-btn" onclick="toggleQris(event)" title="QRIS Payment">
                        <i class="fas fa-qrcode"></i>
                    </button>
                    <div class="notif-dropdown" id="qrisDropdown" style="width:280px;padding:20px;text-align:center">
                        @if($qrisImage)
                        <div style="font-weight:700;font-size:.88rem;margin-bottom:12px">Scan QRIS</div>
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($qrisImage) }}" style="width:100%;max-width:220px;border-radius:10px;border:1px solid #e2e8f0">
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:10px">{{ $user->cabang?->nama ?? 'FIXPRO' }}</div>
                        @else
                        <div style="padding:20px 0">
                            <div style="font-size:2rem;margin-bottom:8px;opacity:.3"><i class="fas fa-qrcode"></i></div>
                            <div style="font-size:.82rem;font-weight:600;color:#64748b">QRIS Belum Diupload</div>
                            <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Minta admin untuk upload QRIS di Pengaturan</div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Language Switcher (pojok kanan atas) — Google Translate ID & EN --}}
                <div style="position:relative" id="langWrap">
                    <a href="#" class="topbar-icon-btn" onclick="toggleLang(event);return false" title="Bahasa" style="font-size:.8rem;font-weight:700;gap:4px;width:auto;padding:0 10px">
                        <span style="font-size:1rem">{{ $gtActive['flag'] }}</span>
                        <span style="font-size:.72rem">{{ strtoupper($gtCurrent) }}</span>
                        <i class="fas fa-caret-down" style="font-size:.6rem"></i>
                    </a>
                    <div class="notif-dropdown" id="langDropdown" style="width:220px;right:0" onclick="event.stopPropagation()">
                        <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;font-size:.84rem"><i class="fas fa-language" style="color:var(--primary)"></i> Bahasa</div>
                        @foreach($gtLangs as $code => $lg)
                        <a href="javascript:void(0)" onclick="setGTranslate('{{ $code }}')" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;font-size:.82rem;transition:background .15s{{ $code===$gtCurrent?';background:var(--primary-bg);color:var(--primary-dark);font-weight:700':'' }}" onmouseover="if('{{ $code }}'!=='{{ $gtCurrent }}')this.style.background='#f8fafc'" onmouseout="if('{{ $code }}'!=='{{ $gtCurrent }}')this.style.background='transparent'">
                            <span style="font-size:1.2rem">{{ $lg['flag'] }}</span>
                            <span style="flex:1">{{ $lg['native'] }}<br><span style="font-size:.66rem;color:#94a3b8">{{ $lg['name'] }}</span></span>
                            @if($code===$gtCurrent)<i class="fas fa-check" style="color:var(--primary)"></i>@endif
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Info Langganan (sisa hari semua paket) di pojok kanan atas --}}
                @if($subSummary && ($subSummary['days_left'] ?? null) !== null)
                <div title="{{ ($subSummary['ends_at']??null)?->translatedFormat('d F Y H:i') }}" style="display:flex;align-items:center;gap:5px;padding:4px 10px;border-radius:10px;border:1.5px solid {{ ($subSummary['days_left']??0)>7?'#bbf7d0':'#fcd34d' }};background:{{ ($subSummary['days_left']??0)>7?'#f0fdf4':'#fffbeb' }};color:{{ ($subSummary['days_left']??0)>7?'#166534':'#92400e' }};font-size:.7rem;font-weight:700;white-space:nowrap">
                    <i class="fas fa-clock" style="font-size:.66rem"></i>
                    <span>{{ $subSummary['days_left'] }} {{ t('subscription.days_left','hari tersisa') }}</span>
                </div>
                @endif

                {{-- Divider --}}
                <div class="topbar-divider"></div>

                {{-- User Info --}}
                <div class="topbar-user">
                    <div class="topbar-avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                    <div class="topbar-user-info">
                        <div class="topbar-user-name">{{ Str::limit($user->name, 18) }}</div>
                        <div class="topbar-user-status">
                            <span class="online-dot"></span> Online
                            @if($subSummary)
                                @if(($subSummary['type']??'')==='super_admin')
                                    <span class="trial-badge-top trial-permanent">Super Admin</span>
                                @elseif(($subSummary['type']??'')==='permanent')
                                    <span class="trial-badge-top trial-permanent">Permanen</span>
                                @elseif(($subSummary['type']??'')==='subscription')
                                    @if(($subSummary['days_left']??0)>0)
                                    <span class="trial-badge-top trial-active" style="background:#dbeafe;color:#1e40af">{{ $subSummary['label'] }} · {{ $subSummary['days_left'] }}h</span>
                                    @else
                                    <span class="trial-badge-top trial-expired">Expired</span>
                                    @endif
                                @elseif(($subSummary['days_left']??null)!==null && ($subSummary['days_left']??0)>0)
                                    <span class="trial-badge-top trial-active">Trial {{ $subSummary['days_left'] }} hari</span>
                                @elseif(($subSummary['days_left']??null)!==null)
                                    <span class="trial-badge-top trial-expired">Expired</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="topbar-icon-btn" title="Logout" style="color:var(--danger)">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </header>
        <div class="page-content">
            {{-- Trial Warning Banner --}}
            @if($showTrial && $daysLeft !== null && $daysLeft <= 7 && $daysLeft > 0)
            <div style="padding:10px 20px;background:linear-gradient(90deg,#fef3c7,#fde68a);border-bottom:1px solid #fcd34d;display:flex;align-items:center;justify-content:center;gap:10px;font-size:.82rem;color:#92400e">
                <i class="fas fa-exclamation-triangle"></i>
                <span><strong>Trial berakhir dalam {{ $daysLeft }} hari!</strong> @if($user->isAdminCabang()) <a href="{{ route('activation-request.index') }}" style="color:#0d9488;font-weight:700;text-decoration:underline">Request Aktivasi</a> untuk perpanjang. @else Masukkan Serial Number di <a href="{{ route('profile.edit') }}" style="color:#0d9488;font-weight:700;text-decoration:underline">Profil</a>. @endif</span>
            </div>
            @elseif($showTrial && $daysLeft !== null && $daysLeft <= 0)
            <div style="padding:10px 20px;background:linear-gradient(90deg,#fee2e2,#fecaca);border-bottom:1px solid #fca5a5;display:flex;align-items:center;justify-content:center;gap:10px;font-size:.82rem;color:#991b1b">
                <i class="fas fa-times-circle"></i>
                <span><strong>Akun sudah expired!</strong> @if($user->isAdminCabang()) <a href="{{ route('activation-request.index') }}" style="color:#0d9488;font-weight:700;text-decoration:underline">Request Aktivasi</a> @else Hubungi admin @endif untuk memperpanjang.</span>
            </div>
            @endif

            {{-- Fitur #11 — Pop-up pengingat masa aktif saat login (sekali per sesi browser) --}}
            @php
                $activationStatus = $user->subscriptionStatus();
                $showActivationPopup = !$user->is_super_admin && !$user->is_permanent
                    && $daysLeft !== null && in_array($daysLeft, [30, 15, 7, 3, 1, 0]);
                $statusLabel = $user->subscriptionStatusLabel();
            @endphp
            @if($showActivationPopup)
            <div id="activationReminderModal" style="display:none;position:fixed;inset:0;z-index:10001;align-items:center;justify-content:center;padding:16px">
                <div onclick="dismissActivationReminder()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
                <div style="position:relative;background:#fff;border-radius:18px;max-width:420px;width:100%;box-shadow:0 25px 60px rgba(0,0,0,.25);overflow:hidden">
                    <div style="padding:24px;text-align:center;background:linear-gradient(135deg,{{ $daysLeft > 7 ? '#f59e0b,#d97706' : '#dc2626,#991b1b' }});color:#fff">
                        <div style="font-size:2.4rem;margin-bottom:6px"><i class="fas fa-{{ $daysLeft > 0 ? 'clock' : 'times-circle' }}"></i></div>
                        <div style="font-weight:800;font-size:1.05rem">
                            @if($daysLeft > 0) Masa Aktif Tersisa {{ $daysLeft }} Hari @else Masa Aktif Berakhir @endif
                        </div>
                    </div>
                    <div style="padding:22px;text-align:center">
                        <p style="font-size:.86rem;color:#475569;margin:0 0 14px">
                            @if($daysLeft > 0)
                            Lisensi <strong>{{ $user->cabang?->nama ?? 'Anda' }}</strong> akan berakhir dalam <strong style="color:{{ $daysLeft > 7 ? '#d97706' : '#dc2626' }}">{{ $daysLeft }} hari</strong> ({{ $expiresAt?->translatedFormat('d F Y') }}).
                            <br>Segera lakukan perpanjangan agar layanan tidak terputus.
                            @else
                            Masa aktif lisensi telah <strong style="color:#dc2626">berakhir</strong>. Sebagian fitur mungkin tidak dapat digunakan.
                            @endif
                        </p>
                        <div style="display:flex;gap:8px;margin-top:16px">
                            @if($user->isAdminCabang())
                            <a href="{{ route('activation-request.index') }}" class="btn btn-primary" style="flex:1"><i class="fas fa-key"></i> Aktivasi Sekarang</a>
                            @else
                            <a href="{{ route('profile.edit') }}" class="btn btn-primary" style="flex:1"><i class="fas fa-key"></i> Masukkan Serial</a>
                            @endif
                            <button onclick="dismissActivationReminder()" class="btn btn-secondary" style="flex:1">Ingatkan Nanti</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            (function() {
                var key = 'fixpro_activation_reminder_{{ $user->id }}_{{ $daysLeft }}d';
                try {
                    if (sessionStorage.getItem(key) !== '1') {
                        window.addEventListener('load', function() {
                            var modal = document.getElementById('activationReminderModal');
                            if (modal) { modal.style.display = 'flex'; }
                        });
                    }
                } catch(e) {}
                window.dismissActivationReminder = function() {
                    var modal = document.getElementById('activationReminderModal');
                    if (modal) modal.style.display = 'none';
                    try { sessionStorage.setItem(key, '1'); } catch(e) {}
                };
            })();
            </script>
            @endif

            @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
            @endif
            @yield('content')
        </div>
    </div>

    {{-- ==================== Fitur #9 — BOTTOM NAVIGATION MOBILE ==================== --}}
    @php
        $isTeknisiRole = auth()->user()->isTeknisi();
        $isUserOnly = auth()->user()->isUser() && !auth()->user()->isAdmin() && !auth()->user()->isStaff() && !$isTeknisiRole;
        $isAdminStaff = auth()->user()->isAdmin() || auth()->user()->isStaff() || auth()->user()->isSuperAdmin();
    @endphp
    <nav class="bottom-nav" id="bottomNav">
        <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        @if($isAdminStaff)
        <a href="{{ route('servis.index') }}" class="bottom-nav-item {{ request()->routeIs('servis.*','arsip-servis.*','my-service.*') ? 'active' : '' }}">
            <i class="fas fa-tools"></i><span>Servis</span>
        </a>
        <a href="{{ route('penjualan-sparepart.index') }}" class="bottom-nav-item {{ request()->routeIs('penjualan-sparepart.*','jualbeli.*','kas.*','payment.*') ? 'active' : '' }}">
            <i class="fas fa-credit-card"></i><span>Transaksi</span>
        </a>
        <a href="{{ route('stok.index') }}" class="bottom-nav-item {{ request()->routeIs('stok.*','pembelian.*','tagihan-sparepart.*','aktivitas-sparepart.*') ? 'active' : '' }}">
            <i class="fas fa-boxes"></i><span>Stok</span>
        </a>
        @elseif(auth()->user()->isAdminCabangAnak())
        <a href="{{ route('stok.index') }}" class="bottom-nav-item {{ request()->routeIs('stok.*','pembelian.*','aktivitas-sparepart.*') ? 'active' : '' }}">
            <i class="fas fa-boxes"></i><span>Stok</span>
        </a>
        @elseif($isTeknisiRole)
        <a href="{{ route('servis.index') }}" class="bottom-nav-item {{ request()->routeIs('servis.*') ? 'active' : '' }}">
            <i class="fas fa-tools"></i><span>Servis</span>
        </a>
        @elseif($isUserOnly)
        <a href="{{ route('my-service.index') }}" class="bottom-nav-item {{ request()->routeIs('my-service.*','servis.*') ? 'active' : '' }}">
            <i class="fas fa-tools"></i><span>Servis</span>
        </a>
        @endif
        <a href="#" class="bottom-nav-item bottom-nav-more" onclick="openBottomSheet();return false">
            <i class="fas fa-bars"></i><span>Lainnya</span>
        </a>
    </nav>

    {{-- Bottom sheet (More menu) --}}
    <div class="bottom-nav-sheet-overlay" id="bnOverlay" onclick="closeBottomSheet()"></div>
    <div class="bottom-nav-sheet" id="bnSheet">
        <div class="sheet-handle"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding:0 4px">
            <div style="font-weight:700;font-size:.95rem"><i class="fas fa-th-large" style="color:var(--primary)"></i> Menu Lainnya</div>
            <button onclick="closeBottomSheet()" style="background:#f1f5f9;border:none;width:30px;height:30px;border-radius:8px;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <div class="sheet-grid">
            @if($isAdminStaff)
                <a href="{{ route('servis.create') }}" class="sheet-link"><i class="fas fa-plus-circle"></i> Input Servis</a>
                <a href="{{ route('pelanggan.index') }}" class="sheet-link"><i class="fas fa-users"></i> Pelanggan</a>
                <a href="{{ route('teknisi.index') }}" class="sheet-link"><i class="fas fa-wrench"></i> Teknisi</a>
                <a href="{{ route('jualbeli.index') }}" class="sheet-link"><i class="fas fa-mobile-alt"></i> Jual Beli HP</a>
                <a href="{{ route('aktivitas-sparepart.index') }}" class="sheet-link"><i class="fas fa-exchange-alt"></i> Aktivitas Sparepart</a>
                <a href="{{ route('laporan-keuangan.index') }}" class="sheet-link"><i class="fas fa-chart-line"></i> Laporan</a>
                <a href="{{ route('kas.index') }}" class="sheet-link"><i class="fas fa-cash-register"></i> Kas Harian</a>
                <a href="{{ route('payment.select') }}" class="sheet-link"><i class="fas fa-credit-card"></i> Pembayaran</a>
                <a href="{{ route('subscription.index') }}" class="sheet-link"><i class="fas fa-star"></i> Langganan</a>
                <a href="{{ route('whatsapp.index') }}" class="sheet-link"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                <a href="{{ route('settings.index') }}" class="sheet-link"><i class="fas fa-cog"></i> Pengaturan</a>
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('sync.index') }}" class="sheet-link"><i class="fas fa-sync-alt"></i> Sinkronisasi</a>
                <a href="{{ route('audit-log.index') }}" class="sheet-link"><i class="fas fa-clipboard-list"></i> Audit Log</a>
                @endif
                <a href="{{ route('profile.edit') }}" class="sheet-link"><i class="fas fa-user-edit"></i> Profil</a>
            @elseif($isUserOnly)
                <a href="{{ route('my-service.create') }}" class="sheet-link"><i class="fas fa-plus-circle"></i> Daftar Servis</a>
                <a href="{{ route('arsip-servis.index') }}" class="sheet-link"><i class="fas fa-search-location"></i> Lacak Servis</a>
                <a href="{{ route('subscription.index') }}" class="sheet-link"><i class="fas fa-star"></i> Langganan</a>
                <a href="{{ route('profile.edit') }}" class="sheet-link"><i class="fas fa-user-edit"></i> Profil</a>
            @endif
        </div>
    </div>
    <script>
    function openBottomSheet() {
        document.getElementById('bnSheet').classList.add('show');
        document.getElementById('bnOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeBottomSheet() {
        document.getElementById('bnSheet').classList.remove('show');
        document.getElementById('bnOverlay').classList.remove('show');
        document.body.style.overflow = '';
    }
    // Tutup sheet dengan swipe-down sederhana
    document.addEventListener('DOMContentLoaded', function() {
        const handle = document.querySelector('.sheet-handle');
        const sheet = document.getElementById('bnSheet');
        if (handle && sheet) {
            let startY = 0;
            handle.addEventListener('touchstart', e => { startY = e.touches[0].clientY; });
            handle.addEventListener('touchmove', e => {
                const dy = e.touches[0].clientY - startY;
                if (dy > 0) sheet.style.transform = 'translateY(' + dy + 'px)';
            });
            handle.addEventListener('touchend', e => {
                const dy = e.changedTouches[0].clientY - startY;
                sheet.style.transform = '';
                if (dy > 80) closeBottomSheet();
            });
        }
    });
    </script>

    {{-- ==================== CHAT WIDGET (for User) ==================== --}}
    @if(auth()->user()->isUser() && !auth()->user()->isAdmin() && !auth()->user()->isStaff())
    <div id="chatWidget" style="position:fixed;bottom:20px;left:20px;z-index:1000">
        <!-- Chat Button -->
        <button id="chatToggle" onclick="toggleChat()" style="width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 15px rgba(13,148,136,.4);font-size:1.3rem;display:flex;align-items:center;justify-content:center;transition:all .3s">
            <i class="fas fa-comments"></i>
        </button>
        <!-- Unread badge -->
        <span id="chatUnread" style="display:none;position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:.65rem;font-weight:700;width:18px;height:18px;border-radius:50%;display:none;align-items:center;justify-content:center"></span>
    </div>

    <!-- Chat Panel -->
    <div id="chatPanel" style="display:none;position:fixed;bottom:20px;left:20px;z-index:1001;width:360px;max-width:calc(100vw - 40px);height:480px;max-height:calc(100vh - 60px);background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.15);flex-direction:column;overflow:hidden">
        <!-- Header -->
        <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));padding:14px 18px;display:flex;align-items:center;justify-content:space-between">
            <div style="display:flex;align-items:center;gap:10px;color:#fff">
                <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1rem"><i class="fas fa-headset"></i></div>
                <div>
                    <div style="font-weight:700;font-size:.88rem">FIXPRO Support</div>
                    <div style="font-size:.65rem;opacity:.8" id="chatBotStatus">🤖 Bot Aktif</div>
                </div>
            </div>
            <button onclick="toggleChat()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:.8rem"><i class="fas fa-times"></i></button>
        </div>
        <!-- Messages -->
        <div id="chatMessages" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;height:calc(100% - 130px)">
            <div style="text-align:center;font-size:.72rem;color:#94a3b8;padding:10px">Memuat pesan...</div>
        </div>
        <!-- Input -->
        <div style="padding:12px 16px;border-top:1px solid #e2e8f0;display:flex;gap:8px">
            <input type="text" id="chatInput" class="form-input" placeholder="Ketik pesan..." style="flex:1;padding:8px 12px;font-size:.82rem" onkeydown="if(event.key==='Enter')sendChatMessage()">
            <button onclick="sendChatMessage()" style="background:var(--primary);color:#fff;border:none;width:38px;height:38px;border-radius:10px;cursor:pointer;font-size:.9rem;display:flex;align-items:center;justify-content:center"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <script>
    let chatOpen = false;
    let chatLoaded = false;

    function toggleChat() {
        chatOpen = !chatOpen;
        const panel = document.getElementById('chatPanel');
        const btn = document.getElementById('chatToggle');
        panel.style.display = chatOpen ? 'flex' : 'none';
        btn.innerHTML = chatOpen ? '<i class="fas fa-times"></i>' : '<i class="fas fa-comments"></i>';
        if (chatOpen && !chatLoaded) {
            loadChatMessages();
            chatLoaded = true;
        }
    }

    function loadChatMessages() {
        fetch('/chat/messages')
            .then(r => r.json())
            .then(data => {
                renderMessages(data.messages);
            })
            .catch(err => console.error(err));
    }

    function renderMessages(messages) {
        const container = document.getElementById('chatMessages');
        if (!messages.length) {
            container.innerHTML = '<div style="text-align:center;padding:30px 10px"><div style="font-size:2rem;margin-bottom:10px">💬</div><div style="font-size:.84rem;font-weight:600;color:#1e293b">Halo! Ada yang bisa dibantu?</div><div style="font-size:.76rem;color:#94a3b8;margin-top:4px">Silakan tanyakan keluhan HP Anda</div></div>';
            return;
        }
        container.innerHTML = messages.map(m => {
            const isMe = m.sender_id == {{ auth()->id() }};
            const isBot = m.is_bot;
            const time = new Date(m.created_at).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
            return '<div style="display:flex;justify-content:' + (isMe ? 'flex-end' : 'flex-start') + '">' +
                '<div style="max-width:75%;padding:8px 12px;border-radius:' + (isMe ? '12px 12px 4px 12px' : '12px 12px 12px 4px') + ';font-size:.82rem;line-height:1.4;' +
                (isMe ? 'background:var(--primary);color:#fff' : 'background:#f1f5f9;color:#1e293b') + '">' +
                (isBot ? '<div style="font-size:.6rem;color:' + (isMe ? '#86efac' : '#0d9488') + ';margin-bottom:3px;display:flex;align-items:center;gap:3px"><i class="fas fa-robot"></i> AI Bot</div>' : '') +
                m.message +
                '<div style="font-size:.6rem;margin-top:3px;opacity:.6">' + time + '</div>' +
                '</div></div>';
        }).join('');
        container.scrollTop = container.scrollHeight;
    }

    function sendChatMessage() {
        const input = document.getElementById('chatInput');
        const msg = input.value.trim();
        if (!msg) return;
        input.value = '';

        // Show message immediately
        const container = document.getElementById('chatMessages');
        const now = new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
        container.innerHTML += '<div style="display:flex;justify-content:flex-end"><div style="max-width:75%;padding:8px 12px;border-radius:12px 12px 4px 12px;font-size:.82rem;line-height:1.4;background:var(--primary);color:#fff">' + msg + '<div style="font-size:.6rem;margin-top:3px;opacity:.6">' + now + '</div></div></div>';
        container.scrollTop = container.scrollHeight;

        // Show typing indicator
        const typingId = 'typing-' + Date.now();
        container.innerHTML += '<div id="' + typingId + '" style="display:flex;justify-content:flex-start"><div style="padding:8px 12px;border-radius:12px 12px 12px 4px;background:#f1f5f9;font-size:.82rem;color:#94a3b8"><i class="fas fa-robot"></i> Bot sedang berpikir...</div></div>';
        container.scrollTop = container.scrollHeight;

        fetch('/chat/send', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify({message: msg})
        })
        .then(r => {
            if (!r.ok) {
                return r.text().then(text => {
                    throw new Error('Server error ' + r.status + ': ' + text.substring(0, 200));
                });
            }
            return r.json();
        })
        .then(data => {
            // Remove typing indicator
            const typing = document.getElementById(typingId);
            if (typing) typing.remove();
            // Reload messages to get bot reply
            loadChatMessages();
        })
        .catch(err => {
            console.error('Chat send error:', err);
            const typing = document.getElementById(typingId);
            if (typing) typing.innerHTML = '<div style="padding:8px 12px;border-radius:12px 12px 12px 4px;background:#fef2f2;font-size:.82rem;color:#dc2626"><i class="fas fa-exclamation-circle"></i> Gagal: ' + err.message.substring(0, 80) + '</div>';
            // Auto retry load after 5 seconds
            setTimeout(() => loadChatMessages(), 5000);
        });
    }
    </script>
    @endif

    {{-- ==================== CHAT WIDGET (for Admin) ==================== --}}
    @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
    <div id="adminChatWidget" style="position:fixed;bottom:20px;left:20px;z-index:1000">
        <button id="adminChatToggle" onclick="toggleAdminChat()" style="width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 15px rgba(13,148,136,.4);font-size:1.3rem;display:flex;align-items:center;justify-content:center;transition:all .3s;position:relative">
            <i class="fas fa-headset"></i>
            <span id="adminChatBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:.65rem;font-weight:700;min-width:18px;height:18px;border-radius:9px;padding:0 4px;display:none;align-items:center;justify-content:center"></span>
        </button>
    </div>

    <div id="adminChatPanel" style="display:none;position:fixed;bottom:20px;left:20px;z-index:1001;width:400px;max-width:calc(100vw - 40px);height:520px;max-height:calc(100vh - 60px);background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.15);flex-direction:column;overflow:hidden">
        <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));padding:14px 18px;display:flex;align-items:center;justify-content:space-between;color:#fff">
            <div style="display:flex;align-items:center;gap:10px">
                <i class="fas fa-headset" style="font-size:1.2rem"></i>
                <div>
                    <div style="font-weight:700;font-size:.88rem">Chat Pelanggan</div>
                    <div style="font-size:.65rem;opacity:.8" id="adminChatRoomInfo">Pilih percakapan</div>
                </div>
            </div>
            <button onclick="toggleAdminChat()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <!-- Room list -->
        <div id="adminChatRooms" style="flex:1;overflow-y:auto;min-height:100%">
            <div style="text-align:center;padding:20px;color:#94a3b8;font-size:.82rem">Memuat...</div>
        </div>
    </div>

    <!-- Admin Chat Detail Panel (replaces rooms list) -->
    <div id="adminChatDetail" style="display:none;position:fixed;bottom:20px;left:20px;z-index:1002;width:400px;max-width:calc(100vw - 40px);height:520px;max-height:calc(100vh - 60px);background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.15);flex-direction:column;overflow:hidden">
        <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));padding:14px 18px;display:flex;align-items:center;justify-content:space-between;color:#fff">
            <div style="display:flex;align-items:center;gap:10px">
                <button onclick="backToRooms()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer"><i class="fas fa-arrow-left"></i></button>
                <div>
                    <div style="font-weight:700;font-size:.88rem" id="adminChatUserName">-</div>
                    <div style="font-size:.65rem;opacity:.8" id="adminChatUserBranch">-</div>
                </div>
            </div>
            <button onclick="toggleAdminChat()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <div id="adminChatMsgs" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;height:calc(100% - 130px)"></div>
        <div style="padding:12px 16px;border-top:1px solid #e2e8f0;display:flex;gap:8px">
            <input type="text" id="adminChatInput" class="form-input" placeholder="Balas pesan..." style="flex:1;padding:8px 12px;font-size:.82rem" onkeydown="if(event.key==='Enter')adminSendMsg()">
            <button onclick="adminSendMsg()" style="background:var(--primary);color:#fff;border:none;width:38px;height:38px;border-radius:10px;cursor:pointer;font-size:.9rem;display:flex;align-items:center;justify-content:center"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <script>
    let adminChatOpen = false;
    let currentAdminRoomId = null;

    function toggleAdminChat() {
        adminChatOpen = !adminChatOpen;
        document.getElementById('adminChatPanel').style.display = adminChatOpen ? 'flex' : 'none';
        document.getElementById('adminChatDetail').style.display = 'none';
        if (adminChatOpen) loadAdminRooms();
    }

    function loadAdminRooms() {
        fetch('/chat/admin/rooms')
            .then(r => r.json())
            .then(rooms => {
                const container = document.getElementById('adminChatRooms');
                if (!rooms.length) {
                    container.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;font-size:.82rem">Belum ada chat dari pelanggan</div>';
                    return;
                }
                container.innerHTML = rooms.map(r => {
                    const lastMsg = r.last_message ? r.last_message.message.substring(0, 40) + (r.last_message.message.length > 40 ? '...' : '') : 'Belum ada pesan';
                    const time = r.last_message ? new Date(r.last_message.created_at).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}) : '';
                    const unread = r.unread || 0;
                    const botTag = (r.last_message && r.last_message.is_bot) ? ' <span style="font-size:.6rem;color:#0d9488">🤖</span>' : '';
                    return '<div onclick="openAdminRoom(' + r.id + ',\'' + (r.user?.name || 'User') + '\',\'' + (r.cabang?.nama || '-') + '\')" style="padding:12px 16px;border-bottom:1px solid #f1f5f9;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background .2s" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'transparent\'">' +
                        '<div style="width:38px;height:38px;border-radius:50%;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:700;font-size:.75rem">' + (r.user?.name || 'U').substring(0, 2).toUpperCase() + '</div>' +
                        '<div style="flex:1;min-width:0">' +
                        '<div style="display:flex;justify-content:space-between;align-items:center"><span style="font-weight:600;font-size:.82rem">' + (r.user?.name || 'User') + '</span><span style="font-size:.65rem;color:#94a3b8">' + time + '</span></div>' +
                        '<div style="font-size:.76rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + botTag + lastMsg + '</div></div>' +
                        (unread > 0 ? '<span style="background:var(--danger);color:#fff;font-size:.6rem;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px">' + unread + '</span>' : '') +
                        '</div>';
                }).join('');
            });
    }

    function openAdminRoom(roomId, userName, branch) {
        currentAdminRoomId = roomId;
        document.getElementById('adminChatPanel').style.display = 'none';
        document.getElementById('adminChatDetail').style.display = 'flex';
        document.getElementById('adminChatUserName').textContent = userName;
        document.getElementById('adminChatUserBranch').textContent = 'Cabang: ' + branch;

        fetch('/chat/admin/messages/' + roomId)
            .then(r => r.json())
            .then(data => {
                renderAdminMessages(data.messages);
            });
    }

    function backToRooms() {
        document.getElementById('adminChatDetail').style.display = 'none';
        document.getElementById('adminChatPanel').style.display = 'flex';
        loadAdminRooms();
    }

    function renderAdminMessages(messages) {
        const container = document.getElementById('adminChatMsgs');
        container.innerHTML = messages.map(m => {
            const isMe = m.sender_id == {{ auth()->id() }};
            const isBot = m.is_bot;
            const time = new Date(m.created_at).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
            return '<div style="display:flex;justify-content:' + (isMe ? 'flex-end' : 'flex-start') + '">' +
                '<div style="max-width:75%;padding:8px 12px;border-radius:' + (isMe ? '12px 12px 4px 12px' : '12px 12px 12px 4px') + ';font-size:.82rem;line-height:1.4;' +
                (isMe ? 'background:var(--primary);color:#fff' : 'background:#f1f5f9;color:#1e293b') + '">' +
                (isBot ? '<div style="font-size:.6rem;color:' + (isMe ? '#86efac' : '#0d9488') + ';margin-bottom:3px"><i class="fas fa-robot"></i> AI Bot</div>' : '') +
                m.message +
                '<div style="font-size:.6rem;margin-top:3px;opacity:.6">' + time + '</div>' +
                '</div></div>';
        }).join('');
        container.scrollTop = container.scrollHeight;
    }

    function adminSendMsg() {
        const input = document.getElementById('adminChatInput');
        const msg = input.value.trim();
        if (!msg || !currentAdminRoomId) return;
        input.value = '';

        fetch('/chat/admin/send', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify({message: msg, room_id: currentAdminRoomId})
        })
        .then(r => r.json())
        .then(() => {
            fetch('/chat/admin/messages/' + currentAdminRoomId)
                .then(r => r.json())
                .then(data => renderAdminMessages(data.messages));
        });
    }
    </script>
    @endif

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        function formatRp(n) {
            return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        }

        /* ========================================================
           FORMATTING ANGKA DENGAN PEMISAH RIBUAN (".")
           Pakai: tambahkan atribut  data-format-rupiah  pada <input>
           Saat diketik otomatis tampil "100.000", saat form di-submit
           nilainya dikembalikan jadi angka murni (100000).
        ======================================================== */
        function _formatRupiahInput(val) {
            var s = String(val == null ? '' : val);
            if (/^\d+\.\d{1,2}$/.test(s)) {
                s = s.replace(/\.\d{1,2}$/, '');
            }
            var angka = s.replace(/[^0-9]/g, '');
            if (angka === '') return '';
            angka = angka.replace(/^0+(?=\d)/, '');
            return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        window._formatRupiahInput = _formatRupiahInput;

        function applyRupiahFormatOnInput(el) {
            var formatted = _formatRupiahInput(el.value);
            if (el.value !== formatted) el.value = formatted;
        }
        window.applyRupiahFormatOnInput = applyRupiahFormatOnInput;

        document.addEventListener('input', function (e) {
            var el = e.target;
            if (el && el.matches('input[data-format-rupiah]')) {
                applyRupiahFormatOnInput(el);
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || !form.querySelectorAll) return;
            form.querySelectorAll('input[data-format-rupiah]').forEach(function (el) {
                el.value = String(el.value).replace(/[^0-9]/g, '');
                if (el.value === '') el.value = '0';
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('input[data-format-rupiah]').forEach(function (el) {
                applyRupiahFormatOnInput(el);
            });
        });

        // === Universal Search ===
        function doUniversalSearch() {
            const q = document.getElementById('universalSearch').value.trim();
            if (!q) return;
            window.location.href = '{{ route("servis.index") }}?search=' + encodeURIComponent(q);
        }

        // === Kalkulator ===
        let calcOpen = false;
        function toggleCalculator() {
            document.getElementById('notifDropdown')?.classList.remove('show');
            document.getElementById('qrisDropdown')?.classList.remove('show');

            calcOpen = !calcOpen;
            let panel = document.getElementById('calcPanel');
            if (!panel) {
                panel = document.createElement('div');
                panel.id = 'calcPanel';
                panel.style.cssText = 'position:fixed;top:72px;right:24px;z-index:9999;width:280px;background:#fff;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.15);border:1px solid #e2e8f0;overflow:hidden';
                panel.innerHTML = `
                    <div style="padding:14px 16px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:flex;justify-content:space-between;align-items:center">
                        <span style="font-weight:700;font-size:.88rem"><i class="fas fa-calculator"></i> Kalkulator</span>
                        <button onclick="toggleCalculator()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:24px;height:24px;border-radius:6px;cursor:pointer"><i class="fas fa-times"></i></button>
                    </div>
                    <div style="padding:12px 16px">
                        <input type="text" id="calcDisplay" readonly style="width:100%;padding:10px;font-size:1.3rem;font-weight:700;text-align:right;border:1.5px solid #e2e8f0;border-radius:8px;background:#f8fafc;margin-bottom:10px" value="0">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px">
                            <button onclick="calcInput('C')" style="padding:10px;border:none;border-radius:8px;background:#fee2e2;color:#dc2626;font-weight:700;cursor:pointer;font-size:.9rem">C</button>
                            <button onclick="calcInput('±')" style="padding:10px;border:none;border-radius:8px;background:#f1f5f9;color:#374151;font-weight:700;cursor:pointer;font-size:.9rem">±</button>
                            <button onclick="calcInput('%')" style="padding:10px;border:none;border-radius:8px;background:#f1f5f9;color:#374151;font-weight:700;cursor:pointer;font-size:.9rem">%</button>
                            <button onclick="calcInput('÷')" style="padding:10px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-weight:700;cursor:pointer;font-size:.9rem">÷</button>
                            <button onclick="calcInput('7')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">7</button>
                            <button onclick="calcInput('8')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">8</button>
                            <button onclick="calcInput('9')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">9</button>
                            <button onclick="calcInput('×')" style="padding:10px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-weight:700;cursor:pointer;font-size:.9rem">×</button>
                            <button onclick="calcInput('4')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">4</button>
                            <button onclick="calcInput('5')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">5</button>
                            <button onclick="calcInput('6')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">6</button>
                            <button onclick="calcInput('-')" style="padding:10px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-weight:700;cursor:pointer;font-size:.9rem">−</button>
                            <button onclick="calcInput('1')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">1</button>
                            <button onclick="calcInput('2')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">2</button>
                            <button onclick="calcInput('3')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">3</button>
                            <button onclick="calcInput('+')" style="padding:10px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-weight:700;cursor:pointer;font-size:.9rem">+</button>
                            <button onclick="calcInput('0')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem;grid-column:span 2">0</button>
                            <button onclick="calcInput('.')" style="padding:10px;border:none;border-radius:8px;background:#f8fafc;color:#1e293b;font-weight:600;cursor:pointer;font-size:.9rem">.</button>
                            <button onclick="calcInput('=')" style="padding:10px;border:none;border-radius:8px;background:#059669;color:#fff;font-weight:700;cursor:pointer;font-size:.9rem">=</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(panel);
            } else {
                panel.style.display = calcOpen ? 'block' : 'none';
            }
        }

        let calcExpr = '';
        function calcInput(val) {
            const display = document.getElementById('calcDisplay');
            if (!display) return;
            if (val === 'C') { calcExpr = ''; display.value = '0'; return; }
            if (val === '±') { calcExpr = String(-parseFloat(calcExpr || '0')); display.value = calcExpr; return; }
            if (val === '%') { calcExpr = String(parseFloat(calcExpr || '0') / 100); display.value = calcExpr; return; }
            if (val === '=') {
                try {
                    let expr = calcExpr.replace(/×/g, '*').replace(/÷/g, '/').replace(/−/g, '-');
                    calcExpr = String(eval(expr));
                    display.value = calcExpr;
                } catch(e) { display.value = 'Error'; calcExpr = ''; }
                return;
            }
            if (calcExpr === '0' && !isNaN(val)) calcExpr = '';
            calcExpr += val;
            display.value = calcExpr;
        }

        // === Notifikasi Toggle ===
        function toggleNotif(e) {
            if (e) e.stopPropagation();
            const dd = document.getElementById('notifDropdown');
            if (dd) {
                document.getElementById('qrisDropdown')?.classList.remove('show');
                let calcPanel = document.getElementById('calcPanel');
                if (calcPanel) calcPanel.style.display = 'none';
                dd.classList.toggle('show');
            }
        }

        // === QRIS Toggle ===
        function toggleQris(e) {
            if (e) e.stopPropagation();
            const dd = document.getElementById('qrisDropdown');
            if (dd) {
                document.getElementById('notifDropdown')?.classList.remove('show');
                let calcPanel = document.getElementById('calcPanel');
                if (calcPanel) calcPanel.style.display = 'none';
                dd.classList.toggle('show');
            }
        }

        // === Language Toggle ===
        function toggleLang(e) {
            if (e) e.stopPropagation();
            const dd = document.getElementById('langDropdown');
            if (dd) {
                document.getElementById('notifDropdown')?.classList.remove('show');
                document.getElementById('qrisDropdown')?.classList.remove('show');
                let calcPanel = document.getElementById('calcPanel');
                if (calcPanel) calcPanel.style.display = 'none';
                dd.classList.toggle('show');
            }
        }

        // Close dropdowns on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#notifWrap')) {
                document.getElementById('notifDropdown')?.classList.remove('show');
            }
            if (!e.target.closest('#qrisWrap')) {
                document.getElementById('qrisDropdown')?.classList.remove('show');
            }
            if (!e.target.closest('#langWrap')) {
                document.getElementById('langDropdown')?.classList.remove('show');
            }
        });

        // === Jam & Tanggal WIB ===
        function updateClock() {
            const now = new Date();
            const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
            const wib = new Date(utc + (7 * 3600000));

            const jam = String(wib.getHours()).padStart(2, '0');
            const menit = String(wib.getMinutes()).padStart(2, '0');
            const detik = String(wib.getSeconds()).padStart(2, '0');
            document.getElementById('clockTime').textContent = jam + ':' + menit + ':' + detik;

            const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const tanggal = hari[wib.getDay()] + ', ' + wib.getDate() + ' ' + bulan[wib.getMonth()] + ' ' + wib.getFullYear();
            document.getElementById('clockDate').textContent = tanggal;
        }
        updateClock();
        setInterval(updateClock, 1000);

        // === Dark Mode ===
        function toggleDarkMode() {
            const isDark = document.getElementById('darkModeToggle').checked;
            document.body.classList.toggle('dark', isDark);
            localStorage.setItem('fixpro_dark', isDark ? '1' : '0');
            updateDarkModeUI(isDark);
        }
        function updateDarkModeUI(isDark) {
            const slider = document.getElementById('darkModeSlider');
            const dot = document.getElementById('darkModeDot');
            const icon = document.getElementById('darkModeIcon');
            const label = document.getElementById('darkModeLabel');
            if (isDark) {
                slider.style.background = '#0d9488';
                dot.style.transform = 'translateX(20px)';
                icon.className = 'fas fa-sun';
                icon.style.color = '#fbbf24';
                label.textContent = 'Light Mode';
            } else {
                slider.style.background = '#cbd5e1';
                dot.style.transform = 'translateX(0)';
                icon.className = 'fas fa-moon';
                icon.style.color = '#64748b';
                label.textContent = 'Dark Mode';
            }
        }
        (function() {
            const saved = localStorage.getItem('fixpro_dark');
            const isDark = saved === '1';
            document.getElementById('darkModeToggle').checked = isDark;
            if (isDark) {
                document.body.classList.add('dark');
            }
            updateDarkModeUI(isDark);
        })();

        // === GLOBAL LOADING SPINNER LOGIC ===
        function showGlobalLoading() {
            const overlay = document.getElementById('globalLoadingOverlay');
            if (overlay) overlay.classList.add('show');
        }
        function hideGlobalLoading() {
            const overlay = document.getElementById('globalLoadingOverlay');
            if (overlay) overlay.classList.remove('show');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Form Submit
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    if (form.classList.contains('no-loading')) return;
                    showGlobalLoading();
                    setTimeout(hideGlobalLoading, 15000); 
                });
            });

            // 2. Link Click
            document.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = link.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.getAttribute('target') === '_blank') return;
                    if (link.classList.contains('no-loading')) return;
                    if (link.closest('.notif-dropdown') || link.closest('.bottom-nav-sheet') || link.closest('#chatPanel') || link.closest('#adminChatDetail')) return;

                    showGlobalLoading();
                    setTimeout(hideGlobalLoading, 15000);
                });
            });

            // 3. Fetch API Intercept
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                const url = typeof args[0] === 'string' ? args[0] : args[0]?.url || '';
                
                // Abaikan fetch untuk Chat, Translate, Chart.js, dan CDN agar tidak nge-freeze UI
                if (url.includes('/chat/') || url.includes('translate') || url.includes('goog') || url.includes('chart.js') || url.includes('cdnjs.cloudflare.com') || url.includes('fonts.gstatic.com') || url.includes('fonts.googleapis.com')) {
                    return originalFetch.apply(this, args);
                }

                showGlobalLoading();
                return originalFetch.apply(this, args)
                    .then(response => { hideGlobalLoading(); return response; })
                    .catch(error => { hideGlobalLoading(); throw error; });
            };

            // 4. BFCache fallback
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) hideGlobalLoading();
            });
        });
    </script>

    <!-- Global Loading Overlay (Spinner Hijau) -->
    <div class="loading-overlay" id="globalLoadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    {{-- ===== Google Translate — translate semua teks otomatis ===== --}}
    <div id="google_translate_element" style="display:none!important"></div>
    <script>
    function googleTranslateElementInit(){
        new google.translate.TranslateElement({
            pageLanguage: 'id',
            includedLanguages: 'id,en,hi',
            autoDisplay: false
        }, 'google_translate_element');
    }
    function setGTranslate(lang){
        var domain = '.' + location.hostname.replace(/^www\./, '');
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + domain;
        if (lang && lang !== 'id') {
            var val = '/id/' + lang;
            document.cookie = 'googtrans=' + val + '; path=/; domain=' + domain;
            document.cookie = 'googtrans=' + val + '; path=/';
        }
        location.reload();
    }
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>