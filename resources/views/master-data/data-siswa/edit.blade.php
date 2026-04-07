@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Edit Data Siswa</h2>
        <p>Form edit data siswa.</p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            <form method="POST" action="{{ route('master.data_siswa.update', ['id' => $id]) }}">
                @csrf
                @method('PUT')
                <div style="display:grid;gap:10px;max-width:620px;">
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">ID</div>
                        <input value="{{ $id }}" disabled style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#f8fafc;">
                    </div>
                    <div style="display:grid;gap:8px;grid-template-columns:1fr 1fr;">
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">NIS</div>
                            <input name="nis" type="text" placeholder="NIS" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Nama</div>
                            <input name="nama" type="text" placeholder="Nama siswa" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit">Update</button>
                        <a class="btn" href="{{ route('master.data_siswa') }}">Kembali</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

