<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataSiswaController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));
        $ui = $this->extractUiFilters($request);
        $filters = $this->toWsFilters($ui);

        // Fallback aman: ambil dataset hasil filter lalu paginate di Laravel.
        // Ini menghindari kasus total count dari WS tidak tersedia.
        $allRows = $api->getSiswa($filters, 200, 0);
        $total = count($allRows);
        $offset = ($page - 1) * $perPage;
        $rows = array_slice($allRows, $offset, $perPage);

        $siswaRows = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $filterOptions = $api->getFilterSiswa();

        return view('master-data.data-siswa.index', [
            'pageTitle' => 'Data Siswa',
            'siswaRows' => $siswaRows,
            'filterOptions' => $filterOptions,
            'angkatan' => $ui['angkatan'],
            'sekolah' => $ui['sekolah'],
            'kelas' => $ui['kelas'],
            'siswa' => $ui['siswa'],
            'keyword' => $ui['q'],
        ]);
    }

    public function exportExcel(Request $request, AmalFatimahApiService $api): StreamedResponse
    {
        $rows = $api->getSiswa($this->toWsFilters($this->extractUiFilters($request)), 200, 0);

        $filename = 'data-siswa-' . now()->format('Ymd-His') . '.xls';
        $headers = [
            'No',
            'NIS',
            'NO VA',
            'NAMA',
            'NO PENDAFTARAN',
            'UNIT',
            'KELAS',
            'JENJANG',
            'ANGKATAN',
        ];

        $callback = static function () use ($rows, $headers): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, implode("\t", $headers) . PHP_EOL);

            foreach ($rows as $index => $row) {
                $digits = preg_replace('/\D+/', '', (string) ($row['nocust'] ?? ''));
                $noVa = '7510050' . ($digits !== '' ? $digits : '0');
                $line = [
                    (string) ($index + 1),
                    (string) ($row['nocust'] ?? ''),
                    $noVa,
                    (string) ($row['nmcust'] ?? ''),
                    (string) ($row['num2nd'] ?? ''),
                    (string) ($row['code02'] ?? ''),
                    (string) ($row['desc02'] ?? ''),
                    (string) ($row['desc03'] ?? ''),
                    (string) ($row['desc04'] ?? ''),
                ];
                fwrite($output, implode("\t", array_map(static fn ($v) => str_replace(["\r", "\n", "\t"], ' ', $v), $line)) . PHP_EOL);
            }

            fclose($output);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request, AmalFatimahApiService $api): View
    {
        $ui = $this->extractUiFilters($request);
        $rows = $api->getSiswa($this->toWsFilters($ui), 200, 0);

        return view('master-data.data-siswa.export-pdf', [
            'rows' => $rows,
            'filters' => $ui,
            'printedAt' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    public function create(): View
    {
        return view('master-data.data-siswa.create', [
            'pageTitle' => 'Tambah Data Siswa',
        ]);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:50'],
            'nama' => ['required', 'string', 'max:200'],
        ], [
            'nis.required' => 'NIS wajib diisi.',
            'nama.required' => 'Nama wajib diisi.',
        ]);

        $result = $api->createSiswa([
            'nis' => $validated['nis'],
            'nama' => $validated['nama'],
        ]);

        if (!($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['api' => $result['message'] ?? 'Gagal menyimpan data siswa.']);
        }

        return redirect()->route('master.data_siswa')->with('status', 'Data siswa berhasil disimpan.');
    }

    public function edit(string $id): View
    {
        return view('master-data.data-siswa.edit', [
            'pageTitle' => 'Edit Data Siswa',
            'id' => $id,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return redirect()->route('master.data_siswa')->with('status', "Data Siswa #{$id} terupdate (dummy).");
    }

    public function destroy(string $id): RedirectResponse
    {
        return redirect()->route('master.data_siswa')->with('status', "Data Siswa #{$id} terhapus (dummy).");
    }

    private function extractUiFilters(Request $request): array
    {
        return [
            'angkatan' => trim((string) $request->query('angkatan', '')),
            'sekolah' => trim((string) $request->query('sekolah', '')),
            'kelas' => trim((string) $request->query('kelas', '')),
            'siswa' => trim((string) $request->query('siswa', '')),
            'q' => trim((string) $request->query('q', '')),
        ];
    }

    private function toWsFilters(array $ui): array
    {
        $search = $ui['q'] !== '' ? $ui['q'] : $ui['siswa'];
        $search = $search !== '' ? $search : null;

        return [
            'search' => $search,
            'desc04' => $ui['angkatan'] !== '' ? $ui['angkatan'] : null,
            'code02' => $ui['sekolah'] !== '' ? $ui['sekolah'] : null,
            'desc02' => $ui['kelas'] !== '' ? $ui['kelas'] : null,
        ];
    }
}
