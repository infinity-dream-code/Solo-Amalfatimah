@extends('layouts.app')

@section('content')
    <style>
        .bp-card { background:#fff; border:1px solid #e4eaf0; border-radius:14px; box-shadow:0 6px 18px rgba(15,23,42,.06); margin-top:16px; }
        .bp-head { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px 18px 8px; }
        .bp-title { font-family:'Sora',sans-serif; font-size:18px; font-weight:700; }
        .bp-btn-create { display:inline-flex; align-items:center; gap:8px; background:#4f6ef7; color:#fff; border:1px solid #4f6ef7; border-radius:8px; padding:8px 14px; font-size:13px; font-weight:700; text-decoration:none; }
        .bp-filter-wrap { padding: 12px 18px 16px; border-top:1px solid #eef2f7; }
        .bp-filter-title { font-size:13px; font-weight:700; margin-bottom:10px; color:#4b5563; }
        .bp-filter-grid { display:grid; grid-template-columns:repeat(2,minmax(240px,1fr)); gap:12px; }
        .bp-field label { display:block; font-size:12px; color:#4b5563; margin-bottom:5px; font-weight:600; }
        .bp-input { width:100%; height:36px; border:1px solid #d1d5db; border-radius:8px; padding:0 10px; font-size:13px; }
        .bp-filter-actions { margin-top:12px; display:flex; justify-content:flex-end; gap:8px; }
        .bp-btn { height:36px; padding:0 14px; border-radius:8px; border:1px solid #d1d5db; background:#fff; font-size:13px; font-weight:600; color:#374151; text-decoration:none; display:inline-flex; align-items:center; }
        .bp-btn-primary { background:#4f6ef7; border-color:#4f6ef7; color:#fff; cursor:pointer; }
        .bp-toolbar { padding:12px 18px; display:flex; justify-content:flex-end; border-top:1px solid #eef2f7; }
        .bp-search { display:flex; align-items:center; gap:8px; font-size:13px; color:#6b7280; }
        .bp-search input { width:220px; height:34px; border:1px solid #e5e7eb; border-radius:7px; padding:0 10px; font-size:12px; }
        .bp-table-wrap { overflow-x:auto; border-top:1px solid #eef2f7; }
        .bp-table { width:100%; border-collapse:collapse; min-width:700px; }
        .bp-table th,.bp-table td { border-bottom:1px solid #eef2f7; padding:10px 12px; font-size:13px; text-align:left; }
        .bp-table th { background:#fafbfd; color:#4b5563; font-weight:700; }
        .bp-col-no { width:56px; text-align:center; }
        .bp-empty { text-align:center; color:#6b7280; padding:20px 12px; }
        .bp-alert { margin:0 18px 12px; padding:10px 12px; border-radius:8px; background:#ecfdf5; color:#047857; font-size:13px; font-weight:600; }
        .bp-pagination-wrap { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:14px 18px 18px; }
        .bp-pagination-info { font-size:12px; color:#6b7280; }
        .bp-pagination { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .bp-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 10px; border:1px solid #d1d5db; border-radius:6px; text-decoration:none; color:#374151; font-size:12px; font-weight:600; background:#fff; }
        .bp-page-link.active { background:#4f6ef7; color:#fff; border-color:#4f6ef7; }
        .bp-page-link.disabled { color:#9ca3af; border-color:#e5e7eb; pointer-events:none; background:#f9fafb; }
    </style>

    <div class="page-heading">
        <h2>Beban Post</h2>
        <p>Daftar beban post dari web service.</p>
    </div>

    <div class="bp-card">
        <div class="bp-head">
            <div class="bp-title">Beban Post</div>
            <a class="bp-btn-create" href="{{ route('master.beban_post.create') }}">+ Buat Data</a>
        </div>

        @if (session('status'))
            <div class="bp-alert">{{ session('status') }}</div>
        @endif

        <div class="bp-filter-wrap">
            <div class="bp-filter-title">Filter</div>
            <form method="GET" action="{{ route('master.beban_post') }}">
                @if (($keyword ?? '') !== '')
                    <input type="hidden" name="q" value="{{ $keyword }}">
                @endif
                <div class="bp-filter-grid">
                    <div class="bp-field">
                        <label>Tahun Angkatan</label>
                        <select name="thn_masuk" class="bp-input">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_masuk'] ?? []) as $thn)
                                @php
                                    $thnVal = is_array($thn)
                                        ? (string) ($thn['thn_masuk'] ?? $thn['THN_MASUK'] ?? '')
                                        : (string) $thn;
                                @endphp
                                @if ($thnVal !== '')
                                    <option value="{{ $thnVal }}" {{ ($thnMasuk ?? '') === $thnVal ? 'selected' : '' }}>{{ $thnVal }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="bp-field">
                        <label>Kelas</label>
                        <select name="kode_prod" class="bp-input">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['kelas'] ?? []) as $kls)
                                @php
                                    $kp = (string) ($kls['kode_prod'] ?? '');
                                    $nk = (string) ($kls['nama_kelas'] ?? '');
                                    $un = (string) ($kls['unit'] ?? '');
                                @endphp
                                <option value="{{ $kp }}" {{ ($kodeProd ?? '') === $kp ? 'selected' : '' }}>{{ ($un !== '' ? $un.' - ' : '') . $nk }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bp-field">
                        <label>Kode Akun</label>
                        <select name="kode_akun" class="bp-input">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['akun'] ?? []) as $akn)
                                @php
                                    $ka = (string) ($akn['KodeAkun'] ?? $akn['kodeakun'] ?? '');
                                    $na = (string) ($akn['NamaAkun'] ?? $akn['namaakun'] ?? '');
                                @endphp
                                <option value="{{ $ka }}" {{ ($kodeAkun ?? '') === $ka ? 'selected' : '' }}>{{ $ka . ($na !== '' ? ' - '.$na : '') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bp-field">
                        <label>Nominal</label>
                        <input type="text" name="nominal" class="bp-input" value="{{ $nominal ?? '' }}" placeholder="Rp. Nominal">
                    </div>
                </div>
                <div class="bp-filter-actions">
                    <a class="bp-btn" href="{{ route('master.beban_post') }}">Reset</a>
                    <button type="submit" class="bp-btn bp-btn-primary">Cari</button>
                </div>
            </form>
        </div>

        <div class="bp-toolbar">
            <form method="GET" action="{{ route('master.beban_post') }}" class="bp-search">
                @if (($thnMasuk ?? '') !== '')<input type="hidden" name="thn_masuk" value="{{ $thnMasuk }}">@endif
                @if (($kodeProd ?? '') !== '')<input type="hidden" name="kode_prod" value="{{ $kodeProd }}">@endif
                @if (($kodeAkun ?? '') !== '')<input type="hidden" name="kode_akun" value="{{ $kodeAkun }}">@endif
                @if (($nominal ?? '') !== '')<input type="hidden" name="nominal" value="{{ $nominal }}">@endif
                <span>Cari:</span>
                <input type="text" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
            </form>
        </div>

        <div class="bp-table-wrap">
            <table class="bp-table">
                <thead>
                    <tr>
                        <th class="bp-col-no">No</th>
                        <th>Kode</th>
                        <th>Nama Post</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($bebanRows ?? []) as $index => $row)
                        <tr>
                            <td class="bp-col-no">{{ ($bebanRows->firstItem() ?? 1) + $index }}</td>
                            <td>{{ $row['kodeakun'] ?? '-' }}</td>
                            <td>{{ $row['namaakun'] ?? '-' }}</td>
                            <td>Rp. {{ number_format((int) preg_replace('/\D+/', '', (string) ($row['nominal'] ?? '0')), 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="bp-empty">Data beban post tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (isset($bebanRows) && method_exists($bebanRows, 'hasPages') && $bebanRows->hasPages())
            <div class="bp-pagination-wrap">
                <div class="bp-pagination-info">Showing {{ $bebanRows->firstItem() }} to {{ $bebanRows->lastItem() }} of {{ $bebanRows->total() }} results</div>
                <div class="bp-pagination">
                    @php
                        $current = $bebanRows->currentPage();
                        $last = $bebanRows->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);
                    @endphp
                    @if ($bebanRows->onFirstPage())
                        <span class="bp-page-link disabled">Prev</span>
                    @else
                        <a class="bp-page-link" href="{{ $bebanRows->appends(request()->query())->url($current - 1) }}">Prev</a>
                    @endif

                    @for ($p = $start; $p <= $end; $p++)
                        @if ($p === $current)
                            <span class="bp-page-link active">{{ $p }}</span>
                        @else
                            <a class="bp-page-link" href="{{ $bebanRows->appends(request()->query())->url($p) }}">{{ $p }}</a>
                        @endif
                    @endfor

                    @if ($bebanRows->hasMorePages())
                        <a class="bp-page-link" href="{{ $bebanRows->appends(request()->query())->url($current + 1) }}">Next</a>
                    @else
                        <span class="bp-page-link disabled">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

