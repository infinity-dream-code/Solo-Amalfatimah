@extends('layouts.app')

@section('content')
    <style>
        .ms-card { background: #fff; border: 1px solid #e4eaf0; border-radius: 14px; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06); margin-top: 16px; }
        .ms-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px 18px 8px; }
        .ms-title { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; }
        .ms-btn-create { display: inline-flex; align-items: center; gap: 8px; background: #4f6ef7; color: #fff; border: 1px solid #4f6ef7; border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 700; text-decoration: none; }
        .ms-alert { margin: 0 18px 12px; padding: 10px 12px; border-radius: 8px; background: #ecfdf5; color: #047857; font-size: 13px; font-weight: 600; }
        .ms-toolbar { padding: 8px 18px 14px; display: flex; justify-content: flex-end; }
        .ms-search { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280; }
        .ms-search input { width: 220px; height: 34px; border: 1px solid #e5e7eb; border-radius: 7px; padding: 0 10px; outline: none; font-size: 12px; }
        .ms-search input:focus { border-color: #4f6ef7; }
        .ms-table-wrap { overflow-x: auto; border-top: 1px solid #eef2f7; }
        .ms-table { width: 100%; border-collapse: collapse; min-width: 620px; }
        .ms-table th, .ms-table td { border-bottom: 1px solid #eef2f7; padding: 10px 12px; font-size: 13px; text-align: left; vertical-align: middle; }
        .ms-table th { color: #4b5563; font-weight: 700; background: #fafbfd; }
        .ms-col-no { width: 56px; text-align: center; }
        .ms-col-action { width: 170px; text-align: center; }
        .ms-btn-edit, .ms-btn-delete { border: 0; border-radius: 6px; color: #fff; font-size: 12px; font-weight: 700; padding: 6px 12px; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
        .ms-btn-edit { background: #2563eb; margin-right: 6px; }
        .ms-btn-delete { background: #ef4444; }
        .ms-empty { text-align: center; color: #6b7280; padding: 20px 12px; }
    </style>

    <div class="page-heading">
        <h2>Master Sekolah</h2>
        <p>Data master sekolah dari web service.</p>
    </div>

    <div class="ms-card">
        <div class="ms-head">
            <div class="ms-title">Master Sekolah</div>
            <a class="ms-btn-create" href="{{ route('master.sekolah.create') }}">+ Buat Data</a>
        </div>

        @if (session('status'))
            <div class="ms-alert">{{ session('status') }}</div>
        @endif

        <div class="ms-toolbar">
            <form method="GET" action="{{ route('master.sekolah') }}" class="ms-search">
                <span>Cari:</span>
                <input type="text" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
            </form>
        </div>

        <div class="ms-table-wrap">
            <table class="ms-table">
                <thead>
                    <tr>
                        <th class="ms-col-no">No</th>
                        <th>Code</th>
                        <th>Unit</th>
                        <th class="ms-col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($sekolahRows ?? []) as $index => $row)
                        <tr>
                            <td class="ms-col-no">{{ ($sekolahRows->firstItem() ?? 1) + $index }}</td>
                            <td>{{ $row['code01'] ?? '-' }}</td>
                            <td>{{ $row['desc01'] ?? '-' }}</td>
                            <td class="ms-col-action">
                                <a class="ms-btn-edit" href="{{ route('master.sekolah.edit', ['id' => $row['id'] ?? 0]) }}">Edit</a>
                                <form method="POST" action="{{ route('master.sekolah.destroy', ['id' => $row['id'] ?? 0]) }}" style="display:inline;" onsubmit="return confirm('Yakin hapus data sekolah ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ms-btn-delete">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ms-empty">Data sekolah tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

