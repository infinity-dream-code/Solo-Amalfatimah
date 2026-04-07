<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    private function manualPembayaranIndex(Request $request, AmalFatimahApiService $api, string $mode): View
    {
        $selectedCustid = (int) $request->query('custid', 0);
        $searchSiswa = trim((string) $request->query('siswa_search', ''));
        $selectedBillcds = $request->query('selected_billcds', []);
        if (!is_array($selectedBillcds)) {
            $selectedBillcds = [];
        }

        $siswaOptions = $this->loadSiswaOptions($api);
        if ($mode === self::MODE_NON_SISWA) {
            // Non-siswa hanya boleh cari via no. pendaftaran (NUM2ND).
            $siswaOptions = array_values(array_filter($siswaOptions, static function (array $row): bool {
                return trim((string) ($row['num2nd'] ?? '')) !== '';
            }));
        }
        $searchUpper = mb_strtoupper($searchSiswa);
        usort($siswaOptions, static function (array $a, array $b) use ($selectedCustid, $searchUpper, $mode): int {
            $aCid = (int) ($a['custid'] ?? 0);
            $bCid = (int) ($b['custid'] ?? 0);
            if ($selectedCustid > 0) {
                if ($aCid === $selectedCustid && $bCid !== $selectedCustid) {
                    return -1;
                }
                if ($bCid === $selectedCustid && $aCid !== $selectedCustid) {
                    return 1;
                }
            }

            if ($searchUpper !== '') {
                if ($mode === self::MODE_NIS) {
                    // NIS = NOCUST; tanpa no. daftar (NUM2ND).
                    $aText = mb_strtoupper(trim((string) (($a['nocust'] ?? '') . ' ' . ($a['nmcust'] ?? '') . ' ' . ($a['desc04'] ?? ''))));
                    $bText = mb_strtoupper(trim((string) (($b['nocust'] ?? '') . ' ' . ($b['nmcust'] ?? '') . ' ' . ($b['desc04'] ?? ''))));
                } elseif ($mode === self::MODE_NON_SISWA) {
                    // Hanya no. daftar + nama; tanpa NIS/NOCUST.
                    $aText = mb_strtoupper(trim((string) (($a['num2nd'] ?? '') . ' ' . ($a['nmcust'] ?? '') . ' ' . ($a['desc04'] ?? ''))));
                    $bText = mb_strtoupper(trim((string) (($b['num2nd'] ?? '') . ' ' . ($b['nmcust'] ?? '') . ' ' . ($b['desc04'] ?? ''))));
                } else {
                    $aText = mb_strtoupper(trim((string) (($a['nis'] ?? '') . ' ' . ($a['num2nd'] ?? '') . ' ' . ($a['nocust'] ?? '') . ' ' . ($a['nmcust'] ?? '') . ' ' . ($a['desc04'] ?? ''))));
                    $bText = mb_strtoupper(trim((string) (($b['nis'] ?? '') . ' ' . ($b['num2nd'] ?? '') . ' ' . ($b['nocust'] ?? '') . ' ' . ($b['nmcust'] ?? '') . ' ' . ($b['desc04'] ?? ''))));
                }
                $aStarts = str_starts_with($aText, $searchUpper) ? 1 : 0;
                $bStarts = str_starts_with($bText, $searchUpper) ? 1 : 0;
                if ($aStarts !== $bStarts) {
                    return $aStarts > $bStarts ? -1 : 1;
                }
                $aHas = str_contains($aText, $searchUpper) ? 1 : 0;
                $bHas = str_contains($bText, $searchUpper) ? 1 : 0;
                if ($aHas !== $bHas) {
                    return $aHas > $bHas ? -1 : 1;
                }
            }

            return strcmp(trim((string) ($a['nmcust'] ?? '')), trim((string) ($b['nmcust'] ?? '')));
        });
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
            } else {
                $manualPembayaranError = $detail['message'] ?: 'Gagal memuat data siswa/tagihan dari server.';
            }
        }
        if ($selectedCustid > 0) {
            foreach ($siswaOptions as $s) {
                if ((int) ($s['custid'] ?? 0) !== $selectedCustid) {
                    continue;
                }
                $nocust = trim((string) ($s['nocust'] ?? ''));
                $nmcust = trim((string) ($s['nmcust'] ?? ''));
                $angkatan = trim((string) ($s['desc04'] ?? ''));
                $nis = trim((string) ($s['nis'] ?? ''));
                $num2nd = trim((string) ($s['num2nd'] ?? ''));
                if ($mode === self::MODE_NIS) {
                    $selectedSiswaLabel = $nocust . ' - ' . $nmcust . ' - ' . $angkatan;
                } elseif ($mode === self::MODE_NON_SISWA) {
                    $selectedSiswaLabel = $num2nd . ' - ' . $nmcust . ' - ' . $angkatan;
                } else {
                    $lead = $num2nd !== '' ? $num2nd : ($nis !== '' ? $nis : $nocust);
                    $selectedSiswaLabel = $lead . ' - ' . $nmcust . ' - ' . $angkatan;
                }
                break;
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
            'siswaOptions' => $siswaOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'bankOptions' => $bankOptions,
            'selectedCustid' => $selectedCustid,
            'selectedSiswa' => $selectedSiswa,
            'selectedSiswaLabel' => $selectedSiswaLabel,
            'tagihanRows' => $tagihanRows,
            'saldoVa' => $saldoVa,
            'totalTagihan' => $totalTagihan,
            'manualPembayaranError' => $manualPembayaranError,
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

        return $successHome->with('status', $res['message'] ?: 'Pembayaran manual berhasil diproses.');
    }

    /**
     * WS getSiswa membatasi limit per request, jadi perlu paging agar autocomplete tidak kehilangan data.
     *
     * @return list<array<string,mixed>>
     */
    private function loadSiswaOptions(AmalFatimahApiService $api): array
    {
        return Cache::remember('manual_pembayaran:siswa_options:stcust_1', now()->addMinutes(5), function () use ($api): array {
            $rows = [];
            $seen = [];
            $limit = 200;
            $maxRows = 5000;

            for ($offset = 0; $offset < $maxRows; $offset += $limit) {
                $chunk = $api->getSiswa(['search' => '', 'stcust' => 1], $limit, $offset);
                if ($chunk === []) {
                    break;
                }

                foreach ($chunk as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $cid = (int) ($row['custid'] ?? 0);
                    if ($cid <= 0 || isset($seen[$cid])) {
                        continue;
                    }
                    $seen[$cid] = true;
                    $rows[] = $row;
                }

                if (count($chunk) < $limit || count($rows) >= $maxRows) {
                    break;
                }
            }

            return $rows;
        });
    }
}
