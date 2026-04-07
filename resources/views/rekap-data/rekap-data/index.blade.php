@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Rekap Data</h2>
        <p>Ringkasan data (dummy).</p>
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('rekap.cek_pelunasan') }}">Cek Pelunasan</a>
    </div>

    <div class="card">
        <div class="card-body-pad">
            Belum ada data.
        </div>
    </div>
@endsection

