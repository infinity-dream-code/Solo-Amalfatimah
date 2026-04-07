@extends('layouts.app')

@section('content')
    <style>
        .rp-wrap { margin-top: 16px; }
        .rp-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .rp-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 4px; }
        .rp-bc { font-size: 12px; color: #6b7280; padding: 0 16px 12px; }
        .rp-filter {
            padding: 0 16px 12px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 16px;
            border-bottom: 1px solid #eef2f7;
        }
        @media (max-width: 900px) { .rp-filter { grid-template-columns: 1fr; } }
        .rp-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .rp-fld input, .rp-fld select {
            width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px;
        }
        .rp-actions {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7;
            justify-content: flex-end; align-items: center;
        }
        .rp-btn {
            height: 38px; padding: 0 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;
            border: 1px solid #d1d5db; background: #fff; color: #374151;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .rp-btn-search { background: #7c3aed; border-color: #7c3aed; color: #fff; }
        .rp-btn-search:hover { filter: brightness(1.05); }
        .rp-btn-print { background: #2563eb; border-color: #2563eb; color: #fff; }
        .rp-btn-print:hover { filter: brightness(1.05); }
        .rp-btn-kartu { background: #1e3a8a; border-color: #1e3a8a; color: #fff; }
        .rp-btn-kartu:hover { filter: brightness(1.05); }
        .rp-notes { padding: 0 16px 8px; font-size: 12px; color: #b91c1c; font-weight: 600; }
        .rp-notes li { margin-bottom: 4px; }
        .rp-foot-notes { padding: 0 16px 12px; font-size: 12px; color: #6b7280; }
        .rp-foot-notes li { margin-bottom: 4px; }
        .rp-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .rp-select, .rp-input-search { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .rp-table-wrap { overflow-x: auto; }
        .rp-table { width: 100%; min-width: 1100px; border-collapse: collapse; font-size: 12px; }
        .rp-table th, .rp-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .rp-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .rp-num { text-align: right; white-space: nowrap; }
        .rp-check { width: 36px; text-align: center; }
        .rp-check input { width: 16px; height: 16px; cursor: pointer; vertical-align: middle; }
        .rp-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280; }
        .rp-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .rp-page.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .rp-page.disabled { pointer-events: none; opacity: 0.45; }
        .rp-alert { margin: 10px 16px 0; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; }
        .rp-err { background: #fef2f2; color: #b91c1c; }
    </style>

    <div class="page-heading">
        <h2>{{ $pageTitle ?? 'Rekap Penerimaan' }}</h2>
    </div>

    <div class="rp-wrap">
        <div class="rp-card">
            <div class="rp-title">Data Penerimaan</div>
            <div class="rp-bc">Beranda &rsaquo; Data Penerimaan</div>

            @if (session('export_error'))
                <div class="rp-alert rp-err">{{ session('export_error') }}</div>
            @endif
            <div id="rpFetchErr" class="rp-alert rp-err" style="display:none;"></div>

            <form method="GET" action="{{ route('keu.penerimaan.rekap') }}" id="formRpFilter">
                <div class="rp-filter">
                    <div class="rp-fld">
                        <label>Tanggal Transaksi</label>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <input type="date" name="tgl_dari" value="{{ $filters['tgl_dari'] ?? '' }}" required style="flex:1;min-width:140px;">
                            <span style="color:#6b7280;font-size:12px;">s/d</span>
                            <input type="date" name="tgl_sampai" value="{{ $filters['tgl_sampai'] ?? '' }}" style="flex:1;min-width:140px;">
                        </div>
                    </div>
                    <div class="rp-fld">
                        <label>Angkatan Siswa</label>
                        <select name="thn_angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rp-fld">
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
                    <div class="rp-fld">
                        <label>Tingkat</label>
                        <select name="sekolah">
                            <option value="">Semua</option>
                            @foreach (($tingkatOptions ?? []) as $unit)
                                @php $u = (string) $unit; @endphp
                                @if ($u !== '')
                                    <option value="{{ $u }}" {{ (($filters['sekolah'] ?? '') === $u) ? 'selected' : '' }}>{{ $u }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="rp-fld">
                        <label>Nama Tagihan</label>
                        <select name="nama_tagihan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['tagihan'] ?? []) as $tag)
                                <option value="{{ $tag }}" {{ (($filters['nama_tagihan'] ?? '') === $tag) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rp-fld">
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
                    <div class="rp-fld">
                        <label>Siswa</label>
                        <input type="text" name="cari" id="rpSiswaKw" value="{{ $filters['cari'] ?? '' }}" placeholder="Masukkan NIS/NAMA Siswa" autocomplete="off">
                    </div>
                </div>

                <ul class="rp-notes">
                    <li>Pastikan telah mengisi Tanggal Transaksi!</li>
                    <li>Pastikan browser anda tidak memblokir POP-UP!</li>
                </ul>

                <div class="rp-actions">
                    <button type="button" class="rp-btn rp-btn-print" id="rpBtnRekapPdf" title="Excel rekap (maks. 8.000 baris)">Export Rekap Excel</button>
                    <button type="button" class="rp-btn rp-btn-kartu" id="rpBtnKartu">Cetak Kartu Siswa</button>
                    <a class="rp-btn" href="{{ route('keu.penerimaan.rekap') }}">Reset</a>
                    <button type="submit" class="rp-btn rp-btn-search">Cari</button>
                </div>

                <ul class="rp-foot-notes">
                    <li>Untuk mencetak kartu siswa, silahkan pilih siswa terlebih dahulu!</li>
                    <li>Cetak kartu siswa, hanya bisa dilakukan per siswa!</li>
                </ul>
            </form>

            <div class="rp-toolbar">
                <form method="GET" action="{{ route('keu.penerimaan.rekap') }}" id="rpToolbarForm" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex:1;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    @foreach ($filters as $fk => $fv)
                        @continue($fk === 'cari')
                        @if ($fv !== '' && $fv !== null && $fv !== false)
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <span>Tampilkan</span>
                    <select class="rp-select" name="per_page" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" {{ ($penerimaanRows->perPage() ?? 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <label for="rpKw" style="font-size:12px;font-weight:700;color:#4b5563;white-space:nowrap;">Cari:</label>
                        <input type="text" name="cari" id="rpKw" class="rp-input-search" value="{{ $filters['cari'] ?? '' }}" placeholder="NIS, nama, unit, tagihan…" autocomplete="off" style="min-width:200px;">
                    </div>
                </form>
            </div>

            <div class="rp-table-wrap">
                <table class="rp-table" id="rpTable">
                    <thead>
                        <tr>
                            <th class="rp-check"><input type="checkbox" id="rpSelAll" aria-label="Pilih semua di halaman ini"></th>
                            <th>No</th>
                            <th>NIS</th>
                            <th>NAMA</th>
                            <th>Unit</th>
                            <th>Kelas</th>
                            <th>Nama Tagihan</th>
                            <th class="rp-num">Tagihan</th>
                            <th>Metode</th>
                            <th>Tanggal Bayar</th>
                            <th>Tahun AKA</th>
                        </tr>
                    </thead>
                    <tbody id="rpTbody">
                        <tr id="rpLoadingRow">
                            <td colspan="11" style="text-align:center;color:#6b7280;padding:20px;">Memuat data tabel…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rp-footer" id="rpFooter">
                <div id="rpFooterInfo">
                    <span style="color:#6b7280;">Menunggu data…</span>
                </div>
                <div style="display:flex;gap:6px;align-items:center;" id="rpFooterNav">
                    <span class="rp-page disabled">Sebelumnya</span>
                    <span class="rp-page active" id="rpPageLabel">{{ $penerimaanRows->currentPage() }}</span>
                    <span class="rp-page disabled">Selanjutnya</span>
                </div>
            </div>
        </div>
    </div>

    <form id="rpFormKartu" method="POST" action="{{ route('keu.penerimaan.kartu_siswa') }}" target="_blank" style="display:none;" aria-hidden="true">
        @csrf
    </form>
    <form id="rpFormRekapPdf" method="POST" action="{{ route('keu.penerimaan.rekap_pdf') }}" target="_blank" style="display:none;" aria-hidden="true">
        @csrf
    </form>

    <script>
        (function () {
            var mainForm = document.getElementById('formRpFilter');
            var toolbarForm = document.getElementById('rpToolbarForm');
            var kw = document.getElementById('rpKw');
            var debounceTimer;

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

            var loadUrl = @json($rekapRowsUrl ?? '');
            var tbody = document.getElementById('rpTbody');
            var errEl = document.getElementById('rpFetchErr');
            var footerInfo = document.getElementById('rpFooterInfo');
            var footerNav = document.getElementById('rpFooterNav');

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

            var rpTable = document.getElementById('rpTable');
            if (rpTable) {
                rpTable.addEventListener('change', function (e) {
                    var t = e.target;
                    if (t && t.id === 'rpSelAll') {
                        var on = t.checked;
                        document.querySelectorAll('.rp-row-cb').forEach(function (cb) { cb.checked = on; });
                    }
                });
            }

            function rpCollectCheckedCustIds() {
                var ids = [];
                document.querySelectorAll('.rp-row-cb:checked').forEach(function (cb) {
                    var id = parseInt(cb.getAttribute('data-custid') || '0', 10);
                    if (id > 0) {
                        ids.push(id);
                    }
                });
                return ids.filter(function (v, i, a) { return a.indexOf(v) === i; });
            }

            function rpAppendDynHidden(form, name, value, dataAttr) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = name;
                inp.value = value == null ? '' : String(value);
                inp.setAttribute(dataAttr, '1');
                form.appendChild(inp);
            }

            function rpFillExportForm(form, dataAttr) {
                form.querySelectorAll('[' + dataAttr + ']').forEach(function (n) { n.remove(); });
                var ff = document.getElementById('formRpFilter');
                if (ff) {
                    ff.querySelectorAll('input[name], select[name]').forEach(function (el) {
                        var ty = (el.type || '').toLowerCase();
                        if (ty === 'button' || ty === 'submit') {
                            return;
                        }
                        if (el.name === 'cari') {
                            return;
                        }
                        rpAppendDynHidden(form, el.name, el.value, dataAttr);
                    });
                }
                var cariVal = '';
                if (kw && (kw.value || '').trim() !== '') {
                    cariVal = (kw.value || '').trim();
                } else if (ff) {
                    var cEl = ff.querySelector('input[name="cari"]');
                    if (cEl) {
                        cariVal = (cEl.value || '').trim();
                    }
                }
                rpAppendDynHidden(form, 'cari', cariVal, dataAttr);
            }

            var rpBtnKartu = document.getElementById('rpBtnKartu');
            var rpFormKartu = document.getElementById('rpFormKartu');
            if (rpBtnKartu && rpFormKartu) {
                rpBtnKartu.addEventListener('click', function () {
                    var ids = rpCollectCheckedCustIds();
                    if (ids.length === 0) {
                        alert('Pilih minimal satu siswa (centang baris di tabel).');
                        return;
                    }
                    rpFillExportForm(rpFormKartu, 'data-rp-kartu-dyn');
                    ids.forEach(function (id) {
                        rpAppendDynHidden(rpFormKartu, 'custids[]', id, 'data-rp-kartu-dyn');
                    });
                    rpFormKartu.submit();
                });
            }

            var rpBtnRekapPdf = document.getElementById('rpBtnRekapPdf');
            var rpFormRekapPdf = document.getElementById('rpFormRekapPdf');
            if (rpBtnRekapPdf && rpFormRekapPdf) {
                rpBtnRekapPdf.addEventListener('click', function () {
                    rpFillExportForm(rpFormRekapPdf, 'data-rp-rekap-pdf-dyn');
                    rpFormRekapPdf.submit();
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
                                return '<tr class="rp-data-row" data-rp-hay="' + hay + '">' +
                                    '<td class="rp-check"><input type="checkbox" class="rp-row-cb" data-custid="' + esc(String(r.custid || r.CUSTID || 0)) + '"></td>' +
                                    '<td>' + esc(no) + '</td>' +
                                    '<td>' + esc(r.nis || '-') + '</td>' +
                                    '<td>' + esc(r.nama || '-') + '</td>' +
                                    '<td>' + esc(r.unit || '-') + '</td>' +
                                    '<td>' + esc(r.kelas || '-') + '</td>' +
                                    '<td>' + esc(r.nama_tagihan || '-') + '</td>' +
                                    '<td class="rp-num">' + fmtRp(r.tagihan) + '</td>' +
                                    '<td>' + esc(r.metode || '-') + '</td>' +
                                    '<td>' + esc(r.tbayar_display || '-') + '</td>' +
                                    '<td>' + esc(r.tahun_aka || '-') + '</td></tr>';
                            }).join('');
                        }
                        if (footerInfo) {
                            footerInfo.innerHTML = 'Menampilkan ' + esc(j.first_item) + ' sampai ' + esc(j.last_item) +
                                ' <span style="color:#6b7280;">(tanpa total global)</span>';
                        }
                        if (footerNav) {
                            var prevH = j.prev_url ? '<a class="rp-page" href="' + escAttr(j.prev_url) + '">Sebelumnya</a>' : '<span class="rp-page disabled">Sebelumnya</span>';
                            var nextH = j.next_url ? '<a class="rp-page" href="' + escAttr(j.next_url) + '">Selanjutnya</a>' : '<span class="rp-page disabled">Selanjutnya</span>';
                            var cur = esc(String(j.page || 1));
                            footerNav.innerHTML = prevH + '<span class="rp-page active">' + cur + '</span>' + nextH;
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
