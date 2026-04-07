@extends('layouts.app')

@section('content')
    <style>
        .dt-wrap { margin-top: 16px; }
        .dt-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .dt-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 8px; }
        .dt-sub { font-size: 13px; color: #6b7280; padding: 0 16px 14px; }
        .dt-filter {
            padding: 0 16px 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            border-bottom: 1px solid #eef2f7;
        }
        @media (max-width: 960px) { .dt-filter { grid-template-columns: 1fr; } }
        .dt-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .dt-fld input, .dt-fld select {
            width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px;
        }
        .dt-actions {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; align-items: center;
        }
        .dt-actions--filter { flex-wrap: wrap; }
        .dt-actions--primary {
            padding: 12px 16px;
            border-bottom: 1px solid #eef2f7;
            align-items: center;
            gap: 10px;
        }
        .dt-btn-emphasis {
            font-weight: 800;
            border-color: #6366f1;
            color: #312e81;
            background: #eef2ff;
        }
        .dt-btn-emphasis:hover { background: #e0e7ff; }
        .dt-btn {
            height: 38px; padding: 0 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; border: 1px solid #d1d5db;
            background: #fff; color: #374151; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .dt-btn-search { background: #6366f1; border-color: #6366f1; color: #fff; }
        .dt-btn-kartu { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .dt-btn-rekap { background: #ea580c; border-color: #ea580c; color: #fff; }
        .dt-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .dt-select, .dt-input { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .dt-table-wrap { overflow-x: auto; }
        .dt-table { width: 100%; min-width: 1400px; border-collapse: collapse; font-size: 12px; }
        .dt-table th, .dt-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .dt-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .dt-num { text-align: right; }
        .dt-urut-actions { display: flex; flex-direction: column; gap: 4px; }
        .dt-urut-actions button {
            font-size: 11px; padding: 4px 6px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; cursor: pointer; font-weight: 600;
        }
        .dt-urut-actions button:hover { background: #e2e8f0; }
        .dt-bill-act { font-size: 11px; padding: 5px 8px; border-radius: 6px; border: 1px solid #94a3b8; background: #f1f5f9; cursor: pointer; font-weight: 600; }
        .dt-bill-act:hover { background: #e2e8f0; }
        .dt-bill-act:disabled { opacity: 0.5; cursor: not-allowed; }
        .dt-hapus { background: #fef2f2 !important; border-color: #fecaca !important; color: #b91c1c; }
        .dt-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280; }
        .dt-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .dt-page.active { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .dt-page.disabled { pointer-events: none; opacity: 0.45; }
        .dt-alert { margin: 10px 16px 0; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; }
        .dt-err { background: #fef2f2; color: #b91c1c; }
        .dt-paid { color: #6b7280; font-size: 11px; }
        .dt-check { width: 36px; text-align: center; }
        .dt-check input { width: 16px; height: 16px; cursor: pointer; vertical-align: middle; }
        .dt-export-slot { display: flex; align-items: center; justify-content: flex-end; }
        .dt-exp { position: relative; }
        .dt-exp-btn {
            height: 36px; padding: 0 18px 0 16px; border-radius: 8px; border: 1px solid #0e7490;
            background: linear-gradient(180deg, #22d3ee 0%, #06b6d4 55%, #0891b2 100%);
            color: #fff; font-weight: 800; font-size: 13px; cursor: pointer;
            display: inline-flex; align-items: center; gap: 10px;
            box-shadow: 0 2px 8px rgba(8, 145, 178, 0.35);
        }
        .dt-exp-btn:hover { filter: brightness(1.06); }
        .dt-exp-btn .dt-exp-chev { font-size: 10px; opacity: 0.95; margin-top: 1px; }
        .dt-exp-panel {
            position: absolute; right: 0; top: calc(100% + 6px); min-width: 168px;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14); z-index: 50; overflow: hidden;
        }
        .dt-exp-panel[hidden] { display: none !important; }
        .dt-exp-item {
            display: flex; width: 100%; align-items: center; gap: 10px;
            padding: 11px 16px; border: 0; background: #fff; font-size: 13px; font-weight: 700;
            color: #334155; cursor: pointer; text-align: left;
        }
        .dt-exp-item:hover { background: #f0fdfa; color: #0f766e; }
        .dt-exp-item + .dt-exp-item { border-top: 1px solid #f1f5f9; }
        .dt-sr-only {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }
    </style>

    <div class="page-heading">
        <h2>Data Tagihan Siswa</h2>
        <p>Filter tagihan, lihat NIS &amp; virtual account, ubah urutan (<strong>furutan</strong> di <strong>scctbill</strong>).</p>
    </div>

    <div class="dt-wrap">
        <div class="dt-card">
            <div class="dt-title">Data Tagihan</div>
            <div class="dt-sub"></div>

            @if (($errorMsg ?? '') !== '')
                <div class="dt-alert dt-err">{{ $errorMsg }}</div>
            @endif
            @if (session('export_error'))
                <div class="dt-alert dt-err">{{ session('export_error') }}</div>
            @endif

            @php
                $dtPrintQs = http_build_query([
                    'tgl_dari' => $filters['tgl_dari'] ?? '',
                    'tgl_sampai' => $filters['tgl_sampai'] ?? '',
                    'thn_angkatan' => $filters['thn_angkatan'] ?? '',
                    'thn_akademik' => $filters['thn_akademik'] ?? '',
                    'kelas_id' => $filters['kelas_id'] ?? '',
                    'nama_tagihan' => $filters['nama_tagihan'] ?? '',
                    'siswa' => $filters['siswa'] ?? '',
                ]);
                $dtPrintUrl = route('keu.tagihan.data_print') . ($dtPrintQs !== '' ? '?' . $dtPrintQs : '');
            @endphp

            <form method="GET" action="{{ route('keu.tagihan.data') }}">
                <div class="dt-filter">
                    <div class="dt-fld">
                        <label>Tanggal pembuatan (dari)</label>
                        <input type="date" name="tgl_dari" value="{{ $filters['tgl_dari'] ?? '' }}">
                    </div>
                    <div class="dt-fld">
                        <label>Tanggal pembuatan (sampai)</label>
                        <input type="date" name="tgl_sampai" value="{{ $filters['tgl_sampai'] ?? '' }}">
                    </div>
                    <div class="dt-fld">
                        <label>Angkatan siswa</label>
                        <select name="thn_angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="dt-fld">
                        <label>Tahun akademik</label>
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
                    <div class="dt-fld">
                        <label>Kelas</label>
                        <select name="kelas_id">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['kelas'] ?? []) as $k)
                                @php $id = (string) ($k['id'] ?? ''); $lbl = trim((string) (($k['unit'] ?? '') . ' ' . ($k['kelas'] ?? ''))); @endphp
                                @if ($id !== '')
                                    <option value="{{ $id }}" {{ (($filters['kelas_id'] ?? '') === $id) ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="dt-fld">
                        <label>Nama tagihan</label>
                        <select name="nama_tagihan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['tagihan'] ?? []) as $tag)
                                <option value="{{ $tag }}" {{ (($filters['nama_tagihan'] ?? '') === $tag) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="dt-fld" style="grid-column: 1 / -1;">
                        <label>Siswa (NIS / nama)</label>
                        <input type="text" name="siswa" value="{{ $filters['siswa'] ?? '' }}" placeholder="Masukkan NIS / nama">
                    </div>
                </div>
                <div class="dt-actions dt-actions--filter">
                    <button type="submit" class="dt-btn dt-btn-search">Cari</button>
                    <a class="dt-btn" href="{{ route('keu.tagihan.data') }}">Reset</a>
                    <button type="button" class="dt-btn dt-btn-kartu" id="dtBtnKartu">Cetak Kartu Siswa</button>
                    <button type="button" class="dt-btn dt-btn-rekap" id="dtBtnRekap">Cetak Rekap</button>
                </div>
            </form>

            <div class="dt-actions dt-actions--primary">
                <a class="dt-btn dt-btn-emphasis" href="{{ route('keu.tagihan.buat') }}">+ Buat Tagihan</a>
                <a class="dt-btn dt-btn-emphasis" href="{{ route('keu.tagihan.upload_excel') }}">Upload Excel</a>
            </div>

            <div class="dt-toolbar">
                <form method="GET" action="{{ route('keu.tagihan.data') }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    @foreach ($filters as $fk => $fv)
                        @if ($fv !== '' && $fk !== 'per_page')
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <span>Tampilkan</span>
                    <select class="dt-select" name="per_page" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" {{ ($tagihanRows->perPage() ?? 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </form>
                <div class="dt-export-slot">
                    <div class="dt-exp" id="dtExpRoot">
                        <button type="button" class="dt-exp-btn" id="dtExpBtn" aria-expanded="false" aria-haspopup="true">
                            <span>Export</span>
                            <span class="dt-exp-chev" aria-hidden="true">▼</span>
                        </button>
                        <div class="dt-exp-panel" id="dtExpPanel" role="menu" hidden>
                            <button type="button" class="dt-exp-item" id="dtExpExcel" role="menuitem">Excel</button>
                            <button type="button" class="dt-exp-item" id="dtExpPdf" role="menuitem">PDF</button>
                            <button type="button" class="dt-exp-item" id="dtExpPrint" role="menuitem" data-print-url="{{ $dtPrintUrl }}">Print</button>
                        </div>
                    </div>
                </div>
            </div>

            <form id="dtFormExcel" class="dt-sr-only" method="POST" action="{{ route('keu.tagihan.data_export_excel') }}" aria-hidden="true">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'siswa'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
            </form>
            <form id="dtFormPdf" class="dt-sr-only" method="POST" action="{{ route('keu.tagihan.data_export_pdf') }}" aria-hidden="true">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'siswa'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
            </form>
            <form id="dtFormKartu" class="dt-sr-only" method="POST" action="{{ route('keu.tagihan.data_print_kartu') }}" aria-hidden="true">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'siswa'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
                <input type="hidden" name="selected_rows" id="dtSelectedRows" value="">
            </form>
            <form id="dtFormRekap" class="dt-sr-only" method="POST" action="{{ route('keu.tagihan.data_print_rekap') }}" aria-hidden="true">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'siswa'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
            </form>

            <div class="dt-table-wrap">
                <table class="dt-table">
                    <thead>
                        <tr>
                            <th class="dt-check" title="Pilih baris di halaman ini — untuk fitur lain (bukan ekspor)">
                                <input type="checkbox" id="dtSelectPage" aria-label="Pilih semua di halaman">
                            </th>
                            <th>No</th>
                            <th>NIS</th>
                            <th>NO DAFT</th>
                            <th>NO VA</th>
                            <th>NAMA</th>
                            <th>Unit</th>
                            <th>Kelas</th>
                            <th>Kelompok</th>
                            <th>Nama Tagihan</th>
                            <th class="dt-num">Tagihan</th>
                            <th>Tahun AKA</th>
                            <th class="dt-num">Urutan</th>
                            <th>Naik</th>
                            <th>Turun</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagihanRows as $index => $row)
                            @php
                                $r = is_array($row) ? $row : (array) $row;
                                $custid = (int) ($r['custid'] ?? 0);
                                $billcd = (string) ($r['billcd'] ?? '');
                                $furutan = (int) ($r['furutan'] ?? 0);
                                $paidRaw = $r['paidst'] ?? '0';
                                $isLunas = $paidRaw === '1' || $paidRaw === 1 || $paidRaw === true;
                                $rowNo = ($tagihanRows->firstItem() ?? 0) + $loop->index;
                            @endphp
                            <tr>
                                <td class="dt-check">
                                    <input type="checkbox" class="dt-row-sel" data-custid="{{ $custid }}" aria-label="Pilih baris">
                                </td>
                                <td>{{ $rowNo }}</td>
                                <td>{{ $r['nis'] ?? '-' }}</td>
                                <td>{{ $r['no_daftar'] ?? '-' }}</td>
                                <td>{{ $r['no_va'] ?? '-' }}</td>
                                <td>{{ $r['nama'] ?? '-' }}</td>
                                <td>{{ $r['unit'] ?? '-' }}</td>
                                <td>{{ $r['kelas'] ?? '-' }}</td>
                                <td>{{ $r['kelompok'] ?? '-' }}</td>
                                <td>{{ $r['nama_tagihan'] ?? '-' }}</td>
                                <td class="dt-num">Rp {{ number_format((int) ($r['tagihan'] ?? 0), 0, ',', '.') }}</td>
                                <td>{{ $r['tahun_aka'] ?? '-' }}</td>
                                <td class="dt-num"><strong>{{ $furutan }}</strong></td>
                                <td>
                                    @if ($custid > 0 && $billcd !== '')
                                        <button type="button" class="dt-bill-act" data-act="up" data-custid="{{ $custid }}" data-billcd="{{ e($billcd) }}">↑ Naikkan</button>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($custid > 0 && $billcd !== '')
                                        <button type="button" class="dt-bill-act" data-act="down" data-custid="{{ $custid }}" data-billcd="{{ e($billcd) }}">↓ Turunkan</button>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($custid > 0 && $billcd !== '' && !$isLunas)
                                        <button type="button" class="dt-bill-del dt-hapus" data-custid="{{ $custid }}" data-billcd="{{ $billcd }}">Hapus</button>
                                    @elseif ($isLunas)
                                        <span class="dt-paid">Lunas</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="dt-footer">
                <div>
                    Menampilkan {{ $tagihanRows->firstItem() ?? 0 }}–{{ $tagihanRows->lastItem() ?? 0 }} dari {{ $tagihanRows->total() ?? 0 }}
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    @if ($tagihanRows->onFirstPage())
                        <span class="dt-page disabled">Sebelumnya</span>
                    @else
                        <a class="dt-page" href="{{ $tagihanRows->appends(request()->query())->previousPageUrl() }}">Sebelumnya</a>
                    @endif
                    @php $cur = $tagihanRows->currentPage(); $last = $tagihanRows->lastPage(); @endphp
                    <span class="dt-page active">{{ $cur }}</span>
                    @if ($tagihanRows->hasMorePages())
                        <a class="dt-page" href="{{ $tagihanRows->appends(request()->query())->nextPageUrl() }}">Selanjutnya</a>
                    @else
                        <span class="dt-page disabled">Selanjutnya</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const csrf = @json(csrf_token());
            const urlUrutan = @json(route('keu.tagihan.data_urutan'));
            const urlHapus = @json(route('keu.tagihan.data_hapus'));

            const selPage = document.getElementById('dtSelectPage');
            if (selPage) {
                selPage.addEventListener('change', function () {
                    const on = selPage.checked;
                    document.querySelectorAll('.dt-row-sel').forEach(function (c) { c.checked = on; });
                });
            }

            (function exportDropdown() {
                const root = document.getElementById('dtExpRoot');
                const btn = document.getElementById('dtExpBtn');
                const panel = document.getElementById('dtExpPanel');
                const formX = document.getElementById('dtFormExcel');
                const formP = document.getElementById('dtFormPdf');
                const itemX = document.getElementById('dtExpExcel');
                const itemP = document.getElementById('dtExpPdf');
                const itemPr = document.getElementById('dtExpPrint');
                if (!root || !btn || !panel) return;

                function close() {
                    panel.hidden = true;
                    btn.setAttribute('aria-expanded', 'false');
                }

                function open() {
                    panel.hidden = false;
                    btn.setAttribute('aria-expanded', 'true');
                }

                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (panel.hidden) open(); else close();
                });

                document.addEventListener('click', function () {
                    close();
                });

                root.addEventListener('click', function (e) {
                    e.stopPropagation();
                });

                if (itemX && formX) {
                    itemX.addEventListener('click', function (e) {
                        e.stopPropagation();
                        close();
                        formX.submit();
                    });
                }
                if (itemP && formP) {
                    itemP.addEventListener('click', function (e) {
                        e.stopPropagation();
                        close();
                        formP.submit();
                    });
                }
                if (itemPr) {
                    itemPr.addEventListener('click', function (e) {
                        e.stopPropagation();
                        close();
                        var u = itemPr.getAttribute('data-print-url');
                        if (u) window.open(u, '_blank', 'noopener,noreferrer');
                    });
                }
            })();

            (function printActions() {
                const btnKartu = document.getElementById('dtBtnKartu');
                const btnRekap = document.getElementById('dtBtnRekap');
                const formKartu = document.getElementById('dtFormKartu');
                const formRekap = document.getElementById('dtFormRekap');
                const selectedRowsInput = document.getElementById('dtSelectedRows');
                const inpThnAka = document.querySelector('select[name="thn_akademik"]');
                const inpKelas = document.querySelector('select[name="kelas_id"]');

                function selectedCustIds() {
                    const ids = {};
                    document.querySelectorAll('.dt-row-sel:checked').forEach(function (cb) {
                        const n = parseInt(cb.getAttribute('data-custid') || '0', 10);
                        if (n > 0) ids[n] = true;
                    });
                    return Object.keys(ids).map(function (k) { return parseInt(k, 10); });
                }

                if (btnKartu && formKartu && selectedRowsInput) {
                    btnKartu.addEventListener('click', function () {
                        const ids = selectedCustIds();
                        if (ids.length === 0) {
                            alert('Pilih minimal 1 siswa dari centang kiri tabel.');
                            return;
                        }
                        selectedRowsInput.value = JSON.stringify(ids);
                        formKartu.submit();
                    });
                }

                if (btnRekap && formRekap) {
                    btnRekap.addEventListener('click', function () {
                        const thn = (inpThnAka && inpThnAka.value || '').trim();
                        const kelas = (inpKelas && inpKelas.value || '').trim();
                        if (!thn || !kelas) {
                            alert('Cetak Rekap wajib pilih Tahun Akademik dan Kelas.');
                            return;
                        }
                        formRekap.submit();
                    });
                }
            })();

            function postJson(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(body),
                    credentials: 'same-origin'
                }).then(function (r) { return r.json(); });
            }

            document.querySelectorAll('.dt-bill-act').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const custid = parseInt(btn.getAttribute('data-custid'), 10);
                    const billcd = btn.getAttribute('data-billcd') || '';
                    const direction = btn.getAttribute('data-act') === 'up' ? 'up' : 'down';
                    if (!custid || !billcd) return;
                    btn.disabled = true;
                    postJson(urlUrutan, { custid: custid, billcd: billcd, direction: direction })
                        .then(function (res) {
                            if (res && res.ok) {
                                window.location.reload();
                                return;
                            }
                            var msg = (res && (res.message || (res.errors && JSON.stringify(res.errors)))) || 'Gagal ubah urutan';
                            alert(msg);
                            btn.disabled = false;
                        })
                        .catch(function () {
                            alert('Koneksi gagal');
                            btn.disabled = false;
                        });
                });
            });

            document.querySelectorAll('.dt-bill-del').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm('Hapus tagihan ini?')) return;
                    const custid = parseInt(btn.getAttribute('data-custid'), 10);
                    const billcd = btn.getAttribute('data-billcd') || '';
                    btn.disabled = true;
                    postJson(urlHapus, { custid: custid, billcd: billcd })
                        .then(function (res) {
                            if (res && res.ok) {
                                window.location.reload();
                                return;
                            }
                            var msg = (res && res.message) || 'Gagal hapus';
                            alert(msg);
                            btn.disabled = false;
                        })
                        .catch(function () {
                            alert('Koneksi gagal');
                            btn.disabled = false;
                        });
                });
            });
        })();
    </script>
@endsection
