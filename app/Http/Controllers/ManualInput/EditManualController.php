<?php

namespace App\Http\Controllers\ManualInput;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditManualController extends Controller
{
    public function index(): View
    {
        return view('manual-input.edit-manual.index', [
            'pageTitle' => 'Edit Manual',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        return redirect()->route('manual_input.edit_manual')->with('status', 'Perubahan manual tersimpan (dummy).');
    }
}

