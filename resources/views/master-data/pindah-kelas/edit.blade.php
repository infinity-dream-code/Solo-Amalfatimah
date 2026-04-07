@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Edit Pindah Kelas</h2>
        <p>Form edit pindah kelas.</p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            <form method="POST" action="{{ route('master.pindah_kelas.update', ['id' => $id]) }}">
                @csrf
                @method('PUT')
                <div style="display:grid;gap:10px;max-width:720px;">
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">ID</div>
                        <input value="{{ $id }}" disabled style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#f8fafc;">
                    </div>
                    <div style="display:grid;gap:8px;grid-template-columns:1fr 1fr;">
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">ID Siswa</div>
                            <input name="siswa_id" type="text" placeholder="ID siswa" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Kelas Tujuan</div>
                            <input name="kelas_tujuan" type="text" placeholder="Kelas tujuan" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit">Update</button>
                        <a class="btn" href="{{ route('master.pindah_kelas') }}">Kembali</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

