<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 14px; }
        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .head-top { border-bottom: 3px solid #111; padding-bottom: 6px; margin-bottom: 8px; }
        .head-row { width: 100%; border-collapse: collapse; }
        .head-row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 80px; }
        .logo { width: 64px; height: 64px; object-fit: contain; display: block; }
        .text-cell { text-align: center; }
        .yayasan { font-size: 18px; font-weight: 700; margin: 0; }
        .sub { font-size: 9px; margin: 2px 0 0; }
        .sub-contact { font-size: 8px; margin: 3px 0 0; color: #333; }
        .title { font-size: 15px; font-weight: 700; text-align: center; margin: 8px 0 4px; }
        .meta-wrap { width: 100%; margin-bottom: 8px; border-collapse: collapse; }
        .meta-wrap td { padding: 2px 4px; font-size: 10px; }
        .meta-lbl { width: 14%; font-weight: 700; }
        .meta-val { width: 36%; }
        .meta-lbl-r { width: 14%; font-weight: 700; }
        .meta-val-r { width: 36%; }
        /* fixed + width di setiap sel: colspan baris ringkasan lebih stabil di DomPDF */
        table.tbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tbl th, .tbl td { border: 1px solid #444; padding: 4px 5px; vertical-align: top; }
        .tbl th { background: #f1f5f9; font-size: 8px; text-align: center; font-weight: 700; }
        .num { text-align: right; }
        .ctr { text-align: center; }
        .ft { font-size: 9px; color: #333; margin-top: 14px; }
        .sign { margin-top: 28px; text-align: right; font-size: 9px; }
        .summary-row td { background: #fafafa; }
        /* Tanpa colspan: teks di kolom Nama Tagihan, rata kanan; kolom Periode kosong → mentok ke border Tagihan */
        .lbl-sum {
            text-align: right;
            font-weight: 700;
            font-size: 9px;
            vertical-align: middle;
            padding: 5px 4px 5px 5px !important;
        }
        .sum-num { font-weight: 700; }
        .total-row td { font-weight: 700; background: #f1f5f9; }
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
        $dengan2000 = !empty($dengan_2000);
        $yayasanName = 'Solo Amal Fatimah';
        $yayasanAddr = 'Jl. Slamet Riyadi No. 443 Pajang, Laweyan, Kota Surakarta';
        $yayasanContact = '';
        if (!function_exists('kuitansi_format_rp')) {
            function kuitansi_format_rp(int $n): string
            {
                return 'Rp. ' . number_format($n, 0, ',', '.');
            }
        }
    @endphp
    @foreach ($cards as $c)
        @php
            $c = is_array($c) ? $c : [];
            $items = $c['items'] ?? [];
            $items = is_array($items) ? $items : [];
            $metodeSet = [];
            foreach ($items as $it0) {
                $it0 = is_array($it0) ? $it0 : [];
                $m0 = trim((string) ($it0['metode'] ?? ''));
                if ($m0 !== '' && $m0 !== '-') {
                    $metodeSet[$m0] = true;
                }
            }
            $metodeHdr = '-';
            if ($metodeSet !== []) {
                $keys = array_keys($metodeSet);
                $metodeHdr = count($keys) === 1 ? $keys[0] : implode(' / ', $keys);
            }
            $metodeHdr = str_ireplace('manual cash', 'Manual Cash', $metodeHdr);
            $kelasTampil = trim((string) ($c['kelas'] ?? ''));
            if ($kelasTampil === '') {
                $kelasTampil = '-';
            }
            $ayahT = trim((string) ($c['ayah'] ?? ''));
            $ibuT = trim((string) ($c['ibu'] ?? ''));
            $baseTotal = (int) ($c['total'] ?? 0);
            $grandTotal = $baseTotal + ($dengan2000 ? 2000 : 0);
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
                            @if ($yayasanContact !== '')
                                <p class="sub-contact">{{ $yayasanContact }}</p>
                            @endif
                        </td>
                        <td class="logo-cell"></td>
                    </tr>
                </table>
            </div>

            <div class="title">KUITANSI</div>

            <table class="meta-wrap">
                <tr>
                    <td class="meta-lbl">NIS</td>
                    <td class="meta-val">: {{ ($c['nis'] ?? '') !== '' ? $c['nis'] : '-' }}</td>
                    <td class="meta-lbl-r">No. VA</td>
                    <td class="meta-val-r">: {{ ($c['no_va'] ?? '') !== '' ? $c['no_va'] : '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Nama</td>
                    <td class="meta-val">: {{ ($c['nama'] ?? '') !== '' ? $c['nama'] : '-' }}</td>
                    <td class="meta-lbl-r">Unit</td>
                    <td class="meta-val-r">: {{ ($c['unit'] ?? '') !== '' ? $c['unit'] : '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Metode Bayar</td>
                    <td class="meta-val">: {{ $metodeHdr }}</td>
                    <td class="meta-lbl-r">Kelas</td>
                    <td class="meta-val-r">: {{ $kelasTampil }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Ayah</td>
                    <td class="meta-val">: {{ $ayahT !== '' ? $ayahT : '-' }}</td>
                    <td class="meta-lbl-r">Ibu</td>
                    <td class="meta-val-r">: {{ $ibuT !== '' ? $ibuT : '-' }}</td>
                </tr>
            </table>

            {{-- cellspacing/cellpadding + tanpa colgroup: DomPDF sering gagal colspan jika colgroup + fixed --}}
            <table class="tbl" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:33%;">Nama Tagihan</th>
                        <th style="width:14%;">Periode</th>
                        <th style="width:14%;">Tagihan</th>
                        <th style="width:14%;">Bayar</th>
                        <th style="width:20%;">Tanggal Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $idx => $it)
                        @php
                            $it = is_array($it) ? $it : [];
                            $tag = (int) ($it['tagihan'] ?? 0);
                            $rawPd = trim((string) ($it['paiddt'] ?? ''));
                            $tglLong = '-';
                            if ($rawPd !== '') {
                                try {
                                    $tglLong = \Carbon\Carbon::parse($rawPd)->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y');
                                } catch (\Throwable $e) {
                                    $tglLong = trim((string) ($it['tbayar'] ?? $rawPd)) ?: '-';
                                }
                            } elseif (trim((string) ($it['tbayar'] ?? '')) !== '') {
                                $tglLong = trim((string) $it['tbayar']);
                            }
                        @endphp
                        <tr>
                            <td class="ctr" style="width:5%;">{{ $idx + 1 }}</td>
                            <td style="width:33%;">{{ ($it['nama_tagihan'] ?? '') !== '' ? $it['nama_tagihan'] : '-' }}</td>
                            <td class="ctr" style="width:14%;">{{ ($it['tahun_aka'] ?? '') !== '' ? $it['tahun_aka'] : '-' }}</td>
                            <td class="num" style="width:14%;">{{ kuitansi_format_rp($tag) }}</td>
                            <td class="num" style="width:14%;">{{ kuitansi_format_rp($tag) }}</td>
                            <td style="font-size:7px;width:20%;">{{ $tglLong }}</td>
                        </tr>
                    @endforeach
                    @if ($dengan2000)
                        <tr class="summary-row">
                            <td colspan="3" class="lbl-sum" width="52%">Biaya Layanan</td>
                            <td class="num sum-num" width="14%">{{ kuitansi_format_rp(2000) }}</td>
                            <td class="num sum-num" width="14%">{{ kuitansi_format_rp(2000) }}</td>
                            <td class="ctr" width="20%">—</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td colspan="3" class="lbl-sum" width="52%">Total</td>
                        <td class="num sum-num" width="14%">{{ kuitansi_format_rp($grandTotal) }}</td>
                        <td class="num sum-num" width="14%">{{ kuitansi_format_rp($grandTotal) }}</td>
                        <td width="20%"></td>
                    </tr>
                </tbody>
            </table>

            <div class="ft">
                SURAKARTA, {{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y') }}
            </div>
            <div class="sign">
                <div style="margin-bottom:36px;">Admin</div>
            </div>
        </div>
    @endforeach
</body>
</html>
