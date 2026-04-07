<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Tagihan Siswa — Cetak</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, Segoe UI, Roboto, sans-serif; font-size: 12px; color: #111827; margin: 0; padding: 16px 20px 40px; }
        .no-print { margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .no-print button {
            height: 38px; padding: 0 18px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; border: 1px solid #cbd5e1;
            background: #f8fafc; color: #334155;
        }
        .no-print button.primary { background: #06b6d4; border-color: #0891b2; color: #fff; }
        .no-print button:hover { filter: brightness(0.97); }
        h1 { font-size: 18px; margin: 0 0 6px; }
        .meta { color: #4b5563; margin: 0 0 16px; font-size: 12px; }
        .err { background: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead th {
            background: #1e3a8a; color: #fff; font-weight: 700; text-transform: uppercase; font-size: 10px;
            border: 1px solid #172554; padding: 8px 6px; text-align: center;
        }
        tbody td {
            border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; word-wrap: break-word;
        }
        tbody tr:nth-child(odd) td { background: #fff; }
        tbody tr:nth-child(even) td { background: #e5e7eb; }
        .dt-left { text-align: left; }
        .dt-center { text-align: center; }
        .dt-num { text-align: right; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" class="primary" onclick="window.print()">Cetak</button>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    <h1>Data Tagihan Siswa</h1>
    <p class="meta">Dicetak {{ now()->format('d/m/Y H:i') }} WIB · Total data: {{ count($rows) }}</p>

    @if ($errorMessage !== '')
        <div class="err">{{ $errorMessage }}</div>
    @elseif (count($rows) === 0)
        <div class="err">Tidak ada data yang cocok dengan filter.</div>
    @else
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
    @endif
</body>
</html>
