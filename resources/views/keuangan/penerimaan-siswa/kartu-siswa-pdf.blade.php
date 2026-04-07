<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 14px; }
        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .head-top { border-bottom: 3px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
        .head-row { width: 100%; border-collapse: collapse; }
        .head-row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 72px; }
        .logo { width: 56px; height: 56px; object-fit: contain; display: block; }
        .text-cell { text-align: center; padding: 0 8px; }
        .yayasan { font-size: 17px; font-weight: 700; margin: 0; }
        .sub { font-size: 8.5px; margin: 4px 0 0; line-height: 1.35; }
        .sub-contact { font-size: 8px; margin: 3px 0 0; color: #222; }
        .title { font-size: 14px; font-weight: 700; text-align: center; margin: 10px 0 8px; letter-spacing: 0.02em; }
        .meta-wrap { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .meta-wrap td { padding: 3px 6px; font-size: 10px; vertical-align: top; }
        .meta-lbl { width: 18%; font-weight: 700; }
        .meta-val { width: 32%; }
        .meta-lbl-r { width: 18%; font-weight: 700; }
        .meta-val-r { width: 32%; }
        table.tbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tbl th, .tbl td { border: 1px solid #333; padding: 5px 4px; vertical-align: middle; }
        .tbl th { background: #f1f5f9; font-size: 8px; text-align: center; font-weight: 700; }
        .num { text-align: right; }
        .ctr { text-align: center; }
        .status-lunas { color: #15803d; font-weight: 700; }
        .sum-label { font-weight: 700; text-align: right; background: #f8fafc; }
        .sum-val { font-weight: 700; text-align: right; background: #f8fafc; }
        .sign-wrap { width: 100%; margin-top: 22px; border-collapse: collapse; }
        .sign-wrap td { border: 0; vertical-align: top; }
        .sign-right { text-align: right; font-size: 9px; line-height: 1.5; padding-right: 4px; }
        .sign-role { margin-top: 36px; font-weight: 700; }
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
        $yayasanName = 'Yayasan Solo Amal Fatimah';
        $yayasanAddr = 'Jl. Slamet Riyadi No. 443, Pajang, Kec. Laweyan, Kota Surakarta';
        $yayasanContact = 'No. Telp: — &nbsp;&nbsp; E-mail: —';
        $tglTtd = 'SURAKARTA, ' . \Illuminate\Support\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y');
        if (!function_exists('kartu_pdf_rp')) {
            function kartu_pdf_rp(int $n): string
            {
                return 'Rp. ' . number_format($n, 0, ',', '.');
            }
        }
    @endphp
    @foreach ($cards as $c)
        @php
            $c = is_array($c) ? $c : [];
            $items = $c['items'] ?? [];
            $total = (int) ($c['total'] ?? 0);
            $totalTerbayar = $total;
            $totalSisa = max(0, $total - $totalTerbayar);
            $angkatan = trim((string) ($c['angkatan'] ?? ''));
            if ($angkatan === '') {
                $angkatan = '-';
            }
        @endphp
        <div class="sheet">
            <div class="head-top">
                <table class="head-row">
                    <tr>
                        <td class="logo-cell">
                            @if ($logoDataUri)
                                <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
                            @endif
                        </td>
                        <td class="text-cell">
                            <p class="yayasan">{{ $yayasanName }}</p>
                            <p class="sub">{{ $yayasanAddr }}</p>
                            <p class="sub-contact">{{ $yayasanContact }}</p>
                        </td>
                        <td class="logo-cell"></td>
                    </tr>
                </table>
            </div>

            <div class="title">KARTU TAGIHAN SISWA</div>

            <table class="meta-wrap">
                <tr>
                    <td class="meta-lbl">NIS</td>
                    <td class="meta-val">: {{ ($c['nis'] ?? '') !== '' ? $c['nis'] : '-' }}</td>
                    <td class="meta-lbl-r">Kelas</td>
                    <td class="meta-val-r">: {{ ($c['kelas'] ?? '') !== '' ? $c['kelas'] : '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">NOVA</td>
                    <td class="meta-val">: {{ ($c['no_va'] ?? '') !== '' ? $c['no_va'] : '-' }}</td>
                    <td class="meta-lbl-r">Unit</td>
                    <td class="meta-val-r">: {{ ($c['unit'] ?? '') !== '' ? $c['unit'] : '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Nama Siswa</td>
                    <td class="meta-val">: {{ ($c['nama'] ?? '') !== '' ? $c['nama'] : '-' }}</td>
                    <td class="meta-lbl-r">Angkatan</td>
                    <td class="meta-val-r">: {{ $angkatan }}</td>
                </tr>
            </table>

            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:16%;">Tahun Akademik</th>
                        <th>Nama Tagihan</th>
                        <th style="width:18%;">Jumlah</th>
                        <th style="width:14%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $idx => $it)
                        @php $it = is_array($it) ? $it : []; @endphp
                        <tr>
                            <td class="ctr">{{ $idx + 1 }}</td>
                            <td class="ctr">{{ ($it['tahun_aka'] ?? '') !== '' ? $it['tahun_aka'] : '-' }}</td>
                            <td>{{ ($it['nama_tagihan'] ?? '') !== '' ? $it['nama_tagihan'] : '-' }}</td>
                            <td class="num">{{ kartu_pdf_rp((int) ($it['tagihan'] ?? 0)) }}</td>
                            <td class="ctr status-lunas">LUNAS</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="sum-label">Total Tagihan</td>
                        <td class="sum-val num">{{ kartu_pdf_rp($total) }}</td>
                        <td class="sum-val"></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="sum-label">Total Tagihan Terbayar</td>
                        <td class="sum-val num">{{ kartu_pdf_rp($totalTerbayar) }}</td>
                        <td class="sum-val"></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="sum-label">Total Sisa Tagihan</td>
                        <td class="sum-val num">{{ kartu_pdf_rp($totalSisa) }}</td>
                        <td class="sum-val"></td>
                    </tr>
                </tbody>
            </table>

            <table class="sign-wrap">
                <tr>
                    <td class="sign-right">
                        <div>{{ $tglTtd }}</div>
                        <div class="sign-role">Admin</div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
