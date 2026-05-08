@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Tambah Master Sekolah</h2>
        <p>Isi data Unit. Code dibuat otomatis.</p>
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
                        <div style="font-weight:700;margin-bottom:6px;">Unit *</div>
                        <input name="desc01" type="text" value="{{ old('desc01') }}" placeholder="Contoh: SDIT AL HADI" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
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

