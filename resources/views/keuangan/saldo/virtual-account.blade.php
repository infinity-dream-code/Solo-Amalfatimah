@extends('layouts.app')

@section('content')
    @php
        $vaDetailBase = preg_replace('#/\d+$#', '/', route('keu.saldo.va.detail', ['custid' => 1]));
        $vaExportQ = request()->query();
    @endphp
    <style>
        .va-wrap { margin-top: 16px; }
        .va-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .va-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 4px; }
        .va-bc { font-size: 12px; color: #6b7280; padding: 0 16px 12px; }
        .va-sub { font-size: 13px; font-weight: 700; color: #374151; padding: 0 16px 8px; }
        .va-filter {
            padding: 0 16px 12px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 16px;
            border-bottom: 1px solid #eef2f7;
        }
        @media (max-width: 900px) { .va-filter { grid-template-columns: 1fr; } }
        .va-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .va-fld input, .va-fld select {
            width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px;
        }
        .va-actions {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7;
            justify-content: flex-end; align-items: center;
        }
        .va-btn {
            height: 38px; padding: 0 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;
            border: 1px solid #d1d5db; background: #fff; color: #374151;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .va-btn-search { background: #2563eb; border-color: #2563eb; color: #fff; }
        .va-btn-export { background: #e0f2fe; border-color: #7dd3fc; color: #0369a1; }
        .va-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .va-select, .va-input-search { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .va-table-wrap { overflow-x: auto; }
        .va-table { width: 100%; min-width: 1180px; border-collapse: collapse; font-size: 12px; }
        .va-table th, .va-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .va-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .va-num { text-align: right; white-space: nowrap; }
        .va-ctr { text-align: center; }
        .va-detail-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 8px; background: #2563eb; color: #fff;
            border: none; cursor: pointer; text-decoration: none;
        }
        .va-detail-btn:hover { filter: brightness(1.08); }
        .va-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280; }
        .va-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .va-page.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .va-page.disabled { pointer-events: none; opacity: 0.45; }
        .va-alert { margin: 10px 16px 0; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; }
        .va-err { background: #fef2f2; color: #b91c1c; }
        .va-export-dd { position: relative; display: inline-block; }
        .va-export-menu {
            display: none; position: absolute; right: 0; top: 100%; margin-top: 4px; background: #fff;
            border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); min-width: 160px; z-index: 20;
        }
        .va-export-dd.open .va-export-menu { display: block; }
        .va-export-menu a, .va-export-menu button {
            display: block; width: 100%; box-sizing: border-box; text-align: left;
            padding: 10px 14px; font-size: 13px; color: #111; text-decoration: none;
            border: none; background: none; cursor: pointer; font-family: inherit;
        }
        .va-export-menu a:hover, .va-export-menu button:hover { background: #f8fafc; }
    </style>

    <div class="page-heading">
        <h2>{{ $pageTitle ?? 'Saldo Virtual Account' }}</h2>
    </div>

    <div class="va-wrap">
        <div class="va-card">
            <div class="va-title">Saldo Virtual Account</div>
            <div class="va-bc">Beranda &rsaquo; Keuangan &rsaquo; Saldo &rsaquo; Saldo Virtual Account</div>

            <div id="vaFetchErr" class="va-alert va-err" style="display:none;"></div>

            <form method="GET" action="{{ route('keu.saldo.va') }}" id="formVaFilter">
                <div class="va-sub">Filter</div>
                <div class="va-filter">
                    <div class="va-fld">
                        <label>Angkatan Siswa</label>
                        <select name="thn_angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="va-fld">
                        <label>Sekolah</label>
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
                    <div class="va-fld">
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
                    <div class="va-fld">
                        <label>Siswa</label>
                        <input type="text" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Masukkan NIS/NAMA Siswa" autocomplete="off">
                    </div>
                </div>
                <div class="va-actions">
                    <div class="va-export-dd" id="vaExportDd">
                        <button type="button" class="va-btn va-btn-export" id="vaBtnExport" aria-expanded="false">Export ▾</button>
                        <div class="va-export-menu" role="menu" id="vaExportMenuInner">
                            <button type="button" role="menuitem" id="vaExportCopy">Salin (Copy)</button>
                            <a href="{{ route('keu.saldo.va', array_merge($vaExportQ, ['export' => 'xls'])) }}" role="menuitem">Excel</a>
                            <a href="{{ route('keu.saldo.va', array_merge($vaExportQ, ['export' => 'pdf'])) }}" role="menuitem">Pdf</a>
                            <a href="{{ route('keu.saldo.va', array_merge($vaExportQ, ['export' => 'print'])) }}" role="menuitem" target="_blank" rel="noopener noreferrer">Print</a>
                            <a href="{{ route('keu.saldo.va', array_merge($vaExportQ, ['export' => 'csv'])) }}" role="menuitem">CSV (UTF-8)</a>
                        </div>
                    </div>
                    <a class="va-btn" href="{{ route('keu.saldo.va') }}">Reset</a>
                    <button type="submit" class="va-btn va-btn-search">Cari</button>
                </div>
            </form>

            <div class="va-toolbar">
                <form method="GET" action="{{ route('keu.saldo.va') }}" id="vaToolbarForm" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex:1;justify-content:space-between;">
                    @foreach ($filters as $fk => $fv)
                        @if ($fv !== '' && $fv !== null && $fv !== false)
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span>Tampilkan</span>
                        <select class="va-select" name="per_page" onchange="this.form.submit()">
                            @foreach ([10, 25, 50, 100] as $pp)
                                <option value="{{ $pp }}" {{ ($rowsPaginator->perPage() ?? 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <label for="vaKw" style="font-size:12px;font-weight:700;color:#4b5563;">Cari:</label>
                        <input type="text" name="cari" id="vaKw" class="va-input-search" value="{{ $filters['cari'] ?? '' }}" placeholder="kata kunci pencarian" autocomplete="off" style="min-width:200px;">
                    </div>
                </form>
            </div>

            <div class="va-table-wrap">
                <table class="va-table" id="vaTable">
                    <thead>
                        <tr>
                            <th class="va-ctr">No</th>
                            <th>NIS</th>
                            <th>NO VA</th>
                            <th>NAMA</th>
                            <th>No Pendaftaran</th>
                            <th>UNIT</th>
                            <th>KELAS</th>
                            <th>JENJANG</th>
                            <th>ANGKATAN</th>
                            <th class="va-num">SALDO</th>
                            <th class="va-ctr">Detail</th>
                        </tr>
                    </thead>
                    <tbody id="vaTbody">
                        <tr>
                            <td colspan="11" style="text-align:center;color:#6b7280;padding:20px;">Memuat data…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="va-footer" id="vaFooter">
                <div id="vaFooterInfo"><span style="color:#6b7280;">Menunggu data…</span></div>
                <div style="display:flex;gap:6px;align-items:center;" id="vaFooterNav">
                    <span class="va-page disabled">Sebelumnya</span>
                    <span class="va-page active">{{ $rowsPaginator->currentPage() }}</span>
                    <span class="va-page disabled">Selanjutnya</span>
                </div>
            </div>
        </div>
    </div>

    <p style="margin-top:12px;">
        <a class="btn btn-primary" href="{{ route('keu.saldo.transaksi') }}">Data Transaksi</a>
    </p>

    <script>
        (function () {
            try {
                sessionStorage.setItem('vaListQs', window.location.search || '');
            } catch (e) {}

            var loadUrl = @json($vaRowsUrl ?? '');
            var tbody = document.getElementById('vaTbody');
            var errEl = document.getElementById('vaFetchErr');
            var footerInfo = document.getElementById('vaFooterInfo');
            var footerNav = document.getElementById('vaFooterNav');
            var detailBase = @json($vaDetailBase);
            var exportDd = document.getElementById('vaExportDd');
            var exportBtn = document.getElementById('vaBtnExport');
            var toolbarForm = document.getElementById('vaToolbarForm');
            var kw = document.getElementById('vaKw');
            var debounceTimer;

            if (exportBtn && exportDd) {
                exportBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    exportDd.classList.toggle('open');
                    exportBtn.setAttribute('aria-expanded', exportDd.classList.contains('open') ? 'true' : 'false');
                });
                var exportMenuInner = document.getElementById('vaExportMenuInner');
                if (exportMenuInner) {
                    exportMenuInner.addEventListener('click', function (e) { e.stopPropagation(); });
                }
                document.addEventListener('click', function () { exportDd.classList.remove('open'); });
            }

            var vaExportCopy = document.getElementById('vaExportCopy');
            if (vaExportCopy && exportDd) {
                vaExportCopy.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    exportDd.classList.remove('open');
                    var u = new URL(window.location.href);
                    u.searchParams.set('export', 'json');
                    fetch(u.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    })
                        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                        .then(function (pack) {
                            var j = pack.j || {};
                            if (!pack.ok || !j.ok) {
                                window.alert(j.message || 'Gagal mengambil data untuk disalin.');
                                return;
                            }
                            var rows = j.rows || [];
                            var header = ['NIS', 'NO VA', 'NAMA', 'NO PENDAFTARAN', 'UNIT', 'KELAS', 'JENJANG', 'ANGKATAN', 'SALDO'];
                            var lines = [header.join('\t')];
                            rows.forEach(function (row) {
                                lines.push([
                                    row.nis, row.no_va, row.nama, row.no_pendaftaran, row.unit,
                                    row.kelas, row.jenjang, row.angkatan, String(row.saldo)
                                ].join('\t'));
                            });
                            var text = lines.join('\n');
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                navigator.clipboard.writeText(text).then(function () {
                                    window.alert('Data disalin ke clipboard (tab-separated). Tempel di Excel jika perlu.');
                                }).catch(function () {
                                    window.prompt('Salin teks di bawah ini:', text);
                                });
                            } else {
                                window.prompt('Salin teks di bawah ini:', text);
                            }
                        })
                        .catch(function () { window.alert('Gagal menghubungi server.'); });
                });
            }

            if (toolbarForm && kw) {
                kw.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () { toolbarForm.submit(); }, 450);
                });
            }

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : String(s);
                return d.innerHTML;
            }
            function escAttr(s) {
                return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }
            function fmtRp(n) {
                return 'Rp. ' + Number(n || 0).toLocaleString('id-ID');
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
                            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data.</td></tr>';
                        } else {
                            tbody.innerHTML = rows.map(function (r, idx) {
                                var no = (j.first_item || 0) + idx;
                                var np = (r.no_pendaftaran || '').trim() !== '' ? r.no_pendaftaran : '-';
                                var href = detailBase + (r.custid || 0);
                                return '<tr>' +
                                    '<td class="va-ctr">' + esc(no) + '</td>' +
                                    '<td>' + esc(r.nis || '-') + '</td>' +
                                    '<td>' + esc(r.no_va || '-') + '</td>' +
                                    '<td>' + esc(r.nama || '-') + '</td>' +
                                    '<td>' + esc(np) + '</td>' +
                                    '<td>' + esc(r.unit || '-') + '</td>' +
                                    '<td>' + esc(r.kelas || '-') + '</td>' +
                                    '<td>' + esc(r.jenjang || '-') + '</td>' +
                                    '<td>' + esc(r.angkatan || '-') + '</td>' +
                                    '<td class="va-num">' + fmtRp(r.saldo) + '</td>' +
                                    '<td class="va-ctr"><a class="va-detail-btn" href="' + escAttr(href) + '" title="Detail transaksi">📋</a></td></tr>';
                            }).join('');
                        }
                        if (footerInfo) {
                            footerInfo.innerHTML = 'Menampilkan ' + esc(j.first_item) + ' sampai ' + esc(j.last_item) +
                                ' <span style="color:#6b7280;">(saldo dari sccttran: KREDIT − DEBET)</span>';
                        }
                        if (footerNav) {
                            var prevH = j.prev_url ? '<a class="va-page" href="' + escAttr(j.prev_url) + '">Sebelumnya</a>' : '<span class="va-page disabled">Sebelumnya</span>';
                            var nextH = j.next_url ? '<a class="va-page" href="' + escAttr(j.next_url) + '">Selanjutnya</a>' : '<span class="va-page disabled">Selanjutnya</span>';
                            var cur = esc(String(j.page || 1));
                            footerNav.innerHTML = prevH + '<span class="va-page active">' + cur + '</span>' + nextH;
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
