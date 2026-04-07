<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Master Data' }} - Solo Amal Fatimah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    @php
        // reuse style from dashboard via include
    @endphp
    @include('dashboard.index', ['__only_styles' => true])
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
            <div class="page-heading">
                <h2>{{ $title ?? 'Master Data' }}</h2>
                <p>Selamat datang di menu {{ $title ?? 'Master Data' }}.</p>
            </div>

            <div class="main-grid">
                <div class="card">
                    <div class="card-body-pad">
                        <p>Halaman ini masih kosong. Silakan isi logika untuk menu <strong>{{ $title ?? 'Master Data' }}</strong> nanti.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('open');
    }
</script>
</body>
</html>

