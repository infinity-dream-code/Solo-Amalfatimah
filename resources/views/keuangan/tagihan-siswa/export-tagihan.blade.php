@extends('layouts.app')

@section('content')
    <style>
        .ex-wrap { margin-top: 16px; }
        .ex-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 8px 20px rgba(15,23,42,.05); overflow: hidden; }
        .ex-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 8px; }
        .ex-tip { padding: 0 16px 12px; color: #111827; font-size: 20px; font-weight: 700; }
        .ex-tip span { color: #dc2626; font-size: 16px; margin-right: 8px; }
        .ex-filter { padding: 0 16px 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; border-bottom: 1px solid #eef2f7; }
        @media (max-width: 960px) { .ex-filter { grid-template-columns: 1fr; } }
        .ex-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .ex-fld input, .ex-fld select { width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px; }
        .ex-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .ex-btn { height: 36px; border-radius: 8px; border: 1px solid #d1d5db; padding: 0 14px; font-size: 13px; font-weight: 700; cursor: pointer; background: #fff; color: #374151; text-decoration: none; display: inline-flex; align-items:center; }
        .ex-btn-print { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .ex-btn-search { background: #4f6ef7; border-color: #4f6ef7; color: #fff; }
        .ex-toolbar { display:flex; justify-content:space-between; align-items:center; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .ex-select, .ex-input { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .ex-left, .ex-right { display:flex; align-items:center; gap:8px; font-size:13px; color:#4b5563; }
        .ex-table-wrap { overflow-x:auto; }
        .ex-table { width:100%; min-width:1020px; border-collapse: collapse; font-size:12px; }
        .ex-table th,.ex-table td { border-bottom:1px solid #eef2f7; padding:9px 8px; text-align:left; }
        .ex-table th { background:#fafbfd; color:#4b5563; font-weight:700; }
        .ex-check { width:36px; text-align:center; }
        .ex-foot { padding: 12px 16px; font-size:12px; color:#6b7280; }
        .ex-foot-wrap { padding: 12px 16px; display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; font-size:12px; color:#6b7280; }
        .ex-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .ex-page.active { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .ex-page.disabled { pointer-events: none; opacity: 0.45; }
        .ex-err { margin:10px 16px 0; border-radius:8px; padding:10px 12px; font-size:13px; font-weight:600; background:#fef2f2; color:#b91c1c; }
    </style>

    <div class="page-heading">
        <h2>Export Tagihan</h2>
    </div>

    <div class="ex-wrap">
        <div class="ex-card">
            <div class="ex-title">Export Tagihan</div>
            <div class="ex-tip"><span>•</span>Pastikan browser anda tidak memblokir <i>POP-UP</i>!</div>
            @if (($errorMsg ?? '') !== '')
                <div class="ex-err">{{ $errorMsg }}</div>
            @endif

            <form method="GET" action="{{ route('keu.tagihan.export') }}">
                <div class="ex-filter">
                    <div class="ex-fld">
                        <label>Tanggal Pembuatan (tanggal-bulan-tahun - tanggal-bulan-tahun)</label>
                        <input type="text" name="tgl_dari" value="{{ $filters['tgl_dari'] ?? '' }}" placeholder="tanggal/bulan/tahun">
                    </div>
                    <div class="ex-fld">
                        <label>Angkatan Siswa</label>
                        <select name="thn_angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ex-fld">
                        <label>Tahun Akademik</label>
                        <select name="thn_akademik">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_akademik'] ?? []) as $th)
                                @php $val = (string) ($th['thn_aka'] ?? ''); @endphp
                                @if ($val !== '')
                                    <option value="{{ $val }}" {{ (($filters['thn_akademik'] ?? '') === $val) ? 'selected' : '' }}>{{ $val }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="ex-fld">
                        <label>Kelas</label>
                        <select name="kelas_id">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['kelas'] ?? []) as $k)
                                @php $id = (string) ($k['id'] ?? ''); $lbl = trim((string) (($k['unit'] ?? '') . ' - ' . ($k['kelas'] ?? ''))); @endphp
                                @if ($id !== '')
                                    <option value="{{ $id }}" {{ (($filters['kelas_id'] ?? '') === $id) ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="ex-fld">
                        <label>Nama Tagihan</label>
                        <select name="nama_tagihan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['tagihan'] ?? []) as $tag)
                                <option value="{{ $tag }}" {{ (($filters['nama_tagihan'] ?? '') === $tag) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ex-fld">
                        <label>Siswa</label>
                        <input type="text" name="siswa" value="{{ $filters['siswa'] ?? '' }}" placeholder="Masukkan NIS/NAMA Siswa">
                    </div>
                    <div class="ex-fld">
                        <label>Urutan</label>
                        <select name="sort_urutan">
                            <option value="asc" {{ (($filters['sort_urutan'] ?? 'asc') === 'asc') ? 'selected' : '' }}>Asc</option>
                            <option value="desc" {{ (($filters['sort_urutan'] ?? 'asc') === 'desc') ? 'selected' : '' }}>Desc</option>
                        </select>
                    </div>
                </div>
                <div class="ex-actions">
                    <button type="button" class="ex-btn ex-btn-print" id="exPrintBtn">Print</button>
                    <a class="ex-btn" href="{{ route('keu.tagihan.export') }}">Reset</a>
                    <button type="submit" class="ex-btn ex-btn-search">Cari</button>
                </div>
            </form>
            <form id="exPrintForm" method="POST" action="{{ route('keu.tagihan.export_print') }}" target="_blank" style="display:none;">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'siswa', 'sort_urutan'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
                <input type="hidden" name="selected_rows" id="exSelectedRows" value="">
            </form>

            <div class="ex-toolbar">
                <form method="GET" action="{{ route('keu.tagihan.export') }}" class="ex-left">
                    @foreach (($filters ?? []) as $fk => $fv)
                        @if ($fv !== '' && $fk !== 'per_page')
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <span>Tampilkan</span>
                    <select class="ex-select" name="per_page" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" {{ ($tagihanRows->perPage() ?? 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="sort_urutan" value="{{ $filters['sort_urutan'] ?? 'asc' }}">
                    <span>entri</span>
                </form>
                <div class="ex-right">
                    <span>Cari:</span>
                    <input class="ex-input" placeholder="kata kunci pencarian" disabled>
                </div>
            </div>

            <div class="ex-table-wrap">
                <table class="ex-table">
                    <thead>
                        <tr>
                            <th class="ex-check"></th>
                            <th>NO</th>
                            <th>NIS</th>
                            <th>NAMA</th>
                            <th>NAMA TAGIHAN</th>
                            <th>JUMLAH</th>
                            <th>TAHUN AKA</th>
                            <th>
                                @php
                                    $nextSort = (($filters['sort_urutan'] ?? 'asc') === 'asc') ? 'desc' : 'asc';
                                    $sortQuery = array_merge(request()->query(), ['sort_urutan' => $nextSort]);
                                @endphp
                                <a href="{{ route('keu.tagihan.export', $sortQuery) }}" style="text-decoration:none;color:inherit;">
                                    URUTAN {{ (($filters['sort_urutan'] ?? 'asc') === 'asc') ? '↑' : '↓' }}
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagihanRows as $index => $r)
                            @php $row = is_array($r) ? $r : (array) $r; @endphp
                            <tr>
                                <td class="ex-check">
                                    <input
                                        type="checkbox"
                                        class="ex-row-sel"
                                        data-custid="{{ (int) ($row['custid'] ?? 0) }}"
                                        data-billcd="{{ trim((string) ($row['billcd'] ?? '')) }}"
                                    >
                                </td>
                                <td>{{ ($tagihanRows->firstItem() ?? 0) + $loop->index }}</td>
                                <td>{{ $row['nis'] ?? '-' }}</td>
                                <td>{{ $row['nama'] ?? '-' }}</td>
                                <td>{{ $row['nama_tagihan'] ?? '-' }}</td>
                                <td>Rp {{ number_format((int) ($row['tagihan'] ?? 0), 0, ',', '.') }}</td>
                                <td>{{ $row['tahun_aka'] ?? '-' }}</td>
                                <td>{{ (int) ($row['furutan'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="ex-foot-wrap">
                <div>Menampilkan {{ $tagihanRows->firstItem() ?? 0 }} sampai {{ $tagihanRows->lastItem() ?? 0 }} dari {{ $tagihanRows->total() ?? 0 }} entri</div>
                <div style="display:flex;gap:6px;align-items:center;">
                    @if ($tagihanRows->onFirstPage())
                        <span class="ex-page disabled">Sebelumnya</span>
                    @else
                        <a class="ex-page" href="{{ $tagihanRows->appends(request()->query())->previousPageUrl() }}">Sebelumnya</a>
                    @endif
                    <span class="ex-page active">{{ $tagihanRows->currentPage() }}</span>
                    @if ($tagihanRows->hasMorePages())
                        <a class="ex-page" href="{{ $tagihanRows->appends(request()->query())->nextPageUrl() }}">Selanjutnya</a>
                    @else
                        <span class="ex-page disabled">Selanjutnya</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const btn = document.getElementById('exPrintBtn');
            const form = document.getElementById('exPrintForm');
            const input = document.getElementById('exSelectedRows');
            if (!btn || !form || !input) return;
            btn.addEventListener('click', function () {
                const keys = {};
                document.querySelectorAll('.ex-row-sel:checked').forEach(function (cb) {
                    const n = parseInt(cb.getAttribute('data-custid') || '0', 10);
                    const b = (cb.getAttribute('data-billcd') || '').trim();
                    if (n > 0 && b !== '') {
                        keys[n + '|' + b] = { custid: n, billcd: b };
                    }
                });
                const picked = Object.keys(keys).map(function (k) { return keys[k]; });
                if (picked.length === 0) {
                    alert('Pilih minimal 1 baris tagihan dari centang kiri tabel.');
                    return;
                }
                input.value = JSON.stringify(picked);
                form.submit();
            });
        })();
    </script>
@endsection

