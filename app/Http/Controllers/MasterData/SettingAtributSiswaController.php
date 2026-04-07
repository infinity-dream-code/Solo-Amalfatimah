<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingAtributSiswaController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));
        $keyword = trim((string) $request->query('q', ''));

        // Tabel hanya menampilkan data hasil import (preview), bukan data existing.
        // Dipull agar setelah refresh kembali kosong seperti behavior Export Import Data.
        $allRows = $request->session()->pull('setting_atribut_preview_rows', []);
        if (!is_array($allRows)) {
            $allRows = [];
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
        $paginator = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('master-data.setting-atribut-siswa.index', [
            'pageTitle' => 'Setting Atribut Siswa',
            'rows' => $paginator,
            'keyword' => $keyword,
            'perPage' => $perPage,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:1024'],
            'preview_rows' => ['nullable', 'string'],
        ], [
            'file.required' => 'File wajib dipilih.',
            'file.mimes' => 'File harus berformat XLS/XLSX/CSV.',
            'file.max' => 'Ukuran file maksimal 1MB.',
        ]);

        $file = $validated['file'];
        $previewRows = $this->decodePreviewRows((string) ($validated['preview_rows'] ?? ''));
        $storedPath = $file->storeAs(
            'tmp-imports',
            'setting-atribut-' . now()->format('YmdHis') . '-' . uniqid() . '.' . $file->getClientOriginalExtension()
        );

        $oldPath = session('setting_atribut_preview_file');
        if (is_string($oldPath) && $oldPath !== '' && Storage::exists($oldPath)) {
            Storage::delete($oldPath);
        }

        session([
            'setting_atribut_preview_rows' => $previewRows,
            'setting_atribut_preview_file' => $storedPath,
            'setting_atribut_preview_filename' => $file->getClientOriginalName(),
        ]);

        return redirect()->route('master.setting_atribut_siswa')
            ->with('status', 'File atribut berhasil dibaca. Klik "Simpan Data" untuk update data siswa.');
    }

    public function save(AmalFatimahApiService $api): RedirectResponse
    {
        $storedPath = session('setting_atribut_preview_file');
        $originalName = (string) session('setting_atribut_preview_filename', 'atribut.xlsx');
        if (!is_string($storedPath) || $storedPath === '' || !Storage::exists($storedPath)) {
            return redirect()->route('master.setting_atribut_siswa')->with('error', 'File atribut belum dipilih.');
        }

        $result = $api->importSettingAtributSiswaByFilePath(Storage::path($storedPath), $originalName);
        if (!($result['ok'] ?? false)) {
            return redirect()->route('master.setting_atribut_siswa')->with('error', 'Gagal simpan atribut siswa. Silakan coba lagi.');
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $summary = sprintf(
            'Simpan data selesai. Insert: %d, Update: %d, Skip: %d, Error: %d.',
            (int) ($data['inserted'] ?? 0),
            (int) ($data['updated'] ?? 0),
            (int) ($data['skipped'] ?? 0),
            is_array($data['errors'] ?? null) ? count($data['errors']) : 0
        );

        Storage::delete($storedPath);
        session()->forget(['setting_atribut_preview_rows', 'setting_atribut_preview_file', 'setting_atribut_preview_filename']);

        return redirect()->route('master.setting_atribut_siswa')->with('status', $summary);
    }

    public function clear(): RedirectResponse
    {
        $storedPath = session('setting_atribut_preview_file');
        if (is_string($storedPath) && $storedPath !== '' && Storage::exists($storedPath)) {
            Storage::delete($storedPath);
        }
        session()->forget(['setting_atribut_preview_rows', 'setting_atribut_preview_file', 'setting_atribut_preview_filename']);
        return redirect()->route('master.setting_atribut_siswa')->with('status', 'Data preview atribut berhasil dibersihkan.');
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
                'nama' => trim((string) ($row['nama'] ?? '')),
                'gender' => trim((string) ($row['gender'] ?? '')),
                'ayah' => trim((string) ($row['ayah'] ?? '')),
                'ibu' => trim((string) ($row['ibu'] ?? '')),
                'kontak' => trim((string) ($row['kontak'] ?? '')),
                'eksint' => trim((string) ($row['eksint'] ?? '')),
                'wisma' => trim((string) ($row['wisma'] ?? '')),
                'alamat' => trim((string) ($row['alamat'] ?? '')),
            ];
        }, $decoded), static fn ($row) => is_array($row) && (($row['nis'] ?? '') !== '')));
    }
}

