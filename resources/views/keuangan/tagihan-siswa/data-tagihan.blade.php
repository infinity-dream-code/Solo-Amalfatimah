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
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px 16px;
            border-bottom: 1px solid #eef2f7;
        }
        @media (max-width: 1100px) { .dt-filter { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 720px) { .dt-filter { grid-template-columns: 1fr; } }
        .dt-filter-col--actions { display: flex; flex-direction: column; gap: 8px; }
        .dt-sel-readonly {
            width: 100%; height: 34px; border: 1px solid #d1d5db; border-radius: 6px;
            padding: 0 8px; font-size: 12px; background: #f9fafb; color: #374151;
        }
        .dt-sel-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .dt-sel-btns .dt-btn { justify-content: center; height: 34px; font-size: 12px; }
        .dt-btn-naik, .dt-btn-turun { background: #f8fafc; }
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
        .dt-table { width: 100%; min-width: 980px; border-collapse: collapse; font-size: 12px; }
        .dt-table th, .dt-table td { border: 1px solid #c4b5fd; padding: 7px 6px; text-align: left; vertical-align: middle; }
        .dt-table th { background: #7c3aed; color: #fff; font-weight: 700; white-space: nowrap; }
        .dt-table tbody tr { cursor: pointer; }
        .dt-table tbody tr:nth-child(even) td { background: #f5f3ff; }
        .dt-table tbody tr.dt-row-selected td { background: #ddd6fe !important; }
        .dt-table tbody tr.dt-row-sub td.dt-student { color: transparent; user-select: none; }
        .dt-sel-icon { width: 28px; text-align: center; color: #7c3aed; font-size: 14px; }
        .dt-center { text-align: center; }
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
        <p>Klik baris tagihan untuk memilih, lalu gunakan <strong>NAIK</strong> / <strong>TURUN</strong> / <strong>Hapus</strong>.</p>
    </div>

    <div class="dt-wrap">
        <div class="dt-card">
            <div class="dt-title">Data Tagihan</div>
            <div class="dt-sub">Data dimuat per halaman (pagination). Filter opsional — kosongkan lalu <strong>Cari</strong> untuk semua tagihan aktif.</div>

            @if (($errorMsg ?? '') !== '')
                <div class="dt-alert dt-err">{{ $errorMsg }}</div>
            @endif
            @if (session('export_error'))
                <div class="dt-alert dt-err">{{ session('export_error') }}</div>
            @endif

            @php
                $dtPrintQs = http_build_query(array_filter([
                    'tgl_dari' => $filters['tgl_dari'] ?? '',
                    'tgl_sampai' => $filters['tgl_sampai'] ?? '',
                    'thn_angkatan' => $filters['thn_angkatan'] ?? '',
                    'thn_akademik' => $filters['thn_akademik'] ?? '',
                    'kelas_id' => $filters['kelas_id'] ?? '',
                    'nama_tagihan' => $filters['nama_tagihan'] ?? '',
                    'nis' => $filters['nis'] ?? '',
                    'nama' => $filters['nama'] ?? '',
                    'siswa' => $filters['siswa'] ?? '',
                ], static fn ($v) => $v !== '' && $v !== null));
                $dtPrintUrl = route('keu.tagihan.data_print') . ($dtPrintQs !== '' ? '?' . $dtPrintQs : '');
            @endphp

            <form method="GET" action="{{ route('keu.tagihan.data') }}" id="dtFilterForm">
                <div class="dt-filter">
                    <div class="dt-filter-col">
                        <div class="dt-fld">
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
                        <div class="dt-fld">
                            <label>NIS</label>
                            <input type="text" name="nis" value="{{ $filters['nis'] ?? '' }}" placeholder="nis" autocomplete="off">
                        </div>
                        <div class="dt-fld">
                            <label>Nama</label>
                            <input type="text" name="nama" value="{{ $filters['nama'] ?? '' }}" placeholder="nama" autocomplete="off">
                        </div>
                        <div class="dt-fld">
                            <label>Nama Tagihan</label>
                            <select name="nama_tagihan">
                                <option value="">Semua</option>
                                @foreach (($filterOptions['tagihan'] ?? []) as $tag)
                                    <option value="{{ $tag }}" {{ (($filters['nama_tagihan'] ?? '') === $tag) ? 'selected' : '' }}>{{ $tag }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="dt-filter-col">
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
                            <label>Tahun Angkatan</label>
                            <select name="thn_angkatan">
                                <option value="">Semua</option>
                                @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                    <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="dt-filter-col dt-filter-col--actions">
                        <div class="dt-fld">
                            <label>Nama Tagihan (pilihan)</label>
                            <input type="text" id="dtSelNamaTagihan" class="dt-sel-readonly" readonly placeholder="—">
                        </div>
                        <div class="dt-fld">
                            <label>Urutan Tagihan</label>
                            <input type="text" id="dtSelUrutan" class="dt-sel-readonly" readonly placeholder="—">
                        </div>
                        <div class="dt-sel-btns">
                            <button type="button" class="dt-btn dt-btn-naik" id="dtBtnNaik" disabled>NAIK</button>
                            <button type="button" class="dt-btn dt-btn-turun" id="dtBtnTurun" disabled>TURUN</button>
                            <button type="submit" class="dt-btn dt-btn-search">Cari</button>
                            <button type="button" class="dt-btn dt-hapus" id="dtBtnHapusSel" disabled>Hapus</button>
                        </div>
                    </div>
                </div>
                <div class="dt-actions dt-actions--filter">
                    <a class="dt-btn" href="{{ route('keu.tagihan.data') }}">Reset</a>
                    <button type="button" class="dt-btn dt-btn-kartu" id="dtBtnKartu">Cetak Kartu Siswa</button>
                    <button type="button" class="dt-btn dt-btn-rekap" id="dtBtnRekap">Cetak Rekap</button>
                </div>
            </form>

            <div class="dt-actions dt-actions--primary">
                <a class="dt-btn dt-btn-emphasis" href="{{ route('keu.tagihan.buat') }}">+ Buat Tagihan</a>
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
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'nis', 'nama', 'siswa'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
            </form>
            <form id="dtFormPdf" class="dt-sr-only" method="POST" action="{{ route('keu.tagihan.data_export_pdf') }}" aria-hidden="true">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'nis', 'nama', 'siswa'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
            </form>
            <form id="dtFormKartu" class="dt-sr-only" method="POST" action="{{ route('keu.tagihan.data_print_kartu') }}" aria-hidden="true">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'nis', 'nama', 'siswa'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
                <input type="hidden" name="selected_rows" id="dtSelectedRows" value="">
            </form>
            <form id="dtFormRekap" class="dt-sr-only" method="POST" action="{{ route('keu.tagihan.data_print_rekap') }}" aria-hidden="true">
                @csrf
                @foreach (['tgl_dari', 'tgl_sampai', 'thn_angkatan', 'thn_akademik', 'kelas_id', 'nama_tagihan', 'nis', 'nama', 'siswa'] as $fk)
                    <input type="hidden" name="{{ $fk }}" value="{{ $filters[$fk] ?? '' }}">
                @endforeach
                <input type="hidden" name="has_search_context" value="1">
            </form>

            <div class="dt-table-wrap">
                <table class="dt-table" id="dtTable">
                    <thead>
                        <tr>
                            <th class="dt-sel-icon"></th>
                            <th>NIS</th>
                            <th>NO VA</th>
                            <th>Nama</th>
                            <th>Nama Tagihan</th>
                            <th class="dt-num">Jumlah</th>
                            <th class="dt-center">Urutan Bayar</th>
                            <th class="dt-center">Tgl Tagih</th>
                            <th>Tahun Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lastCustid = null; @endphp
                        @forelse ($tagihanRows as $index => $row)
                            @php
                                $r = is_array($row) ? $row : (array) $row;
                                $custid = (int) ($r['custid'] ?? 0);
                                $billcd = (string) ($r['billcd'] ?? '');
                                $furutan = (int) ($r['furutan'] ?? 0);
                                $maxFurutan = (int) ($r['max_furutan_cust'] ?? $furutan);
                                if ($maxFurutan < $furutan) {
                                    $maxFurutan = $furutan;
                                }
                                $aa = trim((string) ($r['aa'] ?? ''));
                                $paidRaw = $r['paidst'] ?? '0';
                                $isLunas = $paidRaw === '1' || $paidRaw === 1 || $paidRaw === true;
                                $showStudent = $lastCustid !== $custid;
                                $lastCustid = $custid;
                                $tglTagih = '-';
                                if (!empty($r['tgl_tagih'])) {
                                    try {
                                        $tglTagih = (new \DateTimeImmutable((string) $r['tgl_tagih']))->format('d/m/Y H:i:s');
                                    } catch (\Throwable) {
                                        $tglTagih = (string) $r['tgl_tagih'];
                                    }
                                }
                            @endphp
                            <tr class="dt-bill-row{{ $showStudent ? '' : ' dt-row-sub' }}"
                                data-custid="{{ $custid }}"
                                data-billcd="{{ e($billcd) }}"
                                data-aa="{{ e($aa) }}"
                                data-furutan="{{ $furutan }}"
                                data-max-furutan="{{ $maxFurutan }}"
                                data-nama-tagihan="{{ e($r['nama_tagihan'] ?? '') }}"
                                data-lunas="{{ $isLunas ? '1' : '0' }}">
                                <td class="dt-sel-icon" aria-hidden="true">📄</td>
                                <td class="dt-student">{{ $showStudent ? ($r['nis'] ?? '') : '' }}</td>
                                <td class="dt-student">{{ $showStudent ? ($r['no_va'] ?? '') : '' }}</td>
                                <td class="dt-student">{{ $showStudent ? ($r['nama'] ?? '') : '' }}</td>
                                <td>{{ $r['nama_tagihan'] ?? '-' }}</td>
                                <td class="dt-num">{{ number_format((int) ($r['tagihan'] ?? 0), 0, ',', '.') }}</td>
                                <td class="dt-center">{{ $furutan }}</td>
                                <td class="dt-center">{{ $tglTagih }}</td>
                                <td>{{ $r['tahun_aka'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data.</td>
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

            let selectedRow = null;
            const selNamaTagihan = document.getElementById('dtSelNamaTagihan');
            const selUrutan = document.getElementById('dtSelUrutan');
            const btnNaik = document.getElementById('dtBtnNaik');
            const btnTurun = document.getElementById('dtBtnTurun');
            const btnHapusSel = document.getElementById('dtBtnHapusSel');

            function updateSelButtons() {
                if (!selectedRow) {
                    if (selNamaTagihan) selNamaTagihan.value = '';
                    if (selUrutan) selUrutan.value = '';
                    if (btnNaik) btnNaik.disabled = true;
                    if (btnTurun) btnTurun.disabled = true;
                    if (btnHapusSel) btnHapusSel.disabled = true;
                    return;
                }
                const furutan = parseInt(selectedRow.getAttribute('data-furutan') || '0', 10);
                const maxF = parseInt(selectedRow.getAttribute('data-max-furutan') || String(furutan), 10);
                const aa = selectedRow.getAttribute('data-aa') || '';
                const custid = parseInt(selectedRow.getAttribute('data-custid') || '0', 10);
                const isLunas = selectedRow.getAttribute('data-lunas') === '1';
                if (selNamaTagihan) selNamaTagihan.value = selectedRow.getAttribute('data-nama-tagihan') || '';
                if (selUrutan) selUrutan.value = String(furutan);
                const canUrut = custid > 0 && aa !== '';
                if (btnNaik) btnNaik.disabled = !canUrut || furutan >= maxF;
                if (btnTurun) btnTurun.disabled = !canUrut || furutan <= 1;
                if (btnHapusSel) btnHapusSel.disabled = !(custid > 0 && !isLunas);
            }

            document.querySelectorAll('.dt-bill-row').forEach(function (tr) {
                tr.addEventListener('click', function () {
                    document.querySelectorAll('.dt-bill-row.dt-row-selected').forEach(function (r) {
                        r.classList.remove('dt-row-selected');
                    });
                    tr.classList.add('dt-row-selected');
                    selectedRow = tr;
                    updateSelButtons();
                });
            });

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

                if (btnKartu && formKartu && selectedRowsInput) {
                    btnKartu.addEventListener('click', function () {
                        if (!selectedRow) {
                            alert('Klik baris tagihan dulu untuk memilih siswa.');
                            return;
                        }
                        const cid = parseInt(selectedRow.getAttribute('data-custid') || '0', 10);
                        if (cid <= 0) {
                            alert('Siswa tidak valid.');
                            return;
                        }
                        selectedRowsInput.value = JSON.stringify([cid]);
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

            function doUrutan(direction) {
                if (!selectedRow) {
                    alert('Pilih baris tagihan dulu.');
                    return;
                }
                const custid = parseInt(selectedRow.getAttribute('data-custid') || '0', 10);
                const billcd = selectedRow.getAttribute('data-billcd') || '';
                const aa = selectedRow.getAttribute('data-aa') || '';
                if (!custid || !aa) return;
                if (btnNaik) btnNaik.disabled = true;
                if (btnTurun) btnTurun.disabled = true;
                postJson(urlUrutan, { custid: custid, billcd: billcd, aa: aa, direction: direction })
                    .then(function (res) {
                        if (res && res.ok) {
                            if (res.data && res.data.changed === false) {
                                alert(res.message || 'Urutan tidak berubah.');
                                updateSelButtons();
                                return;
                            }
                            window.location.reload();
                            return;
                        }
                        var msg = (res && (res.message || (res.errors && JSON.stringify(res.errors)))) || 'Gagal ubah urutan';
                        alert(msg);
                        updateSelButtons();
                    })
                    .catch(function () {
                        alert('Koneksi gagal');
                        updateSelButtons();
                    });
            }

            if (btnNaik) {
                btnNaik.addEventListener('click', function () { doUrutan('up'); });
            }
            if (btnTurun) {
                btnTurun.addEventListener('click', function () { doUrutan('down'); });
            }
            if (btnHapusSel) {
                btnHapusSel.addEventListener('click', function () {
                    if (!selectedRow) {
                        alert('Pilih baris tagihan dulu.');
                        return;
                    }
                    if (!confirm('Hapus tagihan ini?')) return;
                    const custid = parseInt(selectedRow.getAttribute('data-custid') || '0', 10);
                    const billcd = selectedRow.getAttribute('data-billcd') || '';
                    btnHapusSel.disabled = true;
                    postJson(urlHapus, { custid: custid, billcd: billcd })
                        .then(function (res) {
                            if (res && res.ok) {
                                window.location.reload();
                                return;
                            }
                            var msg = (res && res.message) || 'Gagal hapus';
                            alert(msg);
                            updateSelButtons();
                        })
                        .catch(function () {
                            alert('Koneksi gagal');
                            updateSelButtons();
                        });
                });
            }
        })();
    </script>
@endsection
