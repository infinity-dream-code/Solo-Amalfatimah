<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaldoController extends Controller
{
    public function virtualAccount(Request $request, AmalFatimahApiService $api): View|StreamedResponse|\Illuminate\Http\Response|JsonResponse
    {
        $export = $request->query('export');
        if ($export === 'csv') {
            return $this->virtualAccountExportCsv($request, $api);
        }
        if ($export === 'pdf') {
            return $this->virtualAccountExportPdf($request, $api);
        }
        if ($export === 'xls') {
            return $this->virtualAccountExportXls($request, $api);
        }
        if ($export === 'print') {
            return $this->virtualAccountExportPrint($request, $api);
        }
        if ($export === 'json') {
            return $this->virtualAccountExportJson($request, $api);
        }

        [$filters, $perPage, $page] = $this->vaPagingFromRequest($request);

        $shell = $api->loadRekapPenerimaanShell();
        $filterOptions = $shell['filterOptions'];
        $tingkatOptions = is_array($shell['tingkatOptions'] ?? null) ? $shell['tingkatOptions'] : [];

        $paginator = new Paginator([], $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $rowsQuery = $request->query();
        unset($rowsQuery['export']);

        return view('keuangan.saldo.virtual-account', [
            'pageTitle' => 'Saldo Virtual Account',
            'filterOptions' => $filterOptions,
            'tingkatOptions' => $tingkatOptions,
            'filters' => $filters,
            'rowsPaginator' => $paginator,
            'vaRowsUrl' => route('keu.saldo.va.rows', $rowsQuery),
        ]);
    }

    /**
     * @return array{0: bool, 1: list<array<string, mixed>>, 2: string}
     */
    private function virtualAccountExportDataset(Request $request, AmalFatimahApiService $api): array
    {
        $filters = $this->vaFiltersOnly($request);
        $res = $api->getSaldoVirtualAccountRows($filters, 3000, 0);
        if (!$res['ok']) {
            return [false, [], (string) ($res['message'] ?? 'Gagal memuat data.')];
        }
        $rows = $res['data']['rows'] ?? [];

        return [true, is_array($rows) ? $rows : [], ''];
    }

    /**
     * CSV ringkas (maks. 3000 baris) — filter sama dengan halaman utama.
     */
    private function virtualAccountExportCsv(Request $request, AmalFatimahApiService $api): StreamedResponse
    {
        [$ok, $rows, $msg] = $this->virtualAccountExportDataset($request, $api);
        if (!$ok) {
            abort(422, $msg);
        }

        $fn = 'saldo-virtual-account-' . date('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No', 'NIS', 'NO VA', 'NAMA', 'No Pendaftaran', 'Unit', 'Kelas', 'Jenjang', 'Angkatan', 'Saldo'], ';');
            $no = 1;
            foreach ($rows as $r) {
                if (!is_array($r)) {
                    continue;
                }
                fputcsv($out, [
                    $no++,
                    $r['nis'] ?? '',
                    $r['no_va'] ?? '',
                    $r['nama'] ?? '',
                    $r['no_pendaftaran'] ?? '',
                    $r['unit'] ?? '',
                    $r['kelas'] ?? '',
                    $r['jenjang'] ?? '',
                    $r['angkatan'] ?? '',
                    (string) ((int) ($r['saldo'] ?? 0)),
                ], ';');
            }
            fclose($out);
        }, $fn, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function virtualAccountExportJson(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$ok, $rows, $msg] = $this->virtualAccountExportDataset($request, $api);
        if (!$ok) {
            return response()->json(['ok' => false, 'message' => $msg], 422);
        }

        $out = [];
        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $np = trim((string) ($r['no_pendaftaran'] ?? ''));
            $out[] = [
                'nis' => (string) ($r['nis'] ?? ''),
                'no_va' => (string) ($r['no_va'] ?? ''),
                'nama' => (string) ($r['nama'] ?? ''),
                'no_pendaftaran' => $np !== '' ? $np : '-',
                'unit' => (string) ($r['unit'] ?? ''),
                'kelas' => (string) ($r['kelas'] ?? ''),
                'jenjang' => (string) ($r['jenjang'] ?? ''),
                'angkatan' => (string) ($r['angkatan'] ?? ''),
                'saldo' => (int) ($r['saldo'] ?? 0),
            ];
        }

        return response()->json(['ok' => true, 'rows' => $out]);
    }

    private function virtualAccountExportXls(Request $request, AmalFatimahApiService $api): \Illuminate\Http\Response
    {
        [$ok, $rows, $msg] = $this->virtualAccountExportDataset($request, $api);
        if (!$ok) {
            abort(422, $msg);
        }

        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        $buf = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $buf .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $buf .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $buf .= '<Styles><Style ss:ID="hdr"><Font ss:Bold="1"/></Style><Style ss:ID="n"><NumberFormat ss:Format="#,##0"/></Style></Styles>' . "\n";
        $buf .= '<Worksheet ss:Name="Saldo VA"><Table>' . "\n";
        $buf .= '<Column ss:Width="90"/><Column ss:Width="110"/><Column ss:Width="180"/><Column ss:Width="100"/><Column ss:Width="140"/><Column ss:Width="70"/><Column ss:Width="80"/><Column ss:Width="80"/><Column ss:Width="100"/>' . "\n";
        $buf .= '<Row>';
        foreach (['NIS', 'NO VA', 'NAMA', 'NO PENDAFTARAN', 'UNIT', 'KELAS', 'JENJANG', 'ANGKATAN', 'SALDO'] as $h) {
            $buf .= '<Cell ss:StyleID="hdr"><Data ss:Type="String">' . $esc($h) . '</Data></Cell>';
        }
        $buf .= '</Row>' . "\n";

        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $np = trim((string) ($r['no_pendaftaran'] ?? ''));
            $saldo = (int) ($r['saldo'] ?? 0);
            $buf .= '<Row>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['nis'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['no_va'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['nama'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc($np !== '' ? $np : '-') . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['unit'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['kelas'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['jenjang'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['angkatan'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell ss:StyleID="n"><Data ss:Type="Number">' . $saldo . '</Data></Cell>';
            $buf .= '</Row>' . "\n";
        }

        $buf .= '</Table></Worksheet></Workbook>';

        $fn = 'saldo-virtual-account-' . date('Ymd-His') . '.xls';

        return response($buf, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fn . '"',
        ]);
    }

    private function virtualAccountExportPdf(Request $request, AmalFatimahApiService $api): \Illuminate\Http\Response
    {
        [$ok, $rows, $msg] = $this->virtualAccountExportDataset($request, $api);
        if (!$ok) {
            abort(422, $msg);
        }

        $pdf = Pdf::loadView('keuangan.saldo.virtual-account-export-pdf', [
            'rows' => $rows,
            'exportedAt' => now(),
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('saldo-virtual-account-' . date('Ymd-His') . '.pdf');
    }

    private function virtualAccountExportPrint(Request $request, AmalFatimahApiService $api): View
    {
        [$ok, $rows, $msg] = $this->virtualAccountExportDataset($request, $api);
        if (!$ok) {
            abort(422, $msg);
        }

        return view('keuangan.saldo.virtual-account-print', [
            'rows' => $rows,
            'exportedAt' => now(),
        ]);
    }

    public function virtualAccountRows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$filters, $perPage, $page] = $this->vaPagingFromRequest($request);

        $res = $api->getSaldoVirtualAccountRows($filters, $perPage, ($page - 1) * $perPage);
        if (!$res['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Gagal memuat data.',
            ], 422);
        }

        $rows = $res['data']['rows'] ?? [];
        $meta = is_array($res['data']['meta'] ?? null) ? $res['data']['meta'] : [];
        // Utamakan flag WS. Jika tidak tersedia/aneh, fallback: halaman penuh biasanya masih ada data lanjutan.
        $hasMore = array_key_exists('has_more', $meta)
            ? (bool) $meta['has_more']
            : (count($rows) >= $perPage);
        if (!$hasMore && count($rows) >= $perPage && $page === 1) {
            $hasMore = true;
        }

        $n = count($rows);
        $firstItem = $n > 0 ? (($page - 1) * $perPage + 1) : 0;
        $lastItem = $n > 0 ? ($firstItem + $n - 1) : 0;

        $q = $request->query();
        $prevUrl = $page > 1 ? route('keu.saldo.va', array_merge($q, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('keu.saldo.va', array_merge($q, ['page' => $page + 1])) : null;

        return response()->json([
            'ok' => true,
            'rows' => $rows,
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

    public function virtualAccountDetail(Request $request, int $custid): View
    {
        if ($custid <= 0) {
            abort(404);
        }

        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));
        // Jangan pakai ?cari= dari halaman daftar: itu filter NIS/nama siswa, bukan filter baris mutasi.
        // Kalau ikut terbawa, API mutasi memfilter METODE/HELPDESK/NOREFF → sering kosong walau saldo ada.
        $mutasiUrl = route('keu.saldo.va.detail_rows', array_merge(
            ['custid' => $custid],
            array_filter(['per_page' => $perPage, 'page' => $page], static fn ($v) => $v !== '' && $v !== null)
        ));

        return view('keuangan.saldo.virtual-account-detail', [
            'pageTitle' => 'Saldo Virtual Account — Detail',
            'custid' => $custid,
            'mutasiUrl' => $mutasiUrl,
            'perPage' => $perPage,
            'mutasiCari' => '',
        ]);
    }

    public function virtualAccountDetailRows(Request $request, AmalFatimahApiService $api, int $custid): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));
        $cari = trim((string) $request->query('cari', ''));

        $res = $api->getSaldoVirtualAccountMutasi($custid, $cari, $perPage, ($page - 1) * $perPage);
        if (!$res['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Gagal memuat mutasi.',
            ], 422);
        }

        $data = $res['data'];
        $rows = $data['rows'] ?? [];
        $hasMore = count($rows) > $perPage;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $perPage);
        }

        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : ['debet' => 0, 'kredit' => 0, 'saldo' => 0];
        $siswa = is_array($data['siswa'] ?? null) ? $data['siswa'] : [];

        $n = count($rows);
        $firstItem = $n > 0 ? (($page - 1) * $perPage + 1) : 0;
        $lastItem = $n > 0 ? ($firstItem + $n - 1) : 0;

        $q = array_filter([
            'per_page' => $perPage,
            'cari' => $cari,
        ], static fn ($v) => $v !== '' && $v !== null);
        $prevUrl = $page > 1 ? route('keu.saldo.va.detail_rows', array_merge(['custid' => $custid], $q, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('keu.saldo.va.detail_rows', array_merge(['custid' => $custid], $q, ['page' => $page + 1])) : null;

        return response()->json([
            'ok' => true,
            'rows' => $rows,
            'totals' => $totals,
            'siswa' => $siswa,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'first_item' => $firstItem,
            'last_item' => $lastItem,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
            'cari' => $cari,
        ]);
    }

    public function transaksi(Request $request, AmalFatimahApiService $api): View
    {
        [$filters, $perPage, $page, $queryForUrls] = $this->dtListBundle($request);

        $shell = $api->loadRekapPenerimaanShell();
        $filterOptions = $shell['filterOptions'];
        $tingkatOptions = is_array($shell['tingkatOptions'] ?? null) ? $shell['tingkatOptions'] : [];

        return view('keuangan.saldo.data-transaksi', [
            'pageTitle' => 'Data Transaksi',
            'filterOptions' => $filterOptions,
            'tingkatOptions' => $tingkatOptions,
            'filters' => $filters,
            'listPage' => $page,
            'listPerPage' => $perPage,
            'queryForUrls' => $queryForUrls,
            'transaksiRowsUrl' => route('keu.saldo.transaksi.rows', array_merge($queryForUrls, ['page' => $page])),
        ]);
    }

    public function transaksiRows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$filters, $perPage, $page, $queryForUrls] = $this->dtListBundle($request);

        $res = $api->getDataTransaksiSccttran($filters, $perPage, ($page - 1) * $perPage);
        if (!$res['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Gagal memuat data.',
            ], 422);
        }

        $rows = $res['data']['rows'] ?? [];
        $meta = is_array($res['data']['meta'] ?? null) ? $res['data']['meta'] : [];
        // WS sudah hitung has_more pakai limit+1.
        $hasMore = array_key_exists('has_more', $meta)
            ? (bool) $meta['has_more']
            : (count($rows) >= $perPage);

        $outRows = [];
        foreach ($rows as $row) {
            $outRows[] = is_array($row) ? $row : [];
        }

        $n = count($outRows);
        $firstItem = $n > 0 ? (($page - 1) * $perPage + 1) : 0;
        $lastItem = $n > 0 ? ($firstItem + $n - 1) : 0;

        $prevUrl = $page > 1 ? route('keu.saldo.transaksi.rows', array_merge($queryForUrls, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('keu.saldo.transaksi.rows', array_merge($queryForUrls, ['page' => $page + 1])) : null;

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
     * @return array{0: array<string, string>, 1: int, 2: int, 3: array<string, string>}
     */
    private function dtListBundle(Request $request): array
    {
        $filters = $this->dtFiltersForList($request);
        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));

        $queryForUrls = $request->query();
        $queryForUrls['tgl_dari'] = $filters['tgl_dari'];
        $queryForUrls['tgl_sampai'] = $filters['tgl_sampai'];
        unset($queryForUrls['semua_tgl'], $queryForUrls['per_page']);
        if ($page <= 1) {
            unset($queryForUrls['page']);
        }

        return [$filters, $perPage, $page, $queryForUrls];
    }

    /**
     * @return array<string, string>
     */
    private function dtFiltersForList(Request $request): array
    {
        $f = $this->dtFiltersOnly($request);
        return $f;
    }

    /**
     * @return array<string, string>
     */
    private function dtFiltersOnly(Request $request): array
    {
        return [
            'tgl_dari' => trim((string) $request->query('tgl_dari', '')),
            'tgl_sampai' => trim((string) $request->query('tgl_sampai', '')),
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'sekolah' => trim((string) $request->query('sekolah', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'nis' => trim((string) $request->query('nis', '')),
            'nama' => trim((string) $request->query('nama', '')),
            'cari' => trim((string) $request->query('cari', '')),
        ];
    }

    /**
     * @return array{0: array<string, string>, 1: int, 2: int}
     */
    private function vaPagingFromRequest(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        return [$this->vaFiltersOnly($request), $perPage, $page];
    }

    /**
     * @return array<string, string>
     */
    private function vaFiltersOnly(Request $request): array
    {
        return [
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'sekolah' => trim((string) $request->query('sekolah', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'cari' => trim((string) $request->query('cari', '')),
        ];
    }
}
