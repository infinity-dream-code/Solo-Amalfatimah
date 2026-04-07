<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Export Tagihan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; margin: 0; padding: 10px; }
        .sheet { width: 100%; }
        .head { border-bottom: 3px solid #111; padding-bottom: 6px; margin-bottom: 6px; }
        .row { width: 100%; border-collapse: collapse; }
        .row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 72px; }
        .logo { width: 56px; height: 56px; object-fit: contain; display: block; }
        .mid { text-align: center; }
        .nm { font-size: 28px; font-weight: 700; margin: 0; line-height: 1; }
        .addr { font-size: 10px; margin: 2px 0 0; }
        .title { text-align: center; font-size: 18px; font-weight: 700; margin: 6px 0; }
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
        .err { border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; padding: 8px; font-weight: 700; margin-bottom: 8px; }
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
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(public_path(), FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if (!$f instanceof SplFileInfo || !$f->isFile()) { continue; }
                    $ext = strtolower((string) $f->getExtension());
                    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) { continue; }
                    $name = strtolower((string) $f->getFilename());
                    if (!str_contains($name, 'logo') && !str_contains($name, 'amal') && !str_contains($name, 'fatimah') && !str_contains($name, 'fataimah')) { continue; }
                    $mime = match ($ext) { 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', default => 'application/octet-stream' };
                    $raw = @file_get_contents($f->getPathname());
                    if ($raw !== false) { $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($raw); break; }
                }
            } catch (Throwable $e) {
                // ignore
            }
        }
        $first = (is_array($rows) && count($rows) > 0 && is_array($rows[0] ?? null)) ? $rows[0] : [];
        $cleanMeta = static function ($v): string {
            $s = trim((string) $v);
            $s = ltrim($s, ": \t\n\r\0\x0B");
            return trim($s);
        };
        $nisDigits = preg_replace('/\D+/', '', (string) ($first['nis'] ?? ''));
        $noVa = trim((string) ($first['no_va'] ?? ''));
        if ($noVa === '') {
            $noVa = '7510050' . ($nisDigits !== '' ? $nisDigits : '0');
        }
        $angkatan = $cleanMeta($first['angkatan'] ?? '');
        if ($angkatan === '') {
            $angkatan = trim((string) ($filters['thn_angkatan'] ?? ''));
        }
        if ($angkatan === '') {
            $tahunAka = trim((string) ($first['tahun_aka'] ?? ''));
            if (preg_match('/^\d{4}/', $tahunAka, $m) === 1) {
                $angkatan = $m[0];
            }
        }
        if ($angkatan === '') {
            $angkatan = '-';
        }
        $todayId = \Carbon\Carbon::now('Asia/Jakarta')->locale('id');
        $totalTagihan = 0;
        foreach ($rows as $row) {
            if (is_array($row)) { $totalTagihan += (int) ($row['tagihan'] ?? 0); }
        }
    @endphp

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
                <td class="lbl">NIS</td><td class="val">: {{ $cleanMeta($first['nis'] ?? '') !== '' ? $cleanMeta($first['nis'] ?? '') : '-' }}</td>
                <td class="lbl-r">Kelas</td><td class="val-r">: {{ $cleanMeta($first['kelas'] ?? '') !== '' ? $cleanMeta($first['kelas'] ?? '') : '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">NO VA</td><td class="val">: {{ $noVa }}</td>
                <td class="lbl-r">Unit</td><td class="val-r">: {{ $cleanMeta($first['unit'] ?? '') !== '' ? $cleanMeta($first['unit'] ?? '') : '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Nama Siswa</td><td class="val">: {{ $cleanMeta($first['nama'] ?? '') !== '' ? $cleanMeta($first['nama'] ?? '') : '-' }}</td>
                <td class="lbl-r">Angkatan</td><td class="val-r">: {{ $angkatan }}</td>
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

        <div class="foot">Surakarta, {{ $todayId->translatedFormat('l, d F Y') }}</div>
    </div>
</body>
</html>
