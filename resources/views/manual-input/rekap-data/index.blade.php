@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Rekap Data</h2>
        <p>Rekap data input manual (dummy).</p>
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('manual_input.edit_manual') }}">Edit Manual</a>
    </div>

    <div class="card">
        <div class="card-body-pad">
            Belum ada data.
        </div>
    </div>
@endsection

