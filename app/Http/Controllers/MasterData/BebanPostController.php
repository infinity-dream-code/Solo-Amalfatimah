<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BebanPostController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $thnMasuk = trim((string) $request->query('thn_masuk', ''));
        $kodeProd = trim((string) $request->query('kode_prod', ''));
        $kodeAkun = trim((string) $request->query('kode_akun', ''));
        $nominal = trim((string) $request->query('nominal', ''));
        $keyword = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $filters = [
            'thn_masuk' => $thnMasuk !== '' ? $thnMasuk : null,
            'kode_prod' => $kodeProd !== '' ? $kodeProd : null,
            'kode_akun' => $kodeAkun !== '' ? $kodeAkun : null,
            'nominal' => $nominal !== '' ? preg_replace('/\D+/', '', $nominal) : null,
        ];

        $allRows = $api->getBebanPost($filters, 200, 0);
        if ($keyword !== '') {
            $needle = mb_strtolower($keyword);
            $allRows = array_values(array_filter($allRows, static function ($row) use ($needle) {
                $kode = mb_strtolower((string) ($row['kodeakun'] ?? ''));
                $nama = mb_strtolower((string) ($row['namaakun'] ?? ''));
                $nml = mb_strtolower((string) ($row['nominal'] ?? ''));
                return str_contains($kode, $needle) || str_contains($nama, $needle) || str_contains($nml, $needle);
            }));
        }

        $total = count($allRows);
        $offset = ($page - 1) * $perPage;
        $rows = array_slice($allRows, $offset, $perPage);
        $bebanRows = new LengthAwarePaginator($rows, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $filterOptions = $api->getFilterBebanPost();

        return view('master-data.beban-post.index', [
            'pageTitle' => 'Beban Post',
            'bebanRows' => $bebanRows,
            'filterOptions' => $filterOptions,
            'thnMasuk' => $thnMasuk,
            'kodeProd' => $kodeProd,
            'kodeAkun' => $kodeAkun,
            'nominal' => $nominal,
            'keyword' => $keyword,
        ]);
    }

    public function create(AmalFatimahApiService $api): View
    {
        $filterOptions = $api->getFilterBebanPost();
        $thnAkaOptions = is_array($filterOptions['thn_masuk'] ?? null)
            ? $filterOptions['thn_masuk']
            : $api->getThnAka();
        $kelasOptions = array_map(static function ($row) {
            $item = is_array($row) ? array_change_key_case($row, CASE_LOWER) : [];
            return [
                'id' => (string) ($item['id'] ?? ''),
                'kelas' => (string) ($item['kelas'] ?? ''),
                'unit' => (string) ($item['unit'] ?? ''),
                'kelompok' => (string) ($item['kelompok'] ?? ''),
                'jenjang' => (string) ($item['jenjang'] ?? ''),
            ];
        }, $api->getKelas());
        $akunOptions = $api->getAkun();

        return view('master-data.beban-post.create', [
            'pageTitle' => 'Tambah Beban Post',
            'thnAkaOptions' => $thnAkaOptions,
            'kelasOptions' => $kelasOptions,
            'akunOptions' => $akunOptions,
        ]);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'thn_masuk' => ['required', 'string', 'max:50'],
            'kode_prod' => ['required', 'string', 'max:20'],
            'kode_akun' => ['required', 'string', 'max:10'],
            'nominal' => ['required', 'string', 'max:50'],
        ], [
            'thn_masuk.required' => 'Tahun Akademik wajib diisi.',
            'kode_prod.required' => 'Kelas wajib dipilih.',
            'kode_akun.required' => 'Kode Akun wajib dipilih.',
            'nominal.required' => 'Nominal wajib diisi.',
        ]);

        $result = $api->createBebanPost([
            'thn_masuk' => $validated['thn_masuk'],
            'kode_prod' => $validated['kode_prod'],
            'kode_akun' => $validated['kode_akun'],
            'nominal' => preg_replace('/\D+/', '', $validated['nominal']),
        ]);

        if (!($result['ok'] ?? false)) {
            return back()->withInput()->withErrors([
                'api' => $result['message'] ?? 'Gagal menambahkan beban post dari web service.',
            ]);
        }

        return redirect()->route('master.beban_post')->with('status', 'Data Beban Post berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        return view('master-data.beban-post.edit', [
            'pageTitle' => 'Edit Beban Post',
            'id' => $id,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return redirect()->route('master.beban_post')->with('status', "Data Beban Post #{$id} terupdate (dummy).");
    }

    public function destroy(string $id): RedirectResponse
    {
        return redirect()->route('master.beban_post')->with('status', "Data Beban Post #{$id} terhapus (dummy).");
    }
}

