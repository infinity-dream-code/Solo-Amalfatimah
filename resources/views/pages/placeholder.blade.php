@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>{{ $pageTitle ?? 'Halaman' }}</h2>
        <p>Selamat datang di menu {{ $pageTitle ?? 'Halaman' }}.</p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            Halaman ini masih placeholder.
        </div>
    </div>
@endsection

