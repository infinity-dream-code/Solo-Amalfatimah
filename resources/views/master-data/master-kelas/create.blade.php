@extends('layouts.app')

@section('content')
    <style>
        .mk-form-card {
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .mk-form-body {
            padding: 18px;
        }

        .mk-field-wrap {
            display: grid;
            gap: 10px;
            padding: 12px;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            background: #fafafa;
        }

        .mk-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #374151;
        }

        .mk-required {
            color: #ef4444;
        }

        .mk-input {
            width: 100%;
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 12px;
            outline: none;
            font-size: 14px;
        }

        .mk-input:focus {
            border-color: #4f6ef7;
        }

        .mk-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 14px;
        }

        .mk-btn {
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

        .mk-btn-cancel {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #4b5563;
        }

        .mk-btn-save {
            border: 1px solid #4f6ef7;
            background: #4f6ef7;
            color: #fff;
        }

        .mk-error-box {
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
        <h2>Tambah Master Kelas</h2>
        <p>Isi data Unit, Kelas, dan Kelompok.</p>
    </div>

    <div class="mk-form-card">
        <div class="mk-form-body">
            <form method="POST" action="{{ route('master.kelas.store') }}">
                @csrf

                @if ($errors->any())
                    <div class="mk-error-box">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mk-field-wrap">
                    <div>
                        <label class="mk-label">Unit <span class="mk-required">*</span></label>
                        <input
                            name="unit"
                            type="text"
                            class="mk-input"
                            list="unit-options"
                            value="{{ old('unit') }}"
                            placeholder="Cari / pilih unit"
                            autocomplete="off"
                            required
                        >
                        <datalist id="unit-options">
                            @foreach (($unitOptions ?? []) as $unit)
                                <option value="{{ $unit }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div>
                        <label class="mk-label">Kelas <span class="mk-required">*</span></label>
                        <input name="kelas" type="text" class="mk-input" value="{{ old('kelas') }}" placeholder="Kelas" required>
                    </div>

                    <div>
                        <label class="mk-label">Kelompok <span class="mk-required">*</span></label>
                        <input name="kelompok" type="text" class="mk-input" value="{{ old('kelompok') }}" placeholder="Kelompok" required>
                    </div>

                    <input type="hidden" name="jenjang" value="{{ old('jenjang') }}">
                </div>

                <div class="mk-actions">
                    <a class="mk-btn mk-btn-cancel" href="{{ route('master.kelas') }}">Batal</a>
                    <button class="mk-btn mk-btn-save" type="submit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var kelasInput = document.querySelector('input[name="kelas"]');
            var jenjangInput = document.querySelector('input[name="jenjang"]');
            if (!kelasInput || !jenjangInput) return;

            var syncJenjang = function() {
                jenjangInput.value = kelasInput.value.trim();
            };

            kelasInput.addEventListener('input', syncJenjang);
            syncJenjang();
        })();
    </script>
@endsection

