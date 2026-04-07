<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Masuk - Solo Amal Fatimah</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #f3f6ff;
            --card-bg: #ffffff;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --border: #d1d5db;
            --text-main: #111827;
            --text-muted: #6b7280;
            --error: #dc2626;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: radial-gradient(circle at top, #dbeafe 0, #eff6ff 40%, #f9fafb 100%);
            color: var(--text-main);
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: var(--card-bg);
            border-radius: 18px;
            padding: 26px 24px 22px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 18px;
        }

        .logo-wrap img {
            height: 70px;
            width: auto;
            object-fit: contain;
        }

        h1 {
            font-size: 22px;
            text-align: center;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 13px;
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 18px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        label {
            font-size: 13px;
            color: #374151;
        }

        .input-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid var(--border);
            padding: 8px 12px;
            background: #f9fafb;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .input-wrap:focus-within {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.18);
        }

        .input-icon {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
        }

        .input {
            border: none;
            outline: none;
            background: transparent;
            flex: 1;
            font-size: 14px;
        }

        .input::placeholder {
            color: #9ca3af;
        }

        .error-text {
            font-size: 12px;
            color: var(--error);
        }

        .row-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            margin-top: 4px;
        }

        .checkbox-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: #4b5563;
            user-select: none;
        }

        .checkbox-group input {
            width: 14px;
            height: 14px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .link {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }

        .btn-primary {
            margin-top: 10px;
            width: 100%;
            border-radius: 999px;
            border: none;
            padding: 10px 16px;
            background: var(--primary);
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.15s ease, transform 0.05s ease, box-shadow 0.15s ease;
            box-shadow: 0 12px 26px rgba(37, 99, 235, 0.26);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
        }

        .foot {
            margin-top: 12px;
            font-size: 11px;
            text-align: center;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-wrap">
        <img src="{{ asset('Amal Fataimah Surakarta.jpg.jpeg') }}" alt="Logo">
    </div>

    <h1>Selamat Datang!</h1>
    <p class="subtitle">Silakan login terlebih dahulu.</p>

    @if (session('status'))
        <div style="margin-bottom:10px;font-size:13px;color:#16a34a;text-align:center;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <div class="input-wrap">
                <span class="input-icon">
                    {{-- icon user sederhana pakai SVG --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <circle cx="12" cy="8" r="3.2"></circle>
                        <path d="M6 18.4C6.8 16.1 9.1 14.7 12 14.7C14.9 14.7 17.2 16.1 18 18.4" stroke-linecap="round"/>
                    </svg>
                </span>
                <input
                    id="email"
                    type="text"
                    name="email"
                    class="input"
                    value="{{ old('email') }}"
                    placeholder="Username atau email"
                    required
                    autofocus
                >
            </div>
            @error('email')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <div class="input-wrap">
                <span class="input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <rect x="4" y="10" width="16" height="9" rx="2"></rect>
                        <path d="M9 10V8.5A3.5 3.5 0 0 1 12.5 5A3.5 3.5 0 0 1 16 8.5V10" stroke-linecap="round"/>
                    </svg>
                </span>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="input"
                    placeholder="Password"
                    required
                >
            </div>
            @error('password')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="row-between">
            <label class="checkbox-group">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <span>ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link">Lupa password?</a>
            @endif
        </div>

        <button type="submit" class="btn-primary">
            Login
        </button>
    </form>

    <div class="foot">
        © {{ date('Y') }} Solo Amal Fatimah
    </div>
</div>
</body>
</html>

