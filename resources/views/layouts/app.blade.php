<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Halaman' }} - Solo Amal Fatimah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f0f4f8;
            --card: #ffffff;
            --sidebar-w: 240px;
            --sidebar: #0f1c2e;
            --sidebar-active-bg: rgba(16,185,129,0.12);
            --sidebar-active: #10b981;
            --sidebar-text: #7a93b0;
            --text: #0d1b2a;
            --text-muted: #6b7a8d;
            --border: #e4eaf0;
            --green: #059669;
            --red: #e11d48;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            line-height: 1.6;
        }

        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            background: var(--sidebar);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            transition: transform .25s ease;
        }

        .sidebar-brand {
            padding: 22px 18px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        .sidebar-brand img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.35);
        }
        .sidebar-brand-title {
            font-weight: 700;
            font-size: 13.5px;
            color: #fff;
            font-family: 'Sora', sans-serif;
            line-height: 1.3;
        }
        .sidebar-brand-sub { font-size: 11px; color: var(--sidebar-text); margin-top: 2px; }

        .sidebar-nav {
            flex: 1;
            padding: 12px 10px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .sidebar-nav::-webkit-scrollbar { width: 0; }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--sidebar-text);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            border-radius: 9px;
            transition: all .15s;
            white-space: nowrap;
        }
        .sidebar-nav button.sidebar-item {
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }
        .sidebar-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar-item.active { background: var(--sidebar-active-bg); color: var(--sidebar-active); }
        .sidebar-item svg { width: 17px; height: 17px; flex-shrink: 0; }
        .sidebar-item.logout { color: #f87171; }
        .sidebar-item.logout:hover { background: rgba(248,113,113,0.1); }

        .sidebar-item.has-children { cursor: pointer; }
        .sidebar-item.has-children .chevron {
            margin-left: auto;
            font-size: 11px;
            opacity: 0.8;
            transition: transform .15s ease;
        }
        .sidebar-item.has-children.open .chevron { transform: rotate(90deg); }

        .sidebar-subnav {
            padding-left: 34px;
            max-height: 0;
            overflow: hidden;
            transition: max-height .2s ease;
        }
        .sidebar-subnav.open { max-height: 520px; }
        .sidebar-subnav a {
            display: block;
            padding: 6px 0;
            font-size: 12.5px;
            color: var(--sidebar-text);
            text-decoration: none;
        }
        .sidebar-subnav a:hover { color: #fff; }
        .sidebar-subnav a.active { color: var(--sidebar-active); font-weight: 700; }

        .sidebar-footer {
            padding: 12px 10px 16px;
            border-top: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }

        .page-wrapper {
            flex: 1;
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--card);
            border-bottom: 1px solid var(--border);
            height: 62px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .menu-toggle { display: none; width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border); background: #fff; margin-right: 10px; cursor: pointer; }
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .topbar-logo { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg,#10b981,#34d399); color: #083344; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 12px; font-family: 'Sora', sans-serif; }
        .topbar-title { font-weight: 800; font-size: 14px; font-family: 'Sora', sans-serif; }
        .topbar-sub { font-size: 11px; color: var(--text-muted); margin-top: -2px; }
        .topbar-spacer { flex: 1; }
        .topbar-right { font-size: 12.5px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }

        .content { padding: 18px 20px 48px; }
        .page-heading h2 { font-family: 'Sora', sans-serif; font-size: 18px; margin-bottom: 4px; }
        .page-heading p { font-size: 12.5px; color: var(--text-muted); }
        .card { background: var(--card); border-radius: 14px; border: 1px solid var(--border); box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06); margin-top: 16px; }
        .card-body-pad { padding: 18px; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: #fff; cursor: pointer; text-decoration: none; color: var(--text); font-weight: 600; font-size: 13px; }
        .btn-primary { background: rgba(16,185,129,0.12); border-color: rgba(16,185,129,0.25); color: #047857; }
        .btn-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 99;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.open { display: block; }

        @media (max-width: 900px) { .topbar-right { display: none; } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .page-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; align-items: center; justify-content: center; }
        }
    </style>
</head>
<body>
@php
    $hariIndo = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
    ];
    $bulanIndo = [
        'January'   => 'Januari',
        'February'  => 'Februari',
        'March'     => 'Maret',
        'April'     => 'April',
        'May'       => 'Mei',
        'June'      => 'Juni',
        'July'      => 'Juli',
        'August'    => 'Agustus',
        'September' => 'September',
        'October'   => 'Oktober',
        'November'  => 'November',
        'December'  => 'Desember',
    ];
    $hariEn     = now()->format('l');
    $bulanEn    = now()->format('F');
    $tanggalStr = ($hariIndo[$hariEn] ?? $hariEn) . ', ' . now()->format('d') . ' ' . ($bulanIndo[$bulanEn] ?? $bulanEn) . ' ' . now()->format('Y');
    $jamStr     = now()->format('H:i:s');
@endphp

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="layout">
    @include('partials.sidebar')

    <div class="page-wrapper">
        <header class="topbar">
            <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Menu">☰</button>
            <div class="topbar-left">
                <div class="topbar-logo">AF</div>
                <div>
                    <div class="topbar-title">SIKEU</div>
                    <div class="topbar-sub">Sistem Informasi Keuangan</div>
                </div>
            </div>
            <div class="topbar-spacer"></div>
            <div class="topbar-right">
                <span class="topbar-yayasan">Yayasan Solo Amal Fatimah</span>
                <span>&nbsp;–&nbsp;</span>
                <span>{{ $tanggalStr }}</span>
                <span>&nbsp;–&nbsp;</span>
                <span id="jam">{{ $jamStr }}</span>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </div>
</div>

<script>
    function closeMenus(exceptIds) {
        var keep = new Set(exceptIds || []);
        var pairs = [
            ['mdToggle', 'mdSubnav'],
            ['keuToggle', 'keuSubnav'],
            ['manualInputToggle', 'manualInputSubnav'],
            ['rekapDataToggle', 'rekapDataSubnav'],
            ['tagihanSiswaToggle', 'tagihanSiswaSubnav'],
            ['penerimaanSiswaToggle', 'penerimaanSiswaSubnav'],
            ['saldoToggle', 'saldoSubnav'],
        ];
        pairs.forEach(function (p) {
            var t = document.getElementById(p[0]);
            var s = document.getElementById(p[1]);
            if (!t || !s) return;
            if (keep.has(p[0]) || keep.has(p[1])) return;
            t.classList.remove('open');
            s.classList.remove('open');
        });
    }
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('open');
    }
    function toggleMasterData() {
        var item = document.getElementById('mdToggle');
        var sub  = document.getElementById('mdSubnav');
        if (!item || !sub) return;
        closeMenus(['mdToggle', 'mdSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) item.classList.add('open'); else item.classList.remove('open');
    }
    function toggleKeuangan() {
        var item = document.getElementById('keuToggle');
        var sub  = document.getElementById('keuSubnav');
        if (!item || !sub) return;
        closeMenus(['keuToggle', 'keuSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) item.classList.add('open'); else item.classList.remove('open');
    }
    function toggleTagihanSiswa() {
        var item = document.getElementById('tagihanSiswaToggle');
        var sub  = document.getElementById('tagihanSiswaSubnav');
        if (!item || !sub) return;
        closeMenus(['keuToggle','keuSubnav','tagihanSiswaToggle','tagihanSiswaSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) item.classList.add('open'); else item.classList.remove('open');
    }
    function togglePenerimaanSiswa() {
        var item = document.getElementById('penerimaanSiswaToggle');
        var sub  = document.getElementById('penerimaanSiswaSubnav');
        if (!item || !sub) return;
        closeMenus(['keuToggle','keuSubnav','penerimaanSiswaToggle','penerimaanSiswaSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) item.classList.add('open'); else item.classList.remove('open');
    }
    function toggleSaldo() {
        var item = document.getElementById('saldoToggle');
        var sub  = document.getElementById('saldoSubnav');
        if (!item || !sub) return;
        closeMenus(['keuToggle','keuSubnav','saldoToggle','saldoSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) item.classList.add('open'); else item.classList.remove('open');
    }
    function toggleManualInput() {
        var item = document.getElementById('manualInputToggle');
        var sub  = document.getElementById('manualInputSubnav');
        if (!item || !sub) return;
        closeMenus(['manualInputToggle','manualInputSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) item.classList.add('open'); else item.classList.remove('open');
    }
    function toggleRekapData() {
        var item = document.getElementById('rekapDataToggle');
        var sub  = document.getElementById('rekapDataSubnav');
        if (!item || !sub) return;
        closeMenus(['rekapDataToggle','rekapDataSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) item.classList.add('open'); else item.classList.remove('open');
    }
    setInterval(function() {
        var el = document.getElementById('jam');
        if (!el) return;
        var now = new Date();
        el.textContent = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
    }, 1000);
</script>
</body>
</html>

