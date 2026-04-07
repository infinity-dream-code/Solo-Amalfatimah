<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
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
        $mode = trim((string) $request->query('mode', 'pilihan'));
        if (!in_array($mode, ['pilihan', 'semua'], true)) {
            $mode = 'pilihan';
        }
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $kelasRows = array_map(static fn ($r) => is_array($r) ? array_change_key_case($r, CASE_LOWER) : [], $api->getKelas());
        $siswaRows = [];
        $total = 0;
        $error = '';
        if ($kelasSumber > 0 && $kelasTujuan > 0 && $kelasSumber === $kelasTujuan) {
            $error = 'Kelas asal dan kelas tujuan tidak boleh sama.';
        } elseif ($kelasSumber > 0) {
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
            'mode' => $mode,
            'siswaRows' => $paginator,
            'errorMsg' => $error,
        ]);
    }

    public function create(): View
    {
        return view('master-data.pindah-kelas.create', ['pageTitle' => 'Tambah Pindah Kelas']);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_sumber' => ['required', 'integer', 'min:1'],
            'kelas_tujuan' => ['required', 'integer', 'min:1', 'different:kelas_sumber'],
            'mode' => ['required', 'in:pilihan,semua'],
            'custids' => ['nullable', 'array'],
            'custids.*' => ['integer', 'min:1'],
            'search' => ['nullable', 'string'],
        ], [
            'kelas_tujuan.different' => 'Kelas asal dan kelas tujuan tidak boleh sama.',
        ]);

        $mode = (string) $validated['mode'];
        $custids = $mode === 'pilihan' ? (array) ($validated['custids'] ?? []) : [];
        if ($mode === 'pilihan' && count($custids) === 0) {
            return back()->withErrors(['custids' => 'Pilih minimal satu siswa.'])->withInput();
        }

        $res = $api->pindahKelas((int) $validated['kelas_sumber'], (int) $validated['kelas_tujuan'], $mode, $custids);
        if (!$res['ok']) {
            return redirect()->route('master.pindah_kelas', $request->only(['kelas_sumber', 'kelas_tujuan', 'search', 'mode']))->with('error', $res['message']);
        }
        $totalDipindah = (int) (($res['data']['total_dipindah'] ?? 0));
        return redirect()->route('master.pindah_kelas', $request->only(['kelas_sumber', 'kelas_tujuan', 'search', 'mode']))
            ->with('status', "Pemindahan kelas berhasil. Total dipindah: {$totalDipindah}");
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

