<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PindahKelasController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $kelasSumber = (int) $request->query('kelas_sumber', 0);
        $kelasTujuan = (int) $request->query('kelas_tujuan', 0);
        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $kelasRows = array_map(static fn ($r) => is_array($r) ? array_change_key_case($r, CASE_LOWER) : [], $api->getKelas());
        $siswaRows = [];
        $total = 0;
        $error = '';
        if ($kelasSumber > 0 && $kelasTujuan > 0 && $kelasSumber === $kelasTujuan) {
            $error = 'Kelas asal dan kelas tujuan tidak boleh sama.';
        } elseif ($kelasSumber > 0 || $search !== '') {
            $res = $api->getSiswaByKelas($kelasSumber, $search !== '' ? $search : null, $perPage, $offset);
            if ($res['ok']) {
                $siswaRows = $res['rows'];
                $total = (int) $res['total'];
            } else {
                $error = $res['message'];
            }
        }

        $paginator = new LengthAwarePaginator(
            $siswaRows,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('master-data.pindah-kelas.index', [
            'pageTitle' => 'Pindah Kelas',
            'kelasRows' => $kelasRows,
            'kelasSumber' => $kelasSumber,
            'kelasTujuan' => $kelasTujuan,
            'search' => $search,
            'siswaRows' => $paginator,
            'errorMsg' => $error,
        ]);
    }

    public function siswaOptions(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $kelasSumber = (int) $request->query('kelas_sumber', 0);
        if ($q === '' && $kelasSumber <= 0) {
            return response()->json([]);
        }

        $res = $api->getSiswaByKelas($kelasSumber, $q !== '' ? $q : null, 40, 0);
        if (!$res['ok']) {
            return response()->json([]);
        }

        $out = [];
        $seen = [];
        foreach ($res['rows'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $nis = trim((string) ($row['nocust'] ?? ''));
            $nama = trim((string) ($row['nmcust'] ?? ''));
            $value = $nis !== '' ? $nis : $nama;
            if ($value === '' || isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $text = $nis !== '' && $nama !== '' ? ($nis . ' - ' . $nama) : ($nama !== '' ? $nama : $nis);
            $out[] = ['value' => $value, 'text' => $text];
        }

        return response()->json($out);
    }

    public function create(): View
    {
        return view('master-data.pindah-kelas.create', ['pageTitle' => 'Tambah Pindah Kelas']);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_sumber' => ['nullable', 'integer', 'min:0'],
            'kelas_tujuan' => ['required', 'integer', 'min:1'],
            'mode' => ['required', 'in:semua,pilihan'],
            'custids' => ['nullable', 'array'],
            'custids.*' => ['integer', 'min:1'],
            'search' => ['nullable', 'string'],
        ]);

        $custids = array_values(array_unique(array_filter(
            array_map('intval', (array) ($validated['custids'] ?? [])),
            static fn (int $v): bool => $v > 0
        )));
        $kelasSumber = (int) ($validated['kelas_sumber'] ?? 0);
        $kelasTujuan = (int) $validated['kelas_tujuan'];
        $mode = (string) $validated['mode'];
        $redirectQuery = $request->only(['kelas_sumber', 'kelas_tujuan', 'search']);

        if ($kelasSumber > 0 && $kelasSumber === $kelasTujuan) {
            return redirect()->route('master.pindah_kelas', $redirectQuery)
                ->with('error', 'Kelas asal dan kelas tujuan tidak boleh sama.');
        }

        if ($mode === 'semua' && $kelasSumber <= 0) {
            return redirect()->route('master.pindah_kelas', $redirectQuery)
                ->with('error', 'Pindah jamak membutuhkan kelas asal.');
        }

        if ($mode === 'pilihan' && $custids === []) {
            return redirect()->route('master.pindah_kelas', $redirectQuery)
                ->with('error', 'Pindah parsial: centang minimal satu siswa.');
        }

        $res = $api->pindahKelas($kelasSumber, $kelasTujuan, $mode, $custids);
        if (!$res['ok']) {
            return redirect()->route('master.pindah_kelas', $redirectQuery)->with('error', $res['message']);
        }
        $totalDipindah = (int) (($res['data']['total_dipindah'] ?? 0));
        $label = $mode === 'semua' ? 'jamak' : 'parsial';

        return redirect()->route('master.pindah_kelas', $redirectQuery)
            ->with('status', "Pindah {$label} berhasil. Total dipindah: {$totalDipindah}");
    }

    public function edit(string $id): View
    {
        return view('master-data.pindah-kelas.edit', [
            'pageTitle' => 'Edit Pindah Kelas',
            'id' => $id,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return redirect()->route('master.pindah_kelas');
    }

    public function destroy(string $id): RedirectResponse
    {
        return redirect()->route('master.pindah_kelas');
    }
}

