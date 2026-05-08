<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class MasterSekolahController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $rows = $api->getSekolah();

        if ($keyword !== '') {
            $keywordLower = mb_strtolower($keyword);
            $rows = array_values(array_filter($rows, static function ($row) use ($keywordLower) {
                $code01 = mb_strtolower((string) ($row['code01'] ?? ''));
                $desc01 = mb_strtolower((string) ($row['desc01'] ?? ''));

                return str_contains($code01, $keywordLower)
                    || str_contains($desc01, $keywordLower);
            }));
        }

        $perPage = 10;
        $currentPage = max(1, (int) $request->query('page', 1));
        $total = count($rows);
        $offset = ($currentPage - 1) * $perPage;
        $pageItems = array_slice($rows, $offset, $perPage);

        $sekolahRows = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('master-data.master-sekolah.index', [
            'pageTitle' => 'Master Sekolah',
            'sekolahRows' => $sekolahRows,
            'keyword' => $keyword,
        ]);
    }

    public function create(): View
    {
        return view('master-data.master-sekolah.create', [
            'pageTitle' => 'Tambah Master Sekolah',
        ]);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'desc01' => ['required', 'string', 'max:150'],
        ], [
            'desc01.required' => 'Unit wajib diisi.',
        ]);

        $result = $api->createSekolah([
            ...$validated,
            'code02' => '',
            'desc02' => '',
        ]);
        if (!($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['api' => $result['message'] ?? 'Gagal menambahkan data sekolah.']);
        }

        return redirect()->route('master.sekolah')->with('status', 'Data Master Sekolah berhasil ditambahkan.');
    }

    public function edit(string $id, AmalFatimahApiService $api): View|RedirectResponse
    {
        $sekolahId = (int) $id;
        if ($sekolahId <= 0) {
            return redirect()->route('master.sekolah')->with('status', 'ID sekolah tidak valid.');
        }

        $row = $api->getSekolahById($sekolahId);
        if ($row === []) {
            return redirect()->route('master.sekolah')->with('status', 'Data sekolah tidak ditemukan.');
        }

        return view('master-data.master-sekolah.edit', [
            'pageTitle' => 'Edit Master Sekolah',
            'row' => $row,
        ]);
    }

    public function update(Request $request, string $id, AmalFatimahApiService $api): RedirectResponse
    {
        $sekolahId = (int) $id;
        if ($sekolahId <= 0) {
            return redirect()->route('master.sekolah')->with('status', 'ID sekolah tidak valid.');
        }

        $existing = $api->getSekolahById($sekolahId);
        if ($existing === []) {
            return redirect()->route('master.sekolah')->with('status', 'Data sekolah tidak ditemukan.');
        }

        $validated = $request->validate([
            'desc01' => ['required', 'string', 'max:150'],
        ], [
            'desc01.required' => 'Unit wajib diisi.',
        ]);

        $result = $api->updateSekolah([
            'id' => $sekolahId,
            'code01' => (string) ($existing['code01'] ?? ''),
            ...$validated,
            'code02' => '',
            'desc02' => '',
        ]);

        if (!($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['api' => $result['message'] ?? 'Gagal mengupdate data sekolah.']);
        }

        return redirect()->route('master.sekolah')->with('status', "Data Master Sekolah #{$sekolahId} berhasil diupdate.");
    }

    public function destroy(string $id, AmalFatimahApiService $api): RedirectResponse
    {
        $sekolahId = (int) $id;
        if ($sekolahId <= 0) {
            return redirect()->route('master.sekolah')->with('status', 'ID sekolah tidak valid.');
        }

        $deleted = $api->deleteSekolah($sekolahId);
        if (!$deleted) {
            return redirect()->route('master.sekolah')->with('status', "Gagal menghapus data Master Sekolah #{$id}.");
        }

        return redirect()->route('master.sekolah')->with('status', "Data Master Sekolah #{$id} berhasil dihapus.");
    }
}

