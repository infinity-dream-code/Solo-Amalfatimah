<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class LainnyaController extends Controller
{
    public function hapusTagihan(Request $request, AmalFatimahApiService $api): View
    {
        [$filters, $perPage, $page] = $this->hapusTagihanPagingFromRequest($request);

        $bundle = $api->loadPenerimaanFilterShell();
        $filterOptions = $bundle['filterOptions'];

        $paginator = new Paginator([], $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('keuangan.lainnya.hapus-tagihan', [
            'pageTitle' => 'Hapus Tagihan Siswa',
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'rowsPaginator' => $paginator,
            'hapusTagihanRowsUrl' => route('keu.hapus_tagihan.rows', $request->query()),
        ]);
    }

    public function hapusTagihanRows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$filters, $perPage, $page] = $this->hapusTagihanPagingFromRequest($request);

        $res = $api->getHapusTagihanRows($filters, $perPage, ($page - 1) * $perPage);
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

        $n = count($rows);
        $firstItem = $n > 0 ? (($page - 1) * $perPage + 1) : 0;
        $lastItem = $n > 0 ? ($firstItem + $n - 1) : 0;

        $q = $request->query();
        $prevUrl = $page > 1 ? route('keu.hapus_tagihan', array_merge($q, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('keu.hapus_tagihan', array_merge($q, ['page' => $page + 1])) : null;

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

    public function hapusTagihanSubmit(Request $request, AmalFatimahApiService $api): RedirectResponse|JsonResponse
    {
        $raw = $request->input('items', []);
        if (!is_array($raw)) {
            $raw = [];
        }

        $items = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cid = (int) ($row['custid'] ?? 0);
            $bcd = trim((string) ($row['billcd'] ?? ''));
            if ($cid > 0 && $bcd !== '') {
                $items[] = ['custid' => $cid, 'billcd' => $bcd];
            }
        }

        $res = $api->hapusTagihanSiswaBatch($items);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => $res['ok'],
                'message' => $res['message'] ?? '',
                'data' => $res['data'] ?? [],
            ], $res['ok'] ? 200 : 422);
        }

        if (!$res['ok']) {
            return redirect()->back()->with('hapus_error', $res['message'] ?? 'Gagal menghapus tagihan.');
        }

        $deleted = (int) ($res['data']['deleted'] ?? 0);
        $failed = $res['data']['failed'] ?? [];
        $msg = 'Berhasil menghapus ' . $deleted . ' tagihan.';
        if (is_array($failed) && $failed !== []) {
            $msg .= ' ' . count($failed) . ' baris tidak bisa dihapus (sudah lunas / tidak ditemukan).';
        }

        return redirect()->back()->with('hapus_ok', $msg);
    }

    /**
     * @return array{0: array<string, string>, 1: int, 2: int}
     */
    private function hapusTagihanPagingFromRequest(Request $request): array
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
            'cari' => trim((string) $request->query('cari', '')),
        ];

        return [$filters, $perPage, $page];
    }

    public function biayaAdmin(Request $request): View
    {
        [$filters, $perPage, $page] = $this->biayaAdminPagingFromRequest($request);

        $paginator = new Paginator([], $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('keuangan.lainnya.data-biaya-admin', [
            'pageTitle' => 'Data Biaya Admin',
            'filters' => $filters,
            'rowsPaginator' => $paginator,
            'biayaAdminRowsUrl' => route('keu.biaya_admin.rows', $request->query()),
        ]);
    }

    public function biayaAdminRows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$filters, $perPage, $page] = $this->biayaAdminPagingFromRequest($request);
        $totalHint = max(0, (int) $request->query('total_filtered', 0));
        $needTotalCount = $totalHint <= 0;

        $res = $api->getDataBiayaAdminRows($filters, $perPage, ($page - 1) * $perPage, $needTotalCount);
        if (!$res['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Gagal memuat data.',
            ], 422);
        }

        $rows = $res['data']['rows'] ?? [];
        $meta = is_array($res['data']['meta'] ?? null) ? $res['data']['meta'] : [];
        $hasMore = (bool) ($meta['has_more'] ?? false);

        $n = count($rows);
        $firstItem = $n > 0 ? (($page - 1) * $perPage + 1) : 0;
        $lastItem = $n > 0 ? ($firstItem + $n - 1) : 0;
        $totalFiltered = $needTotalCount
            ? (int) ($meta['total_filtered'] ?? $n)
            : $totalHint;
        $totalNominalAll = $totalFiltered * 2000;

        $q = $request->query();
        $q['total_filtered'] = $totalFiltered;
        $prevUrl = $page > 1 ? route('keu.biaya_admin.rows', array_merge($q, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('keu.biaya_admin.rows', array_merge($q, ['page' => $page + 1])) : null;

        return response()->json([
            'ok' => true,
            'rows' => $rows,
            'meta' => $meta,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'first_item' => $firstItem,
            'last_item' => $lastItem,
            'total_filtered' => $totalFiltered,
            'total_nominal_all' => $totalNominalAll,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
        ]);
    }

    /**
     * @return array{0: array<string, string>, 1: int, 2: int}
     */
    private function biayaAdminPagingFromRequest(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'tgl_dari' => trim((string) $request->query('tgl_dari', '')),
            'tgl_sampai' => trim((string) $request->query('tgl_sampai', '')),
            'cari' => trim((string) $request->query('cari', '')),
        ];

        return [$filters, $perPage, $page];
    }
}
