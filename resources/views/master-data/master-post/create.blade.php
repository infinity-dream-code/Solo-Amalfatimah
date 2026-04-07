@extends('layouts.app')

@section('content')
    <style>
        .mp-form-card {
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .mp-form-body { padding: 18px; }
        .mp-field-wrap {
            display: grid;
            gap: 10px;
            padding: 12px;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            background: #fafafa;
        }
        .mp-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #374151; }
        .mp-required { color: #ef4444; }
        .mp-input {
            width: 100%;
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 12px;
            outline: none;
            font-size: 14px;
        }
        .mp-input:focus { border-color: #4f6ef7; }
        .mp-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 14px;
        }
        .mp-btn {
            height: 42px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .mp-btn-cancel { border: 1px solid #d1d5db; background: #fff; color: #4b5563; }
        .mp-btn-save { border: 1px solid #4f6ef7; background: #4f6ef7; color: #fff; }
        .mp-error-box {
            margin-top: 12px;
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
        }
    </style>

    <div class="page-heading">
        <h2>Tambah Master Post</h2>
        <p>Isi Kode, Nama Post, dan Nomor Rekening.</p>
    </div>

    <div class="mp-form-card">
        <div class="mp-form-body">
            <form method="POST" action="{{ route('master.post.store') }}">
                @csrf

                @if ($errors->any())
                    <div class="mp-error-box">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mp-field-wrap">
                    <div>
                        <label class="mp-label">Kode <span class="mp-required">*</span></label>
                        <input name="kodeakun" type="text" class="mp-input" value="{{ old('kodeakun') }}" placeholder="Kode" maxlength="5" required>
                    </div>

                    <div>
                        <label class="mp-label">Nama Post <span class="mp-required">*</span></label>
                        <input name="namaakun" type="text" class="mp-input" value="{{ old('namaakun') }}" placeholder="Nama Post" required>
                    </div>

                    <div>
                        <label class="mp-label">Nomor Rekening</label>
                        <input name="norek" type="text" class="mp-input" value="{{ old('norek') }}" placeholder="Nomor Rekening">
                    </div>
                </div>

                <div class="mp-actions">
                    <a class="mp-btn mp-btn-cancel" href="{{ route('master.post') }}">Batal</a>
                    <button class="mp-btn mp-btn-save" type="submit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
@endsection

