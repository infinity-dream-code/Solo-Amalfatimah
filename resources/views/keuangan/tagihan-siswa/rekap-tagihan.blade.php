@extends('layouts.app')

@section('content')
    <style>
        .rk-wrap { margin-top: 16px; }
        .rk-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 8px 20px rgba(15,23,42,.05); overflow: hidden; }
        .rk-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 8px; }
        .rk-filter { padding: 0 16px 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; border-bottom: 1px solid #eef2f7; }
        @media (max-width: 960px) { .rk-filter { grid-template-columns: 1fr; } }
        .rk-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .rk-fld input, .rk-fld select { width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px; }
        .rk-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; flex-wrap: wrap; }
        .rk-btn { height: 36px; border-radius: 8px; border: 1px solid #d1d5db; padding: 0 14px; font-size: 13px; font-weight: 700; cursor: pointer; background: #fff; color: #374151; text-decoration: none; display: inline-flex; align-items:center; }
        .rk-btn-search { background: #4f6ef7; border-color: #4f6ef7; color: #fff; }
        .rk-btn-print { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .rk-table-wrap { overflow-x:auto; }
        .rk-table { width:100%; min-width:1600px; border-collapse: collapse; font-size:12px; }
        .rk-table th,.rk-table td { border-bottom:1px solid #eef2f7; padding:9px 8px; text-align:left; white-space: nowrap; }
        .rk-table th { background:#fafbfd; color:#4b5563; font-weight:700; }
        .rk-check { width: 34px; text-align: center; }
        .rk-num { text-align: right; }
        .rk-center { text-align: center; }
        .rk-err { margin:10px 16px 0; border-radius:8px; padding:10px 12px; font-size:13px; font-weight:600; background:#fef2f2; color:#b91c1c; }
        .rk-foot-wrap { padding: 12px 16px; display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; font-size:12px; color:#6b7280; }
        .rk-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .rk-page.active { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .rk-page.disabled { pointer-events: none; opacity: 0.45; }
    </style>

    <div class="page-heading">
        <h2>Rekap Tagihan</h2>
    </div>

    <div class="rk-wrap">
        <div class="rk-card">
            <div class="rk-title">Rekap Tagihan</div>
            @if (session('export_error'))
                <div class="rk-err">{{ session('export_error') }}</div>
            @endif
            @if (($errorMsg ?? '') !== '')
                <div class="rk-err">{{ $errorMsg }}</div>
            @endif

            <form method="GET" action="{{ route('keu.tagihan.rekap') }}">
                <div class="rk-filter">
                    <div class="rk-fld">
                        <label>Tahun Pelajaran</label>
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
                    <div class="rk-fld">
                        <label>Dari Tanggal</label>
                        <input type="date" name="tgl_dari" value="{{ $filters['tgl_dari'] ?? '' }}">
                    </div>
                    <div class="rk-fld">
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
                    <div class="rk-fld">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="tgl_sampai" value="{{ $filters['tgl_sampai'] ?? '' }}">
                    </div>
                    <div class="rk-fld">
                        <label>Tahun Angkatan</label>
                        <select name="thn_angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rk-fld">
                        <label>Nama Tagihan</label>
                        <select name="nama_tagihan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['tagihan'] ?? []) as $tag)
                                <option value="{{ $tag }}" {{ (($filters['nama_tagihan'] ?? '') === $tag) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rk-fld">
                        <label>NIS / Nama</label>
                        <input type="text" name="siswa" value="{{ $filters['siswa'] ?? '' }}" placeholder="Masukkan NIS/NAMA">
                    </div>
                </div>
                <div class="rk-actions">
                    <button type="button" class="rk-btn rk-btn-print" id="rkCetakRekapBtn">Cetak Rekap</button>
                    <button type="button" class="rk-btn rk-btn-print" id="rkCetakPerNisBtn">Cetak Per NIS</button>
                    <button type="button" class="rk-btn rk-btn-print" id="rkCetakKartuBtn">Cetak Kartu Siswa</button>
                    <a class="rk-btn" href="{{ route('keu.tagihan.rekap') }}">Reset</a>
                    <button type="submit" class="rk-btn rk-btn-search">Cari</button>
                </div>
            </form>
            <form id="rkFormRekap" method="POST" action="{{ route('keu.tagihan.data_print_rekap') }}" target="_blank" style="display:none;">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'siswa', 'sort_urutan'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
                <input type="hidden" name="has_search_context" value="{{ !empty($hasSearchRequest) ? '1' : '0' }}">
            </form>
            <form id="rkFormKartu" method="POST" action="{{ route('keu.tagihan.data_print_kartu') }}" target="_blank" style="display:none;">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'siswa', 'sort_urutan'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
                <input type="hidden" name="selected_rows" id="rkSelectedRowsKartu" value="">
            </form>
            <form id="rkFormPerNis" method="POST" action="{{ route('keu.tagihan.export_print') }}" target="_blank" style="display:none;">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'siswa', 'sort_urutan'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
                <input type="hidden" name="print_mode" value="by_custid">
                <input type="hidden" name="selected_rows" id="rkSelectedRowsPerNis" value="">
            </form>

            <div class="rk-table-wrap">
                <table class="rk-table">
                    <thead>
                        <tr>
                            <th class="rk-check"><input type="checkbox" id="rkCheckAll"></th>
                            <th>NO</th>
                            <th>REK</th>
                            <th>KELAS</th>
                            <th>KELOMPOK</th>
                            <th>ANGKATAN</th>
                            <th>KODE</th>
                            <th>NAMA POST</th>
                            <th>TAGIHAN</th>
                            <th>NIS</th>
                            <th>NO VA</th>
                            <th>NAMA</th>
                            <th>UNIT</th>
                            <th>NAMA TAGIHAN</th>
                            <th>TAHUN AKA</th>
                            <th>URUTAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapRows as $r)
                            @php $row = is_array($r) ? $r : (array) $r; @endphp
                            <tr>
                                <td class="rk-check">
                                    <input
                                        type="checkbox"
                                        class="rk-row-check"
                                        data-custid="{{ (int) ($row['custid'] ?? 0) }}"
                                        data-billcd="{{ trim((string) ($row['billcd'] ?? '')) }}"
                                    >
                                </td>
                                <td>{{ ($rekapRows->firstItem() ?? 0) + $loop->index }}</td>
                                <td>{{ trim((string) ($row['rek'] ?? '-')) !== '' ? $row['rek'] : '-' }}</td>
                                <td>{{ trim((string) ($row['kelas'] ?? '-')) !== '' ? $row['kelas'] : '-' }}</td>
                                <td>{{ trim((string) ($row['kelompok'] ?? '-')) !== '' ? $row['kelompok'] : '-' }}</td>
                                <td>{{ trim((string) ($row['angkatan'] ?? '-')) !== '' ? $row['angkatan'] : '-' }}</td>
                                <td>{{ trim((string) ($row['kode'] ?? '-')) !== '' ? $row['kode'] : '-' }}</td>
                                <td>{{ trim((string) ($row['nama_post'] ?? '-')) !== '' ? $row['nama_post'] : '-' }}</td>
                                <td class="rk-num">{{ number_format((int) ($row['tagihan'] ?? 0), 0, ',', '.') }}</td>
                                <td>{{ trim((string) ($row['nis'] ?? '-')) !== '' ? $row['nis'] : '-' }}</td>
                                <td>{{ trim((string) ($row['no_va'] ?? '-')) !== '' ? $row['no_va'] : '-' }}</td>
                                <td>{{ trim((string) ($row['nama'] ?? '-')) !== '' ? $row['nama'] : '-' }}</td>
                                <td>{{ trim((string) ($row['unit'] ?? '-')) !== '' ? $row['unit'] : '-' }}</td>
                                <td>{{ trim((string) ($row['nama_tagihan'] ?? '-')) !== '' ? $row['nama_tagihan'] : '-' }}</td>
                                <td>{{ trim((string) ($row['tahun_aka'] ?? '-')) !== '' ? $row['tahun_aka'] : '-' }}</td>
                                <td class="rk-center">{{ (int) ($row['furutan'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="16" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rk-foot-wrap">
                <div>Menampilkan {{ $rekapRows->firstItem() ?? 0 }} sampai {{ $rekapRows->lastItem() ?? 0 }} dari {{ $rekapRows->total() ?? 0 }} entri</div>
                <div style="display:flex;gap:6px;align-items:center;">
                    @if ($rekapRows->onFirstPage())
                        <span class="rk-page disabled">Sebelumnya</span>
                    @else
                        <a class="rk-page" href="{{ $rekapRows->appends(request()->query())->previousPageUrl() }}">Sebelumnya</a>
                    @endif
                    <span class="rk-page active">{{ $rekapRows->currentPage() }}</span>
                    @if ($rekapRows->hasMorePages())
                        <a class="rk-page" href="{{ $rekapRows->appends(request()->query())->nextPageUrl() }}">Selanjutnya</a>
                    @else
                        <span class="rk-page disabled">Selanjutnya</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const btnRekap = document.getElementById('rkCetakRekapBtn');
            const btnKartu = document.getElementById('rkCetakKartuBtn');
            const btnPerNis = document.getElementById('rkCetakPerNisBtn');
            const formRekap = document.getElementById('rkFormRekap');
            const formKartu = document.getElementById('rkFormKartu');
            const formPerNis = document.getElementById('rkFormPerNis');
            const inputKartu = document.getElementById('rkSelectedRowsKartu');
            const inputPerNis = document.getElementById('rkSelectedRowsPerNis');
            const checkAll = document.getElementById('rkCheckAll');
            const rowChecks = Array.from(document.querySelectorAll('.rk-row-check'));

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    rowChecks.forEach(function (cb) { cb.checked = !!checkAll.checked; });
                });
            }

            function collectCustIds() {
                const bucket = {};
                rowChecks.forEach(function (cb) {
                    if (!cb.checked) return;
                    const n = parseInt(cb.getAttribute('data-custid') || '0', 10);
                    if (n > 0) bucket[n] = true;
                });
                return Object.keys(bucket).map(function (k) { return parseInt(k, 10); });
            }

            function collectTagihanRows() {
                const out = [];
                rowChecks.forEach(function (cb) {
                    if (!cb.checked) return;
                    const n = parseInt(cb.getAttribute('data-custid') || '0', 10);
                    const b = (cb.getAttribute('data-billcd') || '').trim();
                    if (n > 0 && b !== '') out.push({ custid: n, billcd: b });
                });
                return out;
            }

            if (btnRekap && formRekap) {
                btnRekap.addEventListener('click', function () {
                    if (rowChecks.length === 0) {
                        alert('Data masih kosong. Klik Cari dulu sebelum cetak rekap.');
                        return;
                    }
                    formRekap.submit();
                });
            }

            if (btnKartu && formKartu && inputKartu) {
                btnKartu.addEventListener('click', function () {
                    const picked = collectCustIds();
                    if (picked.length === 0) {
                        alert('Pilih minimal 1 siswa dari centang kiri tabel.');
                        return;
                    }
                    inputKartu.value = JSON.stringify(picked);
                    formKartu.submit();
                });
            }

            if (btnPerNis && formPerNis && inputPerNis) {
                btnPerNis.addEventListener('click', function () {
                    const picked = collectCustIds();
                    inputPerNis.value = JSON.stringify(picked);
                    formPerNis.submit();
                });
            }
        })();
    </script>
@endsection

