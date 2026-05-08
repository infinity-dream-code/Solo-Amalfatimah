<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Solo Amal Fatimah</title>
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
        .sidebar-item.has-children.open .chevron {
            transform: rotate(90deg);
        }

        .sidebar-subnav {
            padding-left: 34px;
            max-height: 0;
            overflow: hidden;
            transition: max-height .2s ease;
        }
        .sidebar-subnav.open {
            max-height: 320px;
        }
        .sidebar-subnav a {
            display: block;
            padding: 6px 0;
            font-size: 12.5px;
            color: var(--sidebar-text);
            text-decoration: none;
        }
        .sidebar-subnav a:hover {
            color: #fff;
        }
        .sidebar-subnav a.active {
            color: var(--sidebar-active);
            font-weight: 700;
        }

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

        .menu-toggle {
            display: none;
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: var(--bg);
            border: 1px solid var(--border);
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 17px;
            color: var(--text);
            flex-shrink: 0;
            margin-right: 10px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .topbar-logo {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: linear-gradient(135deg,#10b981,#059669);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            font-family: 'Sora', sans-serif;
            box-shadow: 0 3px 8px rgba(16,185,129,0.3);
            flex-shrink: 0;
        }
        .topbar-title { font-weight: 700; font-size: 16px; color: var(--text); font-family: 'Sora', sans-serif; line-height: 1.2; }
        .topbar-sub { font-size: 11px; color: var(--text-muted); }

        .topbar-spacer { flex: 1; }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .topbar-right .topbar-yayasan {
            font-weight: 600;
            color: var(--text);
        }

        .content { padding: 26px 24px 56px; flex: 1; }

        .page-heading { margin-bottom: 22px; }
        .page-heading h2 { font-size: 19px; font-weight: 700; font-family: 'Sora', sans-serif; color: var(--text); }
        .page-heading p { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

        .btn-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 26px; }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 15px;
            border-radius: 9px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
            white-space: nowrap;
            transition: all .18s ease;
        }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.11); }
        .btn-action:active { transform: none; }
        .btn-action.purple { background: #f3e8ff; color: #6d28d9; }
        .btn-action.green  { background: #d1fae5; color: #047857; }
        .btn-action.yellow { background: #fef3c7; color: #92400e; }
        .btn-action.blue   { background: #dbeafe; color: #1d4ed8; }
        .btn-action.teal   { background: #ccfbf1; color: #0f766e; }
        .btn-action.red    { background: #ffe4e6; color: #be123c; }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 22px;
            align-items: start;
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header-title { font-size: 14px; font-weight: 700; color: var(--text); font-family: 'Sora', sans-serif; }
        .card-badge { font-size: 11px; font-weight: 600; background: #d1fae5; color: #047857; padding: 3px 9px; border-radius: 20px; }
        .card-body { padding: 0 20px; }
        .card-body-pad { padding: 20px; }

        .pay-list { max-height: 460px; overflow-y: auto; }
        .pay-list::-webkit-scrollbar { width: 3px; }
        .pay-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        .pay-item {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }
        .pay-item:last-child { border-bottom: none; }

        .pay-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg,#d1fae5,#a7f3d0);
            color: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
            border: 2px solid #6ee7b7;
        }
        .pay-icon svg { width: 13px; height: 13px; }

        .pay-body { flex: 1; min-width: 0; }

        .pay-top-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 2px;
        }
        .pay-bulan { font-weight: 700; font-size: 13px; color: var(--text); font-family: 'Sora', sans-serif; }
        .pay-tgl { font-size: 12px; color: var(--text-muted); white-space: nowrap; flex-shrink: 0; }
        .pay-nominal { font-size: 13px; color: var(--green); font-weight: 700; margin-bottom: 6px; }

        .pay-info {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.6;
            border-left: 2px solid var(--border);
            padding-left: 8px;
        }
        .pay-info span { display: block; }

        .tagihan-body { display: flex; flex-direction: column; gap: 14px; }
        .stat-box { background: var(--bg); border-radius: 11px; padding: 14px 16px; border: 1px solid var(--border); }
        .stat-label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .stat-value { font-size: 22px; font-weight: 700; color: var(--text); margin-top: 4px; font-family: 'Sora', sans-serif; }
        .stat-value.green { color: var(--green); }
        .stat-value.red   { color: var(--red); }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .progress-labels { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; margin-bottom: 7px; }
        .progress-labels .lbl-green { color: var(--green); }
        .progress-labels .lbl-red   { color: var(--red); }
        .progress-track { height: 9px; background: #fecdd3; border-radius: 5px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg,#059669,#10b981); border-radius: 5px; transition: width .8s ease; }

        .empty-state {
            padding: 28px 16px;
            text-align: center;
            color: var(--text-muted);
            font-size: 12.5px;
            border: 1px dashed var(--border);
            border-radius: 10px;
            background: #f8fafc;
            line-height: 1.8;
            margin: 16px 0;
        }
        .empty-state code { font-size: 11px; background: var(--border); padding: 1px 6px; border-radius: 4px; }

        .tadb-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
        .tadb-title { font-size: 16px; font-weight: 700; color: var(--text); }
        .tadb-subtitle { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .tadb-detail-btn { padding: 6px 14px; border-radius: 8px; border: 1px solid var(--border); background: #fff; color: var(--text-muted); font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .15s, color .15s; }
        .tadb-detail-btn:hover { background: var(--bg); color: var(--text); }
        .tadb-chart { display: flex; align-items: flex-end; gap: 12px; height: 200px; padding: 20px 0 0; }
        .tadb-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; min-width: 0; height: 200px; }
        .tadb-bar-track { width: 100%; max-width: 48px; height: 150px; display: flex; align-items: flex-end; justify-content: center; }
        .tadb-bar { width: 100%; max-width: 48px; background: linear-gradient(180deg,#059669,#10b981); border-radius: 6px 6px 0 0; min-height: 6px; transition: height .3s ease; }
        .tadb-bar-label { font-size: 11px; color: var(--text-muted); font-weight: 500; white-space: nowrap; }
        .tadb-bar-total { font-size: 13px; font-weight: 700; color: var(--green); }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 99;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.open { display: block; }

        @media (max-width: 1080px) { .main-grid { grid-template-columns: 1fr; } }
        @media (max-width: 900px)  { .topbar-right { display: none; } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .page-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .content { padding: 18px 16px 48px; }
            .btn-action { padding: 8px 12px; font-size: 12px; }
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
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('Amal Fataimah Surakarta.jpg.jpeg') }}" alt="Logo">
            <div>
                <div class="sidebar-brand-title">Solo Amal Fatimah</div>
                <div class="sidebar-brand-sub">Sistem Keuangan</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Beranda
            </a>
            <button type="button" class="sidebar-item has-children {{ request()->routeIs('master.*') ? 'open' : '' }}" id="mdToggle" onclick="toggleMasterData()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                Master Data
                <span class="chevron">›</span>
            </button>
            <div class="sidebar-subnav {{ request()->routeIs('master.*') ? 'open' : '' }}" id="mdSubnav">
                <a href="{{ route('master.kelas') }}" class="{{ request()->routeIs('master.kelas') ? 'active' : '' }}">Master Kelas</a>
                <a href="{{ route('master.sekolah') }}" class="{{ request()->routeIs('master.sekolah') ? 'active' : '' }}">Master Sekolah</a>
                <a href="{{ route('master.tahun_pelajaran') }}" class="{{ request()->routeIs('master.tahun_pelajaran') ? 'active' : '' }}">Tahun Pelajaran</a>
                <a href="{{ route('master.post') }}" class="{{ request()->routeIs('master.post') ? 'active' : '' }}">Master Post</a>
                <a href="{{ route('master.beban_post') }}" class="{{ request()->routeIs('master.beban_post') ? 'active' : '' }}">Beban Post</a>
                <a href="{{ route('master.export_import') }}" class="{{ request()->routeIs('master.export_import') ? 'active' : '' }}">Export Import Data</a>
                <a href="{{ route('master.data_siswa') }}" class="{{ request()->routeIs('master.data_siswa') ? 'active' : '' }}">Data Siswa</a>
                <a href="{{ route('master.pindah_kelas') }}" class="{{ request()->routeIs('master.pindah_kelas') ? 'active' : '' }}">Pindah Kelas</a>
            </div>
            <button type="button" class="sidebar-item has-children {{ request()->routeIs('keu.*') ? 'open active' : '' }}" id="keuToggle" onclick="toggleKeuangan()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Keuangan
                <span class="chevron">›</span>
            </button>
            <div class="sidebar-subnav {{ request()->routeIs('keu.*') ? 'open' : '' }}" id="keuSubnav">
                <button type="button" class="sidebar-item has-children {{ request()->routeIs('keu.tagihan.*') ? 'open' : '' }}" id="tagihanSiswaToggle" onclick="toggleTagihanSiswa()">
                    Tagihan Siswa
                    <span class="chevron">›</span>
                </button>
                <div class="sidebar-subnav {{ request()->routeIs('keu.tagihan.*') ? 'open' : '' }}" id="tagihanSiswaSubnav" style="padding-left:14px;">
                    <a href="{{ route('keu.tagihan.buat') }}" class="{{ request()->routeIs('keu.tagihan.buat') ? 'active' : '' }}">Buat Tagihan</a>
                    <a href="{{ route('keu.tagihan.upload_excel') }}" class="{{ request()->routeIs('keu.tagihan.upload_excel') ? 'active' : '' }}">Upload Tagihan Excel</a>
                    <a href="{{ route('keu.tagihan.upload_pmb') }}" class="{{ request()->routeIs('keu.tagihan.upload_pmb') ? 'active' : '' }}">Upload Tagihan PMB</a>
                    <a href="{{ route('keu.tagihan.data') }}" class="{{ request()->routeIs('keu.tagihan.data') ? 'active' : '' }}">Data Tagihan</a>
                    <a href="{{ route('keu.tagihan.export') }}" class="{{ request()->routeIs('keu.tagihan.export') ? 'active' : '' }}">Export Tagihan</a>
                    <a href="{{ route('keu.tagihan.rekap') }}" class="{{ request()->routeIs('keu.tagihan.rekap') ? 'active' : '' }}">Rekap Tagihan</a>
                </div>
                <a href="{{ route('keu.manual') }}" class="{{ request()->routeIs('keu.manual') ? 'active' : '' }}">Manual Pembayaran</a>
                <a href="{{ route('keu.manual_nis') }}" class="{{ request()->routeIs('keu.manual_nis') ? 'active' : '' }}">Manual Pembayaran NIS</a>
                <a href="{{ route('keu.manual_non_siswa') }}" class="{{ request()->routeIs('keu.manual_non_siswa') ? 'active' : '' }}">Manual Pembayaran No Pendaftaran</a>
                <button type="button" class="sidebar-item has-children {{ request()->routeIs('keu.penerimaan.*') ? 'open' : '' }}" id="penerimaanSiswaToggle" onclick="togglePenerimaanSiswa()">
                    Penerimaan Siswa
                    <span class="chevron">›</span>
                </button>
                <div class="sidebar-subnav {{ request()->routeIs('keu.penerimaan.*') ? 'open' : '' }}" id="penerimaanSiswaSubnav" style="padding-left:14px;">
                    <a href="{{ route('keu.penerimaan.data') }}" class="{{ request()->routeIs('keu.penerimaan.data') ? 'active' : '' }}">Data Penerimaan</a>
                    <a href="{{ route('keu.penerimaan.rekap') }}" class="{{ request()->routeIs(['keu.penerimaan.rekap', 'keu.penerimaan.rekap_rows']) ? 'active' : '' }}">Rekap Penerimaan</a>
                </div>
                <button type="button" class="sidebar-item has-children {{ request()->routeIs('keu.saldo.*') ? 'open' : '' }}" id="saldoToggle" onclick="toggleSaldo()">
                    Saldo
                    <span class="chevron">›</span>
                </button>
                <div class="sidebar-subnav {{ request()->routeIs('keu.saldo.*') ? 'open' : '' }}" id="saldoSubnav" style="padding-left:14px;">
                    <a href="{{ route('keu.saldo.va') }}" class="{{ request()->routeIs(['keu.saldo.va', 'keu.saldo.va.rows', 'keu.saldo.va.detail', 'keu.saldo.va.detail_rows']) ? 'active' : '' }}">Saldo Virtual Account</a>
                    <a href="{{ route('keu.saldo.transaksi') }}" class="{{ request()->routeIs('keu.saldo.transaksi') ? 'active' : '' }}">Data Transaksi</a>
                </div>
                <a href="{{ route('keu.hapus_tagihan') }}" class="{{ request()->routeIs(['keu.hapus_tagihan', 'keu.hapus_tagihan.rows', 'keu.hapus_tagihan.submit']) ? 'active' : '' }}">Hapus Tagihan</a>
                <a href="{{ route('keu.biaya_admin') }}" class="{{ request()->routeIs('keu.biaya_admin') ? 'active' : '' }}">Data Biaya Admin</a>
            </div>
            <button type="button" class="sidebar-item has-children {{ request()->routeIs('manual_input.*') ? 'open active' : '' }}" id="manualInputToggle" onclick="toggleManualInput()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Manual Input
                <span class="chevron">›</span>
            </button>
            <div class="sidebar-subnav {{ request()->routeIs('manual_input.*') ? 'open' : '' }}" id="manualInputSubnav" style="padding-left:14px;">
                <a href="{{ route('manual_input.edit_manual') }}" class="{{ request()->routeIs('manual_input.edit_manual') ? 'active' : '' }}">Edit Manual</a>
            </div>
            <button type="button" class="sidebar-item has-children {{ request()->routeIs('rekap.*') ? 'open active' : '' }}" id="rekapDataToggle" onclick="toggleRekapData()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Rekap Data
                <span class="chevron">›</span>
            </button>
            <div class="sidebar-subnav {{ request()->routeIs('rekap.*') ? 'open' : '' }}" id="rekapDataSubnav" style="padding-left:14px;">
                <a href="{{ route('rekap.cek_pelunasan') }}" class="{{ request()->routeIs('rekap.cek_pelunasan') ? 'active' : '' }}">Cek Pelunasan</a>
            </div>
        </nav>
        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="sidebar-item logout" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;font-family:inherit;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

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
            <div class="page-heading">
                <h2>Beranda</h2>
                <p>Selamat datang kembali — berikut ringkasan aktivitas keuangan terkini.</p>
            </div>

            <div class="btn-row">
                <a href="#" class="btn-action purple">✦ Buat Tagihan</a>
                <a href="#" class="btn-action green">💳 Bayar Manual</a>
                <a href="#" class="btn-action yellow">🏦 Saldo VA</a>
                <a href="#" class="btn-action blue">📋 Data Tagihan</a>
                <a href="#" class="btn-action teal">📥 Data Penerimaan</a>
                <a href="#" class="btn-action red">✕ Batal Bayar</a>
            </div>

            <div class="main-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-header-title">Pembayaran Baru</span>
                        <span class="card-badge">Terbaru</span>
                    </div>
                    <div class="card-body">
                        <div class="pay-list">
                            @forelse($pembayaranBaru as $item)
                                @php
                                    $bulan   = $item['billname'] ?? $item['bulan'] ?? $item['periode'] ?? $item['nama_bulan'] ?? 'BULAN -';
                                    $nominal = (int)($item['billam'] ?? $item['nominal'] ?? $item['jumlah'] ?? $item['total'] ?? 0);
                                    $nama    = $item['nama_cust'] ?? $item['nama'] ?? $item['nama_pembayar'] ?? $item['nama_siswa'] ?? '-';
                                    $inst    = $item['unit'] ?? $item['institusi'] ?? $item['sekolah'] ?? $item['lembaga'] ?? '';
                                    $lulus   = $item['angkatan'] ?? $item['lulusan'] ?? $item['tahun_lulus'] ?? '';
                                    $tipe    = $item['tipe'] ?? $item['jenis'] ?? $item['fullday'] ?? '';
                                    $tahun   = $item['tahun'] ?? $item['ta'] ?? $item['tahun_ajaran'] ?? '';
                                    $sem     = $item['semester'] ?? $item['smt'] ?? '';
                                    $kelas   = $item['desc02'] ?? $item['kelas'] ?? '';
                                    $tgl     = $item['paiddt'] ?? $item['tanggal'] ?? $item['tgl_bayar'] ?? $item['tanggal_bayar'] ?? null;

                                    $tglFormatted = '';
                                    if ($tgl) {
                                        $c   = \Carbon\Carbon::parse($tgl);
                                        $hEn = $c->format('l');
                                        $bEn = $c->format('F');
                                        $tglFormatted = ($hariIndo[$hEn] ?? $hEn) . ', ' . $c->format('d') . ' ' . ($bulanIndo[$bEn] ?? $bEn) . ' ' . $c->format('Y');
                                    }

                                    $baris2Parts = array_filter([$tipe, $tahun, $sem, $kelas]);
                                    $baris2 = implode(' - ', $baris2Parts);
                                @endphp
                                <div class="pay-item">
                                    <div class="pay-icon">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <div class="pay-body">
                                        <div class="pay-top-row">
                                            <span class="pay-bulan">{{ $bulan }}</span>
                                            @if($tglFormatted)<span class="pay-tgl">{{ $tglFormatted }}</span>@endif
                                        </div>
                                        <div class="pay-nominal">Rp. {{ number_format($nominal, 0, ',', '.') }}</div>
                                        <div class="pay-info">
                                            <span>{{ $nama }}</span>
                                            @if($inst || $lulus)
                                                <span>{{ $inst }}{{ $lulus ? ' - Lulusan ' . $lulus : '' }}</span>
                                            @endif
                                            @if($baris2)
                                                <span>{{ $baris2 }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    Belum ada data pembayaran baru.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-header-title">Ringkasan Tagihan</span>
                    </div>
                    <div class="card-body-pad">
                        @php
                            $showTagihan = is_array($tagihan) && (isset($tagihan['total']) || isset($tagihan['dibayar']));
                        @endphp
                        @if($showTagihan)
                            @php
                                $total   = (int)($tagihan['total'] ?? ($tagihan['dibayar'] ?? 0) + ($tagihan['belum_dibayar'] ?? 0));
                                $dibayar = (int)($tagihan['dibayar'] ?? 0);
                                $belum   = (int)($tagihan['belum_dibayar'] ?? 0);
                                $paid    = $total > 0 ? round($dibayar / $total * 100, 1) : 0;
                            @endphp
                            <div class="tagihan-body">
                                <div class="stat-box">
                                    <div class="stat-label">Total Tagihan</div>
                                    <div class="stat-value">{{ number_format($total, 0, ',', '.') }}</div>
                                </div>
                                <div class="stat-grid">
                                    <div class="stat-box">
                                        <div class="stat-label">Tagihan Dibayar</div>
                                        <div class="stat-value green">{{ number_format($dibayar, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-label">Tagihan Belum Dibayar</div>
                                        <div class="stat-value red">{{ number_format($belum, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div>
                                    <div class="progress-labels">
                                        <span class="lbl-green">Dibayar {{ number_format($paid, 2) }}%</span>
                                        <span class="lbl-red">Belum Dibayar {{ number_format(100 - $paid, 2) }}%</span>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width:{{ $paid }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="empty-state">
                                Data tagihan belum tersedia.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 24px;">
                <div class="card-body-pad">
                    <div class="tadb-header">
                        <div>
                            <div class="tadb-title">Tagihan Dibayar</div>
                            <div class="tadb-subtitle">Total tagihan yang dibayar</div>
                        </div>
                        <a href="#" class="tadb-detail-btn">Detail</a>
                    </div>
                    @if(count($tagihanDibayarChart) > 0)
                        @php
                            $chartData = $tagihanDibayarChart;
                            $maxTotal = max(array_column($chartData, 'total')) ?: 1;
                        @endphp
                        <div class="tadb-chart">
                            @foreach($chartData as $row)
                                @php
                                    $pct = $maxTotal > 0 ? ($row['total'] / $maxTotal) * 100 : 0;
                                @endphp
                                <div class="tadb-bar-wrap">
                                    <div class="tadb-bar-track">
                                        <div class="tadb-bar" style="height: {{ max(4, $pct) }}%;"></div>
                                    </div>
                                    <span class="tadb-bar-total">{{ number_format($row['total'] ?? 0, 0, ',', '.') }}</span>
                                    <span class="tadb-bar-label">{{ $row['label'] ?? $row['tanggal'] ?? '-' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">Belum ada data grafik tagihan dibayar.</div>
                    @endif
                </div>
            </div>
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
        pairs.forEach(function(p) {
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
        if (isOpen) {
            item.classList.add('open');
        } else {
            item.classList.remove('open');
        }
    }
    function toggleKeuangan() {
        var item = document.getElementById('keuToggle');
        var sub  = document.getElementById('keuSubnav');
        if (!item || !sub) return;
        closeMenus(['keuToggle', 'keuSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) {
            item.classList.add('open');
        } else {
            item.classList.remove('open');
        }
    }
    function toggleTagihanSiswa() {
        var item = document.getElementById('tagihanSiswaToggle');
        var sub  = document.getElementById('tagihanSiswaSubnav');
        if (!item || !sub) return;
        closeMenus(['keuToggle', 'keuSubnav', 'tagihanSiswaToggle', 'tagihanSiswaSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) {
            item.classList.add('open');
        } else {
            item.classList.remove('open');
        }
    }
    function togglePenerimaanSiswa() {
        var item = document.getElementById('penerimaanSiswaToggle');
        var sub  = document.getElementById('penerimaanSiswaSubnav');
        if (!item || !sub) return;
        closeMenus(['keuToggle', 'keuSubnav', 'penerimaanSiswaToggle', 'penerimaanSiswaSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) {
            item.classList.add('open');
        } else {
            item.classList.remove('open');
        }
    }
    function toggleSaldo() {
        var item = document.getElementById('saldoToggle');
        var sub  = document.getElementById('saldoSubnav');
        if (!item || !sub) return;
        closeMenus(['keuToggle', 'keuSubnav', 'saldoToggle', 'saldoSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) {
            item.classList.add('open');
        } else {
            item.classList.remove('open');
        }
    }
    function toggleManualInput() {
        var item = document.getElementById('manualInputToggle');
        var sub  = document.getElementById('manualInputSubnav');
        if (!item || !sub) return;
        closeMenus(['manualInputToggle', 'manualInputSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) {
            item.classList.add('open');
        } else {
            item.classList.remove('open');
        }
    }
    function toggleRekapData() {
        var item = document.getElementById('rekapDataToggle');
        var sub  = document.getElementById('rekapDataSubnav');
        if (!item || !sub) return;
        closeMenus(['rekapDataToggle', 'rekapDataSubnav']);
        var isOpen = sub.classList.toggle('open');
        if (isOpen) {
            item.classList.add('open');
        } else {
            item.classList.remove('open');
        }
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