@extends('layouts.app')

@section('content')
    <style>
        .tp-form-card {
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        .tp-form-body { padding: 18px; }
        .tp-field-wrap {
            display: grid;
            gap: 10px;
            padding: 12px;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            background: #fafafa;
        }
        .tp-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #374151; }
        .tp-required { color: #ef4444; }
        .tp-input {
            width: 100%;
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 12px;
            outline: none;
            font-size: 14px;
        }
        .tp-input:focus { border-color: #4f6ef7; }
        .tp-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 14px;
        }
        .tp-btn {
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
        .tp-btn-cancel { border: 1px solid #d1d5db; background: #fff; color: #4b5563; }
        .tp-btn-save { border: 1px solid #4f6ef7; background: #4f6ef7; color: #fff; }
        .tp-error-box {
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
        <h2>Tambah Tahun Pelajaran</h2>
        <p>Isi data tahun pelajaran.</p>
    </div>

    <div class="tp-form-card">
        <div class="tp-form-body">
            <form method="POST" action="{{ route('master.tahun_pelajaran.store') }}">
                @csrf

                @if ($errors->any())
                    <div class="tp-error-box">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="tp-field-wrap">
                    <div>
                        <label class="tp-label">Tahun Pelajaran <span class="tp-required">*</span></label>
                        <input name="thn_aka" type="text" class="tp-input" value="{{ old('thn_aka') }}" placeholder="thn_aka" required>
                    </div>
                </div>

                <div class="tp-actions">
                    <a class="tp-btn tp-btn-cancel" href="{{ route('master.tahun_pelajaran') }}">Batal</a>
                    <button class="tp-btn tp-btn-save" type="submit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
@endsection

