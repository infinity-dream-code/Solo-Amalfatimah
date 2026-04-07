@extends('layouts.app')

@section('content')
    <style>
        .sa-wrap { margin-top: 16px; }
        .sa-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 8px 20px rgba(15,23,42,.05); overflow:hidden; }
        .sa-head { padding:14px 16px; border-bottom:1px solid #eef2f7; display:flex; justify-content:space-between; align-items:center; }
        .sa-title { font-size:24px; font-weight:800; color:#111827; }
        .sa-btn-import { border:0; background:#22c55e; color:#fff; height:36px; padding:0 14px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
        .sa-toolbar { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:12px 16px; flex-wrap:wrap; border-bottom:1px solid #eef2f7; }
        .sa-left,.sa-right { display:flex; align-items:center; gap:8px; color:#4b5563; font-size:13px; }
        .sa-select,.sa-input { height:34px; border:1px solid #d1d5db; border-radius:8px; padding:0 10px; font-size:12px; }
        .sa-input { width:180px; }
        .sa-btn-tool { border:1px solid #d1d5db; background:#fff; color:#374151; height:34px; padding:0 12px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; }
        .sa-table-wrap { overflow-x:auto; }
        .sa-table { width:100%; min-width:1100px; border-collapse:collapse; font-size:13px; }
        .sa-table th,.sa-table td { border-bottom:1px solid #eef2f7; padding:9px 10px; white-space:nowrap; text-align:left; }
        .sa-table th { background:#fafbfd; color:#4b5563; font-size:12px; font-weight:700; }
        .sa-empty { text-align:center; color:#6b7280; padding:18px; }
        .sa-footer { padding:12px 16px 16px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
        .sa-info { color:#6b7280; font-size:12px; }
        .sa-pagination { display:flex; align-items:center; gap:6px; }
        .sa-page { min-width:30px; height:30px; border:1px solid #d1d5db; border-radius:999px; padding:0 10px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; color:#4b5563; font-size:12px; font-weight:700; background:#fff; }
        .sa-page.active { background:#4f6ef7; color:#fff; border-color:#4f6ef7; }
        .sa-page.disabled { pointer-events:none; color:#9ca3af; border-color:#e5e7eb; background:#f9fafb; }
        .sa-alert { margin:10px 16px 0; border-radius:8px; padding:10px 12px; font-size:13px; font-weight:600; }
        .sa-alert.ok { background:#ecfdf5; color:#047857; }
        .sa-alert.err { background:#fef2f2; color:#b91c1c; }
        .sa-modal { position:fixed; inset:0; background:rgba(17,24,39,.35); display:none; align-items:center; justify-content:center; z-index:80; padding:14px; }
        .sa-modal.open { display:flex; }
        .sa-modal-box { width:100%; max-width:540px; background:#fff; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 20px 50px rgba(0,0,0,.2); }
        .sa-modal-h { padding:14px 18px 8px; display:flex; justify-content:space-between; align-items:center; }
        .sa-modal-h h3 { margin:0; font-size:28px; font-weight:700; color:#111827; }
        .sa-close { border:0; background:transparent; font-size:18px; color:#6b7280; cursor:pointer; }
        .sa-modal-b { padding:0 18px 16px; }
        .sa-rules { margin:0 0 12px; padding-left:18px; color:#374151; font-size:13px; }
        .sa-rules li { margin:8px 0; }
        .sa-file { border:1px dashed #9ca3af; border-radius:10px; padding:18px; text-align:center; margin-bottom:14px; }
        .sa-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .sa-btn { height:38px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; }
        .sa-btn-cancel { background:#fff; border:1px solid #d1d5db; color:#4b5563; }
        .sa-btn-submit { background:#4f6ef7; border:1px solid #4f6ef7; color:#fff; }
    </style>

    <div class="page-heading">
        <h2>Setting Atribut Siswa</h2>
        <p>Perbarui atribut siswa dari file import.</p>
    </div>

    <div class="sa-wrap">
        <div class="sa-card">
            <div class="sa-head">
                <div class="sa-title">Setting Atribut Siswa</div>
                <button type="button" class="sa-btn-import" id="saOpenImport">Import Atribut Siswa</button>
            </div>

            @if (session('status'))<div class="sa-alert ok">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="sa-alert err">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="sa-alert err">{{ $errors->first() }}</div>@endif

            <div class="sa-toolbar">
                <form method="GET" action="{{ route('master.setting_atribut_siswa') }}" class="sa-left">
                    <span>Tampilkan</span>
                    <select class="sa-select" name="per_page" onchange="this.form.submit()">
                        <option value="10" {{ (int) ($perPage ?? 10) === 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ (int) ($perPage ?? 10) === 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ (int) ($perPage ?? 10) === 50 ? 'selected' : '' }}>50</option>
                    </select>
                    @if (($keyword ?? '') !== '')<input type="hidden" name="q" value="{{ $keyword }}">@endif
                    <span>entri</span>
                </form>
                <form method="GET" action="{{ route('master.setting_atribut_siswa') }}" class="sa-right">
                    <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 10) }}">
                    <span>Cari:</span>
                    <input type="text" class="sa-input" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
                    <button class="sa-btn-tool" type="submit">Cari</button>
                </form>
            </div>

            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>NO</th><th>NIS</th><th>NAMA</th><th>JENIS KELAMIN</th><th>ALAMAT</th><th>AYAH</th><th>IBU</th><th>KONTAK</th><th>WISMA</th><th>EKSINT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($rows ?? []) as $index => $row)
                            <tr>
                                <td>{{ ($rows->firstItem() ?? 1) + $index }}</td>
                                <td>{{ $row['nis'] ?? $row['nocust'] ?? '-' }}</td>
                                <td>{{ $row['nama'] ?? $row['nmcust'] ?? '-' }}</td>
                                <td>{{ $row['gender'] ?? $row['code04'] ?? '-' }}</td>
                                <td>{{ $row['alamat'] ?? $row['desc05'] ?? '-' }}</td>
                                <td>{{ $row['ayah'] ?? $row['genus'] ?? '-' }}</td>
                                <td>{{ $row['ibu'] ?? $row['genus1'] ?? '-' }}</td>
                                <td>{{ $row['kontak'] ?? $row['kontak_wali'] ?? $row['genuscontact'] ?? '-' }}</td>
                                <td>{{ $row['wisma'] ?? $row['getwisma'] ?? '-' }}</td>
                                <td>{{ $row['eksint'] ?? $row['eksternalinternal'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="sa-empty">Tidak ada data yang tersedia pada tabel ini</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sa-footer">
                <div class="sa-info">Menampilkan {{ $rows->firstItem() ?? 0 }} sampai {{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() ?? 0 }} entri</div>
                <div class="sa-pagination">
                    @php $current = $rows->currentPage(); $last = $rows->lastPage(); @endphp
                    @if ($rows->onFirstPage())<span class="sa-page disabled">Sebelumnya</span>@else<a class="sa-page" href="{{ $rows->appends(request()->query())->url($current - 1) }}">Sebelumnya</a>@endif
                    @for ($p = max(1, $current - 1); $p <= min($last, $current + 1); $p++)
                        @if ($p === $current)<span class="sa-page active">{{ $p }}</span>@else<a class="sa-page" href="{{ $rows->appends(request()->query())->url($p) }}">{{ $p }}</a>@endif
                    @endfor
                    @if ($rows->hasMorePages())<a class="sa-page" href="{{ $rows->appends(request()->query())->url($current + 1) }}">Selanjutnya</a>@else<span class="sa-page disabled">Selanjutnya</span>@endif
                </div>
            </div>
            <div style="padding:0 16px 16px;display:flex;justify-content:flex-end;gap:10px;">
                <form method="POST" action="{{ route('master.setting_atribut_siswa.clear') }}">@csrf<button type="submit" class="sa-btn sa-btn-cancel" style="min-width:120px;">Bersihkan Data</button></form>
                <form method="POST" action="{{ route('master.setting_atribut_siswa.save') }}">@csrf<button type="submit" class="sa-btn sa-btn-submit" style="min-width:120px;" {{ (($rows->total() ?? 0) > 0) ? '' : 'disabled' }}>Simpan Data</button></form>
            </div>
        </div>
    </div>

    <div class="sa-modal" id="saImportModal" aria-hidden="true">
        <div class="sa-modal-box">
            <div class="sa-modal-h"><h3>Import Atribut Siswa</h3><button type="button" class="sa-close" id="saCloseImport">×</button></div>
            <div class="sa-modal-b">
                <ul class="sa-rules">
                    <li>File harus berformat <b>XLS/XLSX/CSV</b>.</li>
                    <li>Ukuran file tidak boleh lebih dari <b>1024KB / 1MB</b>.</li>
                    <li>Kolom wajib: <b>NIS</b>.</li>
                    <li>Contoh file import: <a href="{{ asset('atribut.xlsx') }}" target="_blank" rel="noopener">atribut.xlsx</a></li>
                </ul>
                <form method="POST" action="{{ route('master.setting_atribut_siswa.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="sa-file">
                        <input type="hidden" name="preview_rows" id="saPreviewRows">
                        <input type="file" name="file" id="saFileInput" accept=".xlsx,.xls,.csv,text/csv" required>
                    </div>
                    <div class="sa-actions">
                        <button type="button" class="sa-btn sa-btn-cancel" id="saCancelImport">Batal</button>
                        <button type="submit" class="sa-btn sa-btn-submit">Import Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        (function () {
            const modal = document.getElementById('saImportModal');
            const openBtn = document.getElementById('saOpenImport');
            const closeBtn = document.getElementById('saCloseImport');
            const cancelBtn = document.getElementById('saCancelImport');
            const fileInput = document.getElementById('saFileInput');
            const previewInput = document.getElementById('saPreviewRows');
            if (!modal || !openBtn || !closeBtn || !cancelBtn || !fileInput || !previewInput) return;

            const open = () => { modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); };
            const close = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); };
            openBtn.addEventListener('click', open);
            closeBtn.addEventListener('click', close);
            cancelBtn.addEventListener('click', close);
            modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

            fileInput.addEventListener('change', function (event) {
                const file = event.target.files && event.target.files[0];
                if (!file || typeof XLSX === 'undefined') { previewInput.value = '[]'; return; }
                const reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        const wb = XLSX.read(e.target.result, { type: 'binary' });
                        const ws = wb.Sheets[wb.SheetNames[0]];
                        const list = XLSX.utils.sheet_to_json(ws, { defval: '' }).map(function (r) {
                            return {
                                nis: String(r.NIS || '').trim(),
                                nama: String(r.NAMA || r.Nama || '').trim(),
                                gender: String(r['JENIS KELAMIN'] || r.GENDER || '').trim(),
                                ayah: String(r.AYAH || '').trim(),
                                ibu: String(r.IBU || '').trim(),
                                kontak: String(r.KONTAK || r.KontakWali || r.KONTAKWALI || '').trim(),
                                eksint: String(r.EKSINT || '').trim(),
                                wisma: String(r.WISMA || '').trim(),
                                alamat: String(r.ALAMAT || '').trim(),
                            };
                        });
                        previewInput.value = JSON.stringify(list);
                    } catch (err) { previewInput.value = '[]'; }
                };
                reader.readAsBinaryString(file);
            });
        })();
    </script>
@endsection

