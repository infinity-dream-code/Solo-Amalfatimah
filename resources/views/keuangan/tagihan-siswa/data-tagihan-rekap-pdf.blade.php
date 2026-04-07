<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; margin: 0; padding: 12px; }
        .head-top { border-bottom: 3px solid #111; padding-bottom: 6px; margin-bottom: 8px; }
        .head-row { width: 100%; border-collapse: collapse; }
        .head-row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 70px; }
        .logo { width: 56px; height: 56px; object-fit: contain; display: block; }
        .text-cell { text-align: center; }
        .yayasan { font-size: 22px; font-weight: 700; margin: 0; }
        .sub { font-size: 10px; margin: 2px 0 0; }
        .title { font-size: 14px; font-weight: 700; margin: 0 0 4px; text-align: center; }
        .meta { font-size: 8px; color: #4b5563; margin: 0 0 10px; text-align: center; }
        .sec { margin: 0 0 10px; page-break-inside: avoid; }
        .sec-title { font-size: 11px; font-weight: 700; margin: 0 0 5px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #7b7b7b; padding: 5px 6px; }
        th { background: #f9fafb; font-size: 9px; text-align: center; }
        .num { text-align: right; }
        .ctr { text-align: center; }
    </style>
</head>
<body>
    @php
        $logoDataUri = null;
        $logoCandidates = [
            public_path('Amal Fataimah Surakarta.jpg.jpeg'),
            public_path('logo.png'),
            public_path('logo.jpg'),
            public_path('logo.jpeg'),
            public_path('images/logo.png'),
        ];
        foreach ($logoCandidates as $lp) {
            if (is_string($lp) && $lp !== '' && file_exists($lp)) {
                $ext = strtolower((string) pathinfo($lp, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    default => 'application/octet-stream',
                };
                $raw = @file_get_contents($lp);
                if ($raw !== false) {
                    $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($raw);
                    break;
                }
            }
        }
    @endphp

    <div class="head-top">
        <table class="head-row">
            <tr>
                <td class="logo-cell">
                    @if ($logoDataUri)
                        <img class="logo" src="{{ $logoDataUri }}" alt="Logo Solo Amal Fatimah">
                    @endif
                </td>
                <td class="text-cell">
                    <p class="yayasan">Solo Amal Fatimah</p>
                    <p class="sub">Jl. Slamet Riyadi No. 443 Pajang, Laweyan, Kota Surakarta</p>
                </td>
                <td class="logo-cell"></td>
            </tr>
        </table>
    </div>

    <h1 class="title">Rekap Tagihan</h1>
    <p class="meta">
        Dicetak {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · Tahun Akademik: {{ $filters['thn_akademik'] ?? '-' }} · Total data: {{ count($rows ?? []) }}
    </p>

    <table>
        <thead>
            <tr>
                <th style="width:6%">#</th>
                <th style="width:14%">NIS</th>
                <th>Nama</th>
                <th style="width:27%">Nama Tagihan</th>
                <th style="width:15%">Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($rows ?? []) as $i => $r)
                <tr>
                    <td class="ctr">{{ $i }}</td>
                    <td class="ctr">{{ $r['nis'] !== '' ? $r['nis'] : '-' }}</td>
                    <td>{{ $r['nama'] !== '' ? $r['nama'] : '-' }}</td>
                    <td>{{ $r['nama_tagihan'] !== '' ? $r['nama_tagihan'] : '-' }}</td>
                    <td class="num">Rp {{ number_format((int) ($r['tagihan'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="ctr" style="padding:16px;color:#6b7280;">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
