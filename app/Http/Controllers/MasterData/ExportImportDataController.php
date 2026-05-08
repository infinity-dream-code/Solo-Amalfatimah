<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExportImportDataController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));
        $keyword = trim((string) $request->query('q', ''));
        // Preview data hanya tampil sekali setelah import.
        $allRows = $request->session()->pull('import_preview_rows', []);
        if (!is_array($allRows)) {
            $allRows = [];
        }
        if (count($allRows) === 0) {
            $storedPath = session('import_preview_file');
            if (is_string($storedPath) && $storedPath !== '' && Storage::exists($storedPath)) {
                Storage::delete($storedPath);
            }
            session()->forget('import_preview_file');
            session()->forget('import_preview_filename');
        }
        if ($keyword !== '') {
            $needle = mb_strtolower($keyword);
            $allRows = array_values(array_filter($allRows, static function ($row) use ($needle) {
                if (!is_array($row)) {
                    return false;
                }
                $line = mb_strtolower(implode(' ', array_map(static fn ($v) => (string) $v, $row)));
                return str_contains($line, $needle);
            }));
        }
        $total = count($allRows);
        $offset = ($page - 1) * $perPage;
        $rows = array_slice($allRows, $offset, $perPage);

        $importRows = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('master-data.export-import-data.index', [
            'pageTitle' => 'Export Import Data',
            'importRows' => $importRows,
            'keyword' => $keyword,
            'perPage' => $perPage,
        ]);
    }

    public function export(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        return redirect()
            ->route('master.export_import', $request->query())
            ->with('error', 'Fitur export dinonaktifkan di halaman ini.');
    }

    public function import(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:1024'],
            'preview_rows' => ['nullable', 'string'],
        ], [
            'file.required' => 'File wajib dipilih.',
            'file.mimes' => 'File harus berformat XLSX.',
            'file.max' => 'Ukuran file maksimal 1MB.',
        ]);

        $file = $validated['file'];
        $previewRows = $this->decodePreviewRows((string) ($validated['preview_rows'] ?? ''));
        $storedPath = $file->storeAs(
            'tmp-imports',
            'import-siswa-' . now()->format('YmdHis') . '-' . uniqid() . '.' . $file->getClientOriginalExtension()
        );

        $oldPath = session('import_preview_file');
        if (is_string($oldPath) && $oldPath !== '' && Storage::exists($oldPath)) {
            Storage::delete($oldPath);
        }

        session([
            'import_preview_rows' => $previewRows,
            'import_preview_file' => $storedPath,
            'import_preview_filename' => $file->getClientOriginalName(),
        ]);

        return redirect()
            ->route('master.export_import')
            ->with('status', 'File import berhasil dibaca. Klik "Simpan Data" untuk lanjut simpan.');
    }

    public function save(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $storedPath = session('import_preview_file');
        $originalName = (string) session('import_preview_filename', 'import.xlsx');

        if (!is_string($storedPath) || $storedPath === '' || !Storage::exists($storedPath)) {
            return redirect()
                ->route('master.export_import')
                ->with('error', 'File import belum ada. Silakan pilih file import terlebih dahulu.');
        }

        $absolutePath = Storage::path($storedPath);
        $result = $api->importSiswaByFilePath($absolutePath, $originalName);

        if (!($result['ok'] ?? false)) {
            Log::error('[Import Siswa] Request ke WS gagal', [
                'file' => $originalName,
                'stored_path' => $storedPath,
                'result' => $result,
            ]);
            return redirect()
                ->route('master.export_import')
                ->with('error', (string) ($result['message'] ?? 'Gagal simpan data. Silakan coba lagi.'));
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $errorRows = is_array($data['errors'] ?? null) ? $data['errors'] : [];
        $errorCount = count($errorRows);
        $summary = sprintf(
            'Simpan data selesai. Insert: %d, Update: %d, Skip: %d, Error: %d.',
            (int) ($data['inserted'] ?? 0),
            (int) ($data['updated'] ?? 0),
            (int) ($data['skipped'] ?? 0),
            $errorCount
        );

        if ($errorCount > 0) {
            $sampleErrors = array_slice($errorRows, 0, 3);
            Log::warning('[Import Siswa] WS mengembalikan error per baris', [
                'file' => $originalName,
                'inserted' => (int) ($data['inserted'] ?? 0),
                'updated' => (int) ($data['updated'] ?? 0),
                'skipped' => (int) ($data['skipped'] ?? 0),
                'error_count' => $errorCount,
                'sample_errors' => $sampleErrors,
            ]);

            $first = $sampleErrors[0] ?? null;
            $firstNis = is_array($first) ? (string) ($first['nis'] ?? '-') : '-';
            $firstErr = is_array($first) ? (string) ($first['error'] ?? 'Error tidak diketahui') : 'Error tidak diketahui';
            $summary .= " Contoh error [NIS {$firstNis}]: {$firstErr}";
        }

        Storage::delete($storedPath);
        session()->forget(['import_preview_rows', 'import_preview_file', 'import_preview_filename']);

        return redirect()
            ->route('master.export_import')
            ->with('status', $summary);
    }

    public function clear(): RedirectResponse
    {
        $storedPath = session('import_preview_file');
        if (is_string($storedPath) && $storedPath !== '' && Storage::exists($storedPath)) {
            Storage::delete($storedPath);
        }

        session()->forget('import_preview_rows');
        session()->forget('import_preview_file');
        session()->forget('import_preview_filename');
        return redirect()->route('master.export_import')->with('status', 'Data import di tabel berhasil dibersihkan.');
    }

    private function decodePreviewRows(string $payload): array
    {
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($row) {
            if (!is_array($row)) {
                return null;
            }

            return [
                'nis' => trim((string) ($row['nis'] ?? '')),
                'nodaf' => trim((string) ($row['nodaf'] ?? '')),
                'nama' => trim((string) ($row['nama'] ?? '')),
                'unit' => trim((string) ($row['unit'] ?? '')),
                'kelas' => trim((string) ($row['kelas'] ?? '')),
                'kelompok' => trim((string) ($row['kelompok'] ?? '')),
                'angkatan' => trim((string) ($row['angkatan'] ?? '')),
                'gender' => trim((string) ($row['gender'] ?? '')),
                'alamat' => trim((string) ($row['alamat'] ?? '')),
            ];
        }, $decoded), static fn ($row) => is_array($row) && (($row['nis'] ?? '') !== '' || ($row['nama'] ?? '') !== '')));
    }
}

