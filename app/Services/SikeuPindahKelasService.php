<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pindah kelas langsung ke DB SIKEU (scctcust.CODE03 → mst_kelas.id).
 * Dipakai bila WS remote belum di-update atau sebagai jalur utama bila SIKEU_DB_* di-set.
 */
class SikeuPindahKelasService
{
    public function isConfigured(): bool
    {
        $db = (string) config('database.connections.sikeu.database', '');

        return $db !== '';
    }

    /**
     * @return array{ok: bool, message: string, total: int, rows: array<int, array<string, mixed>>}
     */
    public function getSiswaByKelas(int $kelasSumber, ?string $search = null, int $limit = 10, int $offset = 0): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'Koneksi database SIKEU belum dikonfigurasi', 'total' => 0, 'rows' => []];
        }

        $search = $search !== null ? trim($search) : '';
        if ($kelasSumber <= 0 && $search === '') {
            return ['ok' => true, 'message' => '', 'total' => 0, 'rows' => []];
        }

        try {
            $conn = DB::connection('sikeu');

            if ($kelasSumber > 0) {
                $kelasRow = $conn->table('mst_kelas')
                    ->where('id', $kelasSumber)
                    ->first(['id', 'kelas', 'unit', 'kelompok', 'jenjang']);
                if (!$kelasRow) {
                    return ['ok' => false, 'message' => 'Kelas sumber tidak ditemukan', 'total' => 0, 'rows' => []];
                }
            }

            $query = $conn->table('scctcust as c');
            if ($kelasSumber > 0) {
                $query->whereRaw('TRIM(c.CODE03) = ?', [(string) $kelasSumber]);
            }
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('TRIM(c.NOCUST) = ?', [$search])
                        ->orWhereRaw('TRIM(c.NUM2ND) = ?', [$search])
                        ->orWhereRaw('TRIM(c.NMCUST) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('TRIM(c.NOCUST) LIKE ?', ['%' . $search . '%']);
                });
            }

            $total = (clone $query)->count();

            $limit = min(max($limit, 1), 200);
            $offset = max($offset, 0);

            $rows = $query
                ->leftJoin('mst_kelas as mk', function ($join) {
                    $join->on(DB::raw('CAST(mk.id AS CHAR)'), '=', DB::raw('TRIM(c.CODE03)'));
                })
                ->orderBy('c.NMCUST')
                ->offset($offset)
                ->limit($limit)
                ->get([
                    'c.CUSTID as custid',
                    DB::raw('TRIM(c.NOCUST) as nocust'),
                    DB::raw('TRIM(c.NMCUST) as nmcust'),
                    DB::raw('TRIM(c.NUM2ND) as num2nd'),
                    DB::raw('TRIM(c.CODE02) as code02'),
                    DB::raw("COALESCE(NULLIF(TRIM(mk.unit), ''), TRIM(c.CODE02), '') as unit_label"),
                    DB::raw("COALESCE(NULLIF(TRIM(mk.jenjang), ''), TRIM(c.DESC02), '') as desc02"),
                    DB::raw('TRIM(c.CODE03) as code03'),
                    DB::raw("COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC03), '') as desc03"),
                    DB::raw('TRIM(c.DESC04) as desc04'),
                    'c.STCUST as stcust',
                ])
                ->map(static fn ($r) => (array) $r)
                ->all();

            return [
                'ok' => true,
                'message' => '',
                'total' => $total,
                'rows' => $rows,
            ];
        } catch (Throwable $e) {
            Log::error('[SIKEU] getSiswaByKelas: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Gagal mengambil data siswa dari database', 'total' => 0, 'rows' => []];
        }
    }

    /**
     * @param  array<int, int>  $custids
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function pindahKelas(int $kelasSumber, int $kelasTujuan, string $mode, array $custids = []): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'Koneksi database SIKEU belum dikonfigurasi', 'data' => []];
        }

        if ($kelasTujuan <= 0) {
            return ['ok' => false, 'message' => 'kelas_tujuan wajib diisi', 'data' => []];
        }
        if ($kelasSumber > 0 && $kelasSumber === $kelasTujuan) {
            return ['ok' => false, 'message' => 'Kelas sumber dan tujuan tidak boleh sama', 'data' => []];
        }
        if (!in_array($mode, ['semua', 'pilihan'], true)) {
            return ['ok' => false, 'message' => "mode harus bernilai 'semua' atau 'pilihan'", 'data' => []];
        }
        if ($mode === 'semua' && $kelasSumber <= 0) {
            return ['ok' => false, 'message' => 'Mode semua membutuhkan kelas asal', 'data' => []];
        }

        try {
            $conn = DB::connection('sikeu');

            $kelasSumberRow = null;
            if ($kelasSumber > 0) {
                $kelasSumberRow = $conn->table('mst_kelas')->where('id', $kelasSumber)->first();
                if (!$kelasSumberRow) {
                    return ['ok' => false, 'message' => 'Kelas sumber tidak ditemukan', 'data' => []];
                }
            }

            $kelasTujuanRow = $conn->table('mst_kelas')->where('id', $kelasTujuan)->first();
            if (!$kelasTujuanRow) {
                return ['ok' => false, 'message' => 'Kelas tujuan tidak ditemukan', 'data' => []];
            }

            $kelasTujuanNama = trim((string) ($kelasTujuanRow->kelas ?? ''));
            $idTujuan = (string) $kelasTujuanRow->id;
            $kelompokTujuan = trim((string) ($kelasTujuanRow->kelompok ?? ''));

            if ($mode === 'semua') {
                $idSumber = (string) ($kelasSumberRow->id ?? $kelasSumber);
                $total = $conn->table('scctcust')
                    ->whereRaw('TRIM(CODE03) = ?', [$idSumber])
                    ->update([
                        'DESC02' => $kelasTujuanNama,
                        'CODE03' => $idTujuan,
                        'DESC03' => $kelompokTujuan,
                    ]);

                return [
                    'ok' => true,
                    'message' => 'Pemindahan kelas berhasil',
                    'data' => ['mode' => 'semua', 'total_dipindah' => $total],
                ];
            }

            $custids = array_values(array_filter(array_map('intval', $custids), static fn (int $v) => $v > 0));
            if ($custids === []) {
                return ['ok' => false, 'message' => 'custids wajib diisi untuk mode pilihan', 'data' => []];
            }

            $total = $conn->table('scctcust')
                ->whereIn('CUSTID', $custids)
                ->update([
                    'DESC02' => $kelasTujuanNama,
                    'CODE03' => $idTujuan,
                    'DESC03' => $kelompokTujuan,
                ]);

            return [
                'ok' => true,
                'message' => 'Pemindahan kelas berhasil',
                'data' => ['mode' => 'pilihan', 'total_dipindah' => $total, 'custids' => $custids],
            ];
        } catch (Throwable $e) {
            Log::error('[SIKEU] pindahKelas: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Gagal memindahkan kelas', 'data' => []];
        }
    }
}
