@extends('layouts.app')

@section('content')
    <style>
        .em-wrap { margin-top: 16px; }
        .em-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .em-title { font-size: 22px; font-weight: 800; color: #111827; padding: 16px 18px 4px; letter-spacing: -0.02em; }
        .em-bc { font-size: 12px; color: #6b7280; padding: 0 18px 14px; }
        .em-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 0;
            align-items: start;
            border-top: 1px solid #eef2f7;
        }
        @media (max-width: 1100px) {
            .em-layout { grid-template-columns: 1fr; }
            .em-side { border-left: none !important; border-top: 1px solid #eef2f7; }
        }
        .em-main { padding: 0 18px 18px; }
        .em-side {
            border-left: 1px solid #eef2f7;
            padding: 16px 18px 18px;
            background: #fafbfd;
            min-height: 200px;
        }
        .em-search-block { padding: 16px 18px; border-bottom: 1px solid #eef2f7; }
        .em-search-block label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
        }
        .em-search-row { display: flex; gap: 10px; align-items: stretch; flex-wrap: wrap; }
        .em-search-row input[type="text"] {
            flex: 1;
            min-width: 200px;
            height: 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 12px;
            font-size: 14px;
        }
        .em-btn {
            height: 40px;
            padding: 0 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .em-btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .em-btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
        .em-btn-save { width: 100%; justify-content: center; margin-top: 12px; padding: 12px; height: auto; font-size: 14px; }
        .em-section-title {
            font-size: 13px;
            font-weight: 800;
            color: #1f2937;
            margin: 18px 0 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .em-table-wrap { overflow-x: auto; border: 1px solid #eef2f7; border-radius: 10px; background: #fff; }
        .em-table { width: 100%; min-width: 640px; border-collapse: collapse; font-size: 12px; }
        .em-table th, .em-table td {
            border-bottom: 1px solid #eef2f7;
            padding: 10px 10px;
            text-align: left;
            vertical-align: middle;
        }
        .em-table th { background: #f8fafc; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .em-table tbody tr:last-child td { border-bottom: none; }
        .em-num { text-align: right; white-space: nowrap; }
        .em-ctr { text-align: center; }
        .em-check { width: 40px; text-align: center; }
        .em-check input { width: 16px; height: 16px; cursor: pointer; vertical-align: middle; }
        .em-empty {
            text-align: center;
            color: #6b7280;
            padding: 28px 16px !important;
            font-size: 13px;
        }
        .em-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px 12px;
            font-size: 12px;
            color: #6b7280;
            background: #fafbfd;
            border-top: 1px solid #eef2f7;
        }
        .em-page {
            min-width: 32px;
            height: 32px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #4b5563;
            background: #fff;
            cursor: not-allowed;
            opacity: 0.5;
        }
        .em-page.em-page-active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
            opacity: 1;
            cursor: default;
        }
        a.em-page {
            text-decoration: none;
            cursor: pointer;
            opacity: 1;
        }
        a.em-page:hover { background: #f3f4f6; }
        .em-side label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px; }
        .em-side select, .em-side input[type="text"] {
            width: 100%;
            height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 10px;
            font-size: 13px;
            background: #fff;
        }
        .em-side .em-field { margin-bottom: 12px; }
        .em-detail-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 12px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .em-detail-table th, .em-detail-table td { border-bottom: 1px solid #eef2f7; padding: 8px 10px; text-align: left; }
        .em-detail-table th { background: #f1f5f9; font-weight: 700; color: #475569; }
        .em-detail-table tbody tr:last-child td { border-bottom: none; }
        .em-detail-empty { color: #9ca3af; font-style: italic; text-align: center; padding: 16px !important; }
        .em-bill-row { cursor: pointer; }
        .em-bill-row:hover { background: #f8fafc; }
        .em-bill-row.em-bill-selected { background: #eff6ff; }
        .em-line-kode { width: 100%; min-width: 140px; height: 34px; font-size: 12px; }
        .em-line-am { width: 100%; text-align: right; height: 34px; font-size: 12px; }
        .em-btn-ghost { background: #fff; color: #b91c1c; border-color: #fecaca; font-size: 12px; padding: 0 10px; height: 32px; }
        .em-btn-ghost:hover { background: #fef2f2; }
        .em-alert { margin: 0 18px 12px; padding: 10px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; }
        .em-alert-ok { background: #ecfdf5; color: #047857; }
    </style>

    <div class="page-heading">
        <h2>{{ $pageTitle ?? 'Edit Detail Post Manual' }}</h2>
    </div>

    <div class="em-wrap">
        <div class="em-card">
            <div class="em-title">Edit Detail Post Manual</div>
            <div class="em-bc">Beranda &rsaquo; Manual Input &rsaquo; Edit Detail Post Manual</div>

            @if (session('status'))
                <div class="em-alert em-alert-ok">{{ session('status') }}</div>
            @endif

            <div class="em-search-block">
                <label for="emSearchNis">Nis/No Daftar Siswa</label>
                <div class="em-search-row">
                    <input type="text" id="emSearchNis" name="q" placeholder="Masukkan Nis/No Daftar siswa" autocomplete="off">
                    <button type="button" class="em-btn em-btn-primary" id="emBtnCari" title="Cari siswa">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        Cari
                    </button>
                </div>
            </div>

            <div class="em-layout">
                <div class="em-main">
                    <div class="em-section-title" style="margin-top: 0;">Data siswa</div>
                    <div class="em-table-wrap">
                        <table class="em-table" id="emTableSiswa">
                            <thead>
                                <tr>
                                    <th class="em-check"><input type="checkbox" id="emSelAllSiswa" disabled aria-label="Pilih semua"></th>
                                    <th>NIS</th>
                                    <th>NAMA</th>
                                    <th>KELAS</th>
                                    <th>JENJANG</th>
                                    <th>ANGKATAN</th>
                                </tr>
                            </thead>
                            <tbody id="emTbodySiswa">
                                <tr>
                                    <td colspan="6" class="em-empty">Tidak ada siswa yang sesuai kriteria pencarian</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="em-footer" id="emFooterSiswa">
                            <span id="emInfoSiswa"><span id="emRangeText">Menampilkan 0 sampai 0 dari 0 entri</span> — <span id="emSelectedCount">0</span> baris dipilih</span>
                            <div id="emNavSiswa" style="display:flex;gap:8px;align-items:center;"></div>
                        </div>
                    </div>

                    <div class="em-section-title">Tagihan yang tampil di bank <span style="font-weight:600;text-transform:none;letter-spacing:0;color:#6b7280;">(belum lunas)</span></div>
                    <div class="em-table-wrap">
                        <table class="em-table" id="emTableTagihan">
                            <thead>
                                <tr>
                                    <th>NAMA TAGIHAN</th>
                                    <th class="em-num">JUMLAH</th>
                                    <th>TAHUN PELAJARAN</th>
                                    <th class="em-ctr">BAYAR</th>
                                </tr>
                            </thead>
                            <tbody id="emTbodyTagihan">
                                <tr>
                                    <td colspan="4" class="em-empty">Tidak ada siswa yang sesuai kriteria pencarian</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="em-footer" id="emFooterTagihan">
                            <span id="emInfoTagihan">0 tagihan belum lunas</span>
                        </div>
                    </div>

                    <div class="em-section-title">Tagihan yang tampil di bank <span style="font-weight:600;text-transform:none;letter-spacing:0;color:#6b7280;">(lunas)</span></div>
                    <div class="em-table-wrap">
                        <table class="em-table" id="emTableTagihan2">
                            <thead>
                                <tr>
                                    <th>NAMA TAGIHAN</th>
                                    <th class="em-num">JUMLAH</th>
                                    <th>TAHUN PELAJARAN</th>
                                    <th class="em-ctr">BAYAR</th>
                                </tr>
                            </thead>
                            <tbody id="emTbodyTagihan2">
                                <tr>
                                    <td colspan="4" class="em-empty">Tidak ada siswa yang sesuai kriteria pencarian</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="em-footer">
                            <span id="emInfoTagihan2">0 tagihan lunas</span>
                        </div>
                    </div>
                </div>

                <aside class="em-side">
                    <div class="em-field">
                        <label for="emAkun">AKUN</label>
                        <select id="emAkun">
                            <option value="">Pilih Akun</option>
                            @foreach ($akunOptions ?? [] as $o)
                                <option value="{{ $o['kode'] }}">{{ $o['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="em-field">
                        <label for="emNominal">NOMINAL</label>
                        <input type="text" id="emNominal" placeholder="0" inputmode="numeric" autocomplete="off">
                    </div>
                    <button type="button" class="em-btn em-btn-primary" id="emBtnBuatDetail" style="width:100%;justify-content:center;">Buat Detail</button>

                    <table class="em-detail-table" id="emTableDetail">
                        <thead>
                            <tr>
                                <th>NAMA POST / AKUN</th>
                                <th class="em-num">NOMINAL</th>
                                <th class="em-ctr" style="width:88px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="emTbodyDetail">
                            <tr>
                                <td colspan="3" class="em-detail-empty" id="emDetailPlaceholder">Silakan pilih tagihan</td>
                            </tr>
                        </tbody>
                    </table>

                    <form id="emFormSimpan">
                        @csrf
                        <button type="button" class="em-btn em-btn-primary em-btn-save" id="emBtnSimpan">Simpan Data</button>
                    </form>
                </aside>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var rowsBaseUrl = @json($siswaRowsUrl ?? '');
            var billsUrl = @json($billsUrl ?? '');
            var billDetailUrl = @json($billDetailUrl ?? '');
            var saveBillDetailUrl = @json($saveBillDetailUrl ?? '');
            var akunOpts = @json($akunOptions ?? []);

            var tbodySiswa = document.getElementById('emTbodySiswa');
            var rangeText = document.getElementById('emRangeText');
            var navSiswa = document.getElementById('emNavSiswa');
            var searchInput = document.getElementById('emSearchNis');
            var btnCari = document.getElementById('emBtnCari');
            var selAll = document.getElementById('emSelAllSiswa');
            var selectedCountEl = document.getElementById('emSelectedCount');
            var tbodyTagihan = document.getElementById('emTbodyTagihan');
            var tbodyTagihan2 = document.getElementById('emTbodyTagihan2');
            var emInfoTagihan = document.getElementById('emInfoTagihan');
            var emInfoTagihan2 = document.getElementById('emInfoTagihan2');

            var tbodyDetail = document.getElementById('emTbodyDetail');
            var akun = document.getElementById('emAkun');
            var nominal = document.getElementById('emNominal');
            var btnBuat = document.getElementById('emBtnBuatDetail');
            var btnSimpan = document.getElementById('emBtnSimpan');
            var emFormSimpan = document.getElementById('emFormSimpan');
            var emSidePanel = document.getElementById('emSidePanel');

            var siswaPerPage = 10;
            var siswaAbort = null;
            var siswaLoading = false;
            var billsAbort = null;
            var detailAbort = null;

            var selCustid = null;
            var selBillcd = null;
            var selPaidst = 0;
            var selReadOnly = true;

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : String(s);
                return d.innerHTML;
            }
            function escAttr(s) {
                return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }

            function parseNominal(s) {
                var t = String(s || '').replace(/\./g, '').replace(/,/g, '.').trim();
                var n = parseFloat(t);
                return isNaN(n) ? NaN : Math.round(n);
            }

            function fmtRp(n) {
                return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
            }

            function getCsrf() {
                var inp = emFormSimpan ? emFormSimpan.querySelector('input[name="_token"]') : null;
                return inp ? inp.value : '';
            }

            function updateSelectedCount() {
                var n = document.querySelectorAll('.em-row-siswa:checked').length;
                if (selectedCountEl) selectedCountEl.textContent = String(n);
                onSiswaSelectionChanged();
            }

            function clearBillSelectionHighlight() {
                document.querySelectorAll('.em-bill-row.em-bill-selected').forEach(function (tr) {
                    tr.classList.remove('em-bill-selected');
                });
            }

            function setTagihanPlaceholder(msg) {
                var html = '<tr><td colspan="4" class="em-empty">' + esc(msg) + '</td></tr>';
                if (tbodyTagihan) tbodyTagihan.innerHTML = html;
                if (tbodyTagihan2) tbodyTagihan2.innerHTML = html;
                if (emInfoTagihan) emInfoTagihan.textContent = '0 tagihan belum lunas';
                if (emInfoTagihan2) emInfoTagihan2.textContent = '0 tagihan lunas';
            }

            function resetDetailPanel(msg) {
                selBillcd = null;
                selPaidst = 0;
                selReadOnly = true;
                clearBillSelectionHighlight();
                if (!tbodyDetail) return;
                tbodyDetail.innerHTML = '<tr><td colspan="3" class="em-detail-empty">' + esc(msg || 'Silakan pilih tagihan') + '</td></tr>';
                applySideEditState();
            }

            function applySideEditState() {
                var ro = selReadOnly || !selBillcd;
                if (akun) akun.disabled = ro;
                if (nominal) nominal.disabled = ro;
                if (btnBuat) btnBuat.disabled = ro;
                if (btnSimpan) btnSimpan.disabled = ro || !selBillcd;
            }

            function akunSelectHtml(selectedKode) {
                var h = '<option value="">Pilih</option>';
                (akunOpts || []).forEach(function (o) {
                    var k = o.kode || '';
                    var sel = k === selectedKode ? ' selected' : '';
                    h += '<option value="' + escAttr(k) + '"' + sel + '>' + esc(o.label || k) + '</option>';
                });
                return h;
            }

            function renderDetailLines(lines) {
                if (!tbodyDetail) return;
                tbodyDetail.innerHTML = '';
                var paid = selPaidst === 1;
                (lines || []).forEach(function (ln) {
                    var kode = String(ln.kode_post || '').trim();
                    var nam = String(ln.nama_akun || '').trim();
                    var am = Number(ln.billam || 0);
                    var tr = document.createElement('tr');
                    if (paid) {
                        tr.innerHTML = '<td>' + esc(nam || kode || '-') + '</td>'
                            + '<td class="em-num">' + esc(fmtRp(am)) + '</td>'
                            + '<td class="em-ctr">—</td>';
                    } else {
                        tr.innerHTML = '<td><select class="em-line-kode">' + akunSelectHtml(kode) + '</select></td>'
                            + '<td class="em-num"><input type="text" class="em-line-am" value="' + escAttr(String(am)) + '" inputmode="numeric" /></td>'
                            + '<td class="em-ctr"><button type="button" class="em-btn em-btn-ghost em-line-del">Hapus</button></td>';
                    }
                    tbodyDetail.appendChild(tr);
                });
                if (tbodyDetail.rows.length === 0) {
                    tbodyDetail.innerHTML = '<tr><td colspan="3" class="em-detail-empty">Belum ada detail</td></tr>';
                }
                applySideEditState();
            }

            function collectLinesFromDom() {
                var out = [];
                if (!tbodyDetail) return out;
                tbodyDetail.querySelectorAll('tr').forEach(function (tr) {
                    if (tr.querySelector('.em-detail-empty')) return;
                    var sel = tr.querySelector('.em-line-kode');
                    var inp = tr.querySelector('.em-line-am');
                    if (!sel || !inp) return;
                    var kp = String(sel.value || '').trim();
                    var am = parseNominal(inp.value);
                    if (kp === '' || isNaN(am) || am <= 0) return;
                    out.push({ kode_post: kp, billam: am });
                });
                return out;
            }

            function fetchBillDetail(custid, billcd, paidstFromRow) {
                if (!billDetailUrl || custid <= 0 || !billcd) return;
                if (detailAbort) detailAbort.abort();
                detailAbort = new AbortController();
                selBillcd = billcd;
                selPaidst = paidstFromRow ? 1 : 0;
                selReadOnly = selPaidst === 1;
                applySideEditState();
                if (tbodyDetail) {
                    tbodyDetail.innerHTML = '<tr><td colspan="3" class="em-detail-empty">Memuat detail…</td></tr>';
                }

                var u = new URL(billDetailUrl, window.location.origin);
                u.searchParams.set('custid', String(custid));
                u.searchParams.set('billcd', billcd);

                fetch(u.toString(), {
                    signal: detailAbort.signal,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json().then(function (j) { return { okHttp: r.ok, j: j }; }); })
                    .then(function (pack) {
                        var j = pack.j || {};
                        if (!pack.okHttp || !j.ok) {
                            resetDetailPanel(j.message || 'Gagal memuat detail');
                            alert(j.message || 'Gagal memuat detail');
                            return;
                        }
                        selPaidst = Number(j.paidst || 0);
                        selReadOnly = selPaidst === 1;
                        renderDetailLines(j.lines || []);
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') return;
                        resetDetailPanel('Gagal memuat detail');
                        alert('Gagal memuat detail.');
                    });
            }

            function renderBillTables(unpaid, paid) {
                clearBillSelectionHighlight();
                selBillcd = null;
                selPaidst = 0;
                selReadOnly = true;
                if (emInfoTagihan) emInfoTagihan.textContent = String((unpaid || []).length) + ' tagihan belum lunas';
                if (emInfoTagihan2) emInfoTagihan2.textContent = String((paid || []).length) + ' tagihan lunas';

                function rowHtml(r, paidSt) {
                    var bcd = escAttr(String(r.billcd || ''));
                    var ps = paidSt ? '1' : '0';
                    return '<tr class="em-bill-row" data-billcd="' + bcd + '" data-paidst="' + ps + '" data-jumlah="' + escAttr(String(r.jumlah || 0)) + '">'
                        + '<td>' + esc(r.nama_tagihan || '-') + '</td>'
                        + '<td class="em-num">' + esc(fmtRp(r.jumlah || 0)) + '</td>'
                        + '<td>' + esc(r.tahun_pelajaran || '-') + '</td>'
                        + '<td class="em-ctr">' + esc(r.bayar || '') + '</td>'
                        + '</tr>';
                }

                if (tbodyTagihan) {
                    if (!unpaid || unpaid.length === 0) {
                        tbodyTagihan.innerHTML = '<tr><td colspan="4" class="em-empty">Tidak ada tagihan belum lunas</td></tr>';
                    } else {
                        tbodyTagihan.innerHTML = unpaid.map(function (r) { return rowHtml(r, false); }).join('');
                    }
                }
                if (tbodyTagihan2) {
                    if (!paid || paid.length === 0) {
                        tbodyTagihan2.innerHTML = '<tr><td colspan="4" class="em-empty">Tidak ada tagihan lunas</td></tr>';
                    } else {
                        tbodyTagihan2.innerHTML = paid.map(function (r) { return rowHtml(r, true); }).join('');
                    }
                }
                resetDetailPanel('Silakan pilih tagihan');
            }

            function fetchBillsForCustid(custid) {
                if (!billsUrl || custid <= 0) return;
                if (billsAbort) billsAbort.abort();
                billsAbort = new AbortController();
                setTagihanPlaceholder('Memuat tagihan…');

                var u = new URL(billsUrl, window.location.origin);
                u.searchParams.set('custid', String(custid));

                fetch(u.toString(), {
                    signal: billsAbort.signal,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json().then(function (j) { return { okHttp: r.ok, j: j }; }); })
                    .then(function (pack) {
                        var j = pack.j || {};
                        if (!pack.okHttp || !j.ok) {
                            setTagihanPlaceholder(j.message || 'Gagal memuat tagihan');
                            return;
                        }
                        renderBillTables(j.unpaid || [], j.paid || []);
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') return;
                        setTagihanPlaceholder('Gagal memuat tagihan');
                    });
            }

            function onSiswaSelectionChanged() {
                var boxes = document.querySelectorAll('.em-row-siswa:checked');
                if (boxes.length !== 1) {
                    selCustid = null;
                    if (boxes.length === 0) {
                        setTagihanPlaceholder('Pilih siswa di tabel di atas (centang tepat satu baris) untuk memuat tagihan.');
                    } else {
                        setTagihanPlaceholder('Centang tepat satu siswa untuk memuat tagihan.');
                    }
                    resetDetailPanel('Silakan pilih tagihan');
                    return;
                }
                var cid = parseInt(boxes[0].getAttribute('data-custid') || '0', 10);
                if (isNaN(cid) || cid <= 0) return;
                selCustid = cid;
                fetchBillsForCustid(cid);
            }

            function renderSiswaNav(j) {
                if (!navSiswa) return;
                var page = Number(j.page || 1);
                var prevU = j.prev_url || '';
                var nextU = j.next_url || '';
                var prevH = prevU
                    ? '<a class="em-page" href="' + escAttr(prevU) + '" data-em-nav="prev">Sebelumnya</a>'
                    : '<span class="em-page">Sebelumnya</span>';
                var nextH = nextU
                    ? '<a class="em-page" href="' + escAttr(nextU) + '" data-em-nav="next">Selanjutnya</a>'
                    : '<span class="em-page">Selanjutnya</span>';
                navSiswa.innerHTML = prevH + '<span class="em-page em-page-active">' + esc(String(page)) + '</span>' + nextH;
            }

            function applySiswaInfo(j, nRows) {
                if (!rangeText) return;
                var total = Number(j.total || 0);
                var page = Number(j.page || 1);
                var pp = Number(j.per_page || siswaPerPage);
                var first = nRows > 0 ? (page - 1) * pp + 1 : 0;
                var last = nRows > 0 ? first + nRows - 1 : 0;
                rangeText.textContent = 'Menampilkan ' + first + ' sampai ' + last + ' dari ' + total + ' entri';
            }

            function fetchSiswa(page) {
                if (!rowsBaseUrl || !tbodySiswa) return;
                var q = String(searchInput && searchInput.value ? searchInput.value : '').trim();
                if (q === '') {
                    alert('Isi NIS, nama, atau no. daftar siswa terlebih dahulu.');
                    return;
                }
                if (siswaAbort) siswaAbort.abort();
                siswaAbort = new AbortController();
                siswaLoading = true;
                tbodySiswa.innerHTML = '<tr><td colspan="6" class="em-empty">Memuat data…</td></tr>';
                if (navSiswa) navSiswa.innerHTML = '';

                var u = new URL(rowsBaseUrl, window.location.origin);
                u.searchParams.set('q', q);
                u.searchParams.set('page', String(page));
                u.searchParams.set('per_page', String(siswaPerPage));

                fetch(u.toString(), {
                    signal: siswaAbort.signal,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json().then(function (j) { return { okHttp: r.ok, j: j }; }); })
                    .then(function (pack) {
                        siswaLoading = false;
                        var j = pack.j || {};
                        if (!pack.okHttp || !j.ok) {
                            tbodySiswa.innerHTML = '<tr><td colspan="6" class="em-empty">' + esc(j.message || 'Gagal memuat data siswa.') + '</td></tr>';
                            if (selAll) { selAll.checked = false; selAll.disabled = true; }
                            setTagihanPlaceholder('Tidak ada siswa yang sesuai kriteria pencarian');
                            return;
                        }
                        var rows = j.rows || [];
                        if (rows.length === 0) {
                            tbodySiswa.innerHTML = '<tr><td colspan="6" class="em-empty">Tidak ada siswa yang sesuai kriteria pencarian</td></tr>';
                            if (selAll) { selAll.checked = false; selAll.disabled = true; }
                            renderSiswaNav(j);
                            applySiswaInfo(j, 0);
                            setTagihanPlaceholder('Tidak ada siswa yang sesuai kriteria pencarian');
                            return;
                        }
                        if (selAll) selAll.disabled = false;
                        tbodySiswa.innerHTML = rows.map(function (r) {
                            var cid = escAttr(String(r.custid || 0));
                            return '<tr>'
                                + '<td class="em-check"><input type="checkbox" class="em-row-siswa" data-custid="' + cid + '"></td>'
                                + '<td>' + esc(r.nis || '-') + '</td>'
                                + '<td>' + esc(r.nama || '-') + '</td>'
                                + '<td>' + esc(r.kelas || '-') + '</td>'
                                + '<td>' + esc(r.jenjang || '-') + '</td>'
                                + '<td>' + esc(r.angkatan || '-') + '</td>'
                                + '</tr>';
                        }).join('');
                        renderSiswaNav(j);
                        applySiswaInfo(j, rows.length);
                        updateSelectedCount();
                        setTagihanPlaceholder('Pilih siswa di tabel di atas (centang tepat satu baris) untuk memuat tagihan.');
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') return;
                        siswaLoading = false;
                        tbodySiswa.innerHTML = '<tr><td colspan="6" class="em-empty">Gagal menghubungi server.</td></tr>';
                    });
            }

            if (navSiswa) {
                navSiswa.addEventListener('click', function (e) {
                    var a = e.target.closest('a[data-em-nav]');
                    if (!a || !a.getAttribute('href')) return;
                    e.preventDefault();
                    if (siswaLoading) return;
                    var href = a.getAttribute('href');
                    try {
                        var u = new URL(href, window.location.origin);
                        var p = parseInt(u.searchParams.get('page') || '1', 10);
                        fetchSiswa(isNaN(p) ? 1 : p);
                    } catch (err) {
                        fetchSiswa(1);
                    }
                });
            }

            if (selAll) {
                selAll.addEventListener('change', function () {
                    var on = selAll.checked;
                    document.querySelectorAll('.em-row-siswa').forEach(function (cb) { cb.checked = on; });
                    updateSelectedCount();
                });
            }

            var tblSiswa = document.getElementById('emTableSiswa');
            if (tblSiswa) {
                tblSiswa.addEventListener('change', function (e) {
                    if (e.target && e.target.classList.contains('em-row-siswa')) {
                        updateSelectedCount();
                    }
                });
            }

            function bindTagihanTableClicks(tbody) {
                if (!tbody) return;
                tbody.addEventListener('click', function (e) {
                    var tr = e.target.closest('tr.em-bill-row');
                    if (!tr || !selCustid) return;
                    var bcd = tr.getAttribute('data-billcd') || '';
                    var ps = parseInt(tr.getAttribute('data-paidst') || '0', 10);
                    if (!bcd) return;
                    clearBillSelectionHighlight();
                    tr.classList.add('em-bill-selected');
                    fetchBillDetail(selCustid, bcd, ps === 1);
                });
            }
            bindTagihanTableClicks(tbodyTagihan);
            bindTagihanTableClicks(tbodyTagihan2);

            if (tbodyDetail) {
                tbodyDetail.addEventListener('click', function (e) {
                    var btn = e.target.closest('.em-line-del');
                    if (!btn || selReadOnly) return;
                    var tr = btn.closest('tr');
                    if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
                    if (tbodyDetail.rows.length === 0) {
                        tbodyDetail.innerHTML = '<tr><td colspan="3" class="em-detail-empty">Belum ada detail</td></tr>';
                    }
                });
            }

            function runCari() {
                fetchSiswa(1);
            }

            if (btnCari) btnCari.addEventListener('click', runCari);
            if (searchInput) {
                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        runCari();
                    }
                });
            }

            if (btnBuat && tbodyDetail && akun && nominal) {
                btnBuat.addEventListener('click', function () {
                    if (selReadOnly || !selBillcd) {
                        alert('Pilih tagihan yang belum lunas terlebih dahulu.');
                        return;
                    }
                    var kode = String(akun.value || '').trim();
                    if (!kode) {
                        alert('Pilih akun terlebih dahulu.');
                        return;
                    }
                    var num = parseNominal(nominal.value);
                    if (isNaN(num) || num <= 0) {
                        alert('Isi nominal yang valid.');
                        return;
                    }
                    var emptyPh = tbodyDetail.querySelector('.em-detail-empty');
                    if (emptyPh && emptyPh.parentNode) emptyPh.parentNode.removeChild(emptyPh);
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td><select class="em-line-kode">' + akunSelectHtml(kode) + '</select></td>'
                        + '<td class="em-num"><input type="text" class="em-line-am" value="' + escAttr(String(num)) + '" inputmode="numeric" /></td>'
                        + '<td class="em-ctr"><button type="button" class="em-btn em-btn-ghost em-line-del">Hapus</button></td>';
                    tbodyDetail.appendChild(tr);
                    nominal.value = '';
                });
            }

            if (btnSimpan && saveBillDetailUrl) {
                btnSimpan.addEventListener('click', function () {
                    if (selReadOnly || !selBillcd || !selCustid) {
                        alert('Pilih tagihan yang belum lunas dan pastikan detail sudah benar.');
                        return;
                    }
                    var lines = collectLinesFromDom();
                    btnSimpan.disabled = true;
                    fetch(saveBillDetailUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': getCsrf()
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            _token: getCsrf(),
                            custid: selCustid,
                            billcd: selBillcd,
                            lines: lines
                        })
                    })
                        .then(function (r) { return r.json().then(function (j) { return { okHttp: r.ok, j: j }; }); })
                        .then(function (pack) {
                            var j = pack.j || {};
                            if (!pack.okHttp || !j.ok) {
                                alert(j.message || 'Gagal menyimpan');
                                applySideEditState();
                                return;
                            }
                            alert(j.message || 'Berhasil simpan.');
                            var newAm = Number(j.billam || 0);
                            document.querySelectorAll('.em-bill-row.em-bill-selected').forEach(function (tr) {
                                var cells = tr.querySelectorAll('td');
                                if (cells.length >= 2) cells[1].textContent = fmtRp(newAm);
                            });
                            fetchBillDetail(selCustid, selBillcd, false);
                        })
                        .catch(function () {
                            alert('Gagal menyimpan.');
                            applySideEditState();
                        })
                        .finally(function () {
                            applySideEditState();
                        });
                });
            }

            applySideEditState();
        })();
    </script>
@endsection
