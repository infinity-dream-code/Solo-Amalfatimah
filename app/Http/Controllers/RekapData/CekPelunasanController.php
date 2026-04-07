<?php

namespace App\Http\Controllers\RekapData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class CekPelunasanController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        [$filters, $perPage, $page] = $this->pagingFromRequest($request);
        $bundle = $api->loadPenerimaanFilterShell();
        $filterOptions = $bundle['filterOptions'] ?? [];

        $paginator = new Paginator([], $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('rekap-data.cek-pelunasan.index', [
            'pageTitle' => 'Cek Pelunasan',
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'rowsPaginator' => $paginator,
            'cekPelunasanRowsUrl' => route('rekap.cek_pelunasan.rows', $request->query()),
        ]);
    }

    public function rows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$filters, $perPage, $page] = $this->pagingFromRequest($request);

        $res = $api->getCekPelunasanRows($filters, $perPage, ($page - 1) * $perPage);
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

        $q = $request->query();
        $prevUrl = $page > 1 ? route('rekap.cek_pelunasan', array_merge($q, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('rekap.cek_pelunasan', array_merge($q, ['page' => $page + 1])) : null;

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

    public function printKartuSiswa(Request $request, AmalFatimahApiService $api): Response|RedirectResponse
    {
        $custids = $request->input('custids', []);
        if (!is_array($custids)) {
            $custids = [];
        }
        $custids = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) $v, $custids),
            static fn ($n) => $n > 0
        )));
        if ($custids === []) {
            return redirect()->back()->with('export_error', 'Pilih minimal satu siswa untuk Cetak Kartu Siswa.');
        }

        $res = $api->getCekPelunasanCards($custids);
        if (!$res['ok']) {
            return redirect()->back()->with('export_error', $res['message'] ?? 'Gagal mengambil data kartu siswa.');
        }

        $cards = is_array($res['data']['cards'] ?? null) ? $res['data']['cards'] : [];
        if ($cards === []) {
            return redirect()->back()->with('export_error', 'Tidak ada data kartu siswa untuk pilihan ini.');
        }

        $pdf = Pdf::loadView('keuangan.tagihan-siswa.data-tagihan-kartu-siswa-pdf', [
            'cards' => $cards,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('kartu-siswa-cek-pelunasan-' . date('Ymd-His') . '.pdf');
    }

    /**
     * @return array{0: array<string, string>, 1: int, 2: int}
     */
    private function pagingFromRequest(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $filters = [
            'thn_akademik' => trim((string) $request->query('thn_akademik', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'nis' => trim((string) $request->query('nis', '')),
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'nama' => trim((string) $request->query('nama', '')),
            'nama_tagihan' => trim((string) $request->query('nama_tagihan', '')),
            'cari' => trim((string) $request->query('cari', '')),
        ];

        return [$filters, $perPage, $page];
    }
}

