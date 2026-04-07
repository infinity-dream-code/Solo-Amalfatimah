@extends('layouts.app')

@section('content')
    <style>
        .mp-card {
            background: #fff;
            border: 1px solid #e4eaf0;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            margin-top: 16px;
        }

        .mp-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 18px 8px;
        }

        .mp-title {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 700;
        }

        .mp-btn-create {
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

        .mp-toolbar {
            padding: 8px 18px 14px;
            display: flex;
            justify-content: flex-end;
        }

        .mp-search {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }

        .mp-search input {
            width: 220px;
            height: 34px;
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            padding: 0 10px;
            outline: none;
            font-size: 12px;
        }

        .mp-search input:focus { border-color: #4f6ef7; }

        .mp-table-wrap { overflow-x: auto; border-top: 1px solid #eef2f7; }
        .mp-table { width: 100%; border-collapse: collapse; min-width: 700px; }

        .mp-table th, .mp-table td {
            border-bottom: 1px solid #eef2f7;
            padding: 10px 12px;
            font-size: 13px;
            text-align: left;
            vertical-align: middle;
        }

        .mp-table th { color: #4b5563; font-weight: 700; background: #fafbfd; }
        .mp-col-no { width: 56px; text-align: center; }
        .mp-empty { text-align: center; color: #6b7280; padding: 20px 12px; }

        .mp-alert {
            margin: 0 18px 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #ecfdf5;
            color: #047857;
            font-size: 13px;
            font-weight: 600;
        }

        .mp-pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 18px 18px;
        }
        .mp-pagination-info { font-size: 12px; color: #6b7280; }
        .mp-pagination { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .mp-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            padding: 0 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            background: #fff;
        }
        .mp-page-link.active { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .mp-page-link.disabled { color: #9ca3af; border-color: #e5e7eb; pointer-events: none; background: #f9fafb; }
    </style>

    <div class="page-heading">
        <h2>Master Post</h2>
        <p>Data master post dari web service.</p>
    </div>

    <div class="mp-card">
        <div class="mp-head">
            <div class="mp-title">Master Post</div>
            <a class="mp-btn-create" href="{{ route('master.post.create') }}">+ Buat Data</a>
        </div>

        @if (session('status'))
            <div class="mp-alert">{{ session('status') }}</div>
        @endif

        <div class="mp-toolbar">
            <form method="GET" action="{{ route('master.post') }}" class="mp-search">
                <span>Cari:</span>
                <input type="text" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
            </form>
        </div>

        <div class="mp-table-wrap">
            <table class="mp-table">
                <thead>
                    <tr>
                        <th class="mp-col-no">No</th>
                        <th>Kode</th>
                        <th>Nama Post</th>
                        <th>Nomor Rekening</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($postRows ?? []) as $index => $row)
                        <tr>
                            <td class="mp-col-no">{{ ($postRows->firstItem() ?? 1) + $index }}</td>
                            <td>{{ $row['kodeakun'] ?? '-' }}</td>
                            <td>{{ $row['namaakun'] ?? '-' }}</td>
                            <td>{{ $row['norek'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="mp-empty">Data master post tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (isset($postRows) && method_exists($postRows, 'hasPages') && $postRows->hasPages())
            <div class="mp-pagination-wrap">
                <div class="mp-pagination-info">
                    Showing {{ $postRows->firstItem() }} to {{ $postRows->lastItem() }} of {{ $postRows->total() }} results
                </div>
                <div class="mp-pagination">
                    @php
                        $current = $postRows->currentPage();
                        $last = $postRows->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);
                    @endphp

                    @if ($postRows->onFirstPage())
                        <span class="mp-page-link disabled">Prev</span>
                    @else
                        <a class="mp-page-link" href="{{ $postRows->appends(request()->query())->url($current - 1) }}">Prev</a>
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page === $current)
                            <span class="mp-page-link active">{{ $page }}</span>
                        @else
                            <a class="mp-page-link" href="{{ $postRows->appends(request()->query())->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($postRows->hasMorePages())
                        <a class="mp-page-link" href="{{ $postRows->appends(request()->query())->url($current + 1) }}">Next</a>
                    @else
                        <span class="mp-page-link disabled">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

