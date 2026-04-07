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
        .dt-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 4px; }
        .dt-bc { font-size: 12px; color: #6b7280; padding: 0 16px 12px; }
        .dt-sub { font-size: 13px; font-weight: 700; color: #374151; padding: 0 16px 8px; }
        .dt-filter {
            padding: 0 16px 12px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 16px;
            border-bottom: 1px solid #eef2f7;
        }
        @media (max-width: 900px) { .dt-filter { grid-template-columns: 1fr; } }
        .dt-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .dt-fld input, .dt-fld select {
            width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px;
        }
        .dt-actions {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7;
            justify-content: flex-end; align-items: center;
        }
        .dt-btn {
            height: 38px; padding: 0 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;
            border: 1px solid #d1d5db; background: #fff; color: #374151;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .dt-btn-search { background: #2563eb; border-color: #2563eb; color: #fff; }
        .dt-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .dt-select, .dt-input-search { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .dt-table-wrap { overflow-x: auto; }
        .dt-table { width: 100%; min-width: 980px; border-collapse: collapse; font-size: 12px; }
        .dt-table th, .dt-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .dt-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .dt-num { text-align: right; white-space: nowrap; }
        .dt-ctr { text-align: center; }
        .dt-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280; }
        .dt-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .dt-page.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .dt-page.disabled { pointer-events: none; opacity: 0.45; }
        .dt-alert { margin: 10px 16px 0; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; }
        .dt-err { background: #fef2f2; color: #b91c1c; }
        .dt-hint { padding: 0 16px 10px; font-size: 11px; color: #6b7280; }
    </style>

    <div class="page-heading">
        <h2>{{ $pageTitle ?? 'Data Transaksi' }}</h2>
    </div>

    <div class="dt-wrap">
        <div class="dt-card">
            <div class="dt-title">Transaksi Saldo</div>
            <div class="dt-bc">Beranda &rsaquo; Keuangan &rsaquo; Saldo &rsaquo; Data Transaksi</div>

            <div id="dtFetchErr" class="dt-alert dt-err" style="display:none;"></div>

            <form method="GET" action="{{ route('keu.saldo.transaksi') }}" id="formDtFilter">
                <div class="dt-sub">Filter</div>
                <div class="dt-filter">
                    <div class="dt-fld">
                        <label>Dari tanggal</label>
                        <input type="date" name="tgl_dari" value="{{ $filters['tgl_dari'] ?? '' }}">
                    </div>
                    <div class="dt-fld">
                        <label>Sampai tanggal</label>
                        <input type="date" name="tgl_sampai" value="{{ $filters['tgl_sampai'] ?? '' }}">
                    </div>
                    <div class="dt-fld">
                        <label>Sekolah / unit</label>
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
                    <div class="dt-fld">
                        <label>NIS</label>
                        <input type="text" name="nis" value="{{ $filters['nis'] ?? '' }}" placeholder="Masukkan NIS siswa" autocomplete="off">
                    </div>
                    <div class="dt-fld">
                        <label>Nama</label>
                        <input type="text" name="nama" value="{{ $filters['nama'] ?? '' }}" placeholder="Masukkan nama siswa" autocomplete="off">
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
                </div>
                <p class="dt-hint">Sumber: <strong>sccttran</strong>.</p>
                <div class="dt-actions">
                    <a class="dt-btn" href="{{ route('keu.saldo.transaksi') }}">Reset</a>
                    <button type="submit" class="dt-btn dt-btn-search">Cari</button>
                </div>
            </form>

            <div class="dt-toolbar">
                <form method="GET" action="{{ route('keu.saldo.transaksi') }}" id="dtToolbarForm" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex:1;justify-content:flex-end;width:100%;">
                    @foreach ($filters as $fk => $fv)
                        @if ($fk !== 'cari' && $fv !== '' && $fv !== null && $fv !== false)
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <label for="dtKw" style="font-size:12px;font-weight:700;color:#4b5563;">Cari:</label>
                    <input type="text" name="cari" id="dtKw" class="dt-input-search" value="{{ $filters['cari'] ?? '' }}" placeholder="kata kunci pencarian" autocomplete="off" style="min-width:220px;">
                </form>
            </div>

            <div class="dt-table-wrap">
                <table class="dt-table" id="dtTable">
                    <thead>
                        <tr>
                            <th class="dt-ctr">No</th>
                            <th>NIS</th>
                            <th>NO VA</th>
                            <th>NAMA</th>
                            <th>METODE</th>
                            <th>TANGGAL TRANSAKSI</th>
                            <th class="dt-num">DEBET</th>
                            <th class="dt-num">KREDIT</th>
                        </tr>
                    </thead>
                    <tbody id="dtTbody">
                        <tr>
                            <td colspan="8" style="text-align:center;color:#6b7280;padding:20px;">Memuat data…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="dt-footer" id="dtFooter">
                <div style="display:flex;gap:6px;align-items:center;" id="dtFooterNav">
                    <span class="dt-page disabled">Sebelumnya</span>
                    <span class="dt-page active">{{ $listPage ?? 1 }}</span>
                    <span class="dt-page disabled">Selanjutnya</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var loadUrl = @json($transaksiRowsUrl ?? '');
            var tbody = document.getElementById('dtTbody');
            var errEl = document.getElementById('dtFetchErr');
            var footerNav = document.getElementById('dtFooterNav');
            var toolbarForm = document.getElementById('dtToolbarForm');
            var kw = document.getElementById('dtKw');
            var debounceTimer;

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
            function fmtTrx(dtStr) {
                if (!dtStr) return '-';
                var s = String(dtStr).replace(' ', 'T');
                var d = new Date(s);
                if (isNaN(d.getTime())) return String(dtStr);
                try {
                    return new Intl.DateTimeFormat('id-ID', {
                        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                        hour: '2-digit', minute: '2-digit', hour12: false
                    }).format(d).replace(',', '') + '';
                } catch (e) {
                    return String(dtStr);
                }
            }

            function bindFooterNav() {
                if (!footerNav) return;
                footerNav.querySelectorAll('a.dt-page').forEach(function (a) {
                    a.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        fetchRows(a.getAttribute('href'));
                    });
                });
            }
            function pageUrlFrom(baseUrl, pageNo) {
                var u = new URL(baseUrl, window.location.origin);
                u.searchParams.set('page', String(pageNo));
                return u.toString();
            }

            function fetchRows(url) {
                if (!url || !tbody) return;
                fetch(url, {
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
                            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#b91c1c;padding:20px;">' +
                                esc(j.message || 'Gagal memuat data.') + '</td></tr>';
                            return;
                        }
                        if (errEl) errEl.style.display = 'none';
                        var rows = j.rows || [];
                        if (rows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data.</td></tr>';
                        } else {
                            tbody.innerHTML = rows.map(function (r, idx) {
                                var no = (j.first_item || 0) + idx;
                                return '<tr>' +
                                    '<td class="dt-ctr">' + esc(no) + '</td>' +
                                    '<td>' + esc(r.nis || '-') + '</td>' +
                                    '<td>' + esc(r.no_va || '-') + '</td>' +
                                    '<td>' + esc(r.nama || '-') + '</td>' +
                                    '<td>' + esc(r.metode || '-') + '</td>' +
                                    '<td>' + esc(fmtTrx(r.trxdate)) + '</td>' +
                                    '<td class="dt-num">' + fmtRp(r.debet) + '</td>' +
                                    '<td class="dt-num">' + fmtRp(r.kredit) + '</td></tr>';
                            }).join('');
                        }
                        if (footerNav) {
                            var prevH = j.prev_url ? '<a class="dt-page" href="' + escAttr(j.prev_url) + '">Sebelumnya</a>' : '<span class="dt-page disabled">Sebelumnya</span>';
                            var nextH = j.next_url ? '<a class="dt-page" href="' + escAttr(j.next_url) + '">Selanjutnya</a>' : '<span class="dt-page disabled">Selanjutnya</span>';
                            var curPage = Number(j.page || 1);
                            var fromPage = Math.max(1, curPage - 2);
                            var pageH = '';
                            for (var p = fromPage; p <= curPage; p++) {
                                if (p === curPage) {
                                    pageH += '<span class="dt-page active">' + esc(String(p)) + '</span>';
                                } else {
                                    pageH += '<a class="dt-page" href="' + escAttr(pageUrlFrom(url, p)) + '">' + esc(String(p)) + '</a>';
                                }
                            }
                            if (j.has_more) {
                                var pNextNum = curPage + 1;
                                pageH += '<a class="dt-page" href="' + escAttr(pageUrlFrom(url, pNextNum)) + '">' + esc(String(pNextNum)) + '</a>';
                            }
                            footerNav.innerHTML = prevH + pageH + nextH;
                            bindFooterNav();
                        }
                        try {
                            var u = new URL(url, window.location.origin);
                            var path = u.pathname.replace(/\/data-transaksi\/rows\/?$/, '/data-transaksi');
                            if (u.searchParams.get('page') === '1') {
                                u.searchParams.delete('page');
                            }
                            history.replaceState(null, '', path + u.search);
                        } catch (e3) {}
                    })
                    .catch(function () {
                        if (errEl) {
                            errEl.style.display = 'block';
                            errEl.textContent = 'Gagal menghubungi server.';
                        }
                        if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#b91c1c;padding:20px;">Gagal menghubungi server.</td></tr>';
                    });
            }

            if (loadUrl && tbody) {
                fetchRows(loadUrl);
            }
        })();
    </script>
@endsection
