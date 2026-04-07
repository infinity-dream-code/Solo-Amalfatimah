<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class MasterPostController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $rows = $api->getAkun($keyword !== '' ? $keyword : null);

        $perPage = 10;
        $currentPage = max(1, (int) $request->query('page', 1));
        $total = count($rows);
        $offset = ($currentPage - 1) * $perPage;
        $pageItems = array_slice($rows, $offset, $perPage);

        $postRows = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('master-data.master-post.index', [
            'pageTitle' => 'Master Post',
            'postRows' => $postRows,
            'keyword' => $keyword,
        ]);
    }

    public function create(): View
    {
        return view('master-data.master-post.create', [
            'pageTitle' => 'Tambah Master Post',
        ]);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'kodeakun' => ['required', 'string', 'max:5'],
            'namaakun' => ['required', 'string', 'max:150'],
            'norek' => ['nullable', 'string', 'max:100'],
        ], [
            'kodeakun.required' => 'Kode wajib diisi.',
            'kodeakun.max' => 'Kode maksimal 5 karakter.',
            'namaakun.required' => 'Nama Post wajib diisi.',
        ]);

        $result = $api->createAkun($validated);

        if (!($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['api' => $result['message'] ?? 'Gagal menambahkan master post.']);
        }

        return redirect()->route('master.post')->with('status', 'Data Master Post berhasil ditambahkan.');
    }

}

