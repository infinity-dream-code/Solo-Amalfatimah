<?php

namespace App\Http\Controllers\ManualInput;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RekapDataController extends Controller
{
    public function index(): View
    {
        return view('manual-input.rekap-data.index', [
            'pageTitle' => 'Rekap Data',
        ]);
    }
}

