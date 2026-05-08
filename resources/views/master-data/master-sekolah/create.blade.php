@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Tambah Master Sekolah</h2>
        <p>Isi data CODE01, DESC01, dan data tambahan jika diperlukan.</p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            @if ($errors->any())
                <div style="margin-bottom:12px;padding:10px 12px;border-radius:8px;background:#fef2f2;color:#b91c1c;font-size:13px;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('master.sekolah.store') }}">
                @csrf
                <div style="display:grid;gap:10px;max-width:620px;">
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">CODE01 *</div>
                        <input name="code01" type="text" value="{{ old('code01') }}" placeholder="Contoh: 307" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                    </div>
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">DESC01 *</div>
                        <input name="desc01" type="text" value="{{ old('desc01') }}" placeholder="Contoh: SDIT AL HADI" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                    </div>
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">CODE02</div>
                        <input name="code02" type="text" value="{{ old('code02') }}" placeholder="Opsional" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                    </div>
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">DESC02</div>
                        <input name="desc02" type="text" value="{{ old('desc02') }}" placeholder="Opsional" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                    </div>
                    <div class="btn-row">
                        <a class="btn" href="{{ route('master.sekolah') }}">Batal</a>
                        <button class="btn btn-primary" type="submit">Simpan Data</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

