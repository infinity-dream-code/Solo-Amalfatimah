@extends('layouts.app')

@section('content')
    <style>
        .ht-wrap { margin-top: 16px; }
        .ht-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .ht-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 4px; }
        .ht-bc { font-size: 12px; color: #6b7280; padding: 0 16px 12px; }
        .ht-filter {
            padding: 0 16px 12px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 16px;
            border-bottom: 1px solid #eef2f7;
        }
        @media (max-width: 900px) { .ht-filter { grid-template-columns: 1fr; } }
        .ht-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .ht-fld input, .ht-fld select {
            width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px;
        }
        .ht-actions {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7;
            justify-content: flex-end; align-items: center;
        }
        .ht-btn {
            height: 38px; padding: 0 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;
            border: 1px solid #d1d5db; background: #fff; color: #374151;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .ht-btn-search { background: #2563eb; border-color: #2563eb; color: #fff; }
        .ht-btn-del { background: #dc2626; border-color: #dc2626; color: #fff; }
        .ht-btn-del:disabled { opacity: 0.55; cursor: not-allowed; }
        .ht-notes { padding: 0 16px 10px; font-size: 12px; color: #b91c1c; font-weight: 600; }
        .ht-notes li { margin-bottom: 4px; }
        .ht-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .ht-select { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .ht-table-wrap { overflow-x: auto; }
        .ht-table { width: 100%; min-width: 960px; border-collapse: collapse; font-size: 12px; }
        .ht-table th, .ht-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .ht-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .ht-num { text-align: right; white-space: nowrap; }
        .ht-check { width: 36px; text-align: center; }
        .ht-check input { width: 16px; height: 16px; cursor: pointer; vertical-align: middle; }
        .ht-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280; }
        .ht-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .ht-page.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .ht-page.disabled { pointer-events: none; opacity: 0.45; }
        .ht-alert { margin: 10px 16px 0; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; }
        .ht-err { background: #fef2f2; color: #b91c1c; }
        .ht-ok { background: #ecfdf5; color: #047857; }
    </style>

    <div class="page-heading">
        <h2>{{ $pageTitle ?? 'Hapus Tagihan Siswa' }}</h2>
    </div>

    <div class="ht-wrap">
        <div class="ht-card">
            <div class="ht-title">Hapus Tagihan Siswa</div>
            <div class="ht-bc">Beranda &rsaquo; Hapus Tagihan Siswa</div>

            @if (session('hapus_error'))
                <div class="ht-alert ht-err">{{ session('hapus_error') }}</div>
            @endif
            @if (session('hapus_ok'))
                <div class="ht-alert ht-ok">{{ session('hapus_ok') }}</div>
            @endif
            <div id="htFetchErr" class="ht-alert ht-err" style="display:none;"></div>

            <form method="GET" action="{{ route('keu.hapus_tagihan') }}" id="formHtFilter">
                <div class="ht-filter">
                    <div class="ht-fld">
                        <label>Tanggal Pembuatan</label>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <input type="date" name="tgl_dari" value="{{ $filters['tgl_dari'] ?? '' }}" style="flex:1;min-width:140px;">
                            <span style="color:#6b7280;font-size:12px;">s/d</span>
                            <input type="date" name="tgl_sampai" value="{{ $filters['tgl_sampai'] ?? '' }}" style="flex:1;min-width:140px;">
                        </div>
                    </div>
                    <div class="ht-fld">
                        <label>Angkatan Siswa</label>
                        <select name="thn_angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ht-fld">
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
                    <div class="ht-fld">
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
                    <div class="ht-fld">
                        <label>Nama Tagihan</label>
                        <select name="nama_tagihan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['tagihan'] ?? []) as $tag)
                                <option value="{{ $tag }}" {{ (($filters['nama_tagihan'] ?? '') === $tag) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ht-fld">
                        <label>Siswa</label>
                        <input type="text" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Masukkan NIS/NAMA Siswa" autocomplete="off">
                    </div>
                </div>

                <ul class="ht-notes">
                    <li>Hanya tagihan <strong>belum lunas</strong> yang bisa dihapus (rincian lalu header tagihan dihapus dari database).</li>
                    <li>Pastikan browser anda tidak memblokir POP-UP!</li>
                </ul>

                <div class="ht-actions">
                    <button type="button" class="ht-btn ht-btn-del" id="htBtnHapus" disabled>Hapus Tagihan</button>
                    <a class="ht-btn" href="{{ route('keu.hapus_tagihan') }}">Reset</a>
                    <button type="submit" class="ht-btn ht-btn-search">Cari</button>
                </div>
            </form>

            <div class="ht-toolbar">
                <form method="GET" action="{{ route('keu.hapus_tagihan') }}" id="htToolbarForm" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    @foreach ($filters as $fk => $fv)
                        @if ($fv !== '' && $fv !== null && $fv !== false)
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <span>Tampilkan</span>
                    <select class="ht-select" name="per_page" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" {{ ($rowsPaginator->perPage() ?? 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </form>
            </div>

            <div class="ht-table-wrap">
                <table class="ht-table" id="htTable">
                    <thead>
                        <tr>
                            <th class="ht-check"><input type="checkbox" id="htSelAll" aria-label="Pilih semua di halaman ini"></th>
                            <th>NIS</th>
                            <th>NAMA</th>
                            <th>UNIT</th>
                            <th>KELAS</th>
                            <th>NAMA TAGIHAN</th>
                            <th class="ht-num">TAGIHAN</th>
                            <th>TAHUN AKA</th>
                        </tr>
                    </thead>
                    <tbody id="htTbody">
                        <tr id="htLoadingRow">
                            <td colspan="8" style="text-align:center;color:#6b7280;padding:20px;">Memuat data…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="ht-footer" id="htFooter">
                <div id="htFooterInfo"><span style="color:#6b7280;">Menunggu data…</span></div>
                <div style="display:flex;gap:6px;align-items:center;" id="htFooterNav">
                    <span class="ht-page disabled">Sebelumnya</span>
                    <span class="ht-page active">{{ $rowsPaginator->currentPage() }}</span>
                    <span class="ht-page disabled">Selanjutnya</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var loadUrl = @json($hapusTagihanRowsUrl ?? '');
            var tbody = document.getElementById('htTbody');
            var errEl = document.getElementById('htFetchErr');
            var footerInfo = document.getElementById('htFooterInfo');
            var footerNav = document.getElementById('htFooterNav');
            var btnHapus = document.getElementById('htBtnHapus');
            var htTable = document.getElementById('htTable');
            var csrf = @json(csrf_token());
            var submitUrl = @json(route('keu.hapus_tagihan.submit'));

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

            function htUpdateDelButton() {
                if (!btnHapus) return;
                var n = document.querySelectorAll('.ht-row-cb:checked').length;
                btnHapus.disabled = n === 0;
            }

            function htCollectItems() {
                var items = [];
                document.querySelectorAll('.ht-row-cb:checked').forEach(function (cb) {
                    items.push({
                        custid: parseInt(cb.getAttribute('data-custid') || '0', 10),
                        billcd: String(cb.getAttribute('data-billcd') || '').trim()
                    });
                });
                return items.filter(function (x) { return x.custid > 0 && x.billcd !== ''; });
            }

            if (htTable) {
                htTable.addEventListener('change', function (e) {
                    var t = e.target;
                    if (t && t.id === 'htSelAll') {
                        var on = t.checked;
                        document.querySelectorAll('.ht-row-cb').forEach(function (cb) { cb.checked = on; });
                    }
                    htUpdateDelButton();
                });
                htTable.addEventListener('change', function (e) {
                    if (e.target && e.target.classList.contains('ht-row-cb')) {
                        htUpdateDelButton();
                    }
                });
            }

            if (btnHapus) {
                btnHapus.addEventListener('click', function () {
                    var items = htCollectItems();
                    if (items.length === 0) {
                        alert('Centang minimal satu tagihan.');
                        return;
                    }
                    if (!confirm('Hapus ' + items.length + ' tagihan terpilih? Tindakan ini permanen.')) {
                        return;
                    }
                    btnHapus.disabled = true;
                    fetch(submitUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ items: items })
                    })
                        .then(function (r) { return r.json().then(function (j) { return { okHttp: r.ok, j: j }; }); })
                        .then(function (pack) {
                            var j = pack.j || {};
                            if (!pack.okHttp || !j.ok) {
                                alert(j.message || 'Gagal menghapus.');
                                btnHapus.disabled = false;
                                htUpdateDelButton();
                                return;
                            }
                            var d = j.data || {};
                            var msg = 'Berhasil menghapus ' + (d.deleted || 0) + ' tagihan.';
                            if (d.failed && d.failed.length) {
                                msg += '\n' + d.failed.length + ' baris gagal.';
                            }
                            alert(msg);
                            window.location.reload();
                        })
                        .catch(function () {
                            alert('Gagal menghubungi server.');
                            btnHapus.disabled = false;
                            htUpdateDelButton();
                        });
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
                            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#b91c1c;padding:20px;">' +
                                esc(j.message || 'Gagal memuat data.') + '</td></tr>';
                            if (footerInfo) footerInfo.innerHTML = '';
                            return;
                        }
                        var rows = j.rows || [];
                        if (rows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada tagihan belum lunas untuk filter ini.</td></tr>';
                        } else {
                            tbody.innerHTML = rows.map(function (r) {
                                var cid = esc(String(r.custid || 0));
                                var bcd = escAttr(String(r.billcd || ''));
                                return '<tr class="ht-data-row">' +
                                    '<td class="ht-check"><input type="checkbox" class="ht-row-cb" data-custid="' + cid + '" data-billcd="' + bcd + '"></td>' +
                                    '<td>' + esc(r.nis || '-') + '</td>' +
                                    '<td>' + esc(r.nama || '-') + '</td>' +
                                    '<td>' + esc(r.unit || '-') + '</td>' +
                                    '<td>' + esc(r.kelas || '-') + '</td>' +
                                    '<td>' + esc(r.nama_tagihan || '-') + '</td>' +
                                    '<td class="ht-num">' + fmtRp(r.tagihan) + '</td>' +
                                    '<td>' + esc(r.tahun_aka || '-') + '</td></tr>';
                            }).join('');
                        }
                        if (footerInfo) {
                            footerInfo.innerHTML = 'Menampilkan ' + esc(j.first_item) + ' sampai ' + esc(j.last_item) +
                                ' <span style="color:#6b7280;">(hanya belum lunas)</span>';
                        }
                        if (footerNav) {
                            var prevH = j.prev_url ? '<a class="ht-page" href="' + escAttr(j.prev_url) + '">Sebelumnya</a>' : '<span class="ht-page disabled">Sebelumnya</span>';
                            var nextH = j.next_url ? '<a class="ht-page" href="' + escAttr(j.next_url) + '">Selanjutnya</a>' : '<span class="ht-page disabled">Selanjutnya</span>';
                            var cur = esc(String(j.page || 1));
                            footerNav.innerHTML = prevH + '<span class="ht-page active">' + cur + '</span>' + nextH;
                        }
                        htUpdateDelButton();
                    })
                    .catch(function () {
                        if (errEl) {
                            errEl.style.display = 'block';
                            errEl.textContent = 'Gagal menghubungi server.';
                        }
                        if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#b91c1c;padding:20px;">Gagal menghubungi server.</td></tr>';
                    });
            }
        })();
    </script>
@endsection
