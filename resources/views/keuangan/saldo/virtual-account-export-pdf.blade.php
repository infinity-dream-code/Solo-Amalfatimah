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
    <h1 class="doc-title">Saldo Virtual Account</h1>
    <p class="doc-meta">Diekspor {{ $exportedAt->format('d/m/Y H:i') }} WIB · Total data: {{ count($rows) }}</p>
    <table>
        <thead>
            <tr>
                <th style="width:9%">NIS</th>
                <th style="width:11%">NO VA</th>
                <th style="width:18%">NAMA</th>
                <th style="width:9%">NO PENDAFTARAN</th>
                <th style="width:14%">UNIT</th>
                <th style="width:7%">KELAS</th>
                <th style="width:8%">JENJANG</th>
                <th style="width:9%">ANGKATAN</th>
                <th style="width:10%">SALDO</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                @php
                    if (!is_array($r)) { continue; }
                    $np = trim((string) ($r['no_pendaftaran'] ?? ''));
                    $saldo = (int) ($r['saldo'] ?? 0);
                    $rp = 'Rp ' . number_format($saldo, 0, ',', '.') . ',00';
                @endphp
                <tr>
                    <td class="dt-center">{{ $r['nis'] ?? '' }}</td>
                    <td class="dt-center">{{ $r['no_va'] ?? '' }}</td>
                    <td class="dt-left">{{ $r['nama'] ?? '' }}</td>
                    <td class="dt-center">{{ $np !== '' ? $np : '-' }}</td>
                    <td class="dt-left">{{ $r['unit'] ?? '' }}</td>
                    <td class="dt-center">{{ $r['kelas'] ?? '' }}</td>
                    <td class="dt-center">{{ $r['jenjang'] ?? '' }}</td>
                    <td class="dt-center">{{ $r['angkatan'] ?? '' }}</td>
                    <td class="dt-num">{{ $rp }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
