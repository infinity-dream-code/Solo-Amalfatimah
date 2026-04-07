@extends('layouts.app')

@section('content')
    <style>
        .vd-wrap { margin-top: 16px; }
        .vd-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .vd-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 8px; }
        .vd-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
            padding: 0 16px 16px;
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
        }
        @media (max-width: 768px) { .vd-meta { grid-template-columns: 1fr; } }
        .vd-meta dl { margin: 0; }
        .vd-meta dt { font-weight: 700; color: #6b7280; font-size: 11px; text-transform: uppercase; margin-bottom: 2px; }
        .vd-meta dd { margin: 0; font-weight: 600; color: #111827; }
        .vd-saldo-box {
            text-align: right;
            align-self: center;
            padding: 12px 16px;
            background: #f0fdf4;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
        }
        .vd-saldo-box .lbl { font-size: 12px; color: #166534; font-weight: 700; }
        .vd-saldo-box .val { font-size: 22px; font-weight: 800; color: #15803d; }
        .vd-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .vd-select, .vd-input-search { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .vd-table-wrap { overflow-x: auto; }
        .vd-table { width: 100%; min-width: 720px; border-collapse: collapse; font-size: 12px; }
        .vd-table th, .vd-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .vd-table th { background: #fafbfd; color: #4b5563; font-weight: 700; }
        .vd-num { text-align: right; white-space: nowrap; }
        .vd-ctr { text-align: center; }
        .vd-total-row td { font-weight: 800; background: #f8fafc; border-top: 2px solid #e5e7eb; }
        .vd-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280; }
        .vd-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .vd-page.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .vd-page.disabled { pointer-events: none; opacity: 0.45; }
        .vd-err { margin: 10px 16px; padding: 10px 12px; border-radius: 8px; background: #fef2f2; color: #b91c1c; font-weight: 600; }
        .vd-back { margin: 0 16px 12px; }
        .vd-back a { font-weight: 700; color: #2563eb; text-decoration: none; }
    </style>

    <div class="page-heading">
        <h2>Saldo Virtual Account</h2>
    </div>

    <div class="vd-back">
        <a href="{{ route('keu.saldo.va') }}" id="vdBackToList">← Kembali ke daftar</a>
    </div>
    <script>
        (function () {
            var el = document.getElementById('vdBackToList');
            if (!el) return;
            var vaList = @json(route('keu.saldo.va'));
            try {
                var q = sessionStorage.getItem('vaListQs');
                if (q && q.length) {
                    el.setAttribute('href', vaList + (q.charAt(0) === '?' ? q : '?' + q));
                }
            } catch (e) {}
        })();
    </script>

    <div class="vd-wrap">
        <div class="vd-card">
            <div class="vd-title">Riwayat transaksi</div>
            <div id="vdMeta" class="vd-meta" style="display:none;"></div>
            <div id="vdErr" class="vd-err" style="display:none;"></div>

            <div class="vd-toolbar">
                <form method="GET" id="vdToolbarForm" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex:1;justify-content:space-between;width:100%;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span>Tampilkan</span>
                        <select class="vd-select" name="per_page" id="vdPerPage">
                            @foreach ([10, 25, 50, 100] as $pp)
                                <option value="{{ $pp }}" {{ (int) ($perPage ?? 10) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <label for="vdKw" style="font-size:12px;font-weight:700;color:#4b5563;">Cari:</label>
                        <input type="text" name="cari" id="vdKw" class="vd-input-search" value="{{ $mutasiCari ?? '' }}" placeholder="kata kunci" autocomplete="off" style="min-width:200px;">
                    </div>
                </form>
            </div>

            <div class="vd-table-wrap">
                <table class="vd-table" id="vdTable">
                    <thead>
                        <tr>
                            <th class="vd-ctr">No</th>
                            <th>METODE</th>
                            <th>TANGGAL TRANSAKSI</th>
                            <th class="vd-num">DEBET</th>
                            <th class="vd-num">KREDIT</th>
                        </tr>
                    </thead>
                    <tbody id="vdTbody">
                        <tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">Memuat…</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="vd-footer" id="vdFooter">
                <div id="vdFooterInfo"></div>
                <div style="display:flex;gap:6px;align-items:center;" id="vdFooterNav"></div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var custid = {{ (int) $custid }};
            var loadUrl = @json($mutasiUrl ?? '');
            var tbody = document.getElementById('vdTbody');
            var metaEl = document.getElementById('vdMeta');
            var errEl = document.getElementById('vdErr');
            var footerInfo = document.getElementById('vdFooterInfo');
            var footerNav = document.getElementById('vdFooterNav');
            var kw = document.getElementById('vdKw');
            var perPage = document.getElementById('vdPerPage');
            var debounceTimer;

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
                    }).format(d);
                } catch (e) {
                    return String(dtStr);
                }
            }
            function buildMetaHtml(siswa) {
                if (!siswa) return '';
                var saldo = fmtRp(siswa.saldo || 0);
                return '<dl><dt>Nis</dt><dd>' + esc(siswa.nis || '-') + '</dd></dl>' +
                    '<dl><dt>Nama</dt><dd>' + esc(siswa.nama || '-') + '</dd></dl>' +
                    '<dl><dt>Kelas</dt><dd>' + esc(siswa.kelas || '-') + '</dd></dl>' +
                    '<dl><dt>Angkatan</dt><dd>' + esc(siswa.angkatan || '-') + '</dd></dl>' +
                    '<dl><dt>Nomor Virtual Account</dt><dd>' + esc(siswa.no_va || '-') + '</dd></dl>' +
                    '<div class="vd-saldo-box"><div class="lbl">Total Saldo</div><div class="val">' + saldo + '</div></div>';
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
                                errEl.textContent = j.message || 'Gagal memuat mutasi.';
                            }
                            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#b91c1c;">' + esc(j.message || 'Gagal') + '</td></tr>';
                            return;
                        }
                        if (errEl) errEl.style.display = 'none';
                        var siswa = j.siswa || {};
                        if (metaEl) {
                            metaEl.innerHTML = buildMetaHtml(siswa);
                            metaEl.style.display = 'grid';
                        }
                        var rows = j.rows || [];
                        var totals = j.totals || {};
                        if (rows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#6b7280;">Tidak ada transaksi' +
                                (j.cari ? ' untuk pencarian ini' : '') + '.</td></tr>';
                        } else {
                            var body = rows.map(function (r, idx) {
                                var no = (j.first_item || 0) + idx;
                                return '<tr>' +
                                    '<td class="vd-ctr">' + esc(no) + '</td>' +
                                    '<td>' + esc(r.metode || '-') + '</td>' +
                                    '<td>' + esc(fmtTrx(r.trxdate)) + '</td>' +
                                    '<td class="vd-num">' + (r.debet > 0 ? fmtRp(r.debet) : '-') + '</td>' +
                                    '<td class="vd-num">' + (r.kredit > 0 ? fmtRp(r.kredit) : '-') + '</td></tr>';
                            }).join('');
                            body += '<tr class="vd-total-row">' +
                                '<td colspan="3" class="vd-num">TOTAL</td>' +
                                '<td class="vd-num">' + fmtRp(totals.debet || 0) + '</td>' +
                                '<td class="vd-num">' + fmtRp(totals.kredit || 0) + '</td></tr>';
                            body += '<tr class="vd-total-row">' +
                                '<td colspan="3" class="vd-num">TOTAL SALDO (KREDIT − DEBET)</td>' +
                                '<td colspan="2" class="vd-num">' + fmtRp(totals.saldo || 0) + '</td></tr>';
                            tbody.innerHTML = body;
                        }
                        if (footerInfo) {
                            var extra = j.cari ? ' (disaring dari pencarian)' : '';
                            footerInfo.innerHTML = 'Menampilkan ' + esc(j.first_item) + ' sampai ' + esc(j.last_item) + extra;
                        }
                        if (footerNav) {
                            var prevH = j.prev_url ? '<a class="vd-page" href="' + escAttr(j.prev_url) + '">Sebelumnya</a>' : '<span class="vd-page disabled">Sebelumnya</span>';
                            var nextH = j.next_url ? '<a class="vd-page" href="' + escAttr(j.next_url) + '">Selanjutnya</a>' : '<span class="vd-page disabled">Selanjutnya</span>';
                            footerNav.innerHTML = prevH + '<span class="vd-page active">' + esc(String(j.page || 1)) + '</span>' + nextH;
                            footerNav.querySelectorAll('a.vd-page').forEach(function (a) {
                                a.addEventListener('click', function (ev) {
                                    ev.preventDefault();
                                    fetchRows(a.getAttribute('href'));
                                    try {
                                        history.replaceState(null, '', a.getAttribute('href'));
                                    } catch (e2) {}
                                });
                            });
                        }
                    })
                    .catch(function () {
                        if (errEl) {
                            errEl.style.display = 'block';
                            errEl.textContent = 'Gagal menghubungi server.';
                        }
                    });
            }

            if (perPage) {
                perPage.addEventListener('change', function () {
                    var u = new URL(loadUrl, window.location.origin);
                    u.searchParams.set('per_page', perPage.value);
                    u.searchParams.set('page', '1');
                    fetchRows(u.toString());
                    try { history.replaceState(null, '', u.pathname + u.search); } catch (e) {}
                });
            }
            if (kw) {
                kw.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        var u = new URL(loadUrl, window.location.origin);
                        u.searchParams.set('cari', kw.value || '');
                        u.searchParams.set('page', '1');
                        if (perPage) u.searchParams.set('per_page', perPage.value);
                        fetchRows(u.toString());
                        try { history.replaceState(null, '', u.pathname + u.search); } catch (e) {}
                    }, 450);
                });
            }

            fetchRows(loadUrl);
        })();
    </script>
@endsection
