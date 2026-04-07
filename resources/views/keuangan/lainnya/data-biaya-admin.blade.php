@extends('layouts.app')

@section('content')
    <style>
        .ba-wrap { margin-top: 16px; }
        .ba-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, .05); overflow: hidden; }
        .ba-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 4px; }
        .ba-bc { font-size: 12px; color: #6b7280; padding: 0 16px 12px; }
        .ba-filter { padding: 0 16px 12px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 16px; border-bottom: 1px solid #eef2f7; }
        @media (max-width: 900px) { .ba-filter { grid-template-columns: 1fr; } }
        .ba-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .ba-fld input { width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px; }
        .ba-actions { display: flex; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; justify-content: flex-end; }
        .ba-btn { height: 38px; padding: 0 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; border: 1px solid #d1d5db; background: #fff; color: #374151; text-decoration: none; display: inline-flex; align-items: center; }
        .ba-btn-search { background: #2563eb; border-color: #2563eb; color: #fff; }
        .ba-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .ba-select, .ba-input-search { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .ba-table-wrap { overflow-x: auto; }
        .ba-table { width: 100%; min-width: 980px; border-collapse: collapse; font-size: 12px; }
        .ba-table th, .ba-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .ba-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .ba-num { text-align: right; white-space: nowrap; }
        .ba-ctr { text-align: center; }
        .ba-total-row td { font-weight: 800; background: #f8fafc; border-top: 2px solid #e5e7eb; }
        .ba-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; gap: 8px; font-size: 12px; color: #6b7280; }
        .ba-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .ba-page.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .ba-page.disabled { pointer-events: none; opacity: .45; }
        .ba-alert { margin: 10px 16px 0; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; }
        .ba-err { background: #fef2f2; color: #b91c1c; }
    </style>

    <div class="page-heading"><h2>{{ $pageTitle ?? 'Data Biaya Admin' }}</h2></div>

    <div class="ba-wrap">
        <div class="ba-card">
            <div class="ba-title">Data Biaya Admin</div>
            <div class="ba-bc">Beranda &rsaquo; Keuangan &rsaquo; Data Biaya Admin</div>
            <div id="baFetchErr" class="ba-alert ba-err" style="display:none;"></div>

            <form method="GET" action="{{ route('keu.biaya_admin') }}" id="formBaFilter">
                <div class="ba-filter">
                    <div class="ba-fld"><label>Dari Tanggal</label><input type="date" name="tgl_dari" value="{{ $filters['tgl_dari'] ?? '' }}"></div>
                    <div class="ba-fld"><label>Sampai Tanggal</label><input type="date" name="tgl_sampai" value="{{ $filters['tgl_sampai'] ?? '' }}"></div>
                </div>
                <div class="ba-actions">
                    <a class="ba-btn" href="{{ route('keu.biaya_admin') }}">Reset</a>
                    <button type="submit" class="ba-btn ba-btn-search">Cari</button>
                </div>
            </form>

            <div class="ba-toolbar">
                <form method="GET" action="{{ route('keu.biaya_admin') }}" id="baToolbarForm" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex:1;justify-content:space-between;">
                    @foreach ($filters as $fk => $fv)
                        @if ($fk !== 'cari' && $fv !== '' && $fv !== null && $fv !== false)
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span>Tampilkan</span>
                        <select class="ba-select" name="per_page" onchange="this.form.submit()">
                            @foreach ([10, 25, 50, 100] as $pp)
                                <option value="{{ $pp }}" {{ ($rowsPaginator->perPage() ?? 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <label for="baKw" style="font-size:12px;font-weight:700;color:#4b5563;">Cari:</label>
                        <input type="text" name="cari" id="baKw" class="ba-input-search" value="{{ $filters['cari'] ?? '' }}" placeholder="kata kunci pencarian" autocomplete="off" style="min-width:200px;">
                    </div>
                </form>
            </div>

            <div class="ba-table-wrap">
                <table class="ba-table">
                    <thead>
                        <tr>
                            <th class="ba-ctr">No</th>
                            <th>SEKOLAH</th>
                            <th>NIS</th>
                            <th>NAMA</th>
                            <th>TANGGAL</th>
                            <th class="ba-num">NOMINAL</th>
                            <th>NO. INVOICE</th>
                        </tr>
                    </thead>
                    <tbody id="baTbody">
                        <tr><td colspan="7" style="text-align:center;color:#6b7280;padding:20px;">Memuat data…</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="ba-footer">
                <div id="baFooterInfo"></div>
                <div style="display:flex;gap:6px;align-items:center;" id="baFooterNav"></div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var loadUrl = @json($biayaAdminRowsUrl ?? '');
            var tbody = document.getElementById('baTbody');
            var errEl = document.getElementById('baFetchErr');
            var footerInfo = document.getElementById('baFooterInfo');
            var footerNav = document.getElementById('baFooterNav');
            var toolbarForm = document.getElementById('baToolbarForm');
            var kw = document.getElementById('baKw');
            var debounceTimer;
            var activeReq = null;
            var isLoading = false;

            if (toolbarForm && kw) {
                kw.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () { toolbarForm.submit(); }, 450);
                });
            }

            function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
            function escAttr(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }
            function fmtRp(n) { return 'Rp. ' + Number(n || 0).toLocaleString('id-ID'); }
            function fmtTgl(v) {
                if (!v) return '-';
                var s = String(v).replace(' ', 'T');
                var d = new Date(s);
                if (isNaN(d.getTime())) return String(v);
                try {
                    return new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).format(d);
                } catch (e) { return String(v); }
            }

            if (footerNav) {
                footerNav.addEventListener('click', function (ev) {
                    var a = ev.target.closest('a.ba-page');
                    if (!a) return;
                    ev.preventDefault();
                    fetchRows(a.getAttribute('href'));
                });
            }

            function fetchRows(url) {
                if (!url || !tbody || isLoading) return;
                isLoading = true;
                if (activeReq) {
                    try { activeReq.abort(); } catch (e0) {}
                }
                activeReq = new AbortController();
                fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: activeReq.signal
                })
                    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                    .then(function (pack) {
                        var j = pack.j || {};
                        if (!pack.ok || !j.ok) {
                            if (errEl) { errEl.style.display = 'block'; errEl.textContent = j.message || 'Gagal memuat data.'; }
                            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#b91c1c;padding:20px;">' + esc(j.message || 'Gagal') + '</td></tr>';
                            return;
                        }
                        if (errEl) errEl.style.display = 'none';
                        var rows = j.rows || [];
                        if (rows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data.</td></tr>';
                        } else {
                            var html = rows.map(function (r, idx) {
                                var no = (j.first_item || 0) + idx;
                                var nominal = Number(r.nominal || 0);
                                return '<tr>' +
                                    '<td class="ba-ctr">' + esc(no) + '</td>' +
                                    '<td>' + esc(r.sekolah || '-') + '</td>' +
                                    '<td>' + esc(r.nis || '-') + '</td>' +
                                    '<td>' + esc(r.nama || '-') + '</td>' +
                                    '<td>' + esc(fmtTgl(r.tanggal)) + '</td>' +
                                    '<td class="ba-num">' + fmtRp(nominal) + '</td>' +
                                    '<td>' + esc(r.no_invoice || '-') + '</td>' +
                                    '</tr>';
                            }).join('');
                            var totalAll = Number(j.total_nominal_all || 0);
                            html += '<tr class="ba-total-row"><td colspan="5" class="ba-num">TOTAL</td><td class="ba-num">' + fmtRp(totalAll) + '</td><td></td></tr>';
                            tbody.innerHTML = html;
                        }
                        if (footerInfo) {
                            footerInfo.innerHTML = 'Menampilkan ' + esc(j.first_item) + ' sampai ' + esc(j.last_item) + ' (disaring dari ' + esc(j.total_filtered || 0) + ' entri)';
                        }
                        if (footerNav) {
                            var prevH = j.prev_url ? '<a class="ba-page" href="' + escAttr(j.prev_url) + '">Sebelumnya</a>' : '<span class="ba-page disabled">Sebelumnya</span>';
                            var nextH = j.next_url ? '<a class="ba-page" href="' + escAttr(j.next_url) + '">Selanjutnya</a>' : '<span class="ba-page disabled">Selanjutnya</span>';
                            footerNav.innerHTML = prevH + '<span class="ba-page active">' + esc(String(j.page || 1)) + '</span>' + nextH;
                        }
                        isLoading = false;
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') {
                            isLoading = false;
                            return;
                        }
                        if (errEl) { errEl.style.display = 'block'; errEl.textContent = 'Gagal menghubungi server.'; }
                        isLoading = false;
                    });
            }

            fetchRows(loadUrl);
        })();
    </script>
@endsection

