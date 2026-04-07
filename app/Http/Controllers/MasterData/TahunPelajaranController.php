<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class TahunPelajaranController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $rows = $api->getThnAka($keyword !== '' ? $keyword : null);

        $perPage = 10;
        $currentPage = max(1, (int) $request->query('page', 1));
        $total = count($rows);
        $offset = ($currentPage - 1) * $perPage;
        $pageItems = array_slice($rows, $offset, $perPage);

        $tahunRows = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('master-data.tahun-pelajaran.index', [
            'pageTitle' => 'Tahun Pelajaran',
            'tahunRows' => $tahunRows,
            'keyword' => $keyword,
        ]);
    }

    public function create(): View
    {
        return view('master-data.tahun-pelajaran.create', [
            'pageTitle' => 'Tambah Tahun Pelajaran',
        ]);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'thn_aka' => ['required', 'string', 'max:50'],
        ], [
            'thn_aka.required' => 'Tahun Pelajaran wajib diisi.',
        ]);

        $result = $api->createThnAka($validated['thn_aka']);
        if (!($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['api' => $result['message'] ?? 'Gagal menambahkan tahun pelajaran.']);
        }

        return redirect()->route('master.tahun_pelajaran')->with('status', 'Data Tahun Pelajaran berhasil ditambahkan.');
    }
}

