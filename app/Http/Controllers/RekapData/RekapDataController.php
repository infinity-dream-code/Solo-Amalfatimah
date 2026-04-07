<?php

namespace App\Http\Controllers\RekapData;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RekapDataController extends Controller
{
    public function index(): View
    {
        return view('rekap-data.rekap-data.index', [
            'pageTitle' => 'Rekap Data',
        ]);
    }
}

