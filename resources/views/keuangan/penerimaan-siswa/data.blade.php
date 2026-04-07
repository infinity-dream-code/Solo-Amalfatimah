@extends('layouts.app')

@section('content')
    <style>
        .dp-wrap { margin-top: 16px; }
        .dp-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .dp-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 4px; }
        .dp-bc { font-size: 12px; color: #6b7280; padding: 0 16px 12px; }
        .dp-filter {
            padding: 0 16px 12px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px 16px;
            border-bottom: 1px solid #eef2f7;
        }
        @media (max-width: 1100px) { .dp-filter { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .dp-filter { grid-template-columns: 1fr; } }
        .dp-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .dp-fld input, .dp-fld select {
            width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px;
        }
        .dp-actions-top {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7;
            justify-content: flex-end; align-items: center;
        }
        .dp-actions-bottom {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7;
            justify-content: flex-end; align-items: center;
        }
        .dp-btn {
            height: 38px; padding: 0 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;
            border: 1px solid #d1d5db; background: #fff; color: #374151;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .dp-btn-search { background: #2563eb; border-color: #2563eb; color: #fff; }
        .dp-btn-search:hover { filter: brightness(1.05); }
        .dp-btn-print { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .dp-btn-print-pdf { background: #dc2626; border-color: #dc2626; color: #fff; }
        .dp-btn-soon { opacity: 0.85; cursor: not-allowed; }
        .dp-notes { padding: 0 16px 12px; font-size: 12px; color: #b91c1c; font-weight: 600; }
        .dp-notes li { margin-bottom: 4px; }
        .dp-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .dp-select, .dp-input-search { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .dp-table-wrap { overflow-x: auto; }
        .dp-table { width: 100%; min-width: 1100px; border-collapse: collapse; font-size: 12px; }
        .dp-table th, .dp-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .dp-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .dp-num { text-align: right; white-space: nowrap; }
        .dp-check { width: 36px; text-align: center; }
        .dp-check input { width: 16px; height: 16px; cursor: pointer; vertical-align: middle; }
        .dp-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280; }
        .dp-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .dp-page.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .dp-page.disabled { pointer-events: none; opacity: 0.45; }
        .dp-alert { margin: 10px 16px 0; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; }
        .dp-err { background: #fef2f2; color: #b91c1c; }
        .dp-info { background: #eff6ff; color: #1e40af; }
    </style>

    <div class="page-heading">
        <h2>Data Penerimaan</h2>
    </div>

    <div class="dp-wrap">
        <div class="dp-card">
            <div class="dp-title">Data Penerimaan</div>
            <div class="dp-bc">Beranda &rsaquo; Data Penerimaan</div>

            @if (session('export_error'))
                <div class="dp-alert dp-err">{{ session('export_error') }}</div>
            @endif
            <div id="dpFetchErr" class="dp-alert dp-err" style="display:none;"></div>
            <form method="GET" action="{{ route('keu.penerimaan.data') }}" id="formDpFilter">
                <input type="hidden" name="cari" id="formDpFilterCari" value="{{ $filters['cari'] ?? '' }}">
                <div class="dp-filter">
                    <div class="dp-fld">
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
                    <div class="dp-fld">
                        <label>Angkatan Siswa</label>
                        <select name="thn_angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="dp-fld">
                        <label>NIS</label>
                        <input type="text" name="nis" value="{{ $filters['nis'] ?? '' }}" placeholder="Masukkan NIS siswa" autocomplete="off">
                    </div>
                    <div class="dp-fld">
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
                    <div class="dp-fld">
                        <label>Nama</label>
                        <input type="text" name="nama" value="{{ $filters['nama'] ?? '' }}" placeholder="Masukkan nama siswa" autocomplete="off">
                    </div>
                    <div class="dp-fld">
                        <label>Nama Tagihan</label>
                        <select name="nama_tagihan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['tagihan'] ?? []) as $tag)
                                <option value="{{ $tag }}" {{ (($filters['nama_tagihan'] ?? '') === $tag) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="dp-fld">
                        <label>Bank</label>
                        <select name="fidbank">
                            <option value="">Semua</option>
                            @foreach (($bankOptions ?? []) as $b)
                                @php $fb = (string) ($b['fidbank'] ?? ''); $lb = (string) ($b['label'] ?? ''); @endphp
                                @if ($fb !== '')
                                    <option value="{{ $fb }}" {{ (($filters['fidbank'] ?? '') === $fb) ? 'selected' : '' }}>{{ $lb }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="dp-fld">
                        <label>Dari Tanggal</label>
                        <input type="date" name="tgl_dari" value="{{ $filters['tgl_dari'] ?? '' }}" required>
                    </div>
                    <div class="dp-fld">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="tgl_sampai" value="{{ $filters['tgl_sampai'] ?? '' }}">
                    </div>
                    <div class="dp-fld">
                        <label>Sekolah</label>
                        <input type="text" name="sekolah" value="{{ $filters['sekolah'] ?? '' }}" placeholder="Nama unit / sekolah">
                    </div>
                    <div class="dp-fld">
                        <label>Periode Mulai</label>
                        <input type="date" name="periode_mulai" value="{{ $filters['periode_mulai'] ?? '' }}" title="Filter tanggal tagihan (FTGLTagihan)">
                    </div>
                    <div class="dp-fld">
                        <label>Periode Akhir</label>
                        <input type="date" name="periode_akhir" value="{{ $filters['periode_akhir'] ?? '' }}" title="Filter tanggal tagihan (FTGLTagihan)">
                    </div>
                </div>

                <div class="dp-actions-top">
                    <button type="button" class="dp-btn dp-btn-print" id="dpBtnKartu" title="Centang siswa di tabel lalu klik">Cetak Kartu Siswa</button>
                    <button type="button" class="dp-btn dp-btn-print" id="dpBtnKuitansi" title="Centang siswa di tabel lalu klik">Cetak Kuitansi</button>
                    <button type="button" class="dp-btn dp-btn-print" id="dpBtnKuitansi2k" title="Sama + baris tambahan Rp 2.000">Cetak Kuitansi Dengan 2000</button>
                    <button type="button" class="dp-btn dp-btn-print-pdf" id="dpBtnRekapPdf" title="PDF rekap sesuai filter (tanggal opsional; maks. 8.000 baris)">Cetak PDF</button>
                </div>

                <ul class="dp-notes">
                    <li>Untuk kartu siswa, isi Dari Tanggal. PDF rekap &amp; kuitansi bisa tanpa tanggal (persempit filter jika data besar).</li>
                    <li>Pastikan browser anda tidak memblokir POP-UP!</li>
                </ul>

                <div class="dp-actions-bottom">
                    <a class="dp-btn" href="{{ route('keu.penerimaan.data') }}">Reset</a>
                    <button type="submit" class="dp-btn dp-btn-search">Cari</button>
                </div>
            </form>

            <div class="dp-toolbar">
                <form method="GET" action="{{ route('keu.penerimaan.data') }}" id="dpToolbarForm" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex:1;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    @foreach ($filters as $fk => $fv)
                        @continue($fk === 'cari')
                        @if ($fv !== '' && $fv !== null && $fv !== false)
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <span>Tampilkan</span>
                    <select class="dp-select" name="per_page" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" {{ ($penerimaanRows->perPage() ?? 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <label for="dpKw" style="font-size:12px;font-weight:700;color:#4b5563;white-space:nowrap;">Cari (semua halaman):</label>
                        <input type="text" name="cari" id="dpKw" class="dp-input-search" value="{{ $filters['cari'] ?? '' }}" placeholder="NIS, nama, tagihan, unit, tanggal…" autocomplete="off" style="min-width:200px;">
                    </div>
                </form>
            </div>

            <div class="dp-table-wrap">
                <table class="dp-table" id="dpTable">
                    <thead>
                        <tr>
                            <th class="dp-check"><input type="checkbox" id="dpSelAll" aria-label="Pilih semua di halaman ini"></th>
                            <th>No</th>
                            <th>NIS</th>
                            <th>NAMA</th>
                            <th>Unit</th>
                            <th>Kelas</th>
                            <th>Nama Tagihan</th>
                            <th class="dp-num">Tagihan</th>
                            <th>Metode</th>
                            <th>Tanggal Bayar</th>
                            <th>Tahun AKA</th>
                        </tr>
                    </thead>
                    <tbody id="dpTbody">
                        <tr id="dpLoadingRow">
                            <td colspan="11" style="text-align:center;color:#6b7280;padding:20px;">Memuat data tabel…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="dp-footer" id="dpFooter">
                <div id="dpFooterInfo">
                    <span style="color:#6b7280;">Menunggu data…</span>
                </div>
                <div style="display:flex;gap:6px;align-items:center;" id="dpFooterNav">
                    <span class="dp-page disabled">Sebelumnya</span>
                    <span class="dp-page active" id="dpPageLabel">{{ $penerimaanRows->currentPage() }}</span>
                    <span class="dp-page disabled">Selanjutnya</span>
                </div>
            </div>
        </div>
    </div>

    <form id="dpFormKartu" method="POST" action="{{ route('keu.penerimaan.kartu_siswa') }}" target="_blank" style="display:none;" aria-hidden="true">
        @csrf
    </form>
    <form id="dpFormKuitansi" method="POST" action="{{ route('keu.penerimaan.kuitansi') }}" target="_blank" style="display:none;" aria-hidden="true">
        @csrf
    </form>
    <form id="dpFormRekapPdf" method="POST" action="{{ route('keu.penerimaan.rekap_pdf') }}" target="_blank" style="display:none;" aria-hidden="true">
        @csrf
    </form>

    <script>
        (function () {
            var mainForm = document.getElementById('formDpFilter');
            var toolbarForm = document.getElementById('dpToolbarForm');
            var kw = document.getElementById('dpKw');
            var hiddenCari = document.getElementById('formDpFilterCari');
            var debounceTimer;

            if (mainForm && hiddenCari && kw) {
                mainForm.addEventListener('submit', function () {
                    hiddenCari.value = kw.value || '';
                });
            }

            if (toolbarForm && kw) {
                kw.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        toolbarForm.submit();
                    }, 500);
                });
                kw.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(debounceTimer);
                        toolbarForm.submit();
                    }
                });
            }

            var loadUrl = @json($penerimaanRowsUrl ?? '');
            var tbody = document.getElementById('dpTbody');
            var errEl = document.getElementById('dpFetchErr');
            var footerInfo = document.getElementById('dpFooterInfo');
            var footerNav = document.getElementById('dpFooterNav');

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : String(s);
                return d.innerHTML;
            }
            function escAttr(s) {
                return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }
            function fmtRp(n) {
                return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
            }

            var dpTable = document.getElementById('dpTable');
            if (dpTable) {
                dpTable.addEventListener('change', function (e) {
                    var t = e.target;
                    if (t && t.id === 'dpSelAll') {
                        var on = t.checked;
                        document.querySelectorAll('.dp-row-cb').forEach(function (cb) { cb.checked = on; });
                    }
                });
            }

            function dpCollectCheckedCustIds() {
                var ids = [];
                document.querySelectorAll('.dp-row-cb:checked').forEach(function (cb) {
                    var id = parseInt(cb.getAttribute('data-custid') || '0', 10);
                    if (id > 0) {
                        ids.push(id);
                    }
                });
                return ids.filter(function (v, i, a) { return a.indexOf(v) === i; });
            }

            function dpAppendDynHidden(form, name, value, dataAttr) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = name;
                inp.value = value == null ? '' : String(value);
                inp.setAttribute(dataAttr, '1');
                form.appendChild(inp);
            }

            function dpFillExportForm(form, dataAttr, extraPairs) {
                form.querySelectorAll('[' + dataAttr + ']').forEach(function (n) { n.remove(); });
                var ff = document.getElementById('formDpFilter');
                if (ff) {
                    ff.querySelectorAll('input[name], select[name]').forEach(function (el) {
                        var ty = (el.type || '').toLowerCase();
                        if (ty === 'button' || ty === 'submit') {
                            return;
                        }
                        if (el.name === 'cari') {
                            return;
                        }
                        dpAppendDynHidden(form, el.name, el.value, dataAttr);
                    });
                }
                if (kw) {
                    dpAppendDynHidden(form, 'cari', kw.value || '', dataAttr);
                }
                if (extraPairs && extraPairs.length) {
                    extraPairs.forEach(function (p) {
                        dpAppendDynHidden(form, p[0], p[1], dataAttr);
                    });
                }
            }

            var dpBtnKartu = document.getElementById('dpBtnKartu');
            var dpFormKartu = document.getElementById('dpFormKartu');
            if (dpBtnKartu && dpFormKartu) {
                dpBtnKartu.addEventListener('click', function () {
                    var ids = dpCollectCheckedCustIds();
                    if (ids.length === 0) {
                        alert('Pilih minimal satu siswa (centang baris di tabel).');
                        return;
                    }
                    dpFillExportForm(dpFormKartu, 'data-dp-kartu-dyn', null);
                    ids.forEach(function (id) {
                        dpAppendDynHidden(dpFormKartu, 'custids[]', id, 'data-dp-kartu-dyn');
                    });
                    dpFormKartu.submit();
                });
            }

            var dpBtnKuitansi = document.getElementById('dpBtnKuitansi');
            var dpBtnKuitansi2k = document.getElementById('dpBtnKuitansi2k');
            var dpFormKuitansi = document.getElementById('dpFormKuitansi');
            function dpSubmitKuitansi(dengan2k) {
                if (!dpFormKuitansi) {
                    return;
                }
                var ids = dpCollectCheckedCustIds();
                if (ids.length === 0) {
                    alert('Pilih minimal satu siswa (centang baris di tabel).');
                    return;
                }
                dpFillExportForm(dpFormKuitansi, 'data-dp-kuitansi-dyn', [
                    ['dengan_2000', dengan2k ? '1' : '0']
                ]);
                ids.forEach(function (id) {
                    dpAppendDynHidden(dpFormKuitansi, 'custids[]', id, 'data-dp-kuitansi-dyn');
                });
                dpFormKuitansi.submit();
            }
            if (dpBtnKuitansi) {
                dpBtnKuitansi.addEventListener('click', function () { dpSubmitKuitansi(false); });
            }
            if (dpBtnKuitansi2k) {
                dpBtnKuitansi2k.addEventListener('click', function () { dpSubmitKuitansi(true); });
            }

            var dpBtnRekapPdf = document.getElementById('dpBtnRekapPdf');
            var dpFormRekapPdf = document.getElementById('dpFormRekapPdf');
            if (dpBtnRekapPdf && dpFormRekapPdf) {
                dpBtnRekapPdf.addEventListener('click', function () {
                    dpFillExportForm(dpFormRekapPdf, 'data-dp-rekap-pdf-dyn', null);
                    dpFormRekapPdf.submit();
                });
            }

            if (loadUrl && tbody) {
                fetch(loadUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                    .then(function (pack) {
                        var j = pack.j || {};
                        if (!pack.ok || !j.ok) {
                            if (errEl) {
                                errEl.style.display = 'block';
                                errEl.textContent = j.message || 'Gagal memuat data.';
                            }
                            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#b91c1c;padding:20px;">' +
                                esc(j.message || 'Gagal memuat data.') + '</td></tr>';
                            if (footerInfo) footerInfo.innerHTML = '';
                            return;
                        }
                        var rows = j.rows || [];
                        if (rows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data untuk filter ini.</td></tr>';
                        } else {
                            tbody.innerHTML = rows.map(function (r, idx) {
                                var no = (j.first_item || 0) + idx;
                                var hay = esc((r.search_hay || '').replace(/"/g, ''));
                                return '<tr class="dp-data-row" data-dp-hay="' + hay + '">' +
                                    '<td class="dp-check"><input type="checkbox" class="dp-row-cb" data-custid="' + esc(String(r.custid || r.CUSTID || 0)) + '"></td>' +
                                    '<td>' + esc(no) + '</td>' +
                                    '<td>' + esc(r.nis || '-') + '</td>' +
                                    '<td>' + esc(r.nama || '-') + '</td>' +
                                    '<td>' + esc(r.unit || '-') + '</td>' +
                                    '<td>' + esc(r.kelas || '-') + '</td>' +
                                    '<td>' + esc(r.nama_tagihan || '-') + '</td>' +
                                    '<td class="dp-num">' + fmtRp(r.tagihan) + '</td>' +
                                    '<td>' + esc(r.metode || '-') + '</td>' +
                                    '<td>' + esc(r.tbayar_display || '-') + '</td>' +
                                    '<td>' + esc(r.tahun_aka || '-') + '</td></tr>';
                            }).join('');
                        }
                        if (footerInfo) {
                            footerInfo.innerHTML = 'Menampilkan ' + esc(j.first_item) + '–' + esc(j.last_item) +
                                ' <span style="color:#6b7280;">(tanpa total global)</span>';
                        }
                        if (footerNav) {
                            var prevH = j.prev_url ? '<a class="dp-page" href="' + escAttr(j.prev_url) + '">Sebelumnya</a>' : '<span class="dp-page disabled">Sebelumnya</span>';
                            var nextH = j.next_url ? '<a class="dp-page" href="' + escAttr(j.next_url) + '">Selanjutnya</a>' : '<span class="dp-page disabled">Selanjutnya</span>';
                            var cur = esc(String(j.page || 1));
                            footerNav.innerHTML = prevH + '<span class="dp-page active">' + cur + '</span>' + nextH;
                        }
                    })
                    .catch(function () {
                        if (errEl) {
                            errEl.style.display = 'block';
                            errEl.textContent = 'Gagal menghubungi server.';
                        }
                        if (tbody) tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#b91c1c;padding:20px;">Gagal menghubungi server.</td></tr>';
                    });
            }
        })();
    </script>
@endsection
