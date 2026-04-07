@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Edit Master Kelas</h2>
        <p>Form edit Master Kelas.</p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            <form method="POST" action="{{ route('master.kelas.update', ['id' => $id]) }}">
                @csrf
                @method('PUT')
                <div style="display:grid;gap:10px;max-width:520px;">
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">ID</div>
                        <input value="{{ $id }}" disabled style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#f8fafc;">
                    </div>
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">Nama Kelas</div>
                        <input name="nama" type="text" placeholder="Contoh: Kelas 1A" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit">Update</button>
                        <a class="btn" href="{{ route('master.kelas') }}">Kembali</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

