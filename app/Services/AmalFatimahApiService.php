<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmalFatimahApiService
{
    public function __construct(protected JwtService $jwt)
    {
    }

    /**
     * Login user via WS users table.
     *
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function loginUser(string $login, string $password): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'loginUser', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'loginUser',
            'token' => $token,
            'login' => trim($login),
            'password' => $password,
        ];

        try {
            $response = Http::timeout(20)->connectTimeout(10)->post($url, $body);
            $json = $response->json() ?? [];
            $status = (int) ($json['status'] ?? 0);
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            if (!$response->successful() || $status !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Username/email atau password salah.'),
                    'data' => $data,
                ];
            }

            return [
                'ok' => true,
                'message' => '',
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] loginUser: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan login.',
                'data' => [],
            ];
        }
    }

    public function dashboard(): ?array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';

        $token = $this->jwt->encode(['sub' => 'dashboard', 'rnd' => uniqid()], $jwtKey);
        $payload = ['method' => 'dashboard', 'token' => $token];

        Log::channel('single')->info('[WS Amal Fatimah] Request dashboard', [
            'url' => $url,
            'has_jwt_key' => !empty($jwtKey),
        ]);

        try {
            $response = Http::timeout(15)->post($url, $payload);

            Log::channel('single')->info('[WS Amal Fatimah] Response', [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body_preview' => substr($response->body(), 0, 500),
                'body_full' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::channel('single')->info('[WS Amal Fatimah] Parsed JSON keys', [
                    'top_keys' => is_array($data) ? array_keys($data) : 'not_array',
                ]);
                return $data;
            }

            Log::warning('[WS Amal Fatimah] HTTP failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function tagihandashboard(): ?array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';

        $token = $this->jwt->encode(['sub' => 'tagihandashboard', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(15)->post($url, [
                'method' => 'tagihandashboard',
                'token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $inner = $data['data'] ?? $data;
                if (is_array($inner)) {
                    return $inner;
                }
                return $data;
            }
            return null;
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] tagihandashboard: ' . $e->getMessage());
            return null;
        }
    }

    public function tagihanbayarDashboard(): ?array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';

        $token = $this->jwt->encode(['sub' => 'tagihanbayarDashboard', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(15)->post($url, [
                'method' => 'tagihanbayarDashboard',
                'token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $inner = $data['data'] ?? $data;
                return is_array($inner) ? $inner : [];
            }
            return [];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] tagihanbayarDashboard: ' . $e->getMessage());
            return [];
        }
    }

    public function getKelas(array $filters = []): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getKelas', 'rnd' => uniqid()], $jwtKey);

        $payload = array_filter([
            'method' => 'getKelas',
            'token' => $token,
            'jenjang' => $filters['jenjang'] ?? null,
            'unit' => $filters['unit'] ?? null,
            'kelompok' => $filters['kelompok'] ?? null,
        ], static fn ($value) => !is_null($value) && $value !== '');

        try {
            $response = Http::timeout(15)->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] getKelas HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            $inner = $data['data'] ?? $data;

            return is_array($inner) ? array_values($inner) : [];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getKelas: ' . $e->getMessage());
            return [];
        }
    }

    public function getKelasUnits(): array
    {
        $rows = $this->getKelas();
        $units = [];

        foreach ($rows as $row) {
            $item = is_array($row) ? array_change_key_case($row, CASE_LOWER) : [];
            $unit = trim((string) ($item['unit'] ?? ''));

            if ($unit !== '') {
                $units[$unit] = $unit;
            }
        }

        ksort($units);

        return array_values($units);
    }

    public function deleteKelas(int $id): bool
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'deleteKelas', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(15)->post($url, [
                'method' => 'deleteKelas',
                'token' => $token,
                'id' => $id,
            ]);

            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] deleteKelas HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $data = $response->json();
            $status = (int) ($data['status'] ?? 0);

            return $status === 200;
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] deleteKelas: ' . $e->getMessage());
            return false;
        }
    }

    public function createKelas(array $payload): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'createKelas', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'createKelas',
            'token' => $token,
            'kelas' => trim((string) ($payload['kelas'] ?? '')),
            'jenjang' => trim((string) ($payload['jenjang'] ?? '')),
            'unit' => trim((string) ($payload['unit'] ?? '')),
            'kelompok' => trim((string) ($payload['kelompok'] ?? '')),
        ];

        try {
            $response = Http::timeout(15)->post($url, $body);
            $json = $response->json();

            if ($response->successful() && (int) ($json['status'] ?? 0) === 201) {
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Kelas berhasil ditambahkan'),
                    'data' => $json['data'] ?? [],
                ];
            }

            Log::warning('[WS Amal Fatimah] createKelas failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal menambahkan data kelas'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] createKelas: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi web service',
                'data' => [],
            ];
        }
    }

    public function getAkun(?string $namaAkun = null): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getAkun', 'rnd' => uniqid()], $jwtKey);

        $payload = array_filter([
            'method' => 'getAkun',
            'token' => $token,
            'NamaAkun' => $namaAkun,
        ], static fn ($value) => !is_null($value) && $value !== '');

        try {
            $response = Http::timeout(15)->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] getAkun HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            $inner = $data['data'] ?? $data;
            if (!is_array($inner)) {
                return [];
            }

            return array_map(static function ($row) {
                if (is_array($row)) {
                    return array_change_key_case($row, CASE_LOWER);
                }
                return is_object($row) ? array_change_key_case((array) $row, CASE_LOWER) : [];
            }, array_values($inner));
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getAkun: ' . $e->getMessage());
            return [];
        }
    }

    public function createAkun(array $payload): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'createAkun', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(15)->post($url, [
                'method' => 'createAkun',
                'token' => $token,
                'KodeAkun' => trim((string) ($payload['kodeakun'] ?? '')),
                'NamaAkun' => trim((string) ($payload['namaakun'] ?? '')),
                'NoRek' => trim((string) ($payload['norek'] ?? '')),
            ]);

            $json = $response->json();

            if ($response->successful() && (int) ($json['status'] ?? 0) === 201) {
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Akun berhasil ditambahkan'),
                    'data' => $json['data'] ?? [],
                ];
            }

            Log::warning('[WS Amal Fatimah] createAkun failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal menambahkan akun'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] createAkun: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi web service',
                'data' => [],
            ];
        }
    }

    public function getThnAka(?string $keyword = null): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getThnAka', 'rnd' => uniqid()], $jwtKey);

        $payload = array_filter([
            'method' => 'getThnAka',
            'token' => $token,
            'thn_aka' => $keyword,
        ], static fn ($value) => !is_null($value) && $value !== '');

        try {
            $response = Http::timeout(15)->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] getThnAka HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            $inner = $data['data'] ?? $data;
            if (!is_array($inner)) {
                return [];
            }

            $rows = array_map(static function ($row) {
                if (is_array($row)) {
                    return array_change_key_case($row, CASE_LOWER);
                }
                return is_object($row) ? array_change_key_case((array) $row, CASE_LOWER) : [];
            }, array_values($inner));

            if ($keyword === null || $keyword === '') {
                return $rows;
            }

            $needle = mb_strtolower($keyword);
            return array_values(array_filter($rows, static function ($row) use ($needle) {
                $thn = mb_strtolower((string) ($row['thn_aka'] ?? ''));
                return str_contains($thn, $needle);
            }));
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getThnAka: ' . $e->getMessage());
            return [];
        }
    }

    public function createThnAka(string $thnAka): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'createThnAka', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(15)->post($url, [
                'method' => 'createThnAka',
                'token' => $token,
                'thn_aka' => trim($thnAka),
            ]);

            $json = $response->json();

            if ($response->successful() && (int) ($json['status'] ?? 0) === 201) {
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Tahun akademik berhasil ditambahkan'),
                    'data' => $json['data'] ?? [],
                ];
            }

            Log::warning('[WS Amal Fatimah] createThnAka failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal menambahkan tahun akademik'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] createThnAka: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi web service',
                'data' => [],
            ];
        }
    }

    public function getFilterSiswa(): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getFilterSiswa', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(20)->post($url, [
                'method' => 'getFilterSiswa',
                'token' => $token,
            ]);

            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] getFilterSiswa HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['angkatan' => [], 'sekolah' => [], 'kelas' => []];
            }

            $data = $response->json();
            $inner = $data['data'] ?? [];

            return is_array($inner) ? $inner : ['angkatan' => [], 'sekolah' => [], 'kelas' => []];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getFilterSiswa: ' . $e->getMessage());
            return ['angkatan' => [], 'sekolah' => [], 'kelas' => []];
        }
    }

    public function getSiswaCount(array $filters): int
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getSiswaCount', 'rnd' => uniqid()], $jwtKey);

        $body = array_filter([
            'method' => 'getSiswaCount',
            'token' => $token,
            'search' => $filters['search'] ?? null,
            'DESC04' => $filters['desc04'] ?? null,
            'CODE02' => $filters['code02'] ?? null,
            'DESC02' => $filters['desc02'] ?? null,
            'STCUST' => $filters['stcust'] ?? null,
        ], static fn ($v) => !is_null($v) && $v !== '');

        try {
            $response = Http::timeout(20)->post($url, $body);
            if (!$response->successful()) {
                return 0;
            }
            $json = $response->json();
            $inner = $json['data'] ?? [];
            if (is_array($inner) && isset($inner['total'])) {
                return (int) $inner['total'];
            }

            return 0;
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getSiswaCount: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSiswa(array $filters, int $limit = 10, int $offset = 0): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getSiswa', 'rnd' => uniqid()], $jwtKey);

        $body = array_filter([
            'method' => 'getSiswa',
            'token' => $token,
            'search' => $filters['search'] ?? null,
            'DESC04' => $filters['desc04'] ?? null,
            'CODE02' => $filters['code02'] ?? null,
            'DESC02' => $filters['desc02'] ?? null,
            'STCUST' => $filters['stcust'] ?? null,
            'limit' => $limit,
            'offset' => $offset,
        ], static function ($v, $k) {
            if (in_array($k, ['limit', 'offset'], true)) {
                return true;
            }
            return !is_null($v) && $v !== '';
        }, ARRAY_FILTER_USE_BOTH);

        try {
            $response = Http::timeout(30)->post($url, $body);
            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] getSiswa HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }
            $data = $response->json();
            $inner = $data['data'] ?? $data;
            if (!is_array($inner)) {
                return [];
            }

            return array_map(static function ($row) {
                if (is_array($row)) {
                    return array_change_key_case($row, CASE_LOWER);
                }
                return is_object($row) ? array_change_key_case((array) $row, CASE_LOWER) : [];
            }, array_values($inner));
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getSiswa: ' . $e->getMessage());
            return [];
        }
    }

    public function getFilterBebanPost(): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getFilterBebanPost', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(20)->post($url, [
                'method' => 'getFilterBebanPost',
                'token' => $token,
            ]);

            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] getFilterBebanPost HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['thn_masuk' => [], 'kelas' => [], 'akun' => []];
            }

            $json = $response->json();
            $inner = $json['data'] ?? [];
            if (!is_array($inner)) {
                return ['thn_masuk' => [], 'kelas' => [], 'akun' => []];
            }

            return [
                'thn_masuk' => is_array($inner['thn_masuk'] ?? null) ? array_values($inner['thn_masuk']) : [],
                'kelas' => is_array($inner['kelas'] ?? null) ? array_values($inner['kelas']) : [],
                'akun' => is_array($inner['akun'] ?? null) ? array_values($inner['akun']) : [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getFilterBebanPost: ' . $e->getMessage());
            return ['thn_masuk' => [], 'kelas' => [], 'akun' => []];
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function getBebanPost(array $filters = [], int $limit = 200, int $offset = 0): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getBebanPost', 'rnd' => uniqid()], $jwtKey);

        $body = array_filter([
            'method' => 'getBebanPost',
            'token' => $token,
            'thn_masuk' => $filters['thn_masuk'] ?? null,
            'kode_prod' => $filters['kode_prod'] ?? null,
            'KodeAkun' => $filters['kode_akun'] ?? null,
            'nominal' => $filters['nominal'] ?? null,
            'limit' => $limit,
            'offset' => $offset,
        ], static function ($v, $k) {
            if (in_array($k, ['limit', 'offset'], true)) {
                return true;
            }
            return !is_null($v) && $v !== '';
        }, ARRAY_FILTER_USE_BOTH);

        try {
            $response = Http::timeout(20)->post($url, $body);
            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] getBebanPost HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $json = $response->json();
            $inner = $json['data'] ?? $json;
            if (!is_array($inner)) {
                return [];
            }

            return array_map(static function ($row) {
                if (is_array($row)) {
                    return array_change_key_case($row, CASE_LOWER);
                }
                return is_object($row) ? array_change_key_case((array) $row, CASE_LOWER) : [];
            }, array_values($inner));
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getBebanPost: ' . $e->getMessage());
            return [];
        }
    }

    public function createBebanPost(array $payload): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'createBebanPost', 'rnd' => uniqid()], $jwtKey);
        $requestBody = [
            'method' => 'createBebanPost',
            'token' => $token,
            'kode_fak' => trim((string) ($payload['kode_fak'] ?? '')),
            'kode_prod' => trim((string) ($payload['kode_prod'] ?? '')),
            'KodeAkun' => trim((string) ($payload['kode_akun'] ?? '')),
            'thn_masuk' => trim((string) ($payload['thn_masuk'] ?? '')),
            'nominal' => trim((string) ($payload['nominal'] ?? '')),
        ];

        try {
            Log::info('[WS Amal Fatimah] Request createBebanPost', [
                'url' => $url,
                'payload_without_token' => [
                    'method' => $requestBody['method'],
                    'kode_fak' => $requestBody['kode_fak'],
                    'kode_prod' => $requestBody['kode_prod'],
                    'KodeAkun' => $requestBody['KodeAkun'],
                    'thn_masuk' => $requestBody['thn_masuk'],
                    'nominal' => $requestBody['nominal'],
                ],
            ]);

            $response = Http::timeout(20)->post($url, $requestBody);

            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 201) {
                Log::info('[WS Amal Fatimah] createBebanPost success', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Beban post berhasil ditambahkan'),
                    'data' => $json['data'] ?? [],
                ];
            }

            Log::warning('[WS Amal Fatimah] createBebanPost failed response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal menambahkan beban post'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] createBebanPost: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi web service',
                'data' => [],
            ];
        }
    }

    public function exportSiswa(array $filters = []): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'exportSiswa', 'rnd' => uniqid()], $jwtKey);

        $body = array_filter([
            'method' => 'exportSiswa',
            'token' => $token,
            'DESC04' => $filters['desc04'] ?? null,
            'CODE02' => $filters['code02'] ?? null,
            'DESC02' => $filters['desc02'] ?? null,
            'STCUST' => $filters['stcust'] ?? null,
        ], static fn ($v) => !is_null($v) && $v !== '');

        try {
            $response = Http::timeout(60)->asForm()->post($url, $body);

            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] exportSiswa HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'ok' => false,
                    'message' => 'Gagal export data siswa dari web service',
                    'filename' => null,
                    'content' => null,
                    'content_type' => null,
                ];
            }

            $disposition = (string) $response->header('content-disposition', '');
            $filename = 'export_siswa_' . now()->format('Ymd_His') . '.csv';
            if (preg_match('/filename="?([^"]+)"?/i', $disposition, $matches) === 1) {
                $detected = trim((string) ($matches[1] ?? ''));
                if ($detected !== '') {
                    $filename = $detected;
                }
            }

            return [
                'ok' => true,
                'message' => 'Export berhasil',
                'filename' => $filename,
                'content' => $response->body(),
                'content_type' => (string) $response->header('content-type', 'text/csv; charset=utf-8'),
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] exportSiswa: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat export data siswa',
                'filename' => null,
                'content' => null,
                'content_type' => null,
            ];
        }
    }

    public function importSiswa(UploadedFile $file): array
    {
        return $this->importSiswaByFilePath($file->getRealPath(), $file->getClientOriginalName());
    }

    public function importSiswaByFilePath(string $filePath, string $originalName = 'import.xlsx'): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'importSiswa', 'rnd' => uniqid()], $jwtKey);

        try {
            $content = @file_get_contents($filePath);
            if ($content === false) {
                return [
                    'ok' => false,
                    'message' => 'File tidak dapat dibaca',
                    'data' => [],
                ];
            }

            $response = Http::timeout(120)
                ->attach('file', $content, $originalName)
                ->post($url, [
                    'method' => 'importSiswa',
                    'token' => $token,
                ]);

            $json = $response->json();

            if ($response->successful() && (int) ($json['status'] ?? 0) === 200) {
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Import selesai'),
                    'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
                ];
            }

            Log::warning('[WS Amal Fatimah] importSiswa failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal import data siswa'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] importSiswa: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi web service',
                'data' => [],
            ];
        }
    }

    public function getSettingAtributSiswa(array $filters, int $limit = 200, int $offset = 0): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getSettingAtributSiswa', 'rnd' => uniqid()], $jwtKey);

        $body = array_filter([
            'method' => 'getSettingAtributSiswa',
            'token' => $token,
            'search' => $filters['search'] ?? null,
            'limit' => $limit,
            'offset' => $offset,
        ], static function ($v, $k) {
            if (in_array($k, ['limit', 'offset'], true)) {
                return true;
            }
            return !is_null($v) && $v !== '';
        }, ARRAY_FILTER_USE_BOTH);

        try {
            $response = Http::timeout(30)->post($url, $body);
            if (!$response->successful()) {
                Log::warning('[WS Amal Fatimah] getSettingAtributSiswa failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $json = $response->json();
            $inner = $json['data'] ?? [];
            if (!is_array($inner)) {
                return [];
            }

            return array_map(static function ($row) {
                if (is_array($row)) {
                    return array_change_key_case($row, CASE_LOWER);
                }
                return is_object($row) ? array_change_key_case((array) $row, CASE_LOWER) : [];
            }, array_values($inner));
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getSettingAtributSiswa: ' . $e->getMessage());
            return [];
        }
    }

    public function importSettingAtributSiswaByFilePath(string $filePath, string $originalName = 'atribut.xlsx'): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'importSettingAtributSiswa', 'rnd' => uniqid()], $jwtKey);

        try {
            $content = @file_get_contents($filePath);
            if ($content === false) {
                return [
                    'ok' => false,
                    'message' => 'File tidak dapat dibaca',
                    'data' => [],
                ];
            }

            $response = Http::timeout(120)
                ->attach('file', $content, $originalName)
                ->post($url, [
                    'method' => 'importSettingAtributSiswa',
                    'token' => $token,
                ]);

            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 200) {
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Import atribut selesai'),
                    'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
                ];
            }

            Log::warning('[WS Amal Fatimah] importSettingAtributSiswa failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal simpan atribut siswa'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] importSettingAtributSiswa: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => [],
            ];
        }
    }

    public function getSiswaByKelas(int $kelasSumber, ?string $search = null, int $limit = 10, int $offset = 0): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getSiswaByKelas', 'rnd' => uniqid()], $jwtKey);

        $body = array_filter([
            'method' => 'getSiswaByKelas',
            'token' => $token,
            'kelas_sumber' => (string) $kelasSumber,
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset,
        ], static function ($v, $k) {
            if (in_array($k, ['limit', 'offset'], true)) {
                return true;
            }
            return !is_null($v) && $v !== '';
        }, ARRAY_FILTER_USE_BOTH);

        try {
            $response = Http::timeout(30)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return ['ok' => false, 'message' => (string) ($json['message'] ?? 'Gagal mengambil data siswa'), 'total' => 0, 'rows' => []];
            }

            $payload = is_array($json['data'] ?? null) ? $json['data'] : [];
            // Kompatibel untuk beberapa bentuk response:
            // 1) {"data":{"total":x,"data":[...]}}
            // 2) {"data":[...]}
            $rows = [];
            $total = 0;
            if (isset($payload['data']) && is_array($payload['data'])) {
                $rows = $payload['data'];
                $total = (int) ($payload['total'] ?? count($rows));
            } elseif (array_is_list($payload)) {
                $rows = $payload;
                $total = count($rows);
            }

            $rows = array_map(static fn ($r) => is_array($r) ? array_change_key_case($r, CASE_LOWER) : [], $rows);
            return [
                'ok' => true,
                'message' => '',
                'total' => $total,
                'rows' => $rows,
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getSiswaByKelas: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghubungi layanan', 'total' => 0, 'rows' => []];
        }
    }

    public function pindahKelas(int $kelasSumber, int $kelasTujuan, string $mode, array $custids = []): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'pindahKelas', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'pindahKelas',
            'token' => $token,
            'kelas_sumber' => (string) $kelasSumber,
            'kelas_tujuan' => (string) $kelasTujuan,
            'mode' => $mode,
        ];
        if ($mode === 'pilihan') {
            $body['custids'] = array_values(array_filter(array_map('intval', $custids), static fn ($v) => $v > 0));
        }

        try {
            $response = Http::timeout(40)->post($url, $body);
            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 200) {
                return ['ok' => true, 'message' => (string) ($json['message'] ?? 'Pemindahan kelas berhasil'), 'data' => $json['data'] ?? []];
            }
            return ['ok' => false, 'message' => (string) ($json['message'] ?? 'Gagal memindahkan kelas'), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] pindahKelas: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghubungi layanan', 'data' => []];
        }
    }

    public function getFilterBuatTagihan(): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getFilterBuatTagihan', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(30)->post($url, [
                'method' => 'getFilterBuatTagihan',
                'token' => $token,
            ]);
            if (!$response->successful()) {
                return ['thn_akademik' => [], 'thn_angkatan' => [], 'kelas' => [], 'tagihan' => []];
            }

            $json = $response->json();
            $inner = is_array($json['data'] ?? null) ? $json['data'] : [];
            return [
                'thn_akademik' => is_array($inner['thn_akademik'] ?? null) ? array_values($inner['thn_akademik']) : [],
                'thn_angkatan' => is_array($inner['thn_angkatan'] ?? null) ? array_values($inner['thn_angkatan']) : [],
                'kelas' => is_array($inner['kelas'] ?? null) ? array_values($inner['kelas']) : [],
                'tagihan' => is_array($inner['tagihan'] ?? null) ? array_values($inner['tagihan']) : [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getFilterBuatTagihan: ' . $e->getMessage());
            return ['thn_akademik' => [], 'thn_angkatan' => [], 'kelas' => [], 'tagihan' => []];
        }
    }

    public function getBuatTagihan(array $filters, int $limit = 10, int $offset = 0): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getBuatTagihan', 'rnd' => uniqid()], $jwtKey);

        $body = array_filter([
            'method' => 'getBuatTagihan',
            'token' => $token,
            'thn_akademik' => $filters['thn_akademik'] ?? null,
            'thn_angkatan' => $filters['thn_angkatan'] ?? null,
            'kelas_id' => $filters['kelas_id'] ?? null,
            'search' => $filters['search'] ?? null,
            'fungsi' => $filters['fungsi'] ?? null,
            'tagihan' => $filters['tagihan'] ?? null,
            'limit' => $limit,
            'offset' => $offset,
        ], static function ($v, $k) {
            if (in_array($k, ['limit', 'offset'], true)) {
                return true;
            }
            return !is_null($v) && $v !== '';
        }, ARRAY_FILTER_USE_BOTH);

        $maxAttempts = 2;
        $lastMessage = 'Terjadi kesalahan saat menghubungi layanan';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(30)->post($url, $body);
                $json = $response->json();
                if ($response->successful() && (int) ($json['status'] ?? 0) === 200) {
                    Log::info('[WS Amal Fatimah] getBuatTagihan success', [
                        'request' => $body,
                        'attempt' => $attempt,
                        'fungsi' => $json['data']['fungsi'] ?? null,
                        'total_siswa' => is_array($json['data']['siswa'] ?? null) ? count($json['data']['siswa']) : null,
                        'total_daftar_harga' => is_array($json['data']['daftar_harga'] ?? null) ? count($json['data']['daftar_harga']) : null,
                    ]);
                    return ['ok' => true, 'message' => '', 'data' => is_array($json['data'] ?? null) ? $json['data'] : []];
                }

                $lastMessage = (string) ($json['message'] ?? 'Gagal memuat data');
                Log::warning('[WS Amal Fatimah] getBuatTagihan failed', [
                    'status' => $response->status(),
                    'attempt' => $attempt,
                    'body' => $response->body(),
                    'request' => $body,
                ]);
            } catch (\Throwable $e) {
                $lastMessage = 'Terjadi kesalahan saat menghubungi layanan';
                Log::error('[WS Amal Fatimah] getBuatTagihan: ' . $e->getMessage(), [
                    'attempt' => $attempt,
                    'request' => $body,
                ]);
            }

            if ($attempt < $maxAttempts) {
                usleep(250000);
            }
        }

        return ['ok' => false, 'message' => $lastMessage, 'data' => []];
    }

    public function createBuatTagihan(array $payload): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'createBuatTagihan', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'createBuatTagihan',
            'token' => $token,
            'thn_akademik' => trim((string) ($payload['thn_akademik'] ?? '')),
            'thn_angkatan' => trim((string) ($payload['thn_angkatan'] ?? '')),
            'kelas_id' => trim((string) ($payload['kelas_id'] ?? '')),
            'fungsi' => trim((string) ($payload['fungsi'] ?? '')),
            'tagihan' => trim((string) ($payload['tagihan'] ?? '')),
            'custids' => array_values(array_filter(array_map('intval', (array) ($payload['custids'] ?? [])), static fn ($v) => $v > 0)),
            'kode_akuns' => array_values(array_filter(array_map('strval', (array) ($payload['kode_akuns'] ?? [])), static fn ($v) => trim($v) !== '')),
        ];

        try {
            $response = Http::timeout(60)->post($url, $body);
            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 201) {
                return ['ok' => true, 'message' => (string) ($json['message'] ?? 'Tagihan berhasil disimpan'), 'data' => $json['data'] ?? []];
            }
            Log::warning('[WS Amal Fatimah] createBuatTagihan failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'request' => [
                    'thn_akademik' => $body['thn_akademik'],
                    'thn_angkatan' => $body['thn_angkatan'],
                    'kelas_id' => $body['kelas_id'],
                    'fungsi' => $body['fungsi'],
                    'tagihan' => $body['tagihan'],
                    'custids_count' => count($body['custids']),
                    'kode_akuns' => $body['kode_akuns'],
                ],
            ]);
            return ['ok' => false, 'message' => (string) ($json['message'] ?? 'Gagal menyimpan tagihan'), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] createBuatTagihan: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghubungi layanan', 'data' => []];
        }
    }

    public function getFungsiBuatTagihan(string $thnAkademik, string $tagihan = ''): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getFungsiBuatTagihan', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'getFungsiBuatTagihan',
            'token' => $token,
            'thn_akademik' => trim($thnAkademik),
            'tagihan' => trim($tagihan),
        ];

        try {
            $response = Http::timeout(20)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                Log::warning('[WS Amal Fatimah] getFungsiBuatTagihan failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request' => $body,
                ]);
                return ['ok' => false, 'fungsi' => '', 'source' => ''];
            }

            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            Log::info('[WS Amal Fatimah] getFungsiBuatTagihan success', [
                'request' => $body,
                'fungsi' => $data['fungsi'] ?? '',
                'source' => $data['source'] ?? '',
            ]);
            return [
                'ok' => true,
                'fungsi' => trim((string) ($data['fungsi'] ?? '')),
                'source' => trim((string) ($data['source'] ?? '')),
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getFungsiBuatTagihan: ' . $e->getMessage(), ['request' => $body]);
            return ['ok' => false, 'fungsi' => '', 'source' => ''];
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{ok: bool, rows: list<array<string, mixed>>}
     */
    public function enrichTagihanExcelRows(array $rows): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'enrichTagihanExcelRows', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'enrichTagihanExcelRows',
            'token' => $token,
            'rows' => array_values($rows),
        ];

        try {
            $response = Http::timeout(45)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                Log::warning('[WS Amal Fatimah] enrichTagihanExcelRows failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['ok' => false, 'rows' => []];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            $out = is_array($data['rows'] ?? null) ? array_values($data['rows']) : [];

            return ['ok' => true, 'rows' => $out];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] enrichTagihanExcelRows: ' . $e->getMessage());

            return ['ok' => false, 'rows' => []];
        }
    }

    /**
     * @param array{
     *     thn_akademik: string,
     *     periode: string,
     *     kode_akun: string,
     *     rows: list<array<string, mixed>>
     * } $payload
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function createTagihanExcelUpload(array $payload): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'createTagihanExcelUpload', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'createTagihanExcelUpload',
            'token' => $token,
            'thn_akademik' => trim((string) ($payload['thn_akademik'] ?? '')),
            'tagihan' => trim((string) ($payload['tagihan'] ?? '')),
            'periode' => trim((string) ($payload['periode'] ?? '')),
            'kode_akun' => trim((string) ($payload['kode_akun'] ?? '')),
            'billcd_mode' => trim((string) ($payload['billcd_mode'] ?? '')),
            'rows' => array_values((array) ($payload['rows'] ?? [])),
        ];

        try {
            $response = Http::timeout(120)->post($url, $body);
            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 201) {
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Berhasil'),
                    'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
                ];
            }

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal menyimpan tagihan'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] createTagihanExcelUpload: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghubungi layanan', 'data' => []];
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>, total: int}}
     */
    public function getDataTagihan(array $filters, int $limit, int $offset): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getDataTagihan', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getDataTagihan',
            'token' => $token,
            'limit' => $limit,
            'offset' => $offset,
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'thn_akademik' => trim((string) ($filters['thn_akademik'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nama_tagihan' => trim((string) ($filters['nama_tagihan'] ?? '')),
            'siswa' => trim((string) ($filters['siswa'] ?? '')),
            'sort_urutan' => trim((string) ($filters['sort_urutan'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(45)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                Log::warning('[WS Amal Fatimah] getDataTagihan failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data tagihan'),
                    'data' => ['rows' => [], 'total' => 0],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                    'total' => (int) ($data['total'] ?? 0),
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getDataTagihan: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['rows' => [], 'total' => 0],
            ];
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>, total: int}}
     */
    public function getDataPenerimaan(array $filters, int $limit, int $offset): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getDataPenerimaan', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getDataPenerimaan',
            'token' => $token,
            'limit' => $limit,
            'offset' => $offset,
            'include_total' => 0,
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'thn_akademik' => trim((string) ($filters['thn_akademik'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nama_tagihan' => trim((string) ($filters['nama_tagihan'] ?? '')),
            'nis' => trim((string) ($filters['nis'] ?? '')),
            'nama' => trim((string) ($filters['nama'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
            'fidbank' => trim((string) ($filters['fidbank'] ?? '')),
            'sekolah' => trim((string) ($filters['sekolah'] ?? '')),
            'periode_mulai' => trim((string) ($filters['periode_mulai'] ?? '')),
            'periode_akhir' => trim((string) ($filters['periode_akhir'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(180)
                ->connectTimeout(25)
                ->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                Log::warning('[WS Amal Fatimah] getDataPenerimaan failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data penerimaan'),
                    'data' => ['rows' => [], 'total' => 0, 'meta' => ['sort_by_aa' => false, 'exact_total' => true]],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
            $exactTotal = (bool) ($meta['exact_total'] ?? true);

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                    'total' => $exactTotal ? (int) ($data['total'] ?? 0) : 0,
                    'meta' => [
                        'sort_by_aa' => (bool) ($meta['sort_by_aa'] ?? false),
                        'exact_total' => $exactTotal,
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            Log::error('[WS Amal Fatimah] getDataPenerimaan: ' . $msg);

            $userMsg = 'Terjadi kesalahan saat menghubungi layanan';
            if (stripos($msg, 'timed out') !== false || stripos($msg, 'Operation timed out') !== false || stripos($msg, 'cURL error 28') !== false) {
                $userMsg = 'Waktu habis menunggu server keuangan. Persempit dengan filter tanggal, NIS/nama, atau kata kunci, lalu coba lagi.';
            }

            return [
                'ok' => false,
                'message' => $userMsg,
                'data' => ['rows' => [], 'total' => 0, 'meta' => ['sort_by_aa' => false, 'exact_total' => true]],
            ];
        }
    }

    /**
     * Daftar tagihan belum lunas untuk halaman Hapus Tagihan (filter tanggal pembuatan = FTGLTagihan).
     *
     * @param array<string, string> $filters
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>, meta: array<string, mixed>}}
     */
    public function getHapusTagihanRows(array $filters, int $limit, int $offset): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getHapusTagihanRows', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getHapusTagihanRows',
            'token' => $token,
            'limit' => $limit,
            'offset' => $offset,
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'thn_akademik' => trim((string) ($filters['thn_akademik'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nama_tagihan' => trim((string) ($filters['nama_tagihan'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(120)->connectTimeout(25)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data tagihan'),
                    'data' => ['rows' => [], 'meta' => []],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                    'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : [],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getHapusTagihanRows: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['rows' => [], 'meta' => []],
            ];
        }
    }

    /**
     * Cek Pelunasan: semua tagihan (lunas / belum) dengan syarat FSTSBolehBayar = 1.
     *
     * @param array<string, string> $filters
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>, meta: array<string, mixed>}}
     */
    public function getCekPelunasanRows(array $filters, int $limit, int $offset): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getCekPelunasanRows', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getCekPelunasanRows',
            'token' => $token,
            'limit' => $limit,
            'offset' => $offset,
        ], array_filter([
            'thn_akademik' => trim((string) ($filters['thn_akademik'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nis' => trim((string) ($filters['nis'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'nama' => trim((string) ($filters['nama'] ?? '')),
            'nama_tagihan' => trim((string) ($filters['nama_tagihan'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(120)->connectTimeout(25)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data cek pelunasan'),
                    'data' => ['rows' => [], 'meta' => []],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                    'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : [],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getCekPelunasanRows: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['rows' => [], 'meta' => []],
            ];
        }
    }

    /**
     * Kartu siswa dari data cek pelunasan berdasarkan custid terpilih.
     *
     * @param list<int> $custids
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function getCekPelunasanCards(array $custids): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getCekPelunasanCards', 'rnd' => uniqid()], $jwtKey);

        $cleanIds = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) $v, $custids),
            static fn ($v) => $v > 0
        )));

        $body = [
            'method' => 'getCekPelunasanCards',
            'token' => $token,
            'custids' => $cleanIds,
        ];

        try {
            $response = Http::timeout(120)->connectTimeout(25)->post($url, $body);
            $json = $response->json() ?? [];
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data kartu siswa'),
                    'data' => [],
                ];
            }

            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getCekPelunasanCards: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => [],
            ];
        }
    }

    /**
     * Hapus terpilih: baris di scctbill_detail lalu scctbill (hanya belum lunas).
     *
     * @param list<array{custid?: int, billcd?: string}> $items
     * @return array{ok: bool, message: string, data: array{deleted: int, failed: array<int, mixed>, error?: string}}
     */
    public function hapusTagihanSiswaBatch(array $items): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'hapusTagihanSiswaBatch', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'hapusTagihanSiswaBatch',
            'token' => $token,
            'items' => array_values($items),
        ];

        try {
            $response = Http::timeout(120)->connectTimeout(25)->post($url, $body);
            $json = $response->json() ?? [];
            $st = (int) ($json['status'] ?? 0);
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            if ($st !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? $data['error'] ?? 'Gagal menghapus tagihan'),
                    'data' => [
                        'deleted' => (int) ($data['deleted'] ?? 0),
                        'failed' => is_array($data['failed'] ?? null) ? $data['failed'] : [],
                        'error' => (string) ($data['error'] ?? ''),
                    ],
                ];
            }

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'deleted' => (int) ($data['deleted'] ?? 0),
                    'failed' => is_array($data['failed'] ?? null) ? $data['failed'] : [],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] hapusTagihanSiswaBatch: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['deleted' => 0, 'failed' => []],
            ];
        }
    }

    /**
     * @param array<string, string> $filters
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>, meta: array<string, mixed>}}
     */
    public function getSaldoVirtualAccountRows(array $filters, int $limit, int $offset): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getSaldoVirtualAccountRows', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getSaldoVirtualAccountRows',
            'token' => $token,
            'limit' => $limit,
            'offset' => $offset,
        ], array_filter([
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'sekolah' => trim((string) ($filters['sekolah'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(120)->connectTimeout(25)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat saldo VA'),
                    'data' => ['rows' => [], 'meta' => []],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                    'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : [],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getSaldoVirtualAccountRows: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['rows' => [], 'meta' => []],
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function getSaldoVirtualAccountMutasi(int $custid, string $cari, int $limit, int $offset): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getSaldoVirtualAccountMutasi', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getSaldoVirtualAccountMutasi',
            'token' => $token,
            'custid' => $custid,
            'limit' => $limit,
            'offset' => $offset,
        ], array_filter([
            'cari' => trim($cari),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(120)->connectTimeout(25)->post($url, $body);
            $json = $response->json() ?? [];
            $st = (int) ($json['status'] ?? 0);
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            if ($st !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? $data['error'] ?? 'Gagal memuat mutasi'),
                    'data' => $data,
                ];
            }

            return [
                'ok' => true,
                'message' => '',
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getSaldoVirtualAccountMutasi: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => [],
            ];
        }
    }

    /**
     * Data Transaksi: semua baris sccttran (+ NIS, NO VA, nama).
     *
     * @param array<string, string> $filters
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>, meta: array<string, mixed>}}
     */
    public function getDataTransaksiSccttran(array $filters, int $limit, int $offset): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getDataTransaksiSccttran', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getDataTransaksiSccttran',
            'token' => $token,
            'limit' => $limit,
            'offset' => $offset,
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'sekolah' => trim((string) ($filters['sekolah'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nis' => trim((string) ($filters['nis'] ?? '')),
            'nama' => trim((string) ($filters['nama'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(180)->connectTimeout(25)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data transaksi'),
                    'data' => ['rows' => [], 'meta' => []],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                    'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : [],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getDataTransaksiSccttran: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['rows' => [], 'meta' => []],
            ];
        }
    }

    /**
     * Data Biaya Admin dari scctbill (nominal fixed 2000).
     *
     * @param array<string, string> $filters
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>, meta: array<string, mixed>}}
     */
    public function getDataBiayaAdminRows(array $filters, int $limit, int $offset, bool $includeTotal = false): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getDataBiayaAdminRows', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getDataBiayaAdminRows',
            'token' => $token,
            'limit' => $limit,
            'offset' => $offset,
            'include_total' => $includeTotal ? '1' : '0',
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(120)->connectTimeout(25)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data biaya admin'),
                    'data' => ['rows' => [], 'meta' => []],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                    'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : [],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getDataBiayaAdminRows: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['rows' => [], 'meta' => []],
            ];
        }
    }

    /**
     * Data penerimaan untuk cetak PDF rekap (hingga 8000 baris, tanpa COUNT; WS harus terima pdf_export).
     *
     * @param array<string, string> $filters
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>}}
     */
    public function getDataPenerimaanPdfExport(array $filters): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getDataPenerimaan', 'rnd' => uniqid()], $jwtKey);

        $body = array_merge([
            'method' => 'getDataPenerimaan',
            'token' => $token,
            'limit' => 8000,
            'offset' => 0,
            'include_total' => 0,
            'pdf_export' => true,
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'thn_akademik' => trim((string) ($filters['thn_akademik'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nama_tagihan' => trim((string) ($filters['nama_tagihan'] ?? '')),
            'nis' => trim((string) ($filters['nis'] ?? '')),
            'nama' => trim((string) ($filters['nama'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
            'fidbank' => trim((string) ($filters['fidbank'] ?? '')),
            'sekolah' => trim((string) ($filters['sekolah'] ?? '')),
            'periode_mulai' => trim((string) ($filters['periode_mulai'] ?? '')),
            'periode_akhir' => trim((string) ($filters['periode_akhir'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(180)
                ->connectTimeout(25)
                ->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data untuk PDF'),
                    'data' => ['rows' => []],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getDataPenerimaanPdfExport: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['rows' => []],
            ];
        }
    }

    /**
     * Kartu siswa (Data Penerimaan): custids + filter sama seperti getDataPenerimaan.
     *
     * @param array<string, string> $filters
     * @param list<int> $custids
     * @return array{ok: bool, message: string, data: array{cards: list<array<string, mixed>>, error?: string}}
     */
    public function getKartuSiswaPenerimaan(array $filters, array $custids): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getKartuSiswaPenerimaan', 'rnd' => uniqid()], $jwtKey);

        $cleanIds = [];
        foreach ($custids as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $cleanIds[] = $n;
            }
        }
        $cleanIds = array_values(array_unique($cleanIds));

        $body = array_merge([
            'method' => 'getKartuSiswaPenerimaan',
            'token' => $token,
            'custids' => $cleanIds,
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'thn_akademik' => trim((string) ($filters['thn_akademik'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nama_tagihan' => trim((string) ($filters['nama_tagihan'] ?? '')),
            'nis' => trim((string) ($filters['nis'] ?? '')),
            'nama' => trim((string) ($filters['nama'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
            'fidbank' => trim((string) ($filters['fidbank'] ?? '')),
            'sekolah' => trim((string) ($filters['sekolah'] ?? '')),
            'periode_mulai' => trim((string) ($filters['periode_mulai'] ?? '')),
            'periode_akhir' => trim((string) ($filters['periode_akhir'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(120)->connectTimeout(25)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal mengambil data kartu siswa'),
                    'data' => ['cards' => []],
                ];
            }
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'cards' => is_array($data['cards'] ?? null) ? array_values($data['cards']) : [],
                    'error' => isset($data['error']) ? (string) $data['error'] : '',
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getKartuSiswaPenerimaan: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['cards' => []],
            ];
        }
    }

    /**
     * Hanya opsi filter + bank (tanpa query data penerimaan) — untuk shell halaman yang cepat.
     *
     * @return array{
     *     filterOptions: array{thn_akademik: array<int, mixed>, thn_angkatan: array<int, mixed>, kelas: array<int, mixed>, tagihan: array<int, mixed>},
     *     bankOptions: list<array{fidbank: string, label: string}>
     * }
     */
    public function loadPenerimaanFilterShell(): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';

        $tokenFilters = $this->jwt->encode(['sub' => 'getFilterBuatTagihan', 'rnd' => uniqid()], $jwtKey);
        $tokenBanks = $this->jwt->encode(['sub' => 'getManualPembayaranBankOptions', 'rnd' => uniqid()], $jwtKey);

        try {
            $responses = Http::pool(function (Pool $pool) use ($url, $tokenFilters, $tokenBanks) {
                $pool->as('filters')->timeout(30)->post($url, [
                    'method' => 'getFilterBuatTagihan',
                    'token' => $tokenFilters,
                ]);
                $pool->as('banks')->timeout(20)->post($url, [
                    'method' => 'getManualPembayaranBankOptions',
                    'token' => $tokenBanks,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] loadPenerimaanFilterShell pool: ' . $e->getMessage());

            return [
                'filterOptions' => $this->getFilterBuatTagihan(),
                'bankOptions' => $this->getManualPembayaranBankOptions(),
            ];
        }

        $filterOptions = ['thn_akademik' => [], 'thn_angkatan' => [], 'kelas' => [], 'tagihan' => []];
        $rf = $responses['filters'] ?? null;
        if ($rf && $rf->successful()) {
            $json = $rf->json();
            $inner = is_array($json['data'] ?? null) ? $json['data'] : [];
            $filterOptions = [
                'thn_akademik' => is_array($inner['thn_akademik'] ?? null) ? array_values($inner['thn_akademik']) : [],
                'thn_angkatan' => is_array($inner['thn_angkatan'] ?? null) ? array_values($inner['thn_angkatan']) : [],
                'kelas' => is_array($inner['kelas'] ?? null) ? array_values($inner['kelas']) : [],
                'tagihan' => is_array($inner['tagihan'] ?? null) ? array_values($inner['tagihan']) : [],
            ];
        }

        $bankRows = [];
        $rb = $responses['banks'] ?? null;
        if ($rb && $rb->successful()) {
            $json = $rb->json();
            $rows = $json['data'] ?? [];
            if (is_array($rows)) {
                $bankRows = array_values(array_filter(array_map(static function ($r) {
                    if (!is_array($r)) {
                        return null;
                    }
                    $fidbank = trim((string) ($r['fidbank'] ?? ''));
                    $label = trim((string) ($r['label'] ?? ''));
                    if ($fidbank === '' || $label === '') {
                        return null;
                    }

                    return ['fidbank' => $fidbank, 'label' => $label];
                }, $rows)));
            }
        }

        return [
            'filterOptions' => $filterOptions,
            'bankOptions' => $bankRows,
        ];
    }

    /**
     * Opsi filter halaman Rekap Penerimaan: tagihan dari mst_tagihan + daftar Tingkat (unit).
     *
     * @return array{filterOptions: array{thn_akademik: array, thn_angkatan: array, kelas: array, tagihan: array}, tingkatOptions: list<string>}
     */
    public function loadRekapPenerimaanShell(): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getRekapPenerimaanFilterShell', 'rnd' => uniqid()], $jwtKey);

        try {
            $res = Http::timeout(30)->post($url, [
                'method' => 'getRekapPenerimaanFilterShell',
                'token' => $token,
            ]);
            if ($res->successful()) {
                $json = $res->json();
                $inner = is_array($json['data'] ?? null) ? $json['data'] : [];
                $filterOptions = [
                    'thn_akademik' => is_array($inner['thn_akademik'] ?? null) ? array_values($inner['thn_akademik']) : [],
                    'thn_angkatan' => is_array($inner['thn_angkatan'] ?? null) ? array_values($inner['thn_angkatan']) : [],
                    'kelas' => is_array($inner['kelas'] ?? null) ? array_values($inner['kelas']) : [],
                    'tagihan' => is_array($inner['tagihan'] ?? null) ? array_values($inner['tagihan']) : [],
                ];
                $tingkat = is_array($inner['tingkat'] ?? null) ? array_values($inner['tingkat']) : [];

                return ['filterOptions' => $filterOptions, 'tingkatOptions' => $tingkat];
            }
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] loadRekapPenerimaanShell: ' . $e->getMessage());
        }

        $fallback = $this->loadPenerimaanFilterShell();
        $fo = $fallback['filterOptions'] ?? [];
        $tingkat = [];
        foreach ($fo['kelas'] ?? [] as $k) {
            if (!is_array($k)) {
                continue;
            }
            $u = trim((string) ($k['unit'] ?? ''));
            if ($u !== '' && !in_array($u, $tingkat, true)) {
                $tingkat[] = $u;
            }
        }
        sort($tingkat, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'filterOptions' => is_array($fo) ? $fo : ['thn_akademik' => [], 'thn_angkatan' => [], 'kelas' => [], 'tagihan' => []],
            'tingkatOptions' => $tingkat,
        ];
    }

    /**
     * Memuat opsi filter, bank, dan halaman data penerimaan dalam satu pool HTTP (paralel).
     *
     * @param array<string, mixed> $filters
     * @return array{
     *     filterOptions: array{thn_akademik: array<int, mixed>, thn_angkatan: array<int, mixed>, kelas: array<int, mixed>, tagihan: array<int, mixed>},
     *     bankOptions: list<array{fidbank: string, label: string}>,
     *     penerimaan: array{ok: bool, message: string, data: array{rows: array<int, mixed>, total: int, meta: array<string, mixed>}}
     * }
     */
    public function loadPenerimaanIndexData(array $filters, int $limit, int $offset): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';

        $tokenFilters = $this->jwt->encode(['sub' => 'getFilterBuatTagihan', 'rnd' => uniqid()], $jwtKey);
        $tokenBanks = $this->jwt->encode(['sub' => 'getManualPembayaranBankOptions', 'rnd' => uniqid()], $jwtKey);
        $tokenPenerimaan = $this->jwt->encode(['sub' => 'getDataPenerimaan', 'rnd' => uniqid()], $jwtKey);

        $bodyPenerimaan = array_merge([
            'method' => 'getDataPenerimaan',
            'token' => $tokenPenerimaan,
            'limit' => $limit,
            'offset' => $offset,
            'include_total' => 0,
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'thn_akademik' => trim((string) ($filters['thn_akademik'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nama_tagihan' => trim((string) ($filters['nama_tagihan'] ?? '')),
            'nis' => trim((string) ($filters['nis'] ?? '')),
            'nama' => trim((string) ($filters['nama'] ?? '')),
            'cari' => trim((string) ($filters['cari'] ?? '')),
            'fidbank' => trim((string) ($filters['fidbank'] ?? '')),
            'sekolah' => trim((string) ($filters['sekolah'] ?? '')),
            'periode_mulai' => trim((string) ($filters['periode_mulai'] ?? '')),
            'periode_akhir' => trim((string) ($filters['periode_akhir'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $responses = Http::pool(function (Pool $pool) use ($url, $tokenFilters, $tokenBanks, $bodyPenerimaan) {
                $pool->as('filters')->timeout(30)->post($url, [
                    'method' => 'getFilterBuatTagihan',
                    'token' => $tokenFilters,
                ]);
                $pool->as('banks')->timeout(20)->post($url, [
                    'method' => 'getManualPembayaranBankOptions',
                    'token' => $tokenBanks,
                ]);
                $pool->as('penerimaan')->timeout(180)->connectTimeout(25)->post($url, $bodyPenerimaan);
            });
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] loadPenerimaanIndexData pool: ' . $e->getMessage());

            return [
                'filterOptions' => $this->getFilterBuatTagihan(),
                'bankOptions' => $this->getManualPembayaranBankOptions(),
                'penerimaan' => $this->getDataPenerimaan($filters, $limit, $offset),
            ];
        }

        $filterOptions = ['thn_akademik' => [], 'thn_angkatan' => [], 'kelas' => [], 'tagihan' => []];
        $rf = $responses['filters'] ?? null;
        if ($rf && $rf->successful()) {
            $json = $rf->json();
            $inner = is_array($json['data'] ?? null) ? $json['data'] : [];
            $filterOptions = [
                'thn_akademik' => is_array($inner['thn_akademik'] ?? null) ? array_values($inner['thn_akademik']) : [],
                'thn_angkatan' => is_array($inner['thn_angkatan'] ?? null) ? array_values($inner['thn_angkatan']) : [],
                'kelas' => is_array($inner['kelas'] ?? null) ? array_values($inner['kelas']) : [],
                'tagihan' => is_array($inner['tagihan'] ?? null) ? array_values($inner['tagihan']) : [],
            ];
        }

        $bankRows = [];
        $rb = $responses['banks'] ?? null;
        if ($rb && $rb->successful()) {
            $json = $rb->json();
            $rows = $json['data'] ?? [];
            if (is_array($rows)) {
                $bankRows = array_values(array_filter(array_map(static function ($r) {
                    if (!is_array($r)) {
                        return null;
                    }
                    $fidbank = trim((string) ($r['fidbank'] ?? ''));
                    $label = trim((string) ($r['label'] ?? ''));
                    if ($fidbank === '' || $label === '') {
                        return null;
                    }

                    return ['fidbank' => $fidbank, 'label' => $label];
                }, $rows)));
            }
        }

        $penerimaan = [
            'ok' => false,
            'message' => 'Gagal memuat data penerimaan',
            'data' => ['rows' => [], 'total' => 0, 'meta' => ['sort_by_aa' => false, 'exact_total' => true]],
        ];
        $rp = $responses['penerimaan'] ?? null;
        if ($rp && $rp->successful()) {
            $json = $rp->json();
            if ((int) ($json['status'] ?? 0) === 200) {
                $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
                $exactTotal = (bool) ($meta['exact_total'] ?? true);
                $penerimaan = [
                    'ok' => true,
                    'message' => '',
                    'data' => [
                        'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                        'total' => $exactTotal ? (int) ($data['total'] ?? 0) : 0,
                        'meta' => [
                            'sort_by_aa' => (bool) ($meta['sort_by_aa'] ?? false),
                            'exact_total' => $exactTotal,
                        ],
                    ],
                ];
            } else {
                $penerimaan['message'] = (string) ($json['message'] ?? 'Gagal memuat data penerimaan');
                Log::warning('[WS Amal Fatimah] loadPenerimaanIndexData penerimaan failed', [
                    'status' => $rp->status(),
                    'body' => substr($rp->body(), 0, 500),
                ]);
            }
        } else {
            Log::warning('[WS Amal Fatimah] loadPenerimaanIndexData penerimaan HTTP failed', [
                'status' => $rp ? $rp->status() : null,
            ]);
        }

        return [
            'filterOptions' => $filterOptions,
            'bankOptions' => $bankRows,
            'penerimaan' => $penerimaan,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @param list<int> $custids
     * @return array{ok: bool, message: string, data: array{rows: array<int, mixed>}}
     */
    public function getDataPembayaranPerNis(array $filters, array $custids): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getDataPembayaranPerNis', 'rnd' => uniqid()], $jwtKey);

        $cleanCustids = [];
        foreach ($custids as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $cleanCustids[] = $n;
            }
        }
        $cleanCustids = array_values(array_unique($cleanCustids));

        $body = array_merge([
            'method' => 'getDataPembayaranPerNis',
            'token' => $token,
            'custids' => $cleanCustids,
        ], array_filter([
            'tgl_dari' => trim((string) ($filters['tgl_dari'] ?? '')),
            'tgl_sampai' => trim((string) ($filters['tgl_sampai'] ?? '')),
            'thn_angkatan' => trim((string) ($filters['thn_angkatan'] ?? '')),
            'thn_akademik' => trim((string) ($filters['thn_akademik'] ?? '')),
            'kelas_id' => trim((string) ($filters['kelas_id'] ?? '')),
            'nama_tagihan' => trim((string) ($filters['nama_tagihan'] ?? '')),
            'siswa' => trim((string) ($filters['siswa'] ?? '')),
        ], static fn ($v) => $v !== ''));

        try {
            $response = Http::timeout(45)->post($url, $body);
            $json = $response->json();
            if (!$response->successful() || (int) ($json['status'] ?? 0) !== 200) {
                Log::warning('[WS Amal Fatimah] getDataPembayaranPerNis failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return [
                    'ok' => false,
                    'message' => (string) ($json['message'] ?? 'Gagal memuat data pembayaran per NIS'),
                    'data' => ['rows' => []],
                ];
            }

            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            return [
                'ok' => true,
                'message' => '',
                'data' => [
                    'rows' => is_array($data['rows'] ?? null) ? array_values($data['rows']) : [],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getDataPembayaranPerNis: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan',
                'data' => ['rows' => []],
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function updateDataTagihanUrutan(int $custid, string $billcd, string $direction): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'updateDataTagihanUrutan', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'updateDataTagihanUrutan',
            'token' => $token,
            'custid' => $custid,
            'billcd' => $billcd,
            'direction' => $direction,
        ];

        try {
            $response = Http::timeout(20)->post($url, $body);
            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 200) {
                return [
                    'ok' => true,
                    'message' => 'Urutan diperbarui',
                    'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
                ];
            }

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal mengubah urutan'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] updateDataTagihanUrutan: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghubungi layanan', 'data' => []];
        }
    }

    /**
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function deleteDataTagihan(int $custid, string $billcd): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'deleteDataTagihan', 'rnd' => uniqid()], $jwtKey);

        $body = [
            'method' => 'deleteDataTagihan',
            'token' => $token,
            'custid' => $custid,
            'billcd' => $billcd,
        ];

        try {
            $response = Http::timeout(20)->post($url, $body);
            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 200) {
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Terhapus'),
                    'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
                ];
            }

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal menghapus'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] deleteDataTagihan: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghubungi layanan', 'data' => []];
        }
    }

    /**
     * @param list<string> $selectedBillcds
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function getSiswaByCustid(int $custid, array $selectedBillcds = [], string $thnAka = ''): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getSiswaByCustid', 'rnd' => uniqid()], $jwtKey);

        $billcds = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $selectedBillcds), static fn ($v) => $v !== ''));
        $body = array_merge([
            'method' => 'getSiswaByCustid',
            'token' => $token,
            'CUSTID' => $custid,
            'selected_billcds' => $billcds,
        ], trim($thnAka) !== '' ? ['thn_aka' => trim($thnAka)] : []);

        try {
            $response = Http::timeout(20)->post($url, $body);
            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 200) {
                return [
                    'ok' => true,
                    'message' => '',
                    'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
                ];
            }

            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal mengambil siswa'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getSiswaByCustid: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghubungi layanan', 'data' => []];
        }
    }

    /**
     * @return list<array{fidbank:string,label:string}>
     */
    public function getManualPembayaranBankOptions(): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'getManualPembayaranBankOptions', 'rnd' => uniqid()], $jwtKey);

        try {
            $response = Http::timeout(20)->post($url, [
                'method' => 'getManualPembayaranBankOptions',
                'token' => $token,
            ]);
            if (!$response->successful()) {
                return [];
            }
            $json = $response->json();
            $rows = $json['data'] ?? [];
            if (!is_array($rows)) {
                return [];
            }
            return array_values(array_filter(array_map(static function ($r) {
                if (!is_array($r)) {
                    return null;
                }
                $fidbank = trim((string) ($r['fidbank'] ?? ''));
                $label = trim((string) ($r['label'] ?? ''));
                if ($fidbank === '' || $label === '') {
                    return null;
                }
                return ['fidbank' => $fidbank, 'label' => $label];
            }, $rows)));
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] getManualPembayaranBankOptions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param list<string> $selectedBillcds
     * @return array{ok: bool, message: string, data: array<string,mixed>}
     */
    public function createManualPembayaran(int $custid, string $fidbank, array $selectedBillcds, string $paiddt = ''): array
    {
        $url = config('services.ws_amal_fatimah.url');
        $jwtKey = config('services.ws_amal_fatimah.jwt_key') ?? '';
        $token = $this->jwt->encode(['sub' => 'createManualPembayaran', 'rnd' => uniqid()], $jwtKey);

        $billcds = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $selectedBillcds), static fn ($v) => $v !== ''));
        $body = [
            'method' => 'createManualPembayaran',
            'token' => $token,
            'custid' => $custid,
            'fidbank' => trim($fidbank),
            'selected_billcds' => $billcds,
            'paiddt' => trim($paiddt),
        ];

        try {
            $response = Http::timeout(30)->post($url, $body);
            $json = $response->json();
            if ($response->successful() && (int) ($json['status'] ?? 0) === 201) {
                return [
                    'ok' => true,
                    'message' => (string) ($json['message'] ?? 'Pembayaran manual berhasil'),
                    'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
                ];
            }
            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'Gagal memproses pembayaran manual'),
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('[WS Amal Fatimah] createManualPembayaran: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghubungi layanan', 'data' => []];
        }
    }
}
