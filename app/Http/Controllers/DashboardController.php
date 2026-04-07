<?php

namespace App\Http\Controllers;

use App\Services\AmalFatimahApiService;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __invoke(AmalFatimahApiService $api)
    {
        $response = $api->dashboard();
        $tagihanResponse = $api->tagihandashboard();
        $tagihanDibayarChart = $api->tagihanbayarDashboard();
        $pembayaranBaru = [];
        $tagihan = null;

        if (is_array($response)) {
            $list = $response['pembayaran_baru'] ?? $response['pembayaran'] ?? $response['data'] ?? $response['result'] ?? [];
            if (isset($response['result']) && is_array($response['result'])) {
                $list = $response['result']['pembayaran_baru'] ?? $response['result']['data'] ?? $list;
            }
            if (is_array($list)) {
                $pembayaranBaru = array_slice(array_values($list), 0, 5);
            }
        }

        if (is_array($tagihanResponse)) {
            $tagihan = [
                'total' => (int)($tagihanResponse['jumlah_tagihan'] ?? $tagihanResponse['total'] ?? 0),
                'dibayar' => (int)($tagihanResponse['tagihan_dibayar'] ?? $tagihanResponse['dibayar'] ?? 0),
                'belum_dibayar' => (int)($tagihanResponse['tagihan_belum_dibayar'] ?? $tagihanResponse['belum_dibayar'] ?? 0),
            ];
        }

        return view('dashboard.index', [
            'pembayaranBaru' => $pembayaranBaru,
            'tagihan' => $tagihan,
            'tagihanDibayarChart' => is_array($tagihanDibayarChart) ? $tagihanDibayarChart : [],
        ]);
    }
}
