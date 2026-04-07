<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Export Tagihan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; margin: 0; padding: 10px; }
        .tools { margin-bottom: 8px; }
        .tools button { height: 30px; border: 1px solid #0f766e; background: #14b8a6; color: #fff; border-radius: 6px; padding: 0 12px; font-weight: 700; cursor: pointer; }
        .sheet { width: 760px; margin: 0 auto; }
        .head { border-bottom: 3px solid #111; padding-bottom: 6px; margin-bottom: 6px; }
        .row { width: 100%; border-collapse: collapse; }
        .row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 72px; }
        .logo { width: 56px; height: 56px; object-fit: contain; display: block; }
        .mid { text-align: center; }
        .nm { font-size: 33px; font-weight: 700; margin: 0; line-height: 1; }
        .addr { font-size: 10px; margin: 2px 0 0; }
        .title { text-align: center; font-size: 22px; font-weight: 700; margin: 5px 0 6px; }
        .meta-wrap { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .meta-wrap td { border: 0; padding: 1px 3px; font-size: 10px; }
        .lbl { width: 14%; font-weight: 700; }
        .val { width: 36%; }
        .lbl-r { width: 14%; font-weight: 700; }
        .val-r { width: 36%; }
        .tbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tbl th, .tbl td { border: 1px solid #444; padding: 4px 5px; }
        .tbl th { background: #fff; font-size: 10px; text-align: center; font-weight: 700; }
        .ctr { text-align: center; }
        .num { text-align: right; }
        .status-belum { color: #b91c1c; font-weight: 700; }
        .total-row td { font-weight: 700; }
        .foot { margin-top: 6px; text-align: right; font-size: 10px; font-weight: 700; }
        .err { border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; padding: 8px; border-radius: 8px; margin-bottom: 8px; font-weight: 700; }
        @media print { .tools { display: none; } body { padding: 4px; } .sheet { width: auto; } }
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
                $mime = match ($ext) { 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', default => 'application/octet-stream' };
                $raw = @file_get_contents($lp);
                if ($raw !== false) { $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($raw); break; }
            }
        }
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
                    $mime = match ($ext) { 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', default => 'application/octet-stream' };
                    $raw = @file_get_contents($f->getPathname());
                    if ($raw !== false) { $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($raw); break; }
                }
            } catch (Throwable $e) {
                // abaikan jika scanning gagal
            }
        }
    @endphp

    @php
        $first = (is_array($rows) && count($rows) > 0 && is_array($rows[0] ?? null)) ? $rows[0] : [];
        $totalTagihan = 0;
        foreach ($rows as $row) {
            if (is_array($row)) {
                $totalTagihan += (int) ($row['tagihan'] ?? 0);
            }
        }
    @endphp

    <div class="tools"><button type="button" onclick="window.print()">Print</button></div>
    <div class="sheet">
        <div class="head">
            <table class="row">
                <tr>
                    <td class="logo-cell">@if ($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo">@endif</td>
                    <td class="mid">
                        <p class="nm">Solo Amal Fatimah</p>
                        <p class="addr">Jl. Slamet Riyadi No. 443 Pajang, Laweyan, Kota Surakarta</p>
                    </td>
                    <td class="logo-cell"></td>
                </tr>
            </table>
        </div>

        <div class="title">Tagihan Siswa</div>

        <table class="meta-wrap">
            <tr>
                <td class="lbl">NIS</td><td class="val">: {{ trim((string) ($first['nis'] ?? '')) !== '' ? $first['nis'] : '-' }}</td>
                <td class="lbl-r">Tahun Aka</td><td class="val-r">: {{ trim((string) ($first['tahun_aka'] ?? '')) !== '' ? $first['tahun_aka'] : '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Nama Siswa</td><td class="val">: {{ trim((string) ($first['nama'] ?? '')) !== '' ? $first['nama'] : '-' }}</td>
                <td class="lbl-r">Dicetak</td><td class="val-r">: {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
            </tr>
        </table>

        @if (($errorMessage ?? '') !== '')
            <div class="err">{{ $errorMessage }}</div>
        @endif

        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:20%">Tahun Akademik</th>
                    <th>Nama Tagihan</th>
                    <th style="width:23%">Jumlah</th>
                    <th style="width:18%">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $idx => $r)
                    @php $st = strtoupper((string) ($r['status'] ?? '-')); @endphp
                    <tr>
                        <td class="ctr">{{ $idx + 1 }}</td>
                        <td class="ctr">{{ trim((string) ($r['tahun_aka'] ?? '')) !== '' ? $r['tahun_aka'] : '-' }}</td>
                        <td>{{ trim((string) ($r['nama_tagihan'] ?? '')) !== '' ? $r['nama_tagihan'] : '-' }}</td>
                        <td class="num">Rp {{ number_format((int) ($r['tagihan'] ?? 0), 0, ',', '.') }}</td>
                        <td class="ctr {{ $st === 'BELUM LUNAS' ? 'status-belum' : '' }}">{{ $st }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="ctr" style="padding: 14px; color:#6b7280;">Tidak ada data</td></tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="3">Total Tagihan</td>
                    <td class="num">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="foot">Surakarta, {{ now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</div>
    </div>
</body>
</html>
