@extends('layouts.app')

@section('content')
    <style>
        .tp-card {
            background: #fff;
            border: 1px solid #e4eaf0;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            margin-top: 16px;
        }
        .tp-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 18px 8px;
        }
        .tp-title { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; }
        .tp-btn-create {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #4f6ef7;
            color: #fff;
            border: 1px solid #4f6ef7;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }
        .tp-toolbar { padding: 8px 18px 14px; display: flex; justify-content: flex-end; }
        .tp-search { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280; }
        .tp-search input {
            width: 220px; height: 34px; border: 1px solid #e5e7eb; border-radius: 7px; padding: 0 10px; outline: none; font-size: 12px;
        }
        .tp-search input:focus { border-color: #4f6ef7; }
        .tp-table-wrap { overflow-x: auto; border-top: 1px solid #eef2f7; }
        .tp-table { width: 100%; border-collapse: collapse; min-width: 500px; }
        .tp-table th, .tp-table td {
            border-bottom: 1px solid #eef2f7;
            padding: 10px 12px;
            font-size: 13px;
            text-align: left;
            vertical-align: middle;
        }
        .tp-table th { color: #4b5563; font-weight: 700; background: #fafbfd; }
        .tp-col-no { width: 56px; text-align: center; }
        .tp-empty { text-align: center; color: #6b7280; padding: 20px 12px; }
        .tp-alert {
            margin: 0 18px 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #ecfdf5;
            color: #047857;
            font-size: 13px;
            font-weight: 600;
        }
        .tp-pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 18px 18px;
        }
        .tp-pagination-info { font-size: 12px; color: #6b7280; }
        .tp-pagination { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .tp-page-link {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 30px; height: 30px; padding: 0 10px;
            border: 1px solid #d1d5db; border-radius: 6px;
            text-decoration: none; color: #374151; font-size: 12px; font-weight: 600; background: #fff;
        }
        .tp-page-link.active { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .tp-page-link.disabled { color: #9ca3af; border-color: #e5e7eb; pointer-events: none; background: #f9fafb; }
    </style>

    <div class="page-heading">
        <h2>Tahun Pelajaran</h2>
        <p>Data tahun pelajaran dari web service.</p>
    </div>

    <div class="tp-card">
        <div class="tp-head">
            <div class="tp-title">Tahun Pelajaran</div>
            <a class="tp-btn-create" href="{{ route('master.tahun_pelajaran.create') }}">+ Buat Data</a>
        </div>

        @if (session('status'))
            <div class="tp-alert">{{ session('status') }}</div>
        @endif

        <div class="tp-toolbar">
            <form method="GET" action="{{ route('master.tahun_pelajaran') }}" class="tp-search">
                <span>Cari:</span>
                <input type="text" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
            </form>
        </div>

        <div class="tp-table-wrap">
            <table class="tp-table">
                <thead>
                    <tr>
                        <th class="tp-col-no">No</th>
                        <th>Tahun Pelajaran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($tahunRows ?? []) as $index => $row)
                        <tr>
                            <td class="tp-col-no">{{ ($tahunRows->firstItem() ?? 1) + $index }}</td>
                            <td>{{ $row['thn_aka'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="tp-empty">Data tahun pelajaran tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (isset($tahunRows) && method_exists($tahunRows, 'hasPages') && $tahunRows->hasPages())
            <div class="tp-pagination-wrap">
                <div class="tp-pagination-info">
                    Showing {{ $tahunRows->firstItem() }} to {{ $tahunRows->lastItem() }} of {{ $tahunRows->total() }} results
                </div>
                <div class="tp-pagination">
                    @php
                        $current = $tahunRows->currentPage();
                        $last = $tahunRows->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);
                    @endphp
                    @if ($tahunRows->onFirstPage())
                        <span class="tp-page-link disabled">Prev</span>
                    @else
                        <a class="tp-page-link" href="{{ $tahunRows->appends(request()->query())->url($current - 1) }}">Prev</a>
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page === $current)
                            <span class="tp-page-link active">{{ $page }}</span>
                        @else
                            <a class="tp-page-link" href="{{ $tahunRows->appends(request()->query())->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($tahunRows->hasMorePages())
                        <a class="tp-page-link" href="{{ $tahunRows->appends(request()->query())->url($current + 1) }}">Next</a>
                    @else
                        <span class="tp-page-link disabled">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

