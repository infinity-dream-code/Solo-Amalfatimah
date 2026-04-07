<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TagihanSiswaController extends Controller
{
    public function fungsi(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $thnAkademik = trim((string) $request->query('thn_akademik', ''));
        $tagihan = trim((string) $request->query('tagihan', ''));
        Log::info('[BuatTagihan] fungsi endpoint hit', [
            'thn_akademik' => $thnAkademik,
            'tagihan' => $tagihan,
        ]);

        if ($thnAkademik === '') {
            return response()->json(['ok' => true, 'fungsi' => '', 'periode' => '', 'source' => 'empty_param']);
        }

        $res = $api->getFungsiBuatTagihan($thnAkademik, $tagihan);
        $fungsi = trim((string) ($res['fungsi'] ?? ''));

        return response()->json([
            'ok' => (bool) ($res['ok'] ?? false),
            'fungsi' => $fungsi,
            'periode' => $fungsi,
            'source' => $res['source'] ?? '',
        ]);
    }

    public function daftarHarga(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $kelasId = trim((string) $request->query('kelas_id', ''));
        $thnAngkatan = trim((string) $request->query('thn_angkatan', ''));
        $thnAkademik = trim((string) $request->query('thn_akademik', ''));
        $tagihan = trim((string) $request->query('tagihan', ''));

        if ($kelasId === '') {
            return response()->json(['ok' => true, 'rows' => []]);
        }

        $filters = [
            'thn_akademik' => $thnAkademik,
            'thn_angkatan' => $thnAngkatan,
            'kelas_id' => $kelasId,
            'search' => '',
            'fungsi' => '',
            'tagihan' => $tagihan,
        ];

        $res = $api->getBuatTagihan($filters, 1, 0);
        if (!($res['ok'] ?? false)) {
            return response()->json(['ok' => false, 'rows' => []]);
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        $rows = is_array($data['daftar_harga'] ?? null) ? $data['daftar_harga'] : [];
        if (!array_is_list($rows) && is_array($rows['data'] ?? null)) {
            $rows = $rows['data'];
        }
        $rows = array_values(array_filter($rows, static fn ($row) => is_array($row)));
        return response()->json(['ok' => true, 'rows' => $rows]);
    }

    public function buat(Request $request, AmalFatimahApiService $api): View
    {
        $hasSearchRequest = $request->query->count() > 0;
        $filters = [
            'thn_akademik' => trim((string) $request->query('thn_akademik', '')),
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'search' => trim((string) $request->query('search', '')),
            'fungsi' => trim((string) $request->query('fungsi', '')),
            'tagihan' => trim((string) $request->query('tagihan', '')),
        ];
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $filterOptions = $api->getFilterBuatTagihan();
        $siswa = [];
        $total = 0;
        $daftarHarga = [];
        $error = '';

        if ($hasSearchRequest) {
            $result = $api->getBuatTagihan($filters, $perPage, ($page - 1) * $perPage);
            if (!($result['ok'] ?? false)) {
                // WS kadang intermittent error/timeout; retry sekali agar hasil pencarian lebih stabil.
                usleep(250000);
                $result = $api->getBuatTagihan($filters, $perPage, ($page - 1) * $perPage);
            }

            if ($result['ok']) {
                $data = is_array($result['data']) ? $result['data'] : [];
                $filters['fungsi'] = trim((string) ($data['fungsi'] ?? $filters['fungsi']));
                $rawSiswa = is_array($data['siswa'] ?? null) ? $data['siswa'] : [];
                if (!array_is_list($rawSiswa) && is_array($rawSiswa['data'] ?? null)) {
                    $rawSiswa = $rawSiswa['data'];
                }
                $siswa = array_values(array_filter($rawSiswa, static fn ($row) => is_array($row)));

                $rawDaftarHarga = is_array($data['daftar_harga'] ?? null) ? $data['daftar_harga'] : [];
                if (!array_is_list($rawDaftarHarga) && is_array($rawDaftarHarga['data'] ?? null)) {
                    $rawDaftarHarga = $rawDaftarHarga['data'];
                }
                $daftarHarga = array_values(array_filter($rawDaftarHarga, static fn ($row) => is_array($row)));
                $total = max((int) ($data['total_siswa'] ?? 0), count($siswa));
            } elseif (($result['message'] ?? '') !== '') {
                $error = (string) $result['message'];
            }
        }

        $siswaPaginator = new LengthAwarePaginator(
            $siswa,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('keuangan.tagihan-siswa.buat-tagihan', [
            'pageTitle' => 'Buat Tagihan',
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'siswaRows' => $siswaPaginator,
            'daftarHargaRows' => $daftarHarga,
            'errorMsg' => $error,
        ]);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        Log::info('[BuatTagihan] store request received', [
            'thn_akademik' => $request->input('thn_akademik'),
            'thn_angkatan' => $request->input('thn_angkatan'),
            'kelas_id' => $request->input('kelas_id'),
            'custids_count' => is_array($request->input('custids')) ? count($request->input('custids')) : 0,
            'kode_akuns_count' => is_array($request->input('kode_akuns')) ? count($request->input('kode_akuns')) : 0,
        ]);

        $validated = $request->validate([
            'thn_akademik' => ['required', 'string'],
            'thn_angkatan' => ['nullable', 'string'],
            'kelas_id' => ['required', 'string'],
            'fungsi' => ['nullable', 'string'],
            'tagihan' => ['nullable', 'string'],
            'custids' => ['required', 'array', 'min:1'],
            'custids.*' => ['integer', 'min:1'],
            'kode_akuns' => ['required', 'array', 'min:1'],
            'kode_akuns.*' => ['string'],
        ], [
            'thn_akademik.required' => 'Tahun Pelajaran wajib diisi.',
            'kelas_id.required' => 'Kelas wajib diisi.',
            'custids.required' => 'Pilih minimal satu siswa.',
            'kode_akuns.required' => 'Pilih minimal satu akun tagihan.',
        ]);

        $res = $api->createBuatTagihan($validated);
        if (!$res['ok']) {
            return redirect()->route('keu.tagihan.buat', $request->only(['thn_akademik', 'thn_angkatan', 'kelas_id', 'search', 'fungsi', 'tagihan']))
                ->with('error', $res['message']);
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        $msg = sprintf(
            'Tagihan berhasil disimpan. Insert: %d, Skip: %d, Error: %d.',
            (int) ($data['inserted'] ?? 0),
            (int) ($data['skipped'] ?? 0),
            is_array($data['errors'] ?? null) ? count($data['errors']) : 0
        );
        return redirect()->route('keu.tagihan.buat', $request->only(['thn_akademik', 'thn_angkatan', 'kelas_id', 'search', 'fungsi', 'tagihan']))
            ->with('status', $msg);
    }

    public function uploadExcel(Request $request, AmalFatimahApiService $api): View
    {
        $this->restoreTagihanExcelPreviewFromCacheIfNeeded();

        $rRows = session('tagihan_excel_preview_rows', []);
        $rMeta = session('tagihan_excel_meta', []);
        if (is_array($rRows) && count($rRows) > 0 && is_array($rMeta) && count($rMeta) > 0) {
            $this->persistTagihanExcelPreviewToCache($rRows, $rMeta);
        }

        $filterOptions = $api->getFilterBuatTagihan();
        $akunPosts = $api->getAkun();

        return view('keuangan.tagihan-siswa.upload-tagihan-excel', [
            'pageTitle' => 'Upload Tagihan Excel',
            'filterOptions' => $filterOptions,
            'akunPosts' => $akunPosts,
            'importRows' => $this->paginateTagihanExcelPreview($request),
            'keyword' => trim((string) $request->query('q', '')),
            'perPage' => $this->normalizePerPage((int) $request->query('per_page', 10)),
            'excelMeta' => is_array(session('tagihan_excel_meta')) ? session('tagihan_excel_meta') : [],
        ]);
    }

    public function uploadExcelImport(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'thn_akademik' => ['required', 'string'],
            'tagihan' => ['required', 'string'],
            'periode' => ['required', 'string'],
            'kode_akun' => ['required', 'string'],
            'raw_rows' => ['required', 'string'],
        ], [
            'thn_akademik.required' => 'Tahun pelajaran wajib dipilih.',
            'tagihan.required' => 'Tagihan wajib dipilih.',
            'periode.required' => 'Periode belum terisi. Pilih tahun pelajaran dan tagihan.',
            'kode_akun.required' => 'Post wajib dipilih.',
            'raw_rows.required' => 'File atau preview baris kosong.',
        ]);

        $decoded = json_decode((string) $validated['raw_rows'], true);
        if (!is_array($decoded)) {
            return redirect()->route('keu.tagihan.upload_excel')->with('error', 'Format preview file tidak valid.');
        }

        $rawRows = array_values(array_filter($decoded, static fn ($r) => is_array($r)));
        if (count($rawRows) === 0) {
            return redirect()->route('keu.tagihan.upload_excel')->with('error', 'Tidak ada baris data di file.');
        }

        $enrich = $api->enrichTagihanExcelRows($rawRows);
        if (!$enrich['ok']) {
            return redirect()->route('keu.tagihan.upload_excel')->with('error', 'Gagal memuat data siswa dari server. Coba lagi.');
        }

        $meta = [
            'thn_akademik' => trim((string) $validated['thn_akademik']),
            'tagihan' => trim((string) $validated['tagihan']),
            'periode' => trim((string) $validated['periode']),
            'kode_akun' => trim((string) $validated['kode_akun']),
        ];

        session([
            'tagihan_excel_meta' => $meta,
            'tagihan_excel_preview_rows' => $enrich['rows'],
        ]);

        $this->persistTagihanExcelPreviewToCache($enrich['rows'], $meta);
        $request->session()->save();

        return redirect()
            ->route('keu.tagihan.upload_excel')
            ->with('status', 'File berhasil dibaca. Periksa tabel lalu klik Simpan Data.');
    }

    public function uploadExcelSave(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $this->restoreTagihanExcelPreviewFromCacheIfNeeded();

        $meta = session('tagihan_excel_meta', []);
        $rows = session('tagihan_excel_preview_rows', []);
        if (!is_array($meta) || !is_array($rows) || $rows === []) {
            return redirect()->route('keu.tagihan.upload_excel')->with('error', 'Belum ada data impor. Unggah file terlebih dahulu.');
        }

        $thn = trim((string) ($meta['thn_akademik'] ?? ''));
        $periode = trim((string) ($meta['periode'] ?? ''));
        $kode = trim((string) ($meta['kode_akun'] ?? ''));

        $payloadRows = [];
        foreach ($rows as $r) {
            if (!is_array($r) || empty($r['ok'])) {
                continue;
            }
            $payloadRows[] = [
                'nis' => trim((string) ($r['nis'] ?? '')),
                'custid' => (int) ($r['custid'] ?? 0),
                'nominal' => (int) ($r['nominal'] ?? 0),
            ];
        }

        if (count($payloadRows) === 0) {
            return redirect()->route('keu.tagihan.upload_excel')->with('error', 'Tidak ada baris valid untuk disimpan (periksa NIS / nominal).');
        }

        $res = $api->createTagihanExcelUpload([
            'thn_akademik' => $thn,
            'tagihan' => trim((string) ($meta['tagihan'] ?? '')),
            'periode' => $periode,
            'kode_akun' => $kode,
            'billcd_mode' => 'E',
            'rows' => $payloadRows,
        ]);

        if (!$res['ok']) {
            return redirect()->route('keu.tagihan.upload_excel')->with('error', $res['message']);
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        $errList = is_array($data['errors'] ?? null) ? $data['errors'] : [];
        $msg = sprintf(
            'Simpan selesai. Insert: %d, Error: %d.',
            (int) ($data['inserted'] ?? 0),
            count($errList)
        );

        session()->forget(['tagihan_excel_meta', 'tagihan_excel_preview_rows']);
        $this->forgetTagihanExcelPreviewCache();

        return redirect()->route('keu.tagihan.upload_excel')->with('status', $msg);
    }

    public function uploadExcelClear(): RedirectResponse
    {
        session()->forget(['tagihan_excel_meta', 'tagihan_excel_preview_rows']);
        $this->forgetTagihanExcelPreviewCache();

        return redirect()->route('keu.tagihan.upload_excel')->with('status', 'Data pratinjau dibersihkan.');
    }

    private function normalizePerPage(int $perPage): int
    {
        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }

    private function paginateTagihanExcelPreview(Request $request): LengthAwarePaginator
    {
        $perPage = $this->normalizePerPage((int) $request->query('per_page', 10));
        $page = max(1, (int) $request->query('page', 1));
        $keyword = mb_strtolower(trim((string) $request->query('q', '')));

        $allRows = session('tagihan_excel_preview_rows', []);
        if (!is_array($allRows)) {
            $allRows = [];
        }

        if ($keyword !== '') {
            $allRows = array_values(array_filter($allRows, static function ($row) use ($keyword) {
                if (!is_array($row)) {
                    return false;
                }
                $line = mb_strtolower(implode(' ', array_map(static fn ($v) => (string) $v, $row)));

                return str_contains($line, $keyword);
            }));
        }

        $total = count($allRows);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($allRows, $offset, $perPage);

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function tagihanExcelCacheKeyRows(): string
    {
        return 'tagihan_excel_preview:' . session()->getId();
    }

    private function tagihanExcelCacheKeyMeta(): string
    {
        return 'tagihan_excel_meta:' . session()->getId();
    }

    /**
     * Cadangan pratinjau di cache agar tidak hilang saat session ter-reset (refresh / navigasi).
     */
    private function persistTagihanExcelPreviewToCache(array $rows, array $meta): void
    {
        $ttl = now()->addMinutes(max(1, (int) config('session.lifetime', 120)));
        Cache::put($this->tagihanExcelCacheKeyRows(), $rows, $ttl);
        Cache::put($this->tagihanExcelCacheKeyMeta(), $meta, $ttl);
    }

    private function restoreTagihanExcelPreviewFromCacheIfNeeded(): void
    {
        $rows = session('tagihan_excel_preview_rows');
        $meta = session('tagihan_excel_meta');

        $needRows = !is_array($rows) || count($rows) === 0;
        $needMeta = !is_array($meta) || count($meta) === 0;

        if ($needRows && Cache::has($this->tagihanExcelCacheKeyRows())) {
            $from = Cache::get($this->tagihanExcelCacheKeyRows());
            if (is_array($from) && count($from) > 0) {
                session(['tagihan_excel_preview_rows' => $from]);
            }
        }

        if ($needMeta && Cache::has($this->tagihanExcelCacheKeyMeta())) {
            $fromM = Cache::get($this->tagihanExcelCacheKeyMeta());
            if (is_array($fromM) && count($fromM) > 0) {
                session(['tagihan_excel_meta' => $fromM]);
            }
        }
    }

    private function forgetTagihanExcelPreviewCache(): void
    {
        Cache::forget($this->tagihanExcelCacheKeyRows());
        Cache::forget($this->tagihanExcelCacheKeyMeta());
    }

    private function paginateTagihanPmbPreview(Request $request): LengthAwarePaginator
    {
        $perPage = $this->normalizePerPage((int) $request->query('per_page', 10));
        $page = max(1, (int) $request->query('page', 1));
        $keyword = mb_strtolower(trim((string) $request->query('q', '')));

        $allRows = session('tagihan_pmb_preview_rows', []);
        if (!is_array($allRows)) {
            $allRows = [];
        }

        if ($keyword !== '') {
            $allRows = array_values(array_filter($allRows, static function ($row) use ($keyword) {
                if (!is_array($row)) {
                    return false;
                }
                $line = mb_strtolower(implode(' ', array_map(static fn ($v) => (string) $v, $row)));

                return str_contains($line, $keyword);
            }));
        }

        $total = count($allRows);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($allRows, $offset, $perPage);

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function tagihanPmbCacheKeyRows(): string
    {
        return 'tagihan_pmb_preview:' . session()->getId();
    }

    private function tagihanPmbCacheKeyMeta(): string
    {
        return 'tagihan_pmb_meta:' . session()->getId();
    }

    private function persistTagihanPmbPreviewToCache(array $rows, array $meta): void
    {
        $ttl = now()->addMinutes(max(1, (int) config('session.lifetime', 120)));
        Cache::put($this->tagihanPmbCacheKeyRows(), $rows, $ttl);
        Cache::put($this->tagihanPmbCacheKeyMeta(), $meta, $ttl);
    }

    private function restoreTagihanPmbPreviewFromCacheIfNeeded(): void
    {
        $rows = session('tagihan_pmb_preview_rows');
        $meta = session('tagihan_pmb_meta');

        $needRows = !is_array($rows) || count($rows) === 0;
        $needMeta = !is_array($meta) || count($meta) === 0;

        if ($needRows && Cache::has($this->tagihanPmbCacheKeyRows())) {
            $from = Cache::get($this->tagihanPmbCacheKeyRows());
            if (is_array($from) && count($from) > 0) {
                session(['tagihan_pmb_preview_rows' => $from]);
            }
        }

        if ($needMeta && Cache::has($this->tagihanPmbCacheKeyMeta())) {
            $fromM = Cache::get($this->tagihanPmbCacheKeyMeta());
            if (is_array($fromM) && count($fromM) > 0) {
                session(['tagihan_pmb_meta' => $fromM]);
            }
        }
    }

    private function forgetTagihanPmbPreviewCache(): void
    {
        Cache::forget($this->tagihanPmbCacheKeyRows());
        Cache::forget($this->tagihanPmbCacheKeyMeta());
    }

    public function uploadPmb(): View
    {
        $this->restoreTagihanPmbPreviewFromCacheIfNeeded();

        $rRows = session('tagihan_pmb_preview_rows', []);
        $rMeta = session('tagihan_pmb_meta', []);
        if (is_array($rRows) && count($rRows) > 0 && is_array($rMeta) && count($rMeta) > 0) {
            $this->persistTagihanPmbPreviewToCache($rRows, $rMeta);
        }

        /** @var AmalFatimahApiService $api */
        $api = app(AmalFatimahApiService::class);

        return view('keuangan.tagihan-siswa.upload-tagihan-pmb', [
            'pageTitle' => 'Upload Tagihan PMB',
            'filterOptions' => $api->getFilterBuatTagihan(),
            'akunPosts' => $api->getAkun(),
            'importRows' => $this->paginateTagihanPmbPreview(request()),
            'keyword' => trim((string) request()->query('q', '')),
            'perPage' => $this->normalizePerPage((int) request()->query('per_page', 10)),
            'pmbMeta' => is_array(session('tagihan_pmb_meta')) ? session('tagihan_pmb_meta') : [],
        ]);
    }

    public function uploadPmbSubmit(Request $request): RedirectResponse
    {
        return $this->uploadPmbImport($request, app(AmalFatimahApiService::class));
    }

    public function uploadPmbImport(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'thn_akademik' => ['required', 'string'],
            'tagihan' => ['required', 'string'],
            'periode' => ['required', 'string'],
            'kode_akun' => ['required', 'string'],
            'raw_rows' => ['required', 'string'],
        ]);

        $decoded = json_decode((string) $validated['raw_rows'], true);
        if (!is_array($decoded)) {
            return redirect()->route('keu.tagihan.upload_pmb')->with('error', 'Format preview file tidak valid.');
        }

        $rawRows = array_values(array_filter($decoded, static fn ($r) => is_array($r)));
        if (count($rawRows) === 0) {
            return redirect()->route('keu.tagihan.upload_pmb')->with('error', 'Tidak ada baris data di file.');
        }

        // PMB: no_pendaftaran dipetakan ke nis agar WS match via NUM2ND alias.
        $rowsForEnrich = [];
        foreach ($rawRows as $r) {
            $nop = trim((string) ($r['no_pendaftaran'] ?? $r['nis'] ?? ''));
            $rowsForEnrich[] = [
                'nis' => $nop,
                'nominal' => (int) ($r['nominal'] ?? 0),
                'custid' => (int) ($r['custid'] ?? 0),
            ];
        }

        $enrich = $api->enrichTagihanExcelRows($rowsForEnrich);
        if (!$enrich['ok']) {
            return redirect()->route('keu.tagihan.upload_pmb')->with('error', 'Gagal memuat data siswa dari server. Coba lagi.');
        }

        $meta = [
            'thn_akademik' => trim((string) $validated['thn_akademik']),
            'tagihan' => trim((string) $validated['tagihan']),
            'periode' => trim((string) $validated['periode']),
            'kode_akun' => trim((string) $validated['kode_akun']),
        ];

        session([
            'tagihan_pmb_meta' => $meta,
            'tagihan_pmb_preview_rows' => $enrich['rows'],
        ]);
        $this->persistTagihanPmbPreviewToCache($enrich['rows'], $meta);
        $request->session()->save();

        return redirect()->route('keu.tagihan.upload_pmb')->with('status', 'File PMB berhasil dibaca. Periksa tabel lalu klik Simpan Data.');
    }

    public function uploadPmbSave(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $this->restoreTagihanPmbPreviewFromCacheIfNeeded();

        $meta = session('tagihan_pmb_meta', []);
        $rows = session('tagihan_pmb_preview_rows', []);
        if (!is_array($meta) || !is_array($rows) || $rows === []) {
            return redirect()->route('keu.tagihan.upload_pmb')->with('error', 'Belum ada data impor. Unggah file terlebih dahulu.');
        }

        $payloadRows = [];
        foreach ($rows as $r) {
            if (!is_array($r) || empty($r['ok'])) {
                continue;
            }
            $payloadRows[] = [
                'nis' => trim((string) ($r['nis'] ?? '')),
                'custid' => (int) ($r['custid'] ?? 0),
                'nominal' => (int) ($r['nominal'] ?? 0),
            ];
        }
        if (count($payloadRows) === 0) {
            return redirect()->route('keu.tagihan.upload_pmb')->with('error', 'Tidak ada baris valid untuk disimpan (periksa no pendaftaran / nominal).');
        }

        $res = $api->createTagihanExcelUpload([
            'thn_akademik' => trim((string) ($meta['thn_akademik'] ?? '')),
            'tagihan' => trim((string) ($meta['tagihan'] ?? '')),
            'periode' => trim((string) ($meta['periode'] ?? '')),
            'kode_akun' => trim((string) ($meta['kode_akun'] ?? '')),
            'billcd_mode' => 'P',
            'rows' => $payloadRows,
        ]);
        if (!$res['ok']) {
            return redirect()->route('keu.tagihan.upload_pmb')->with('error', $res['message']);
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        $errList = is_array($data['errors'] ?? null) ? $data['errors'] : [];
        $msg = sprintf('Simpan selesai. Insert: %d, Error: %d.', (int) ($data['inserted'] ?? 0), count($errList));

        session()->forget(['tagihan_pmb_meta', 'tagihan_pmb_preview_rows']);
        $this->forgetTagihanPmbPreviewCache();

        return redirect()->route('keu.tagihan.upload_pmb')->with('status', $msg);
    }

    public function uploadPmbClear(): RedirectResponse
    {
        session()->forget(['tagihan_pmb_meta', 'tagihan_pmb_preview_rows']);
        $this->forgetTagihanPmbPreviewCache();

        return redirect()->route('keu.tagihan.upload_pmb')->with('status', 'Data pratinjau PMB dibersihkan.');
    }

    public function data(Request $request, AmalFatimahApiService $api): View
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $filters = [
            'tgl_dari' => trim((string) $request->query('tgl_dari', '')),
            'tgl_sampai' => trim((string) $request->query('tgl_sampai', '')),
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'thn_akademik' => trim((string) $request->query('thn_akademik', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'nama_tagihan' => trim((string) $request->query('nama_tagihan', '')),
            'siswa' => trim((string) $request->query('siswa', '')),
            'sort_urutan' => in_array(strtolower(trim((string) $request->query('sort_urutan', 'asc'))), ['asc', 'desc'], true)
                ? strtolower(trim((string) $request->query('sort_urutan', 'asc')))
                : 'asc',
        ];

        $filterOptions = $api->getFilterBuatTagihan();
        $res = $api->getDataTagihan($filters, $perPage, ($page - 1) * $perPage);

        $rows = [];
        $total = 0;
        $errorMsg = '';
        if ($res['ok']) {
            $rows = $res['data']['rows'] ?? [];
            $total = (int) ($res['data']['total'] ?? 0);
        } else {
            $errorMsg = $res['message'] ?? 'Gagal memuat data.';
        }

        $paginator = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('keuangan.tagihan-siswa.data-tagihan', [
            'pageTitle' => 'Data Tagihan Siswa',
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'tagihanRows' => $paginator,
            'errorMsg' => $errorMsg,
        ]);
    }

    public function dataUrutan(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $validated = $request->validate([
            'custid' => ['required', 'integer', 'min:1'],
            'billcd' => ['required', 'string'],
            'direction' => ['required', 'in:up,down'],
        ]);

        $res = $api->updateDataTagihanUrutan(
            (int) $validated['custid'],
            trim((string) $validated['billcd']),
            (string) $validated['direction']
        );

        return response()->json([
            'ok' => $res['ok'],
            'message' => $res['message'],
            'data' => $res['data'] ?? [],
        ], $res['ok'] ? 200 : 422);
    }

    public function dataHapus(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $validated = $request->validate([
            'custid' => ['required', 'integer', 'min:1'],
            'billcd' => ['required', 'string'],
        ]);

        $res = $api->deleteDataTagihan((int) $validated['custid'], trim((string) $validated['billcd']));

        $status = $res['ok'] ? 200 : 400;

        return response()->json([
            'ok' => $res['ok'],
            'message' => $res['message'],
            'data' => $res['data'] ?? [],
        ], $status);
    }

    public function dataExportExcel(Request $request, AmalFatimahApiService $api): StreamedResponse|RedirectResponse
    {
        $filters = $this->validatedDataTagihanFiltersFromRequest($request);
        $rawRows = $this->fetchAllDataTagihanRowsForExport($api, $filters);
        if ($rawRows === null) {
            return redirect()->back()->with('export_error', 'Gagal mengambil data dari server. Coba lagi.');
        }
        $rows = $this->buildDataTagihanExportRowsFromApiRows($rawRows);
        if (count($rows) === 0) {
            return redirect()->back()->with('export_error', 'Tidak ada data yang cocok dengan filter saat ini.');
        }

        $filename = 'data-tagihan-' . date('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $h = fopen('php://output', 'w');
            if ($h !== false) {
                fprintf($h, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($h, ['No', 'NIS', 'NO DAFT', 'NO VA', 'NAMA', 'Unit', 'Kelas', 'Kelompok', 'Nama Tagihan', 'Tagihan (Rp)', 'Tahun AKA', 'Urutan', 'Status'], ';');
                foreach ($rows as $r) {
                    fputcsv($h, [
                        $r['no'],
                        $r['nis'],
                        $r['no_daftar'],
                        $r['no_va'],
                        $r['nama'],
                        $r['unit'],
                        $r['kelas'],
                        $r['kelompok'],
                        $r['nama_tagihan'],
                        $r['tagihan'],
                        $r['tahun_aka'],
                        $r['urutan'],
                        $r['status'],
                    ], ';');
                }
                fclose($h);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function dataExportPdf(Request $request, AmalFatimahApiService $api): \Illuminate\Http\Response|RedirectResponse
    {
        $filters = $this->validatedDataTagihanFiltersFromRequest($request);
        $rawRows = $this->fetchAllDataTagihanRowsForExport($api, $filters);
        if ($rawRows === null) {
            return redirect()->back()->with('export_error', 'Gagal mengambil data dari server. Coba lagi.');
        }
        $rows = $this->buildDataTagihanExportRowsFromApiRows($rawRows);
        if (count($rows) === 0) {
            return redirect()->back()->with('export_error', 'Tidak ada data yang cocok dengan filter saat ini.');
        }

        $pdf = Pdf::loadView('keuangan.tagihan-siswa.data-tagihan-export-pdf', ['rows' => $rows])
            ->setPaper('a4', 'landscape');

        return $pdf->download('data-tagihan-' . date('Ymd-His') . '.pdf');
    }

    public function dataPrint(Request $request, AmalFatimahApiService $api): View
    {
        $filters = $this->validatedDataTagihanFiltersFromRequest($request);
        $rawRows = $this->fetchAllDataTagihanRowsForExport($api, $filters);
        $rows = [];
        $errorMessage = '';
        if ($rawRows === null) {
            $errorMessage = 'Gagal mengambil data dari server.';
        } else {
            $rows = $this->buildDataTagihanExportRowsFromApiRows($rawRows);
        }

        return view('keuangan.tagihan-siswa.data-tagihan-print', [
            'rows' => $rows,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function dataPrintKartu(Request $request, AmalFatimahApiService $api): \Illuminate\Http\Response|RedirectResponse
    {
        $filters = $this->validatedDataTagihanFiltersFromRequest($request);
        $selectedCustIds = $this->selectedCustIdsFromRequest($request);
        if ($selectedCustIds === []) {
            return redirect()->back()->with('export_error', 'Pilih minimal 1 siswa untuk Cetak Kartu Siswa.');
        }

        // Kartu siswa harus memuat semua tagihan milik siswa terpilih.
        // Abaikan filter nama_tagihan agar tidak hanya 1 bulan/tagihan saja.
        $filtersForKartu = $filters;
        $filtersForKartu['nama_tagihan'] = '';

        $rawRows = $this->fetchAllDataTagihanRowsForExport($api, $filtersForKartu);
        if ($rawRows === null) {
            return redirect()->back()->with('export_error', 'Gagal mengambil data dari server. Coba lagi.');
        }

        $selectedMap = array_fill_keys($selectedCustIds, true);
        $cards = [];
        foreach ($rawRows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $custid = (int) ($r['custid'] ?? 0);
            if ($custid <= 0 || !isset($selectedMap[$custid])) {
                continue;
            }
            $paidRaw = $r['paidst'] ?? '0';
            $isLunas = $paidRaw === '1' || $paidRaw === 1 || $paidRaw === true;
            // Cetak kartu dari Rekap Tagihan: tampilkan hanya yang belum lunas.
            if ($isLunas) {
                continue;
            }
            if (!isset($cards[$custid])) {
                $kelompok = trim((string) ($r['kelompok'] ?? ''));
                if ($kelompok === '') {
                    $kelompok = trim((string) ($r['DESC03'] ?? $r['desc03'] ?? ''));
                }
                $cards[$custid] = [
                    'custid' => $custid,
                    'nis' => trim((string) ($r['nis'] ?? '')),
                    'nama' => trim((string) ($r['nama'] ?? '')),
                    'unit' => trim((string) ($r['unit'] ?? '')),
                    'kelas' => trim((string) ($r['kelas'] ?? '')),
                    'kelompok' => $kelompok,
                    'items' => [],
                ];
            }
            $cards[$custid]['items'][] = [
                'nama_tagihan' => trim((string) ($r['nama_tagihan'] ?? '')),
                'tahun_aka' => trim((string) ($r['tahun_aka'] ?? '')),
                'tagihan' => (int) ($r['tagihan'] ?? 0),
                'status' => $isLunas ? 'Lunas' : 'Belum lunas',
            ];
        }
        if ($cards === []) {
            return redirect()->back()->with('export_error', 'Siswa terpilih tidak memiliki data tagihan pada filter saat ini.');
        }

        $pdf = Pdf::loadView('keuangan.tagihan-siswa.data-tagihan-kartu-siswa-pdf', [
            'cards' => array_values($cards),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('kartu-siswa-' . date('Ymd-His') . '.pdf');
    }

    public function dataPrintRekap(Request $request, AmalFatimahApiService $api): \Illuminate\Http\Response|RedirectResponse
    {
        $hasSearchContext = trim((string) $request->input('has_search_context', '')) === '1';
        if (!$hasSearchContext) {
            return redirect()->back()->with('export_error', 'Data masih kosong. Klik Cari dulu sebelum cetak rekap.');
        }

        $filters = $this->validatedDataTagihanFiltersFromRequest($request);

        $rawRows = $this->fetchAllDataTagihanRowsForExport($api, $filters);
        if ($rawRows === null) {
            return redirect()->back()->with('export_error', 'Gagal mengambil data dari server. Coba lagi.');
        }
        if ($rawRows === []) {
            return redirect()->back()->with('export_error', 'Tidak ada data yang cocok untuk cetak rekap.');
        }

        $rows = [];
        foreach ($rawRows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $rows[] = [
                'nis' => trim((string) ($r['nis'] ?? '')),
                'nama' => trim((string) ($r['nama'] ?? '')),
                'nama_tagihan' => trim((string) ($r['nama_tagihan'] ?? '')),
                'tahun_aka' => trim((string) ($r['tahun_aka'] ?? '')),
                'tagihan' => (int) ($r['tagihan'] ?? 0),
            ];
        }

        $pdf = Pdf::loadView('keuangan.tagihan-siswa.data-tagihan-rekap-pdf', [
            'rows' => $rows,
            'filters' => $filters,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('rekap-tagihan-' . date('Ymd-His') . '.pdf');
    }

    /**
     * @return array<string, string>
     */
    private function validatedDataTagihanFiltersFromRequest(Request $request): array
    {
        return [
            'tgl_dari' => trim((string) $request->input('tgl_dari', '')),
            'tgl_sampai' => trim((string) $request->input('tgl_sampai', '')),
            'thn_angkatan' => trim((string) $request->input('thn_angkatan', '')),
            'thn_akademik' => trim((string) $request->input('thn_akademik', '')),
            'kelas_id' => trim((string) $request->input('kelas_id', '')),
            'nama_tagihan' => trim((string) $request->input('nama_tagihan', '')),
            'siswa' => trim((string) $request->input('siswa', '')),
        ];
    }

    /**
     * Ambil semua baris yang cocok filter (API max 200 per panggilan).
     *
     * @return list<array<string, mixed>>|null null jika error WS
     */
    private function fetchAllDataTagihanRowsForExport(AmalFatimahApiService $api, array $filters, int $maxRows = 10000): ?array
    {
        $chunk = 200;
        $all = [];
        $offset = 0;

        while (count($all) < $maxRows) {
            $res = $api->getDataTagihan($filters, $chunk, $offset);
            if (!$res['ok']) {
                return null;
            }
            $rows = $res['data']['rows'] ?? [];
            if (!is_array($rows) || $rows === []) {
                break;
            }
            foreach ($rows as $r) {
                $all[] = $r;
            }
            if (count($rows) < $chunk) {
                break;
            }
            $offset += $chunk;
        }

        return $all;
    }

    /**
     * @param list<array<string, mixed>> $apiRows
     * @return list<array<string, mixed>>
     */
    private function buildDataTagihanExportRowsFromApiRows(array $apiRows): array
    {
        $out = [];
        $no = 1;
        foreach ($apiRows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $paidRaw = $r['paidst'] ?? '0';
            $isLunas = $paidRaw === '1' || $paidRaw === 1 || $paidRaw === true;
            $out[] = [
                'no' => $no++,
                'nis' => mb_substr(trim((string) ($r['nis'] ?? '')), 0, 64),
                'no_daftar' => mb_substr(trim((string) ($r['no_daftar'] ?? '')), 0, 64),
                'no_va' => mb_substr(trim((string) ($r['no_va'] ?? '')), 0, 32),
                'nama' => mb_substr(trim((string) ($r['nama'] ?? '')), 0, 128),
                'unit' => mb_substr(trim((string) ($r['unit'] ?? '')), 0, 64),
                'kelas' => mb_substr(trim((string) ($r['kelas'] ?? '')), 0, 64),
                'kelompok' => mb_substr(trim((string) ($r['kelompok'] ?? '')), 0, 64),
                'angkatan' => mb_substr(trim((string) ($r['angkatan'] ?? '')), 0, 32),
                'nama_tagihan' => mb_substr(trim((string) ($r['nama_tagihan'] ?? '')), 0, 128),
                'tagihan' => (int) ($r['tagihan'] ?? 0),
                'tahun_aka' => mb_substr(trim((string) ($r['tahun_aka'] ?? '')), 0, 32),
                'urutan' => (int) ($r['furutan'] ?? 0),
                'status' => $isLunas ? 'Lunas' : 'Belum lunas',
            ];
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    private function selectedCustIdsFromRequest(Request $request): array
    {
        $raw = $request->input('selected_rows');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $bucket = [];
        foreach ($decoded as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $bucket[$n] = true;
            }
        }

        return array_map('intval', array_keys($bucket));
    }

    /**
     * @return list<string> key format: "custid|billcd"
     */
    private function selectedTagihanKeysFromRequest(Request $request): array
    {
        $raw = $request->input('selected_rows');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $bucket = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $custid = (int) ($row['custid'] ?? 0);
            $billcd = trim((string) ($row['billcd'] ?? ''));
            if ($custid <= 0 || $billcd === '') {
                continue;
            }
            $bucket[$custid . '|' . $billcd] = true;
        }

        return array_keys($bucket);
    }

    private function monthOrderWeight(string $name): int
    {
        $u = mb_strtoupper($name);
        $map = [
            'JANUARI' => 1,
            'FEBRUARI' => 2,
            'MARET' => 3,
            'APRIL' => 4,
            'MEI' => 5,
            'JUNI' => 6,
            'JULI' => 7,
            'AGUSTUS' => 8,
            'SEPTEMBER' => 9,
            'OKTOBER' => 10,
            'NOVEMBER' => 11,
            'DESEMBER' => 12,
        ];
        foreach ($map as $m => $w) {
            if (str_contains($u, $m)) {
                return $w;
            }
        }

        return 99;
    }

    public function export(Request $request, AmalFatimahApiService $api): View
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $filters = [
            'tgl_dari' => trim((string) $request->query('tgl_dari', '')),
            'tgl_sampai' => trim((string) $request->query('tgl_sampai', '')),
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'thn_akademik' => trim((string) $request->query('thn_akademik', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'nama_tagihan' => trim((string) $request->query('nama_tagihan', '')),
            'siswa' => trim((string) $request->query('siswa', '')),
            'sort_urutan' => in_array(strtolower(trim((string) $request->query('sort_urutan', 'asc'))), ['asc', 'desc'], true)
                ? strtolower(trim((string) $request->query('sort_urutan', 'asc')))
                : 'asc',
        ];

        $filterOptions = $api->getFilterBuatTagihan();
        $res = $api->getDataTagihan($filters, $perPage, ($page - 1) * $perPage);
        $rows = [];
        $total = 0;
        $errorMsg = '';
        if ($res['ok']) {
            $rows = $res['data']['rows'] ?? [];
            $total = (int) ($res['data']['total'] ?? 0);
        } else {
            $errorMsg = $res['message'] ?? 'Gagal memuat data.';
        }
        $paginator = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('keuangan.tagihan-siswa.export-tagihan', [
            'pageTitle' => 'Export Tagihan',
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'tagihanRows' => $paginator,
            'errorMsg' => $errorMsg,
        ]);
    }

    public function exportPrint(Request $request, AmalFatimahApiService $api): \Illuminate\Http\Response
    {
        $filters = [
            'tgl_dari' => trim((string) $request->input('tgl_dari', $request->query('tgl_dari', ''))),
            'tgl_sampai' => trim((string) $request->input('tgl_sampai', $request->query('tgl_sampai', ''))),
            'thn_angkatan' => trim((string) $request->input('thn_angkatan', $request->query('thn_angkatan', ''))),
            'thn_akademik' => trim((string) $request->input('thn_akademik', $request->query('thn_akademik', ''))),
            'kelas_id' => trim((string) $request->input('kelas_id', $request->query('kelas_id', ''))),
            'nama_tagihan' => trim((string) $request->input('nama_tagihan', $request->query('nama_tagihan', ''))),
            'siswa' => trim((string) $request->input('siswa', $request->query('siswa', ''))),
            'sort_urutan' => in_array(strtolower(trim((string) $request->input('sort_urutan', $request->query('sort_urutan', 'asc')))), ['asc', 'desc'], true)
                ? strtolower(trim((string) $request->input('sort_urutan', $request->query('sort_urutan', 'asc'))))
                : 'asc',
        ];
        $printMode = trim((string) $request->input('print_mode', ''));
        $useCustOnly = $printMode === 'by_custid';
        $selectedTagihanKeys = $useCustOnly ? [] : $this->selectedTagihanKeysFromRequest($request);
        $selectedCustIds = $useCustOnly ? $this->selectedCustIdsFromRequest($request) : [];

        if ($request->isMethod('post') && !$useCustOnly && $selectedTagihanKeys === []) {
            $pdf = Pdf::loadView('keuangan.tagihan-siswa.export-tagihan-print-pdf', [
                'rows' => [],
                'filters' => $filters,
                'errorMessage' => 'Pilih minimal 1 baris tagihan dari centang kiri tabel.',
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('export-tagihan-' . date('Ymd-His') . '.pdf');
        }
        if ($useCustOnly) {
            $res = $api->getDataPembayaranPerNis($filters, $selectedCustIds);
            $raw = $res['ok'] ? ($res['data']['rows'] ?? []) : [];
            $errorMessage = $res['ok'] ? '' : (string) ($res['message'] ?? 'Gagal mengambil data dari server.');

            $billacAkunMap = [];
            $pivot = [];
            foreach ($raw as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $custid = (int) ($r['custid'] ?? 0);
                if ($custid <= 0) {
                    continue;
                }
                $billac = trim((string) ($r['billac'] ?? ''));
                $akun = trim((string) ($r['akun'] ?? ''));
                if ($billac === '' || $akun === '') {
                    continue;
                }

                if (!isset($billacAkunMap[$billac])) {
                    $billacAkunMap[$billac] = [];
                }
                $billacAkunMap[$billac][$akun] = true;

                if (!isset($pivot[$custid])) {
                    $pivot[$custid] = [
                        'tahun_masuk' => trim((string) ($r['tahun_masuk'] ?? '')),
                        'unit' => trim((string) ($r['unit'] ?? '')),
                        'kelas' => trim((string) ($r['kelas'] ?? '')),
                        'kelompok' => trim((string) ($r['kelompok'] ?? '')),
                        'nis' => trim((string) ($r['nis'] ?? '')),
                        'nama' => trim((string) ($r['nama'] ?? '')),
                        'values' => [],
                    ];
                }
                if (!isset($pivot[$custid]['values'][$billac])) {
                    $pivot[$custid]['values'][$billac] = [];
                }
                $pivot[$custid]['values'][$billac][$akun] = (int) ($pivot[$custid]['values'][$billac][$akun] ?? 0) + (int) ($r['nominal'] ?? 0);
            }

            $billacGroups = [];
            $billacOrder = array_keys($billacAkunMap);
            sort($billacOrder);
            foreach ($billacOrder as $billac) {
                $akunList = array_keys($billacAkunMap[$billac]);
                sort($akunList);
                $billacGroups[] = ['billac' => $billac, 'akuns' => $akunList];
            }

            $rows = [];
            $no = 1;
            foreach ($pivot as $custid => $baseRow) {
                $totalAkhir = 0;
                foreach ($billacGroups as $g) {
                    $b = $g['billac'];
                    foreach ($g['akuns'] as $akun) {
                        $v = (int) ($baseRow['values'][$b][$akun] ?? 0);
                        $totalAkhir += $v;
                    }
                }
                $rows[] = [
                    'no' => $no++,
                    'tahun_masuk' => $baseRow['tahun_masuk'],
                    'unit' => $baseRow['unit'],
                    'kelas' => $baseRow['kelas'],
                    'kelompok' => $baseRow['kelompok'],
                    'nis' => $baseRow['nis'],
                    'nama' => $baseRow['nama'],
                    'values' => $baseRow['values'],
                    'total_akhir' => $totalAkhir,
                ];
            }

            $tglDari = trim((string) ($filters['tgl_dari'] ?? ''));
            $tglSampai = trim((string) ($filters['tgl_sampai'] ?? ''));
            if ($tglDari !== '' && $tglSampai !== '') {
                $dateRange = $tglDari . ' s.d ' . $tglSampai;
            } elseif ($tglDari !== '') {
                $dateRange = $tglDari;
            } elseif ($tglSampai !== '') {
                $dateRange = $tglSampai;
            } else {
                $dateRange = now('Asia/Jakarta')->format('Y-m-d');
            }

            $pdf = Pdf::loadView('keuangan.tagihan-siswa.data-pembayaran-per-nis-pdf', [
                'rows' => $rows,
                'billacGroups' => $billacGroups,
                'dateRange' => $dateRange,
                'errorMessage' => $errorMessage,
            ])->setPaper('a4', 'landscape');

            return $pdf->stream('data-pembayaran-per-nis-' . date('Ymd-His') . '.pdf');
        }

        $rawRows = $this->fetchAllDataTagihanRowsForExport($api, $filters, 5000);
        if (is_array($rawRows) && $useCustOnly && $selectedCustIds !== []) {
            $selectedMap = array_fill_keys($selectedCustIds, true);
            $rawRows = array_values(array_filter($rawRows, static function ($r) use ($selectedMap) {
                if (!is_array($r)) {
                    return false;
                }
                $cid = (int) ($r['custid'] ?? 0);

                return $cid > 0 && isset($selectedMap[$cid]);
            }));
        } elseif (is_array($rawRows) && $selectedTagihanKeys !== []) {
            $selectedMap = array_fill_keys($selectedTagihanKeys, true);
            $rawRows = array_values(array_filter($rawRows, static function ($r) use ($selectedMap) {
                if (!is_array($r)) {
                    return false;
                }
                $cid = (int) ($r['custid'] ?? 0);
                $billcd = trim((string) ($r['billcd'] ?? ''));
                if ($cid <= 0 || $billcd === '') {
                    return false;
                }
                $key = $cid . '|' . $billcd;

                return isset($selectedMap[$key]);
            }));
        }
        $rows = $rawRows === null ? [] : $this->buildDataTagihanExportRowsFromApiRows($rawRows);
        $errorMessage = $rawRows === null ? 'Gagal mengambil data dari server.' : '';

        $pdf = Pdf::loadView('keuangan.tagihan-siswa.export-tagihan-print-pdf', [
            'rows' => $rows,
            'filters' => $filters,
            'errorMessage' => $errorMessage,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('export-tagihan-' . date('Ymd-His') . '.pdf');
    }

    public function rekap(Request $request, AmalFatimahApiService $api): View
    {
        $hasSearchRequest = $request->query->count() > 0;
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $filters = [
            'tgl_dari' => trim((string) $request->query('tgl_dari', '')),
            'tgl_sampai' => trim((string) $request->query('tgl_sampai', '')),
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'thn_akademik' => trim((string) $request->query('thn_akademik', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'nama_tagihan' => trim((string) $request->query('nama_tagihan', '')),
            'siswa' => trim((string) $request->query('siswa', '')),
            'sort_urutan' => in_array(strtolower(trim((string) $request->query('sort_urutan', 'asc'))), ['asc', 'desc'], true)
                ? strtolower(trim((string) $request->query('sort_urutan', 'asc')))
                : 'asc',
        ];

        $filterOptions = $api->getFilterBuatTagihan();
        $rows = [];
        $total = 0;
        $errorMsg = '';
        if ($hasSearchRequest) {
            $res = $api->getDataTagihan($filters, $perPage, ($page - 1) * $perPage);
            if ($res['ok']) {
                $rows = $res['data']['rows'] ?? [];
                $total = (int) ($res['data']['total'] ?? 0);
            } else {
                $errorMsg = $res['message'] ?? 'Gagal memuat data.';
            }
        }

        $paginator = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('keuangan.tagihan-siswa.rekap-tagihan', [
            'pageTitle' => 'Rekap Tagihan',
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'rekapRows' => $paginator,
            'errorMsg' => $errorMsg,
            'hasSearchRequest' => $hasSearchRequest,
        ]);
    }
}

