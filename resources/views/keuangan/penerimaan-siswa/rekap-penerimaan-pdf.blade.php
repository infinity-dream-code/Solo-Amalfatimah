<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 7pt; color: #111; margin: 0; padding: 10px; }
        .doc-title { font-size: 11pt; font-weight: 700; text-align: left; margin: 0 0 6px; }
        .meta { margin: 0 0 8px; font-size: 7pt; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { border: 0; padding: 1px 6px 1px 0; vertical-align: top; }
        .meta .k { font-weight: 700; white-space: nowrap; width: 16%; }
        table.tbl { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 3px; }
        .tbl th, .tbl td { border: 1px solid #000; padding: 3px 3px; vertical-align: middle; }
        .tbl th {
            background: #fff;
            color: #000;
            font-size: 6.5pt;
            text-align: center;
            font-weight: 700;
            word-wrap: break-word;
        }
        .num { text-align: right; white-space: nowrap; }
        .idx { text-align: center; width: 3%; }
        .cnis { width: 10%; }
        .cnama { width: 14%; }
        .ctot { font-weight: 700; }
        .tot-row td { font-weight: 700; }
        .hint { font-size: 6pt; color: #6b7280; margin-top: 6px; }
    </style>
</head>
<body>
    @php
        $columns = is_array($columns ?? null) ? $columns : [];
        $students = is_array($students ?? null) ? $students : [];
        $colTotals = is_array($colTotals ?? null) ? $colTotals : [];
        $filterSummary = is_array($filterSummary ?? null) ? $filterSummary : [];
        $grandTotal = (int) ($grandTotal ?? 0);
        if (!function_exists('rekap_matrix_rp')) {
            function rekap_matrix_rp(int $n): string
            {
                return 'Rp. ' . number_format($n, 0, ',', '.');
            }
        }
    @endphp

    <div class="doc-title">REKAP PENERIMAAN</div>

    <div class="meta">
        <table>
            <tr>
                <td class="k">Unit_Kelas</td>
                <td>: {{ $filterSummary['unit_kelas'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="k">Tahun Akademik</td>
                <td>: {{ $filterSummary['thn_akademik'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="k">Dari</td>
                <td>: {{ $filterSummary['dari'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="k">Hingga</td>
                <td>: {{ $filterSummary['hingga'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    @if ($columns === [] || $students === [])
        <p style="font-size:8pt;">Tidak ada data untuk ditampilkan.</p>
    @else
        <table class="tbl">
            <thead>
                <tr>
                    <th class="idx">No.</th>
                    <th class="cnis">NIS</th>
                    <th class="cnama">Nama</th>
                    @foreach ($columns as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                    <th style="width:7%;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students as $idx => $st)
                    @php $st = is_array($st) ? $st : []; @endphp
                    <tr>
                        <td class="idx">{{ $idx + 1 }}</td>
                        <td>{{ ($st['nis'] ?? '') !== '' ? $st['nis'] : '-' }}</td>
                        <td style="font-size:5pt;">{{ ($st['nama'] ?? '') !== '' ? $st['nama'] : '-' }}</td>
                        @foreach ($columns as $col)
                            @php $v = (int) ($st['cells'][$col] ?? 0); @endphp
                            <td class="num">{{ rekap_matrix_rp($v) }}</td>
                        @endforeach
                        <td class="num ctot">{{ rekap_matrix_rp((int) ($st['row_total'] ?? 0)) }}</td>
                    </tr>
                @endforeach
                <tr class="tot-row">
                    <td colspan="3" class="num" style="text-align:right;padding-right:6px;">TOTAL</td>
                    @foreach ($columns as $col)
                        @php $tv = (int) ($colTotals[$col] ?? 0); @endphp
                        <td class="num">{{ rekap_matrix_rp($tv) }}</td>
                    @endforeach
                    <td class="num">{{ rekap_matrix_rp($grandTotal) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if (!empty($maybeTruncated))
        <p class="hint">Catatan: sumber data dibatasi maksimal 8.000 baris bill lunas per cetak. Persempit filter bila perlu seluruh pembayaran.</p>
    @endif

</body>
</html>
