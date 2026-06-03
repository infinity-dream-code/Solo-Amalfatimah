<?php

namespace App\Http\Controllers;

use App\Services\AmalFatimahApiService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('dummy_logged_in')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request, AmalFatimahApiService $api)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        $res = $api->loginUser($login, $password);
        if (!$res['ok']) {
            return back()
                ->withErrors(['email' => $res['message'] ?? 'Username/email atau password salah.'])
                ->withInput($request->only('email'));
        }

        $user = is_array($res['data']['user'] ?? null) ? $res['data']['user'] : [];

        session([
            'dummy_logged_in' => true,
            'auth_user' => $user,
            'auth_user_id' => (int) ($user['id'] ?? 0),
            'auth_username' => (string) ($user['username'] ?? ''),
            'auth_name' => (string) ($user['name'] ?? ''),
            'auth_fid' => (string) ($user['fid'] ?? ''),
            'auth_kel' => (string) ($user['kel'] ?? ''),
            'auth_is_superadmin' => (bool) ($user['is_superadmin'] ?? false),
            'auth_sekolah_code01' => (string) ($user['sekolah_code01'] ?? ''),
            'auth_sekolah_nama' => (string) ($user['sekolah_nama'] ?? ($user['unit'] ?? '')),
        ]);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        session()->forget([
            'dummy_logged_in',
            'auth_user',
            'auth_user_id',
            'auth_username',
            'auth_name',
            'auth_fid',
            'auth_kel',
            'auth_is_superadmin',
            'auth_sekolah_code01',
            'auth_sekolah_nama',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
