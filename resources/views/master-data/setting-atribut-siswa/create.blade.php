@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Tambah Setting Atribut Siswa</h2>
        <p>Form input atribut siswa.</p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            <form method="POST" action="{{ route('master.setting_atribut_siswa.store') }}">
                @csrf
                <div style="display:grid;gap:10px;max-width:620px;">
                    <div style="display:grid;gap:8px;grid-template-columns:1fr 1fr;">
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Key</div>
                            <input name="key" type="text" placeholder="contoh: nama_ayah" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Label</div>
                            <input name="label" type="text" placeholder="Contoh: Nama Ayah" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit">Simpan</button>
                        <a class="btn" href="{{ route('master.setting_atribut_siswa') }}">Kembali</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

