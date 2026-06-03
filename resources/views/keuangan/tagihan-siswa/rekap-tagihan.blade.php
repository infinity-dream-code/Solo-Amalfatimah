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
        .rk-table { width:100%; min-width:1280px; border-collapse: collapse; font-size:12px; }
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
            <form id="rkFormRekap" method="POST" action="{{ route('keu.tagihan.data_print_rekap') }}" style="display:none;">
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
                            <th>No</th>
                            <th>NIS</th>
                            <th>No VA</th>
                            <th>Nama</th>
                            <th>Unit</th>
                            <th>Kelas</th>
                            <th>Kelompok</th>
                            <th>Angkatan</th>
                            <th>Kode</th>
                            <th>Nama Post</th>
                            <th>Nama Tagihan</th>
                            <th>Tahun AKA</th>
                            <th class="rk-num">Tagihan</th>
                            <th class="rk-center">Urutan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapRows as $r)
                            @php
                                $row = is_array($r) ? $r : (array) $r;
                                $rkCell = static function (array $row, string $key, string $fallback = ''): string {
                                    $v = trim((string) ($row[$key] ?? $fallback));
                                    return $v !== '' ? $v : '-';
                                };
                            @endphp
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
                                <td>{{ $rkCell($row, 'nis') }}</td>
                                <td>{{ $rkCell($row, 'no_va') }}</td>
                                <td>{{ $rkCell($row, 'nama') }}</td>
                                <td>{{ $rkCell($row, 'unit') }}</td>
                                <td>{{ $rkCell($row, 'kelas') }}</td>
                                <td>{{ $rkCell($row, 'kelompok') }}</td>
                                <td>{{ $rkCell($row, 'angkatan') }}</td>
                                <td>{{ $rkCell($row, 'kode', (string) ($row['billcd'] ?? '')) }}</td>
                                <td>{{ $rkCell($row, 'nama_post', (string) ($row['nama_tagihan'] ?? '')) }}</td>
                                <td>{{ $rkCell($row, 'nama_tagihan') }}</td>
                                <td>{{ $rkCell($row, 'tahun_aka') }}</td>
                                <td class="rk-num">Rp {{ number_format((int) ($row['tagihan'] ?? 0), 0, ',', '.') }}</td>
                                <td class="rk-center">{{ (int) ($row['furutan'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="15" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data</td></tr>
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
    <script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
    <script>
        (function () {
            const rekapExportUrl = @json(route('keu.tagihan.data_print_rekap'));
            const csrfToken = @json(csrf_token());
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

            function parseIsoDate(str) {
                if (!str || str === '-') return '-';
                const parts = String(str).split('-');
                if (parts.length !== 3) return str;
                const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10), 12);
                return isNaN(d.getTime()) ? str : d;
            }

            function fullBorder() {
                return {
                    top: { style: 'thin' },
                    left: { style: 'thin' },
                    bottom: { style: 'thin' },
                    right: { style: 'thin' }
                };
            }

            function cellBGColor() {
                return {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFEBE1FF' }
                };
            }

            async function exportRekapTagihanExcel(matrix, meta) {
                if (!matrix || !matrix.rows || matrix.rows.length === 0 || typeof ExcelJS === 'undefined') return;
                const wbTitle = 'REKAP TAGIHAN';
                const wb = new ExcelJS.Workbook();
                const ws = wb.addWorksheet(wbTitle);
                const rows = matrix.rows;
                const kelasOrder = matrix.kelasOrder || [];
                const kelompokOrder = matrix.kelompokOrder || [];
                meta = meta || {};

                ws.insertRow(1, [wbTitle]);
                ws.insertRow(2, ['Sekolah', meta.sekolah || 'Semua']);
                ws.insertRow(3, ['Tahun Pelajaran', meta.tahun_pelajaran || 'Semua']);
                ws.insertRow(4, ['Periode Mulai', meta.periode_mulai || '-']);
                ws.insertRow(5, ['Periode Akhir', meta.periode_akhir || '-']);
                ws.insertRow(6, ['Dari Tanggal', parseIsoDate(meta.dari_tanggal || '-')]);
                ws.insertRow(7, ['Sampai Tanggal', parseIsoDate(meta.sampai_tanggal || '-')]);

                [6, 7].forEach(function (rowNumber) {
                    const cell = ws.getRow(rowNumber).getCell(2);
                    if (cell.value instanceof Date) cell.numFmt = 'dddd, dd mmmm yyyy';
                });
                [1, 2, 3, 4, 5, 6, 7].forEach(function (rowNumber) {
                    ws.getRow(rowNumber).eachCell({ includeEmpty: true }, function (cell) { cell.font = { bold: true }; });
                });

                ws.insertRow(9, []);
                const headerRow1Number = 10;
                const headerRow1 = ws.getRow(headerRow1Number);
                const headerRow2 = ws.getRow(headerRow1Number + 1);

                let col = 1;
                headerRow1.getCell(col).value = 'Thn Akademik'; ws.mergeCells(headerRow1Number, col, headerRow1Number + 1, col); col++;
                headerRow1.getCell(col).value = 'Kode'; ws.mergeCells(headerRow1Number, col, headerRow1Number + 1, col); col++;
                headerRow1.getCell(col).value = 'Nama'; ws.mergeCells(headerRow1Number, col, headerRow1Number + 1, col); col++;

                kelasOrder.forEach(function (kelas) {
                    const startCol = col;
                    kelompokOrder.forEach(function (k) {
                        headerRow2.getCell(col).value = k;
                        col++;
                    });
                    headerRow2.getCell(col).value = 'Sum';
                    const endCol = col;
                    ws.mergeCells(headerRow1Number, startCol, headerRow1Number, endCol);
                    headerRow1.getCell(startCol).value = kelas;
                    col++;
                });

                headerRow1.getCell(col).value = 'Total';
                ws.mergeCells(headerRow1Number, col, headerRow1Number + 1, col);
                const lastCol = col;

                for (let i = 1; i <= lastCol; i++) {
                    ws.getColumn(i).width = i <= 3 ? [12, 10, 26][i - 1] : 14;
                    [headerRow1, headerRow2].forEach(function (r) {
                        const cell = r.getCell(i);
                        cell.font = { bold: true };
                        cell.alignment = { horizontal: 'center', vertical: 'middle' };
                        cell.border = fullBorder();
                        cell.fill = cellBGColor();
                    });
                }

                const dataStartRow = headerRow1Number + 2;
                let currentRow = dataStartRow;
                rows.forEach(function (r, idx) {
                    const row = ws.getRow(currentRow);
                    row.getCell(1).value = idx === 0 ? (r.tahun || '') : '';
                    row.getCell(2).value = r.kode;
                    row.getCell(3).value = r.nama;
                    let c = 4;
                    kelasOrder.forEach(function (kelas) {
                        let subtotalKelas = 0;
                        kelompokOrder.forEach(function (k) {
                            const val = Number((r.byClass && r.byClass[kelas] && r.byClass[kelas][k]) || 0);
                            subtotalKelas += val;
                            row.getCell(c).value = val;
                            row.getCell(c).numFmt = '#,##0';
                            row.getCell(c).alignment = { horizontal: 'right' };
                            c++;
                        });
                        row.getCell(c).value = subtotalKelas;
                        row.getCell(c).numFmt = '#,##0';
                        row.getCell(c).alignment = { horizontal: 'right' };
                        c++;
                    });
                    row.getCell(c).value = Number(r.total || 0);
                    row.getCell(c).numFmt = '#,##0';
                    row.getCell(c).alignment = { horizontal: 'right' };
                    for (let i = 1; i <= lastCol; i++) row.getCell(i).border = fullBorder();
                    currentRow++;
                });

                const totalRow = ws.getRow(currentRow);
                totalRow.getCell(3).value = 'Total';
                totalRow.getCell(3).font = { bold: true };
                for (let i = 4; i <= lastCol; i++) {
                    const colLetter = ws.getColumn(i).letter;
                    totalRow.getCell(i).value = { formula: 'SUM(' + colLetter + dataStartRow + ':' + colLetter + (currentRow - 1) + ')' };
                    totalRow.getCell(i).numFmt = '#,##0';
                    totalRow.getCell(i).font = { bold: true };
                    totalRow.getCell(i).alignment = { horizontal: 'right' };
                }
                for (let i = 1; i <= lastCol; i++) {
                    totalRow.getCell(i).border = fullBorder();
                    totalRow.getCell(i).fill = cellBGColor();
                }

                const buffer = await wb.xlsx.writeBuffer();
                const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = wbTitle + '.xlsx';
                a.click();
            }

            function submitPrintForm(form, btn, label) {
                if (!form) return;
                const buttons = [btnRekap, btnKartu, btnPerNis].filter(Boolean);
                buttons.forEach(function (b) { b.disabled = true; });
                if (btn) {
                    btn.dataset.prevLabel = btn.textContent;
                    btn.textContent = label || 'Memproses…';
                }
                form.submit();
                window.setTimeout(function () {
                    buttons.forEach(function (b) { b.disabled = false; });
                    if (btn && btn.dataset.prevLabel) {
                        btn.textContent = btn.dataset.prevLabel;
                    }
                }, 180000);
            }

            if (btnRekap && formRekap) {
                btnRekap.addEventListener('click', async function () {
                    if (rowChecks.length === 0) {
                        alert('Data masih kosong. Klik Cari dulu sebelum cetak rekap.');
                        return;
                    }
                    const buttons = [btnRekap, btnKartu, btnPerNis].filter(Boolean);
                    buttons.forEach(function (b) { b.disabled = true; });
                    btnRekap.dataset.prevLabel = btnRekap.textContent;
                    btnRekap.textContent = 'Mengekspor rekap…';

                    const body = new FormData(formRekap);
                    try {
                        const res = await fetch(rekapExportUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: body,
                            credentials: 'same-origin'
                        });
                        const json = await res.json().catch(function () { return {}; });
                        if (!res.ok || !json.ok) {
                            alert(json.message || 'Gagal mengekspor rekap tagihan.');
                            return;
                        }
                        await exportRekapTagihanExcel(json.matrix, json.meta);
                    } catch (e) {
                        alert('Gagal mengekspor rekap tagihan. Pastikan ws.php terbaru sudah di-upload.');
                    } finally {
                        buttons.forEach(function (b) { b.disabled = false; });
                        if (btnRekap.dataset.prevLabel) {
                            btnRekap.textContent = btnRekap.dataset.prevLabel;
                        }
                    }
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
                    submitPrintForm(formKartu, btnKartu, 'Memproses kartu…');
                });
            }

            if (btnPerNis && formPerNis && inputPerNis) {
                btnPerNis.addEventListener('click', function () {
                    const picked = collectCustIds();
                    if (picked.length === 0) {
                        alert('Pilih minimal 1 siswa dari centang kiri tabel.');
                        return;
                    }
                    inputPerNis.value = JSON.stringify(picked);
                    submitPrintForm(formPerNis, btnPerNis, 'Memproses per NIS…');
                });
            }
        })();
    </script>
@endsection

