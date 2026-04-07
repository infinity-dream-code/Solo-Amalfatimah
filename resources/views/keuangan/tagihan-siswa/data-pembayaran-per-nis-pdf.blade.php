<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Pembayaran Siswa</title>
    <style>
        @page { margin: 18px 22px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .head { text-align: center; margin-bottom: 8px; }
        .logo { height: 52px; margin-bottom: 6px; }
        .line { border-top: 2px solid #111827; margin: 6px 0 8px; }
        .school { font-size: 24px; font-weight: 700; margin: 0; line-height: 1; }
        .addr { font-size: 10px; margin: 2px 0 0; }
        .ttl { font-size: 22px; font-weight: 700; margin: 0; }
        .sub { margin: 3px 0 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111827; padding: 3px 4px; vertical-align: middle; }
        thead th { background: #f3f4f6; font-weight: 700; text-align: center; }
        td.txt { text-align: left; }
        td.ctr { text-align: center; }
        td.num { text-align: right; white-space: nowrap; }
        .empty { text-align: center; padding: 14px; color: #6b7280; }
    </style>
</head>
<body>
@php
    $logoData = null;
    $logoCandidates = ['logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp', 'logoyayasan.png', 'logo-solo-amal-fatimah.png', 'Amal Fataimah Surakarta.jpg.jpeg'];
    $publicDir = public_path();
    if (is_dir($publicDir)) {
        try {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicDir, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if (!$f->isFile()) continue;
                $name = strtolower($f->getFilename());
                $hit = in_array($name, $logoCandidates, true) || str_contains($name, 'logo') || str_contains($name, 'amal') || str_contains($name, 'fatimah') || str_contains($name, 'fataimah');
                if (!$hit) continue;
                $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
                $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : ($ext === 'webp' ? 'image/webp' : 'image/png');
                $raw = @file_get_contents($f->getPathname());
                if ($raw !== false && $raw !== '') { $logoData = 'data:' . $mime . ';base64,' . base64_encode($raw); break; }
            }
        } catch (Throwable $e) {}
    }
@endphp

<div class="head">
    @if($logoData)<img class="logo" src="{{ $logoData }}" alt="logo">@endif
    <p class="school">Solo Amal Fatimah</p>
    <p class="addr">Jl. Slamet Riyadi No. 443 Pajang, Laweyan, Kota Surakarta</p>
    <div class="line"></div>
    <p class="ttl">DATA PEMBAYARAN SISWA</p>
    <p class="sub">{{ $dateRange }}</p>
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2" style="width:3%">No</th>
            <th rowspan="2" style="width:9%">Tahun Masuk</th>
            <th rowspan="2" style="width:6%">Unit</th>
            <th rowspan="2" style="width:6%">Kelas</th>
            <th rowspan="2" style="width:7%">Kelompok</th>
            <th rowspan="2" style="width:7%">NIS</th>
            <th rowspan="2" style="width:12%">Nama</th>
            @foreach(($billacGroups ?? []) as $g)
                <th colspan="{{ count($g['akuns']) + 1 }}">{{ $g['billac'] }}</th>
            @endforeach
            <th rowspan="2" style="width:8%">Total</th>
        </tr>
        <tr>
            @foreach(($billacGroups ?? []) as $g)
                @foreach(($g['akuns'] ?? []) as $akun)
                    <th>{{ $akun }}</th>
                @endforeach
                <th>Total</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @if(!empty($errorMessage ?? ''))
            <tr><td colspan="999" class="empty">{{ $errorMessage }}</td></tr>
        @elseif(empty($rows ?? []))
            <tr><td colspan="999" class="empty">Tidak ada data</td></tr>
        @else
            @php $grand = 0; @endphp
            @foreach(($rows ?? []) as $r)
                @php $rowTotal = 0; @endphp
                <tr>
                    <td class="ctr">{{ $r['no'] ?? '' }}</td>
                    <td class="ctr">{{ $r['tahun_masuk'] !== '' ? $r['tahun_masuk'] : '-' }}</td>
                    <td class="ctr">{{ $r['unit'] !== '' ? $r['unit'] : '-' }}</td>
                    <td class="ctr">{{ $r['kelas'] !== '' ? $r['kelas'] : '-' }}</td>
                    <td class="ctr">{{ $r['kelompok'] !== '' ? $r['kelompok'] : '-' }}</td>
                    <td class="ctr">{{ $r['nis'] !== '' ? $r['nis'] : '-' }}</td>
                    <td class="txt">{{ $r['nama'] !== '' ? $r['nama'] : '-' }}</td>
                    @foreach(($billacGroups ?? []) as $g)
                        @php $billacTotal = 0; @endphp
                        @foreach(($g['akuns'] ?? []) as $akun)
                            @php $nom = (int) ($r['values'][$g['billac']][$akun] ?? 0); $billacTotal += $nom; @endphp
                            <td class="num">{{ $nom > 0 ? number_format($nom, 0, ',', '.') : '-' }}</td>
                        @endforeach
                        @php $rowTotal += $billacTotal; @endphp
                        <td class="num">{{ $billacTotal > 0 ? number_format($billacTotal, 0, ',', '.') : '-' }}</td>
                    @endforeach
                    <td class="num">{{ number_format($rowTotal, 0, ',', '.') }}</td>
                </tr>
                @php $grand += $rowTotal; @endphp
            @endforeach
            <tr>
                <td colspan="{{ 7 + array_reduce(($billacGroups ?? []), fn($c, $g) => $c + count($g['akuns']) + 1, 0) }}" class="ctr"><strong>Total</strong></td>
                <td class="num"><strong>{{ number_format($grand, 0, ',', '.') }}</strong></td>
            </tr>
        @endif
    </tbody>
</table>
</body>
</html>
