<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Transaksi — Cetak</title>
    <style>
        body {
            font-family: system-ui, Segoe UI, Roboto, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 16px;
        }
        .doc-title { font-size: 18px; font-weight: 800; margin: 0 0 4px; }
        .doc-meta { font-size: 11px; color: #6b7280; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #1e3a8a;
            color: #fff;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 8px 6px;
            border: 1px solid #172554;
        }
        tbody td {
            border: 1px solid #cbd5e1;
            padding: 6px;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background: #f1f5f9; }
        .dt-num { text-align: right; white-space: nowrap; }
        .dt-ctr { text-align: center; }
        @media print {
            body { padding: 8px; }
        }
    </style>
</head>
<body>
    <h1 class="doc-title">Data Transaksi</h1>
    <p class="doc-meta">Diekspor {{ $exportedAt->format('d/m/Y H:i') }} WIB · Total data: {{ count($rows) }}</p>
    <table>
        <thead>
            <tr>
                <th>NO</th>
                <th>NIS</th>
                <th>NO VA</th>
                <th>NAMA</th>
                <th>METODE</th>
                <th>TANGGAL TRANSAKSI</th>
                <th>DEBET</th>
                <th>KREDIT</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $r)
                @php
                    if (!is_array($r)) { continue; }
                    $debet = (int) ($r['debet'] ?? 0);
                    $kredit = (int) ($r['kredit'] ?? 0);
                    $rpDebet = 'Rp ' . number_format($debet, 0, ',', '.') . ',00';
                    $rpKredit = 'Rp ' . number_format($kredit, 0, ',', '.') . ',00';
                    $trx = $r['trxdate'] ?? null;
                    $trxFmt = '-';
                    if ($trx !== null && trim((string) $trx) !== '') {
                        try {
                            $trxFmt = (new \DateTimeImmutable(str_replace(' ', 'T', trim((string) $trx))))->format('d/m/Y H:i');
                        } catch (\Throwable) {
                            $trxFmt = (string) $trx;
                        }
                    }
                @endphp
                <tr>
                    <td class="dt-ctr">{{ $i + 1 }}</td>
                    <td class="dt-ctr">{{ $r['nis'] ?? '' }}</td>
                    <td class="dt-ctr">{{ $r['no_va'] ?? '' }}</td>
                    <td>{{ $r['nama'] ?? '' }}</td>
                    <td>{{ $r['metode'] ?? '' }}</td>
                    <td class="dt-ctr">{{ $trxFmt }}</td>
                    <td class="dt-num">{{ $rpDebet }}</td>
                    <td class="dt-num">{{ $rpKredit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
