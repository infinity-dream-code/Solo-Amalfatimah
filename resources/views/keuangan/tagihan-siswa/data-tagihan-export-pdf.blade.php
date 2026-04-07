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
            letter-spacing: 0.02em;
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
        tbody tr:nth-child(odd) td {
            background: #ffffff;
        }
        tbody tr:nth-child(even) td {
            background: #e5e7eb;
        }
        .dt-left { text-align: left; }
        .dt-center { text-align: center; }
        .dt-num { text-align: right; }
    </style>
</head>
<body>
    <h1 class="doc-title">Data Tagihan Siswa</h1>
    <p class="doc-meta">Diekspor {{ now()->format('d/m/Y H:i') }} WIB · Total data: {{ count($rows) }}</p>
    <table>
        <thead>
            <tr>
                <th style="width:3%">No</th>
                <th style="width:7%">NIS</th>
                <th style="width:8%">No Daft</th>
                <th style="width:9%">No VA</th>
                <th style="width:14%">Nama</th>
                <th style="width:10%">Unit</th>
                <th style="width:6%">Kelas</th>
                <th style="width:6%">Kelompok</th>
                <th style="width:10%">Nama tagihan</th>
                <th style="width:8%">Tagihan</th>
                <th style="width:7%">Thn AKA</th>
                <th style="width:4%">Urut</th>
                <th style="width:8%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                <tr>
                    <td class="dt-center">{{ $r['no'] }}</td>
                    <td class="dt-center">{{ $r['nis'] }}</td>
                    <td class="dt-center">{{ $r['no_daftar'] }}</td>
                    <td class="dt-center">{{ $r['no_va'] }}</td>
                    <td class="dt-left">{{ $r['nama'] }}</td>
                    <td class="dt-left">{{ $r['unit'] }}</td>
                    <td class="dt-center">{{ $r['kelas'] }}</td>
                    <td class="dt-center">{{ $r['kelompok'] }}</td>
                    <td class="dt-left">{{ $r['nama_tagihan'] }}</td>
                    <td class="dt-num">Rp {{ number_format((int) $r['tagihan'], 0, ',', '.') }}</td>
                    <td class="dt-center">{{ $r['tahun_aka'] }}</td>
                    <td class="dt-center">{{ $r['urutan'] }}</td>
                    <td class="dt-center">{{ $r['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
