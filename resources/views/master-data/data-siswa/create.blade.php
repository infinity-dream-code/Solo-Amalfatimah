@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Tambah Data Siswa</h2>
        <p>Form input data siswa.</p>
    </div>

        <div class="card">
        <div class="card-body-pad">
            @if ($errors->any())
                <div style="margin-bottom:12px;padding:10px 12px;border-radius:8px;background:#fef2f2;color:#b91c1c;font-size:13px;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('master.data_siswa.store') }}">
                @csrf
                <div style="display:grid;gap:10px;max-width:620px;">
                    <div style="display:grid;gap:8px;grid-template-columns:1fr 1fr;">
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">NIS</div>
                            <input name="nis" type="text" value="{{ old('nis') }}" placeholder="NIS" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Nama</div>
                            <input name="nama" type="text" value="{{ old('nama') }}" placeholder="Nama siswa" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit">Simpan</button>
                        <a class="btn" href="{{ route('master.data_siswa') }}">Kembali</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

