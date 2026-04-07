<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saldo Virtual Account — Cetak</title>
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
        @media print {
            body { padding: 8px; }
        }
    </style>
</head>
<body>
    <h1 class="doc-title">Saldo Virtual Account</h1>
    <p class="doc-meta">Diekspor {{ $exportedAt->format('d/m/Y H:i') }} WIB · Total data: {{ count($rows) }}</p>
    <table>
        <thead>
            <tr>
                <th>NIS</th>
                <th>NO VA</th>
                <th>NAMA</th>
                <th>NO PENDAFTARAN</th>
                <th>UNIT</th>
                <th>KELAS</th>
                <th>JENJANG</th>
                <th>ANGKATAN</th>
                <th>SALDO</th>
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
                    <td>{{ $r['nis'] ?? '' }}</td>
                    <td>{{ $r['no_va'] ?? '' }}</td>
                    <td>{{ $r['nama'] ?? '' }}</td>
                    <td>{{ $np !== '' ? $np : '-' }}</td>
                    <td>{{ $r['unit'] ?? '' }}</td>
                    <td>{{ $r['kelas'] ?? '' }}</td>
                    <td>{{ $r['jenjang'] ?? '' }}</td>
                    <td>{{ $r['angkatan'] ?? '' }}</td>
                    <td class="dt-num">{{ $rp }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
