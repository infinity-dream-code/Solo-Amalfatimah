<?php

namespace App\Http\Controllers\ManualInput;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditManualController extends Controller
{
    public function index(AmalFatimahApiService $api): View
    {
        $fp = $api->getFilterBebanPost();
        $akunRaw = is_array($fp['akun'] ?? null) ? $fp['akun'] : [];
        $akunOptions = [];
        foreach ($akunRaw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $r = array_change_key_case($row, CASE_LOWER);
            $k = trim((string) ($r['kodeakun'] ?? ''));
            $n = trim((string) ($r['namaakun'] ?? ''));
            if ($k === '') {
                continue;
            }
            $akunOptions[] = [
                'kode' => $k,
                'label' => $n !== '' ? $n . ' — ' . $k : $k,
            ];
        }

        return view('manual-input.edit-manual.index', [
            'pageTitle' => 'Edit Detail Post Manual',
            'siswaRowsUrl' => route('manual_input.edit_manual.siswa_rows'),
            'billsUrl' => route('manual_input.edit_manual.bills'),
            'billDetailUrl' => route('manual_input.edit_manual.bill_detail'),
            'saveBillDetailUrl' => route('manual_input.edit_manual.save_bill_detail'),
            'akunOptions' => $akunOptions,
        ]);
    }

    public function siswaRows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([
                'ok' => true,
                'rows' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 10,
                'prev_url' => null,
                'next_url' => null,
            ]);
        }

        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $filters = ['search' => $q];
        $total = $api->getSiswaCount($filters);
        $offset = ($page - 1) * $perPage;
        $raw = $api->getSiswa($filters, $perPage, $offset);

        $rows = [];
        foreach ($raw as $r) {
            if (!is_array($r)) {
                continue;
            }
            $custid = (int) ($r['custid'] ?? 0);
            $kelas = trim((string) ($r['desc02'] ?? ''));
            if ($kelas === '') {
                $kelas = trim((string) ($r['desc03'] ?? ''));
            }
            $rows[] = [
                'custid' => $custid,
                'nis' => trim((string) ($r['nocust'] ?? '')),
                'nama' => trim((string) ($r['nmcust'] ?? '')),
                'kelas' => $kelas !== '' ? $kelas : '-',
                'jenjang' => trim((string) ($r['desc01'] ?? '')) !== '' ? trim((string) ($r['desc01'] ?? '')) : '-',
                'angkatan' => trim((string) ($r['desc04'] ?? '')) !== '' ? trim((string) ($r['desc04'] ?? '')) : '-',
            ];
        }

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $baseQ = array_filter([
            'q' => $q,
            'per_page' => $perPage,
        ], static fn ($v) => $v !== '' && $v !== null);

        $prevUrl = $page > 1
            ? route('manual_input.edit_manual.siswa_rows', array_merge($baseQ, ['page' => $page - 1]))
            : null;
        $nextUrl = $page < $lastPage
            ? route('manual_input.edit_manual.siswa_rows', array_merge($baseQ, ['page' => $page + 1]))
            : null;

        return response()->json([
            'ok' => true,
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
        ]);
    }

    public function bills(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $custid = (int) $request->query('custid', 0);
        if ($custid <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'custid tidak valid',
                'unpaid' => [],
                'paid' => [],
            ], 422);
        }

        $data = $api->getEditManualBillsByCustid($custid);
        if (!empty($data['error'])) {
            return response()->json([
                'ok' => false,
                'message' => $data['error'],
                'unpaid' => [],
                'paid' => [],
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'unpaid' => $data['unpaid'] ?? [],
            'paid' => $data['paid'] ?? [],
        ]);
    }

    public function billDetail(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $custid = (int) $request->query('custid', 0);
        $billcd = trim((string) $request->query('billcd', ''));
        if ($custid <= 0 || $billcd === '') {
            return response()->json([
                'ok' => false,
                'message' => 'custid dan billcd wajib diisi',
            ], 422);
        }

        $data = $api->getEditManualBillDetailRows($custid, $billcd);
        if (!empty($data['error'])) {
            return response()->json([
                'ok' => false,
                'message' => $data['error'],
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'paidst' => (int) ($data['paidst'] ?? 0),
            'bill_aa' => (int) ($data['bill_aa'] ?? 0),
            'lines' => $data['lines'] ?? [],
        ]);
    }

    public function saveBillDetail(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $custid = (int) $request->input('custid', 0);
        $billcd = trim((string) $request->input('billcd', ''));
        $raw = $request->input('lines', []);
        if (!is_array($raw)) {
            $raw = [];
        }

        $lines = [];
        foreach ($raw as $ln) {
            if (!is_array($ln)) {
                continue;
            }
            $kp = trim((string) ($ln['kode_post'] ?? ''));
            $am = (int) ($ln['billam'] ?? 0);
            if ($kp === '' && $am <= 0) {
                continue;
            }
            $lines[] = ['kode_post' => $kp, 'billam' => $am];
        }

        if ($custid <= 0 || $billcd === '') {
            return response()->json([
                'ok' => false,
                'message' => 'custid dan billcd wajib diisi',
            ], 422);
        }

        $res = $api->saveEditManualBillDetail($custid, $billcd, $lines);

        return response()->json([
            'ok' => $res['ok'],
            'message' => $res['message'] ?? '',
            'billam' => (int) ($res['billam'] ?? 0),
        ], $res['ok'] ? 200 : 422);
    }
}
