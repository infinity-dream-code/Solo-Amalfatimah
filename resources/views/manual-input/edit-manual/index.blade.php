@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Edit Manual</h2>
        <p>Halaman edit transaksi/pembayaran manual (dummy, siap dikembangkan).</p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            @if (session('status'))
                <div style="margin-bottom:12px;color:#047857;font-weight:600;">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('manual_input.edit_manual.update') }}">
                @csrf
                @method('PUT')
                <div style="display:grid;gap:10px;max-width:820px;">
                    <div style="display:grid;gap:8px;grid-template-columns:1fr 1fr 1fr;">
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">ID Transaksi</div>
                            <input name="trx_id" type="text" placeholder="ID transaksi" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Nominal</div>
                            <input name="nominal" type="number" placeholder="0" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Tanggal</div>
                            <input name="tanggal" type="date" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#fff;">
                        </div>
                    </div>
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">Keterangan</div>
                        <input name="keterangan" type="text" placeholder="Keterangan" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;">
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

