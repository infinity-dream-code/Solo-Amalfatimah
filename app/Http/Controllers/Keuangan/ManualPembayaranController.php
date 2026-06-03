<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ManualPembayaranController extends Controller
{
    /** Manual biasa: NIS / NUM2ND / NOCUST / nama */
    private const MODE_PENDAFTARAN = 'pendaftaran';

    /** NIS di UI = NOCUST di DB; bukan no. pendaftaran */
    private const MODE_NIS = 'nis';

    /** Hanya no. pendaftaran (NUM2ND) + nama; bukan NIS/NOCUST */
    private const MODE_NON_SISWA = 'non_siswa';

    public function index(Request $request, AmalFatimahApiService $api): View
    {
        return $this->manualPembayaranIndex($request, $api, self::MODE_PENDAFTARAN);
    }

    public function nis(Request $request, AmalFatimahApiService $api): View
    {
        return $this->manualPembayaranIndex($request, $api, self::MODE_NIS);
    }

    public function nonSiswa(Request $request, AmalFatimahApiService $api): View
    {
        return $this->manualPembayaranIndex($request, $api, self::MODE_NON_SISWA);
    }

    public function searchSiswa(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $mode = trim((string) $request->query('mode', self::MODE_PENDAFTARAN));
        if (!in_array($mode, [self::MODE_PENDAFTARAN, self::MODE_NIS, self::MODE_NON_SISWA], true)) {
            $mode = self::MODE_PENDAFTARAN;
        }

        if ($q === '') {
            return response()->json(['rows' => []]);
        }

        $raw = $api->getSiswa(['search' => $q], 40, 0);
        $rows = [];
        foreach ($raw as $s) {
            if (!is_array($s)) {
                continue;
            }
            if ($mode === self::MODE_NON_SISWA && trim((string) ($s['num2nd'] ?? '')) === '') {
                continue;
            }
            $rows[] = $this->mapSiswaSearchRow($s, $mode);
        }

        return response()->json(['rows' => $rows]);
    }

    public function printKuitansi(Request $request, AmalFatimahApiService $api): Response|RedirectResponse
    {
        $custid = (int) $request->input('custid', 0);
        $custids = $request->input('custids', []);
        if (!is_array($custids)) {
            $custids = [];
        }
        $custids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $custids), static fn ($n) => $n > 0)));
        if ($custid > 0) {
            $custids[] = $custid;
            $custids = array_values(array_unique($custids));
        }
        if ($custids === []) {
            return redirect()->back()->with('manual_pembayaran_error', 'Data siswa tidak valid untuk cetak kuitansi.');
        }

        $today = now('Asia/Jakarta')->format('Y-m-d');
        $filters = [
            'tgl_dari' => $today,
            'tgl_sampai' => $today,
            'thn_angkatan' => '',
            'thn_akademik' => '',
            'kelas_id' => '',
            'nama_tagihan' => '',
            'siswa' => '',
        ];

        $res = $api->getKartuSiswaPenerimaan($filters, $custids);
        if (!$res['ok']) {
            return redirect()->back()->with('manual_pembayaran_error', $res['message'] ?? 'Gagal mengambil data kuitansi.');
        }
        $err = trim((string) ($res['data']['error'] ?? ''));
        if ($err !== '') {
            return redirect()->back()->with('manual_pembayaran_error', $err);
        }
        $cards = $res['data']['cards'] ?? [];
        if (!is_array($cards) || $cards === []) {
            $resAll = $api->getKartuSiswaPenerimaan([
                'tgl_dari' => '',
                'tgl_sampai' => '',
                'thn_angkatan' => '',
                'thn_akademik' => '',
                'kelas_id' => '',
                'nama_tagihan' => '',
                'siswa' => '',
            ], $custids);
            if ($resAll['ok']) {
                $cards = $resAll['data']['cards'] ?? [];
            }
        }
        if (!is_array($cards) || $cards === []) {
            return redirect()->back()->with('manual_pembayaran_error', 'Tidak ada data kuitansi untuk pembayaran ini.');
        }

        $pdf = Pdf::loadView('keuangan.penerimaan-siswa.kuitansi-pdf', [
            'cards' => $cards,
            'dengan_2000' => false,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('kuitansi-manual-' . date('Ymd-His') . '.pdf');
    }

    private function manualPembayaranIndex(Request $request, AmalFatimahApiService $api, string $mode): View
    {
        $selectedCustid = (int) $request->query('custid', 0);
        $searchSiswa = trim((string) $request->query('siswa_search', ''));
        $selectedBillcds = $request->query('selected_billcds', []);
        if (!is_array($selectedBillcds)) {
            $selectedBillcds = [];
        }

        $tahunAjaranOptions = $api->getThnAka();
        $bankOptions = $api->getManualPembayaranBankOptions();

        $selectedSiswa = [];
        $selectedSiswaLabel = '';
        $tagihanRows = [];
        $saldoVa = 0;
        $totalTagihan = 0;
        $manualPembayaranError = '';
        $thnAkaFilter = trim((string) $request->query('thn_aka', ''));
        if ($selectedCustid > 0) {
            $detail = $api->getSiswaByCustid($selectedCustid, $selectedBillcds, $thnAkaFilter);
            if ($detail['ok']) {
                $selectedSiswa = is_array($detail['data'] ?? null) ? $detail['data'] : [];
                $tagihanRows = is_array($selectedSiswa['tagihan_belum_lunas'] ?? null) ? $selectedSiswa['tagihan_belum_lunas'] : [];
                $saldoVa = (int) ($selectedSiswa['SALDO_VA'] ?? $selectedSiswa['saldo_va'] ?? $selectedSiswa['SALDO'] ?? $selectedSiswa['saldo'] ?? 0);
                $totalTagihan = (int) ($selectedSiswa['TOTAL_TAGIHAN'] ?? $selectedSiswa['total_tagihan'] ?? 0);
                $selectedSiswaLabel = $this->formatSiswaLabel($selectedSiswa, $mode);
            } else {
                $manualPembayaranError = $detail['message'] ?: 'Gagal memuat data siswa/tagihan dari server.';
            }
        }

        $pageTitle = match ($mode) {
            self::MODE_NIS => 'Manual Pembayaran NIS',
            self::MODE_NON_SISWA => 'Manual Pembayaran No Pendaftaran',
            default => 'Manual Pembayaran',
        };

        return view('keuangan.manual-pembayaran.index', [
            'pageTitle' => $pageTitle,
            'mpMode' => $mode,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'bankOptions' => $bankOptions,
            'selectedCustid' => $selectedCustid,
            'selectedSiswa' => $selectedSiswa,
            'selectedSiswaLabel' => $selectedSiswaLabel,
            'tagihanRows' => $tagihanRows,
            'saldoVa' => $saldoVa,
            'totalTagihan' => $totalTagihan,
            'manualPembayaranError' => $manualPembayaranError,
            'manualPembayaranSuccess' => (bool) session('manual_pembayaran_success', false),
            'manualPembayaranSuccessMessage' => trim((string) session('manual_pembayaran_message', '')),
            'manualPembayaranSuccessCustid' => (int) session('manual_pembayaran_custid', 0),
            'filters' => [
                'siswa_search' => $searchSiswa,
                'thn_aka' => trim((string) $request->query('thn_aka', '')),
                'tanggal_bayar' => trim((string) $request->query('tanggal_bayar', now('Asia/Jakarta')->format('d-m-Y'))),
                'fidbank' => trim((string) $request->query('fidbank', '1140000')),
            ],
        ]);
    }

    public function submit(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $custid = (int) $request->input('custid', 0);
        $fidbank = trim((string) $request->input('fidbank', ''));
        $billcds = $request->input('selected_billcds', []);
        if (!is_array($billcds)) {
            $billcds = [];
        }
        $billcds = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $billcds), static fn ($v) => $v !== ''));

        if ($request->routeIs('keu.manual_nis.submit')) {
            $successHome = redirect()->route('keu.manual_nis');
        } elseif ($request->routeIs('keu.manual_non_siswa.submit')) {
            $successHome = redirect()->route('keu.manual_non_siswa');
        } else {
            $successHome = redirect()->route('keu.manual');
        }

        if ($custid <= 0) {
            return redirect()->back()->withInput()->with('manual_pembayaran_error', 'Data siswa tidak valid. Muat ulang halaman dan pilih siswa lagi.');
        }
        if ($billcds === []) {
            return redirect()->back()->withInput()->with('manual_pembayaran_error', 'Pilih minimal satu tagihan (centang baris di tabel) sebelum klik Bayar.');
        }

        $res = $api->createManualPembayaran($custid, $fidbank, $billcds, '');
        if (!$res['ok']) {
            return redirect()->back()->withInput()->with('manual_pembayaran_error', $res['message'] ?: 'Gagal memproses pembayaran manual.');
        }

        return $successHome->with([
            'manual_pembayaran_success' => true,
            'manual_pembayaran_custid' => $custid,
            'manual_pembayaran_message' => $res['message'] ?: 'Pembayaran manual berhasil diproses.',
        ]);
    }

    /**
     * @param array<string, mixed> $s
     * @return array{cid: int, label: string, nocust: string, nis: string, num2nd: string, nmcust: string, angkatan: string}
     */
    private function mapSiswaSearchRow(array $s, string $mode): array
    {
        $nocust = trim((string) ($s['nocust'] ?? ''));
        $nmcust = trim((string) ($s['nmcust'] ?? ''));
        $num2nd = trim((string) ($s['num2nd'] ?? ''));
        $angkatan = trim((string) ($s['desc04'] ?? ''));
        $nisLike = $nocust !== '' ? $nocust : trim((string) ($s['nis'] ?? ''));

        return [
            'cid' => (int) ($s['custid'] ?? 0),
            'label' => $this->formatSiswaPartsLabel($mode, $nisLike, $num2nd, $nocust, $nmcust),
            'nocust' => $nocust,
            'nis' => trim((string) ($s['nis'] ?? '')),
            'nis_like' => $nisLike,
            'num2nd' => $num2nd,
            'nmcust' => $nmcust,
            'angkatan' => $angkatan,
        ];
    }

    /**
     * @param array<string, mixed> $s
     */
    private function formatSiswaLabel(array $s, string $mode): string
    {
        $nocust = trim((string) ($s['NOCUST'] ?? $s['nocust'] ?? ''));
        $nmcust = trim((string) ($s['NMCUST'] ?? $s['nmcust'] ?? ''));
        $num2nd = trim((string) ($s['NUM2ND'] ?? $s['num2nd'] ?? ''));
        $nisLike = $nocust !== '' ? $nocust : trim((string) ($s['nis'] ?? ''));

        return $this->formatSiswaPartsLabel($mode, $nisLike, $num2nd, $nocust, $nmcust);
    }

    private function formatSiswaPartsLabel(
        string $mode,
        string $nisLike,
        string $num2nd,
        string $nocust,
        string $nmcust
    ): string {
        if ($mode === self::MODE_NON_SISWA) {
            $id = $num2nd !== '' ? $num2nd : '—';

            return $nmcust !== '' ? $id . ' - ' . $nmcust : $id;
        }

        $nis = $nocust !== '' ? $nocust : ($nisLike !== '' ? $nisLike : '');
        if ($nis === '' && $mode === self::MODE_PENDAFTARAN && $num2nd !== '') {
            $nis = $num2nd;
        }
        if ($nis === '') {
            return $nmcust !== '' ? $nmcust : '—';
        }

        return $nmcust !== '' ? $nis . ' - ' . $nmcust : $nis;
    }
}
