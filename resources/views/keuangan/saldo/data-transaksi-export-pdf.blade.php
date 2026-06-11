<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7pt;
            color: #111827;
            margin: 0;
            padding: 12px;
        }
        .doc-title {
            font-size: 12pt;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px;
        }
        .doc-meta {
            font-size: 7pt;
            color: #4b5563;
            margin: 0 0 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        thead tr th {
            background: #1e3a8a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 6.5pt;
            border: 1px solid #172554;
            padding: 6px 4px;
            vertical-align: middle;
            text-align: center;
        }
        tbody tr td {
            border: 1px solid #cbd5e1;
            padding: 4px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }
        tbody tr:nth-child(odd) td { background: #ffffff; }
        tbody tr:nth-child(even) td { background: #e5e7eb; }
        .dt-left { text-align: left; }
        .dt-center { text-align: center; }
        .dt-num { text-align: right; }
    </style>
</head>
<body>
    <h1 class="doc-title">Data Transaksi</h1>
    <p class="doc-meta">Diekspor {{ $exportedAt->format('d/m/Y H:i') }} WIB · Total data: {{ count($rows) }}</p>
    <table>
        <thead>
            <tr>
                <th style="width:5%">NO</th>
                <th style="width:9%">NIS</th>
                <th style="width:11%">NO VA</th>
                <th style="width:18%">NAMA</th>
                <th style="width:10%">METODE</th>
                <th style="width:17%">TANGGAL TRANSAKSI</th>
                <th style="width:15%">DEBET</th>
                <th style="width:15%">KREDIT</th>
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
                    <td class="dt-center">{{ $i + 1 }}</td>
                    <td class="dt-center">{{ $r['nis'] ?? '' }}</td>
                    <td class="dt-center">{{ $r['no_va'] ?? '' }}</td>
                    <td class="dt-left">{{ $r['nama'] ?? '' }}</td>
                    <td class="dt-left">{{ $r['metode'] ?? '' }}</td>
                    <td class="dt-center">{{ $trxFmt }}</td>
                    <td class="dt-num">{{ $rpDebet }}</td>
                    <td class="dt-num">{{ $rpKredit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
