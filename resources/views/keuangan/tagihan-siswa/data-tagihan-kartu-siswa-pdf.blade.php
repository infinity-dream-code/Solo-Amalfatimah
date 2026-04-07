<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; margin: 0; padding: 14px; }
        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .head-top { border-bottom: 3px solid #111; padding-bottom: 6px; margin-bottom: 8px; }
        .head-row { width: 100%; border-collapse: collapse; }
        .head-row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 80px; }
        .logo {
            width: 64px; height: 64px; object-fit: contain; display: block;
        }
        .text-cell { text-align: center; }
        .yayasan { font-size: 23px; font-weight: 700; margin: 0; }
        .sub { font-size: 11px; margin: 2px 0 0; }
        .title { font-size: 16px; font-weight: 700; text-align: center; margin: 8px 0 6px; }
        .meta-wrap { width: 100%; margin-bottom: 8px; border-collapse: collapse; }
        .meta-wrap td { padding: 2px 4px; font-size: 11px; }
        .meta-lbl { width: 13%; font-weight: 700; }
        .meta-val { width: 37%; }
        .meta-lbl-r { width: 13%; font-weight: 700; }
        .meta-val-r { width: 37%; }
        table.tbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tbl th, .tbl td { border: 1px solid #444; padding: 5px 6px; vertical-align: top; }
        .tbl th { background: #f1f5f9; font-size: 10px; text-align: center; font-weight: 700; }
        .num { text-align: right; }
        .ctr { text-align: center; }
        .ft { font-size: 9px; color: #555; margin-top: 6px; text-align: right; }
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

        // Fallback: scan recursive di public untuk file gambar yang namanya mengandung logo/amal/fatimah.
        if ($logoDataUri === null && is_dir(public_path())) {
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(public_path(), FilesystemIterator::SKIP_DOTS)
                );
                foreach ($it as $f) {
                    if (!$f instanceof SplFileInfo || !$f->isFile()) {
                        continue;
                    }
                    $ext = strtolower((string) $f->getExtension());
                    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                        continue;
                    }
                    $name = strtolower((string) $f->getFilename());
                    if (
                        !str_contains($name, 'logo')
                        && !str_contains($name, 'amal')
                        && !str_contains($name, 'fatimah')
                        && !str_contains($name, 'fataimah')
                    ) {
                        continue;
                    }
                    $mime = match ($ext) {
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        'webp' => 'image/webp',
                        default => 'application/octet-stream',
                    };
                    $raw = @file_get_contents($f->getPathname());
                    if ($raw !== false) {
                        $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($raw);
                        break;
                    }
                }
            } catch (Throwable $e) {
                // abaikan jika scanning gagal
            }
        }
    @endphp
    @foreach ($cards as $c)
        <div class="sheet">
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

            <div class="title">DATA TAGIHAN SISWA</div>

            <table class="meta-wrap">
                <tr>
                    <td class="meta-lbl">NIS</td><td class="meta-val">: {{ $c['nis'] !== '' ? $c['nis'] : '-' }}</td>
                    <td class="meta-lbl-r">Unit</td><td class="meta-val-r">: {{ $c['unit'] !== '' ? $c['unit'] : '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Nama Siswa</td><td class="meta-val">: {{ $c['nama'] !== '' ? $c['nama'] : '-' }}</td>
                    <td class="meta-lbl-r">Kelas</td><td class="meta-val-r">: {{ $c['kelas'] !== '' ? $c['kelas'] : '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl"></td><td class="meta-val"></td>
                    <td class="meta-lbl-r">Kelompok</td><td class="meta-val-r">: {{ $c['kelompok'] !== '' ? $c['kelompok'] : '-' }}</td>
                </tr>
            </table>

            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:5%">#</th>
                        <th style="width:17%">Tahun Akademik</th>
                        <th>Nama Tagihan</th>
                        <th style="width:20%">Jumlah</th>
                        <th style="width:16%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($c['items'] ?? []) as $idx => $it)
                        <tr>
                            <td class="ctr">{{ $idx + 1 }}</td>
                            <td class="ctr">{{ $it['tahun_aka'] !== '' ? $it['tahun_aka'] : '-' }}</td>
                            <td>{{ $it['nama_tagihan'] !== '' ? $it['nama_tagihan'] : '-' }}</td>
                            <td class="num">Rp {{ number_format((int) ($it['tagihan'] ?? 0), 0, ',', '.') }}</td>
                            <td class="ctr">{{ strtoupper((string) ($it['status'] ?? '-')) }}</td>
                        </tr>
                    @endforeach
                    @php
                        $totalTagihan = 0;
                        foreach (($c['items'] ?? []) as $itx) {
                            $totalTagihan += (int) ($itx['tagihan'] ?? 0);
                        }
                    @endphp
                    <tr>
                        <td colspan="3"><strong>Total Tagihan</strong></td>
                        <td class="num"><strong>Rp {{ number_format($totalTagihan, 0, ',', '.') }}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <div class="ft">Dicetak {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
        </div>
    @endforeach
</body>
</html>
