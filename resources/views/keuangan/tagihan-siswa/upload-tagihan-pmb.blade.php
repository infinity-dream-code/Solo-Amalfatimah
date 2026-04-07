@extends('layouts.app')

@section('content')
    @php
        $pmbMeta = is_array($pmbMeta ?? null) ? $pmbMeta : [];
        $selThn = (string) ($pmbMeta['thn_akademik'] ?? '');
        $selTag = (string) ($pmbMeta['tagihan'] ?? '');
        $selPeriode = (string) ($pmbMeta['periode'] ?? '');
        $selPost = (string) ($pmbMeta['kode_akun'] ?? '');
    @endphp
    <style>
        .eid-wrap { margin-top: 16px; }
        .eid-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05); overflow: hidden; }
        .eid-head { padding: 14px 16px; border-bottom: 1px solid #eef2f7; }
        .eid-title { font-size: 20px; font-weight: 800; color: #111827; }
        .eid-sub { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .eid-filter { padding: 14px 16px; border-bottom: 1px solid #eef2f7; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 900px) { .eid-filter { grid-template-columns: 1fr; } }
        .eid-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .eid-fld select, .eid-fld input { width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px; }
        .eid-fld input[readonly] { background: #f9fafb; color: #374151; }
        .eid-req { color: #dc2626; }
        .eid-actions-top { display: flex; justify-content: flex-end; padding: 0 16px 14px; }
        .eid-btn-import { border: 0; background: #22c55e; color: #fff; height: 40px; padding: 0 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .eid-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 12px 16px; flex-wrap: wrap; border-bottom: 1px solid #eef2f7; }
        .eid-left, .eid-right { display: flex; align-items: center; gap: 8px; color: #4b5563; font-size: 13px; }
        .eid-select, .eid-input { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .eid-input { width: 180px; }
        .eid-btn-tool { border: 1px solid #d1d5db; background: #fff; color: #374151; height: 34px; padding: 0 12px; border-radius: 8px; font-size: 12px; font-weight: 700; }
        .eid-table-wrap { overflow-x: auto; }
        .eid-table { width: 100%; min-width: 920px; border-collapse: collapse; font-size: 13px; }
        .eid-table th, .eid-table td { border-bottom: 1px solid #eef2f7; padding: 9px 10px; text-align: left; }
        .eid-table th { background: #fafbfd; color: #4b5563; font-size: 12px; font-weight: 700; }
        .eid-empty { text-align: center; color: #6b7280; padding: 18px; }
        .eid-row-bad { background: #fef2f2; }
        .eid-footer { padding: 12px 16px 16px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .eid-info { color: #6b7280; font-size: 12px; }
        .eid-pagination { display: flex; align-items: center; gap: 6px; }
        .eid-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-size: 12px; font-weight: 700; background: #fff; }
        .eid-page.active { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .eid-page.disabled { pointer-events: none; color: #9ca3af; border-color: #e5e7eb; background: #f9fafb; }
        .eid-alert { margin: 10px 16px 0; border-radius: 8px; padding: 10px 12px; font-size: 13px; font-weight: 600; }
        .eid-alert.ok { background: #ecfdf5; color: #047857; }
        .eid-alert.err { background: #fef2f2; color: #b91c1c; }
        .eid-modal { position: fixed; inset: 0; background: rgba(17, 24, 39, 0.35); display: none; align-items: center; justify-content: center; z-index: 80; padding: 14px; }
        .eid-modal.open { display: flex; }
        .eid-modal-box { width: 100%; max-width: 540px; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2); }
        .eid-modal-h { padding: 14px 18px 8px; display: flex; justify-content: space-between; align-items: center; }
        .eid-modal-h h3 { margin: 0; font-size: 22px; font-weight: 700; color: #111827; }
        .eid-close { border: 0; background: transparent; font-size: 20px; color: #6b7280; cursor: pointer; }
        .eid-modal-b { padding: 0 18px 16px; }
        .eid-rules { margin: 0 0 12px; padding-left: 18px; color: #374151; font-size: 13px; }
        .eid-rules li { margin: 8px 0; }
        .eid-file { border: 1px dashed #9ca3af; border-radius: 10px; padding: 18px; text-align: center; margin-bottom: 14px; }
        .eid-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .eid-btn { height: 38px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; }
        .eid-btn-cancel { background: #fff; border: 1px solid #d1d5db; color: #4b5563; }
        .eid-btn-submit { background: #4f6ef7; border: 1px solid #4f6ef7; color: #fff; }
    </style>

    <div class="page-heading">
        <h2>Tagihan Siswa — Upload Tagihan PMB Excel</h2>
        <p>Sama seperti upload tagihan excel, tapi identitas siswa memakai <strong>No Pendaftaran (NUM2ND)</strong>.</p>
    </div>

    <div class="eid-wrap">
        <div class="eid-card">
            <div class="eid-head">
                <div class="eid-title">Upload Tagihan PMB Excel</div>
                <div class="eid-sub">Periode diisi otomatis dari <strong>billac</strong>; post dari <strong>u_akun</strong>.</div>
            </div>

            @if (session('status')) <div class="eid-alert ok">{{ session('status') }}</div> @endif
            @if (session('error')) <div class="eid-alert err">{{ session('error') }}</div> @endif
            @if ($errors->any()) <div class="eid-alert err">{{ $errors->first() }}</div> @endif

            <div class="eid-filter">
                <div class="eid-fld">
                    <label>Tahun Pelajaran <span class="eid-req">*</span></label>
                    <select id="filter-thn-akademik">
                        <option value="">Pilih Tahun Pelajaran</option>
                        @foreach (($filterOptions['thn_akademik'] ?? []) as $th)
                            @php $val = (string) ($th['thn_aka'] ?? ''); @endphp
                            @if ($val !== '') <option value="{{ $val }}" {{ $selThn === $val ? 'selected' : '' }}>{{ $val }}</option> @endif
                        @endforeach
                    </select>
                </div>
                <div class="eid-fld">
                    <label>Tagihan <span class="eid-req">*</span></label>
                    <select id="filter-tagihan">
                        <option value="">Pilih Tagihan</option>
                        @foreach (($filterOptions['tagihan'] ?? []) as $bta)
                            @php $tagihanValue = is_array($bta) ? (string) ($bta['tagihan'] ?? $bta['nama'] ?? '') : (string) $bta; @endphp
                            @if ($tagihanValue !== '') <option value="{{ $tagihanValue }}" {{ $selTag === $tagihanValue ? 'selected' : '' }}>{{ $tagihanValue }}</option> @endif
                        @endforeach
                    </select>
                </div>
                <div class="eid-fld">
                    <label>Post (u_akun) <span class="eid-req">*</span></label>
                    <select id="filter-kode-akun">
                        <option value="">Pilih Post</option>
                        @foreach (($akunPosts ?? []) as $a)
                            @php $kode = (string) ($a['kodeakun'] ?? $a['KodeAkun'] ?? ''); $nama = (string) ($a['namaakun'] ?? $a['NamaAkun'] ?? ''); @endphp
                            @if ($kode !== '') <option value="{{ $kode }}" {{ $selPost === $kode ? 'selected' : '' }}>{{ $kode }} — {{ $nama }}</option> @endif
                        @endforeach
                    </select>
                </div>
                <div class="eid-fld">
                    <label>Periode (billac)</label>
                    <input type="text" id="filter-periode" value="{{ $selPeriode }}" placeholder="Otomatis" readonly>
                </div>
            </div>

            <div class="eid-actions-top"><button type="button" class="eid-btn-import" id="teOpenImport">Import Data Siswa PMB</button></div>

            <div class="eid-toolbar">
                <form method="GET" action="{{ route('keu.tagihan.upload_pmb') }}" class="eid-left">
                    <span>Tampilkan</span>
                    <select class="eid-select" name="per_page" onchange="this.form.submit()">
                        <option value="10" {{ (int) ($perPage ?? 10) === 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ (int) ($perPage ?? 10) === 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ (int) ($perPage ?? 10) === 50 ? 'selected' : '' }}>50</option>
                    </select>
                    @if (($keyword ?? '') !== '')<input type="hidden" name="q" value="{{ $keyword }}">@endif
                    <span>entri</span>
                </form>
                <form method="GET" action="{{ route('keu.tagihan.upload_pmb') }}" class="eid-right">
                    <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 10) }}">
                    <span>Cari:</span>
                    <input type="text" class="eid-input" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci">
                    <button class="eid-btn-tool" type="submit">Cari</button>
                </form>
            </div>

            <div class="eid-table-wrap">
                <table class="eid-table">
                    <thead>
                        <tr><th>No</th><th>NO PENDAFTARAN</th><th>NAMA</th><th>SEKOLAH</th><th>KELAS</th><th>KELOMPOK</th><th>NOMINAL</th><th>STATUS</th></tr>
                    </thead>
                    <tbody>
                        @forelse (($importRows ?? []) as $index => $row)
                            @php $ok = !empty($row['ok']); $nom = (int) ($row['nominal'] ?? 0); @endphp
                            <tr class="{{ $ok ? '' : 'eid-row-bad' }}">
                                <td>{{ ($importRows->firstItem() ?? 1) + $index }}</td>
                                <td>{{ $row['nis'] ?? '-' }}</td>
                                <td>{{ $row['nama'] ?? '-' }}</td>
                                <td>{{ $row['sekolah'] ?? '-' }}</td>
                                <td>{{ $row['kelas'] ?? '-' }}</td>
                                <td>{{ $row['kelompok'] ?? '-' }}</td>
                                <td>Rp {{ number_format($nom, 0, ',', '.') }}</td>
                                <td>{{ $ok ? 'OK' : ($row['error'] ?? 'Error') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="eid-empty">Tidak ada data yang tersedia pada tabel ini</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="eid-footer">
                <div class="eid-info">Menampilkan {{ $importRows->firstItem() ?? 0 }} sampai {{ $importRows->lastItem() ?? 0 }} dari {{ $importRows->total() ?? 0 }} entri</div>
                <div class="eid-pagination">
                    @php $current = $importRows->currentPage(); $last = $importRows->lastPage(); @endphp
                    @if ($importRows->onFirstPage())<span class="eid-page disabled">Sebelumnya</span>@else<a class="eid-page" href="{{ $importRows->appends(request()->query())->url($current - 1) }}">Sebelumnya</a>@endif
                    @for ($p = max(1, $current - 1); $p <= min($last, $current + 1); $p++)
                        @if ($p === $current)<span class="eid-page active">{{ $p }}</span>@else<a class="eid-page" href="{{ $importRows->appends(request()->query())->url($p) }}">{{ $p }}</a>@endif
                    @endfor
                    @if ($importRows->hasMorePages())<a class="eid-page" href="{{ $importRows->appends(request()->query())->url($current + 1) }}">Selanjutnya</a>@else<span class="eid-page disabled">Selanjutnya</span>@endif
                </div>
            </div>

            <div style="padding:0 16px 16px;display:flex;justify-content:flex-end;gap:10px;">
                <form method="POST" action="{{ route('keu.tagihan.upload_pmb.clear') }}">@csrf<button type="submit" class="eid-btn eid-btn-cancel" style="min-width:120px;">Bersihkan Data</button></form>
                <form method="POST" action="{{ route('keu.tagihan.upload_pmb.save') }}">@csrf<button type="submit" class="eid-btn eid-btn-submit" style="min-width:120px;" {{ (($importRows->total() ?? 0) > 0) ? '' : 'disabled' }}>Simpan Data</button></form>
            </div>
        </div>
    </div>

    <div class="eid-modal" id="teImportModal" aria-hidden="true">
        <div class="eid-modal-box">
            <div class="eid-modal-h"><h3>Import Data Tagihan PMB</h3><button type="button" class="eid-close" id="teCloseImport" aria-label="Tutup">×</button></div>
            <div class="eid-modal-b">
                <ul class="eid-rules">
                    <li>File harus berformat <b>XLS / XLSX</b>, maksimal <b>1 MB</b>.</li>
                    <li>Kolom wajib: <b>NO PENDAFTARAN</b> (NUM2ND) dan <b>NOMINAL</b>/<b>TAGIHAN</b>.</li>
                </ul>
                <form method="POST" action="{{ route('keu.tagihan.upload_pmb.import') }}" enctype="multipart/form-data" id="teImportForm">
                    @csrf
                    <input type="hidden" name="thn_akademik" id="te-in-thn">
                    <input type="hidden" name="tagihan" id="te-in-tagihan">
                    <input type="hidden" name="periode" id="te-in-periode">
                    <input type="hidden" name="kode_akun" id="te-in-kode">
                    <input type="hidden" name="raw_rows" id="te-raw-rows">
                    <div class="eid-file">
                        <input type="file" name="te_file_hint" id="teFileInput" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
                    </div>
                    <div class="eid-actions">
                        <button type="button" class="eid-btn eid-btn-cancel" id="teCancelImport">Batal</button>
                        <button type="submit" class="eid-btn eid-btn-submit">Import Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        (function () {
            const fungsiUrl = @json(route('keu.tagihan.fungsi'));
            const selThn = document.getElementById('filter-thn-akademik');
            const selTag = document.getElementById('filter-tagihan');
            const selPost = document.getElementById('filter-kode-akun');
            const inpPeriode = document.getElementById('filter-periode');
            const btnOpen = document.getElementById('teOpenImport');
            const modal = document.getElementById('teImportModal');
            const closeBtn = document.getElementById('teCloseImport');
            const cancelBtn = document.getElementById('teCancelImport');
            const fileInput = document.getElementById('teFileInput');
            const rawRowsInput = document.getElementById('te-raw-rows');
            const form = document.getElementById('teImportForm');
            const inThn = document.getElementById('te-in-thn');
            const inTag = document.getElementById('te-in-tagihan');
            const inPer = document.getElementById('te-in-periode');
            const inKode = document.getElementById('te-in-kode');

            function parseMoney(v) { const s = String(v ?? '').replace(/Rp/gi, '').replace(/\./g, '').replace(/,/g, '').trim(); const n = parseInt(s.replace(/\D+/g, ''), 10); return isNaN(n) ? 0 : n; }
            function cellText(v) { if (v === null || v === undefined || v === '') return ''; if (typeof v === 'number' && Number.isFinite(v)) return String(Math.trunc(v)); return String(v).trim(); }
            function cellCustId(v) { const s = String(v ?? '').replace(/\D+/g, ''); const n = parseInt(s, 10); return isNaN(n) || n <= 0 ? 0 : n; }
            function normalizeHeader(r) { const o = {}; Object.keys(r || {}).forEach(k => { o[String(k).trim().replace(/\s+/g, ' ').toUpperCase()] = r[k]; }); return o; }

            async function refreshPeriode() {
                const thn = selThn && selThn.value ? selThn.value.trim() : '';
                const tag = selTag && selTag.value ? selTag.value.trim() : '';
                if (!thn || !inpPeriode) return;
                if (!tag) { inpPeriode.value = ''; return; }
                try {
                    const res = await fetch(fungsiUrl + '?thn_akademik=' + encodeURIComponent(thn) + '&tagihan=' + encodeURIComponent(tag), { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    inpPeriode.value = (data.periode || data.fungsi || '').toString();
                } catch (e) { inpPeriode.value = ''; }
            }

            if (selThn) selThn.addEventListener('change', refreshPeriode);
            if (selTag) selTag.addEventListener('change', refreshPeriode);

            function filtersOk() { return selThn && selThn.value && selTag && selTag.value && selPost && selPost.value && inpPeriode && inpPeriode.value.trim() !== ''; }
            if (btnOpen) btnOpen.addEventListener('click', function () { if (!filtersOk()) { alert('Lengkapi Tahun Pelajaran, Tagihan, Post, dan Periode.'); return; } modal.classList.add('open'); });
            function closeModal() { modal.classList.remove('open'); }
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
            if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

            if (form) form.addEventListener('submit', function (e) {
                if (!filtersOk()) { e.preventDefault(); alert('Filter belum lengkap atau periode kosong.'); return; }
                inThn.value = selThn.value; inTag.value = selTag.value; inPer.value = inpPeriode.value.trim(); inKode.value = selPost.value;
                if (!rawRowsInput.value || rawRowsInput.value === '[]') { e.preventDefault(); alert('Pilih file excel terlebih dahulu.'); }
            });

            if (fileInput) fileInput.addEventListener('change', function (event) {
                const file = event.target.files && event.target.files[0];
                if (!file) { rawRowsInput.value = '[]'; return; }
                if (file.size > 1024 * 1024) { alert('Ukuran file maksimal 1 MB.'); rawRowsInput.value = '[]'; fileInput.value = ''; return; }
                const reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        const wb = XLSX.read(e.target.result, { type: 'binary' });
                        const ws = wb.Sheets[wb.SheetNames[0]];
                        const rows = XLSX.utils.sheet_to_json(ws, { defval: '' });
                        const normalized = rows.map(function (r) {
                            const rx = normalizeHeader(r);
                            const nop = cellText(rx['NO PENDAFTARAN'] ?? rx['NO_DAFTARAN'] ?? rx['NO DAFT'] ?? rx.NODAF ?? rx.NUM2ND ?? rx.NIS ?? '');
                            const nominal = parseMoney(rx.NOMINAL ?? rx.TAGIHAN ?? rx.JUMLAH ?? 0);
                            const custid = cellCustId(rx.IDCUST ?? rx.CUSTID ?? 0);
                            const row = { no_pendaftaran: nop, nominal: nominal };
                            if (custid > 0) row.custid = custid;
                            return row;
                        }).filter(r => r.no_pendaftaran !== '' || (r.custid && r.custid > 0));
                        rawRowsInput.value = JSON.stringify(normalized);
                    } catch (err) { rawRowsInput.value = '[]'; alert('Gagal membaca file.'); }
                };
                reader.readAsBinaryString(file);
            });

            window.addEventListener('load', function () { if (selThn && selThn.value && selTag && selTag.value) refreshPeriode(); });
        })();
    </script>
@endsection

