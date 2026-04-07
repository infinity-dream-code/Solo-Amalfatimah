<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PenerimaanSiswaController extends Controller
{
    public function data(Request $request, AmalFatimahApiService $api): View
    {
        [$filters, $perPage, $page] = $this->penerimaanPagingFromRequest($request);

        $bundle = $api->loadPenerimaanFilterShell();
        $filterOptions = $bundle['filterOptions'];
        $bankRows = $bundle['bankOptions'];
        if (!is_array($bankRows)) {
            $bankRows = [];
        }

        $paginator = new Paginator([], $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('keuangan.penerimaan-siswa.data', [
            'pageTitle' => 'Data Penerimaan',
            'filterOptions' => $filterOptions,
            'bankOptions' => $bankRows,
            'filters' => $filters,
            'penerimaanRows' => $paginator,
            'penerimaanRowsUrl' => route('keu.penerimaan.data_rows', $request->query()),
            'penerimaanDeferred' => true,
        ]);
    }

    /**
     * JSON isi tabel (dipanggil async) agar halaman HTML tidak menunggu query berat di WS/DB.
     */
    public function dataRows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$filters, $perPage, $page] = $this->penerimaanPagingFromRequest($request);

        $res = $api->getDataPenerimaan($filters, $perPage, ($page - 1) * $perPage);
        if (!$res['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Gagal memuat data.',
            ], 422);
        }

        $rows = $res['data']['rows'] ?? [];
        $hasMore = count($rows) > $perPage;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $perPage);
        }

        $meta = is_array($res['data']['meta'] ?? null) ? $res['data']['meta'] : [];

        $outRows = [];
        foreach ($rows as $row) {
            $r = is_array($row) ? array_change_key_case($row, CASE_LOWER) : [];
            $paiddt = trim((string) ($r['paiddt'] ?? ''));
            $tbayar = '-';
            if ($paiddt !== '') {
                $ts = strtotime($paiddt);
                $tbayar = $ts ? date('d-m-Y H:i', $ts) : $paiddt;
                if ($tbayar === '01-01-1970 07:00') {
                    $tbayar = $paiddt;
                }
            }
            $r['tbayar_display'] = $tbayar;
            $r['search_hay'] = mb_strtolower(trim(
                ($r['nis'] ?? '') . ' ' . ($r['nama'] ?? '') . ' ' . ($r['unit'] ?? '') . ' ' . ($r['kelas'] ?? '') . ' '
                . ($r['nama_tagihan'] ?? '') . ' ' . ($r['metode'] ?? '') . ' ' . $tbayar . ' ' . ($r['tahun_aka'] ?? '')
            ));
            $outRows[] = $r;
        }

        $n = count($outRows);
        $firstItem = $n > 0 ? (($page - 1) * $perPage + 1) : 0;
        $lastItem = $n > 0 ? ($firstItem + $n - 1) : 0;

        $q = $request->query();
        $prevUrl = $page > 1 ? route('keu.penerimaan.data', array_merge($q, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('keu.penerimaan.data', array_merge($q, ['page' => $page + 1])) : null;

        return response()->json([
            'ok' => true,
            'rows' => $outRows,
            'meta' => $meta,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'first_item' => $firstItem,
            'last_item' => $lastItem,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
        ]);
    }

    /**
     * @return array{0: array<string, string>, 1: int, 2: int}
     */
    private function penerimaanPagingFromRequest(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $filters = [
            'tgl_dari' => trim((string) $request->query('tgl_dari', '')),
            'tgl_sampai' => trim((string) $request->query('tgl_sampai', '')),
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'thn_akademik' => trim((string) $request->query('thn_akademik', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'nama_tagihan' => trim((string) $request->query('nama_tagihan', '')),
            'nis' => trim((string) $request->query('nis', '')),
            'nama' => trim((string) $request->query('nama', '')),
            'cari' => trim((string) $request->query('cari', '')),
            'fidbank' => trim((string) $request->query('fidbank', '')),
            'sekolah' => trim((string) $request->query('sekolah', '')),
            'periode_mulai' => trim((string) $request->query('periode_mulai', '')),
            'periode_akhir' => trim((string) $request->query('periode_akhir', '')),
        ];

        return [$filters, $perPage, $page];
    }

    /**
     * Cetak kartu siswa (PDF) — siswa dipilih di grid; Ayah/Ibu dari scctcust (GENUS/GENUS1) via WS.
     */
    public function printKartuSiswa(Request $request, AmalFatimahApiService $api): Response|RedirectResponse
    {
        $custids = $request->input('custids', []);
        if (!is_array($custids)) {
            $custids = [];
        }
        $custids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $custids), static fn ($n) => $n > 0)));
        if ($custids === []) {
            return redirect()->back()->with('export_error', 'Pilih minimal satu siswa (centang baris di tabel).');
        }

        $filters = $this->penerimaanFiltersFromPost($request);
        if ($filters['tgl_dari'] === '') {
            return redirect()->back()->with('export_error', 'Isi Tanggal Transaksi (Dari Tanggal) sebelum cetak kartu.');
        }

        $res = $api->getKartuSiswaPenerimaan($filters, $custids);
        if (!$res['ok']) {
            return redirect()->back()->with('export_error', $res['message'] ?? 'Gagal mengambil data.');
        }
        $err = trim((string) ($res['data']['error'] ?? ''));
        if ($err !== '') {
            return redirect()->back()->with('export_error', $err);
        }
        $cards = $res['data']['cards'] ?? [];
        if (!is_array($cards) || $cards === []) {
            return redirect()->back()->with('export_error', 'Tidak ada data kartu untuk siswa terpilih.');
        }

        $pdf = Pdf::loadView('keuangan.penerimaan-siswa.kartu-siswa-pdf', [
            'cards' => $cards,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('kartu-siswa-penerimaan-' . date('Ymd-His') . '.pdf');
    }

    /**
     * Cetak kuitansi (PDF) — data sama kartu (WS getKartuSiswaPenerimaan); tanggal diformat di server (Carbon) agar tahun benar.
     */
    public function printKuitansi(Request $request, AmalFatimahApiService $api): Response|RedirectResponse
    {
        $custids = $request->input('custids', []);
        if (!is_array($custids)) {
            $custids = [];
        }
        $custids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $custids), static fn ($n) => $n > 0)));
        if ($custids === []) {
            return redirect()->back()->with('export_error', 'Pilih minimal satu siswa (centang baris di tabel).');
        }

        $filters = $this->penerimaanFiltersFromPost($request);

        $res = $api->getKartuSiswaPenerimaan($filters, $custids);
        if (!$res['ok']) {
            return redirect()->back()->with('export_error', $res['message'] ?? 'Gagal mengambil data.');
        }
        $err = trim((string) ($res['data']['error'] ?? ''));
        if ($err !== '') {
            return redirect()->back()->with('export_error', $err);
        }
        $cards = $res['data']['cards'] ?? [];
        if (!is_array($cards) || $cards === []) {
            return redirect()->back()->with('export_error', 'Tidak ada data kuitansi untuk siswa terpilih.');
        }

        $dengan2000 = $request->boolean('dengan_2000');

        $pdf = Pdf::loadView('keuangan.penerimaan-siswa.kuitansi-pdf', [
            'cards' => $cards,
            'dengan_2000' => $dengan2000,
        ])->setPaper('a4', 'portrait');

        $suffix = $dengan2000 ? '-2000' : '';

        return $pdf->stream('kuitansi-penerimaan' . $suffix . '-' . date('Ymd-His') . '.pdf');
    }

    /**
     * Rekap data penerimaan (Excel) — mengikuti filter halaman; tanggal tidak wajib; hingga 8000 baris (lihat WS pdf_export).
     */
    public function printRekapPdf(Request $request, AmalFatimahApiService $api): Response|RedirectResponse
    {
        $filters = $this->penerimaanFiltersFromPost($request);

        $res = $api->getDataPenerimaanPdfExport($filters);
        if (!$res['ok']) {
            return redirect()->back()->with('export_error', $res['message'] ?? 'Gagal mengambil data untuk Excel.');
        }

        $rows = $res['data']['rows'] ?? [];
        if (!is_array($rows) || $rows === []) {
            return redirect()->back()->with('export_error', 'Tidak ada data penerimaan untuk filter ini.');
        }

        $norm = [];
        foreach ($rows as $row) {
            $r = is_array($row) ? array_change_key_case($row, CASE_LOWER) : [];
            $norm[] = $r;
        }

        $matrix = $this->rekapPenerimaanMatrixFromRows($norm);
        $filterSummary = $this->rekapPenerimaanPdfFilterSummary($filters);
        $this->fillRekapSummaryFromRows($filterSummary, $norm);

        $maybeTruncated = count($norm) >= 8000;

        $columns = is_array($matrix['columns'] ?? null) ? $matrix['columns'] : [];
        $students = is_array($matrix['students'] ?? null) ? $matrix['students'] : [];
        $colTotals = is_array($matrix['col_totals'] ?? null) ? $matrix['col_totals'] : [];
        $grandTotal = (int) ($matrix['grand_total'] ?? 0);

        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $rp = static fn (int $n): string => 'Rp ' . number_format($n, 0, ',', '.');

        $fixedHeaders = ['No.', 'NIS', 'Nama'];
        $allHeaders = array_merge($fixedHeaders, $columns, ['TOTAL']);
        $colCount = count($allHeaders);
        $lastIdx = max(0, $colCount - 1);

        $buf = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $buf .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $buf .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $buf .= '<Styles>'
            . '<Style ss:ID="title"><Font ss:Bold="1" ss:Size="14"/></Style>'
            . '<Style ss:ID="metaKey"><Font ss:Bold="1"/></Style>'
            . '<Style ss:ID="hdr"><Font ss:Bold="1"/></Style>'
            . '<Style ss:ID="num"><Alignment ss:Horizontal="Right"/></Style>'
            . '<Style ss:ID="tot"><Font ss:Bold="1"/><Alignment ss:Horizontal="Right"/></Style>'
            . '</Styles>' . "\n";
        $buf .= '<Worksheet ss:Name="REKAP PENERIMAAN"><Table>' . "\n";
        for ($i = 0; $i < $colCount; $i++) {
            $w = 90;
            if ($i === 0) {
                $w = 45;
            } elseif ($i === 1) {
                $w = 95;
            } elseif ($i === 2) {
                $w = 140;
            }
            $buf .= '<Column ss:Width="' . $w . '"/>';
        }
        $buf .= "\n";

        $buf .= '<Row><Cell ss:StyleID="title" ss:MergeAcross="' . $lastIdx . '"><Data ss:Type="String">REKAP PENERIMAAN</Data></Cell></Row>' . "\n";
        $buf .= '<Row><Cell ss:StyleID="metaKey"><Data ss:Type="String">Unit_Kelas</Data></Cell><Cell ss:MergeAcross="' . max(0, $lastIdx - 1) . '"><Data ss:Type="String">' . $esc((string) ($filterSummary['unit_kelas'] ?? '-')) . '</Data></Cell></Row>' . "\n";
        $buf .= '<Row><Cell ss:StyleID="metaKey"><Data ss:Type="String">Tahun Akademik</Data></Cell><Cell ss:MergeAcross="' . max(0, $lastIdx - 1) . '"><Data ss:Type="String">' . $esc((string) ($filterSummary['thn_akademik'] ?? '-')) . '</Data></Cell></Row>' . "\n";
        $buf .= '<Row><Cell ss:StyleID="metaKey"><Data ss:Type="String">Dari</Data></Cell><Cell ss:MergeAcross="' . max(0, $lastIdx - 1) . '"><Data ss:Type="String">' . $esc((string) ($filterSummary['dari'] ?? '-')) . '</Data></Cell></Row>' . "\n";
        $buf .= '<Row><Cell ss:StyleID="metaKey"><Data ss:Type="String">Hingga</Data></Cell><Cell ss:MergeAcross="' . max(0, $lastIdx - 1) . '"><Data ss:Type="String">' . $esc((string) ($filterSummary['hingga'] ?? '-')) . '</Data></Cell></Row>' . "\n";
        if ($maybeTruncated) {
            $buf .= '<Row><Cell ss:MergeAcross="' . $lastIdx . '"><Data ss:Type="String">Catatan: data dibatasi maksimal 8000 baris sumber.</Data></Cell></Row>' . "\n";
        }
        $buf .= '<Row/>' . "\n";

        $buf .= '<Row>';
        foreach ($allHeaders as $h) {
            $buf .= '<Cell ss:StyleID="hdr"><Data ss:Type="String">' . $esc((string) $h) . '</Data></Cell>';
        }
        $buf .= '</Row>' . "\n";

        foreach ($students as $idx => $st) {
            if (!is_array($st)) {
                continue;
            }
            $buf .= '<Row>';
            $buf .= '<Cell><Data ss:Type="Number">' . ($idx + 1) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($st['nis'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($st['nama'] ?? '')) . '</Data></Cell>';
            foreach ($columns as $col) {
                $v = (int) (($st['cells'][$col] ?? 0));
                $buf .= '<Cell ss:StyleID="num"><Data ss:Type="String">' . $esc($rp($v)) . '</Data></Cell>';
            }
            $buf .= '<Cell ss:StyleID="tot"><Data ss:Type="String">' . $esc($rp((int) ($st['row_total'] ?? 0))) . '</Data></Cell>';
            $buf .= '</Row>' . "\n";
        }

        $buf .= '<Row>';
        $buf .= '<Cell ss:MergeAcross="2" ss:StyleID="tot"><Data ss:Type="String">TOTAL</Data></Cell>';
        foreach ($columns as $col) {
            $tv = (int) ($colTotals[$col] ?? 0);
            $buf .= '<Cell ss:StyleID="tot"><Data ss:Type="String">' . $esc($rp($tv)) . '</Data></Cell>';
        }
        $buf .= '<Cell ss:StyleID="tot"><Data ss:Type="String">' . $esc($rp($grandTotal)) . '</Data></Cell>';
        $buf .= '</Row>' . "\n";

        $buf .= '</Table></Worksheet></Workbook>';

        $fn = 'rekap-penerimaan-' . date('Ymd-His') . '.xls';

        return response($buf, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fn . '"',
        ]);
    }

    /**
     * @param array<string, string> $summary
     * @param list<array<string, mixed>> $rows
     */
    private function fillRekapSummaryFromRows(array &$summary, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        // Fallback tahun akademik dari data transaksi jika filter tidak diisi.
        $akaSet = [];
        foreach ($rows as $r) {
            $aka = trim((string) ($r['tahun_aka'] ?? ''));
            if ($aka !== '') {
                $akaSet[$aka] = true;
            }
        }
        if (($summary['thn_akademik'] ?? '') === 'Semua' && $akaSet !== []) {
            $akaList = array_keys($akaSet);
            sort($akaList, SORT_NATURAL | SORT_FLAG_CASE);
            $summary['thn_akademik'] = count($akaList) === 1 ? $akaList[0] : implode(', ', array_slice($akaList, 0, 3));
        }

        // Fallback Unit/Kelas dari baris pertama jika memungkinkan.
        if (($summary['unit_kelas'] ?? '') === 'Semua / Semua') {
            $u = trim((string) ($rows[0]['unit'] ?? ''));
            $k = trim((string) ($rows[0]['kelas'] ?? ''));
            if ($u !== '' || $k !== '') {
                $summary['unit_kelas'] = ($u !== '' ? $u : 'Semua') . ' / ' . ($k !== '' ? $k : 'Semua');
            }
        }

        // Fallback rentang tanggal dari PAIDDT jika filter tanggal kosong.
        $hasDari = trim((string) ($summary['dari'] ?? '-')) !== '-';
        $hasHingga = trim((string) ($summary['hingga'] ?? '-')) !== '-';
        if (!$hasDari || !$hasHingga) {
            $minTs = null;
            $maxTs = null;
            foreach ($rows as $r) {
                $raw = trim((string) ($r['paiddt'] ?? ''));
                if ($raw === '') {
                    continue;
                }
                $ts = strtotime($raw);
                if ($ts === false) {
                    continue;
                }
                $minTs = $minTs === null ? $ts : min($minTs, $ts);
                $maxTs = $maxTs === null ? $ts : max($maxTs, $ts);
            }
            if ($minTs !== null) {
                $summary['dari'] = Carbon::createFromTimestamp($minTs, 'Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y');
            }
            if ($maxTs !== null) {
                $summary['hingga'] = Carbon::createFromTimestamp($maxTs, 'Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function penerimaanFiltersFromPost(Request $request): array
    {
        $sekolah = trim((string) $request->input('sekolah', ''));
        $tingkat = trim((string) $request->input('tingkat', ''));
        if ($sekolah === '' && $tingkat !== '') {
            $sekolah = $tingkat;
        }

        return [
            'tgl_dari' => trim((string) $request->input('tgl_dari', '')),
            'tgl_sampai' => trim((string) $request->input('tgl_sampai', '')),
            'thn_angkatan' => trim((string) $request->input('thn_angkatan', '')),
            'thn_akademik' => trim((string) $request->input('thn_akademik', '')),
            'kelas_id' => trim((string) $request->input('kelas_id', '')),
            'nama_tagihan' => trim((string) $request->input('nama_tagihan', '')),
            'nis' => trim((string) $request->input('nis', '')),
            'nama' => trim((string) $request->input('nama', '')),
            'cari' => trim((string) $request->input('cari', '')),
            'fidbank' => trim((string) $request->input('fidbank', '')),
            'sekolah' => $sekolah,
            'periode_mulai' => trim((string) $request->input('periode_mulai', '')),
            'periode_akhir' => trim((string) $request->input('periode_akhir', '')),
        ];
    }

    public function rekap(Request $request, AmalFatimahApiService $api): View
    {
        [$filters, $perPage, $page] = $this->penerimaanPagingFromRequest($request);

        $bundle = $api->loadRekapPenerimaanShell();
        $filterOptions = $bundle['filterOptions'];
        $tingkatOptions = is_array($bundle['tingkatOptions'] ?? null) ? $bundle['tingkatOptions'] : [];

        $paginator = new Paginator([], $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('keuangan.penerimaan-siswa.rekap', [
            'pageTitle' => 'Rekap Penerimaan',
            'filterOptions' => $filterOptions,
            'tingkatOptions' => $tingkatOptions,
            'filters' => $filters,
            'penerimaanRows' => $paginator,
            'rekapRowsUrl' => route('keu.penerimaan.rekap_rows', $request->query()),
        ]);
    }

    /**
     * JSON isi tabel Rekap Penerimaan (async).
     */
    public function rekapRows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$filters, $perPage, $page] = $this->penerimaanPagingFromRequest($request);

        $res = $api->getDataPenerimaan($filters, $perPage, ($page - 1) * $perPage);
        if (!$res['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Gagal memuat data.',
            ], 422);
        }

        $rows = $res['data']['rows'] ?? [];
        $hasMore = count($rows) > $perPage;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $perPage);
        }

        $meta = is_array($res['data']['meta'] ?? null) ? $res['data']['meta'] : [];

        $outRows = [];
        foreach ($rows as $row) {
            $r = is_array($row) ? array_change_key_case($row, CASE_LOWER) : [];
            $paiddt = trim((string) ($r['paiddt'] ?? ''));
            $tbayar = '-';
            if ($paiddt !== '') {
                $ts = strtotime($paiddt);
                $tbayar = $ts ? date('d-m-Y H:i', $ts) : $paiddt;
                if ($tbayar === '01-01-1970 07:00') {
                    $tbayar = $paiddt;
                }
            }
            $r['tbayar_display'] = $tbayar;
            $r['search_hay'] = mb_strtolower(trim(
                ($r['nis'] ?? '') . ' ' . ($r['nama'] ?? '') . ' ' . ($r['unit'] ?? '') . ' ' . ($r['kelas'] ?? '') . ' '
                . ($r['nama_tagihan'] ?? '') . ' ' . ($r['metode'] ?? '') . ' ' . $tbayar . ' ' . ($r['tahun_aka'] ?? '')
            ));
            $outRows[] = $r;
        }

        $n = count($outRows);
        $firstItem = $n > 0 ? (($page - 1) * $perPage + 1) : 0;
        $lastItem = $n > 0 ? ($firstItem + $n - 1) : 0;

        $q = $request->query();
        $prevUrl = $page > 1 ? route('keu.penerimaan.rekap', array_merge($q, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('keu.penerimaan.rekap', array_merge($q, ['page' => $page + 1])) : null;

        return response()->json([
            'ok' => true,
            'rows' => $outRows,
            'meta' => $meta,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'first_item' => $firstItem,
            'last_item' => $lastItem,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
        ]);
    }

    /**
     * Urutan kolom: BULAN JULI … BULAN JUNI (siklus tahun ajaran), lalu tagihan lain alfabetis.
     *
     * @return array{0: int, 1: int, 2: string}
     */
    private static function rekapPenerimaanColumnSortKey(string $name): array
    {
        $raw = trim($name);
        $n = mb_strtoupper($raw, 'UTF-8');
        if (preg_match('/^BULAN[\s\x{00A0}]+(.+)$/u', $n, $m)) {
            $mn = trim($m[1]);
            $order = [
                'JULI' => 0,
                'AGUSTUS' => 1,
                'SEPTEMBER' => 2,
                'OKTOBER' => 3,
                'NOVEMBER' => 4,
                'DESEMBER' => 5,
                'JANUARI' => 6,
                'FEBRUARI' => 7,
                'MARET' => 8,
                'APRIL' => 9,
                'MEI' => 10,
                'JUNI' => 11,
            ];
            if (isset($order[$mn])) {
                return [0, $order[$mn], $raw];
            }

            return [0, 50, $raw];
        }

        return [1, 0, $raw];
    }

    /**
     * @param list<array<string, mixed>> $norm
     * @return array{columns: list<string>, students: list<array<string, mixed>>, col_totals: array<string, int>, grand_total: int}
     */
    private function rekapPenerimaanMatrixFromRows(array $norm): array
    {
        $colSet = [];
        foreach ($norm as $r) {
            $k = trim((string) ($r['nama_tagihan'] ?? ''));
            if ($k === '') {
                $k = '-';
            }
            $colSet[$k] = true;
        }
        $columns = array_keys($colSet);
        usort($columns, function (string $a, string $b): int {
            $ka = self::rekapPenerimaanColumnSortKey($a);
            $kb = self::rekapPenerimaanColumnSortKey($b);
            if ($ka[0] !== $kb[0]) {
                return $ka[0] <=> $kb[0];
            }
            if ($ka[1] !== $kb[1]) {
                return $ka[1] <=> $kb[1];
            }

            return strnatcasecmp($a, $b);
        });

        $studentsMap = [];
        foreach ($norm as $r) {
            $custid = (int) ($r['custid'] ?? 0);
            if ($custid > 0) {
                $sid = 'c:' . $custid;
            } else {
                $sid = 'h:' . md5(mb_strtolower(trim((string) (($r['nis'] ?? '') . '|' . ($r['nama'] ?? ''))), 'UTF-8'));
            }
            $col = trim((string) ($r['nama_tagihan'] ?? ''));
            if ($col === '') {
                $col = '-';
            }
            $amt = (int) ($r['tagihan'] ?? 0);
            if (!isset($studentsMap[$sid])) {
                $studentsMap[$sid] = [
                    'custid' => $custid,
                    'nis' => (string) ($r['nis'] ?? ''),
                    'nama' => (string) ($r['nama'] ?? ''),
                    'unit' => (string) ($r['unit'] ?? ''),
                    'kelas' => (string) ($r['kelas'] ?? ''),
                    'cells' => [],
                ];
            }
            $studentsMap[$sid]['cells'][$col] = ($studentsMap[$sid]['cells'][$col] ?? 0) + $amt;
        }

        $students = array_values($studentsMap);
        usort($students, static fn (array $a, array $b): int => strnatcasecmp($a['nis'] ?? '', $b['nis'] ?? ''));

        $colTotals = [];
        foreach ($columns as $c) {
            $colTotals[$c] = 0;
        }
        foreach ($students as $i => $st) {
            $rowSum = 0;
            foreach ($columns as $c) {
                $v = (int) ($st['cells'][$c] ?? 0);
                $rowSum += $v;
                $colTotals[$c] += $v;
            }
            $students[$i]['row_total'] = $rowSum;
        }

        $grandTotal = array_sum($colTotals);

        return [
            'columns' => $columns,
            'students' => $students,
            'col_totals' => $colTotals,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * @param array<string, string> $filters
     * @return array{tanggal: string, dari: string, hingga: string, unit: string, kelas: string, unit_kelas: string, nama_tagihan: string, thn_akademik: string}
     */
    private function rekapPenerimaanPdfFilterSummary(array $filters): array
    {
        $tglDari = trim((string) ($filters['tgl_dari'] ?? ''));
        $tglSampai = trim((string) ($filters['tgl_sampai'] ?? ''));
        $tanggal = '-';
        $dari = '-';
        $hingga = '-';
        if ($tglDari !== '' || $tglSampai !== '') {
            try {
                $d1 = $tglDari !== '' ? Carbon::parse($tglDari)->timezone('Asia/Jakarta') : null;
                $d2 = $tglSampai !== '' ? Carbon::parse($tglSampai)->timezone('Asia/Jakarta') : null;
                if ($d1 && $d2) {
                    $tanggal = $d1->locale('id')->translatedFormat('l, j F Y') . ' - ' . $d2->locale('id')->translatedFormat('l, j F Y');
                    $dari = $d1->locale('id')->translatedFormat('l, j F Y');
                    $hingga = $d2->locale('id')->translatedFormat('l, j F Y');
                } elseif ($d1) {
                    $tanggal = $d1->locale('id')->translatedFormat('l, j F Y');
                    $dari = $tanggal;
                } elseif ($d2) {
                    $tanggal = $d2->locale('id')->translatedFormat('l, j F Y');
                    $hingga = $tanggal;
                }
            } catch (\Throwable $e) {
                $tanggal = trim($tglDari . ' – ' . $tglSampai, ' –');
                $dari = $tglDari !== '' ? $tglDari : '-';
                $hingga = $tglSampai !== '' ? $tglSampai : '-';
            }
        }

        $unit = trim((string) ($filters['sekolah'] ?? ''));
        $kelasId = trim((string) ($filters['kelas_id'] ?? ''));
        $unitLabel = $unit !== '' ? $unit : 'Semua';
        $kelasLabel = $kelasId === '' ? 'Semua' : ('Kelas #' . $kelasId);
        $unitKelas = $unitLabel . ' / ' . $kelasLabel;
        $namaTag = trim((string) ($filters['nama_tagihan'] ?? ''));
        $thAka = trim((string) ($filters['thn_akademik'] ?? ''));

        return [
            'tanggal' => $tanggal !== '' ? $tanggal : '-',
            'dari' => $dari !== '' ? $dari : '-',
            'hingga' => $hingga !== '' ? $hingga : '-',
            'unit' => $unitLabel,
            'kelas' => $kelasLabel,
            'unit_kelas' => $unitKelas,
            'nama_tagihan' => $namaTag !== '' ? $namaTag : 'Semua',
            'thn_akademik' => $thAka !== '' ? $thAka : 'Semua',
        ];
    }
}
