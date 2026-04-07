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
        ]);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        session()->forget('dummy_logged_in');
        session()->forget('auth_user');
        session()->forget('auth_user_id');
        session()->forget('auth_username');
        session()->forget('auth_name');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
