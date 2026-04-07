@extends('layouts.app')

@section('content')
    <style>
        .cp-wrap { margin-top: 16px; }
        .cp-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .cp-title { font-size: 20px; font-weight: 800; color: #111827; padding: 14px 16px 4px; }
        .cp-bc { font-size: 12px; color: #6b7280; padding: 0 16px 12px; }
        .cp-filter {
            padding: 0 16px 12px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px 16px;
            border-bottom: 1px solid #eef2f7;
        }
        @media (max-width: 980px) { .cp-filter { grid-template-columns: 1fr; } }
        .cp-fld label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
        .cp-fld input, .cp-fld select {
            width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 13px;
        }
        .cp-actions {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #eef2f7;
            justify-content: flex-end; align-items: center;
        }
        .cp-btn {
            height: 38px; padding: 0 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;
            border: 1px solid #d1d5db; background: #fff; color: #374151; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .cp-btn-search { background: #2563eb; border-color: #2563eb; color: #fff; }
        .cp-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #eef2f7; }
        .cp-select { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; font-size: 12px; }
        .cp-search { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #4b5563; }
        .cp-search input { height: 34px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 10px; min-width: 220px; font-size: 12px; }
        .cp-table-wrap { overflow-x: auto; }
        .cp-table { width: 100%; min-width: 980px; border-collapse: collapse; font-size: 12px; }
        .cp-table th, .cp-table td { border-bottom: 1px solid #eef2f7; padding: 8px 6px; text-align: left; vertical-align: middle; }
        .cp-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .cp-num { text-align: right; white-space: nowrap; }
        .cp-center { text-align: center; }
        .cp-check { width: 36px; text-align: center; }
        .cp-check input { width: 16px; height: 16px; cursor: pointer; vertical-align: middle; }
        .cp-pill {
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }
        .cp-pill-ok { background: #dcfce7; color: #15803d; }
        .cp-pill-no { background: #fee2e2; color: #b91c1c; }
        .cp-footer { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280; }
        .cp-page { min-width: 30px; height: 30px; border: 1px solid #d1d5db; border-radius: 999px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #4b5563; font-weight: 700; background: #fff; }
        .cp-page.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .cp-page.disabled { pointer-events: none; opacity: 0.45; }
        .cp-alert { margin: 10px 16px 0; padding: 10px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; background: #fef2f2; color: #b91c1c; display: none; }
    </style>

    <div class="page-heading">
        <h2>{{ $pageTitle ?? 'Cek Pelunasan' }}</h2>
    </div>

    <div class="cp-wrap">
        <div class="cp-card">
            <div class="cp-title">Cek Pelunasan</div>
            <div class="cp-bc">Beranda &rsaquo; Rekap Data &rsaquo; Cek Pelunasan</div>
            <div id="cpFetchErr" class="cp-alert"></div>
            @if (session('export_error'))
                <div class="cp-alert" style="display:block;">{{ session('export_error') }}</div>
            @endif

            <form method="GET" action="{{ route('rekap.cek_pelunasan') }}" id="formCpFilter">
                <div class="cp-filter">
                    <div class="cp-fld">
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
                    <div class="cp-fld">
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
                    <div class="cp-fld">
                        <label>NIS</label>
                        <input type="text" name="nis" value="{{ $filters['nis'] ?? '' }}" placeholder="nis" autocomplete="off">
                    </div>
                    <div class="cp-fld">
                        <label>Tahun Angkatan</label>
                        <select name="thn_angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                                <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cp-fld">
                        <label>Nama</label>
                        <input type="text" name="nama" value="{{ $filters['nama'] ?? '' }}" placeholder="nama" autocomplete="off">
                    </div>
                    <div class="cp-fld">
                        <label>Nama Tagihan</label>
                        <select name="nama_tagihan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['tagihan'] ?? []) as $tag)
                                <option value="{{ $tag }}" {{ (($filters['nama_tagihan'] ?? '') === $tag) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="cp-actions">
                    <button type="button" class="cp-btn" id="cpBtnKartu">Cetak Kartu Siswa</button>
                    <a class="cp-btn" href="{{ route('rekap.cek_pelunasan') }}">Reset</a>
                    <button type="submit" class="cp-btn cp-btn-search">Cari</button>
                </div>
            </form>

            <div class="cp-toolbar">
                <form method="GET" action="{{ route('rekap.cek_pelunasan') }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    @foreach ($filters as $fk => $fv)
                        @if ($fk !== 'cari' && $fv !== '' && $fv !== null && $fv !== false)
                            <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                        @endif
                    @endforeach
                    <span>Tampilkan</span>
                    <select class="cp-select" name="per_page" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" {{ ($rowsPaginator->perPage() ?? 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </form>

                <div class="cp-search">
                    <span>Cari:</span>
                    <input type="text" id="cpSearchInput" placeholder="kata kunci pencarian" value="{{ $filters['cari'] ?? '' }}" autocomplete="off">
                </div>
            </div>

            <div class="cp-table-wrap">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th class="cp-check"><input type="checkbox" id="cpSelAll" aria-label="Pilih semua di halaman ini"></th>
                            <th class="cp-center">No</th>
                            <th>Tahun Pelajaran</th>
                            <th>NIS</th>
                            <th>No Pendaftaran</th>
                            <th>NAMA</th>
                            <th>Nama Tagihan</th>
                            <th class="cp-num">Tagihan</th>
                            <th class="cp-center">Lunas</th>
                        </tr>
                    </thead>
                    <tbody id="cpTbody">
                        <tr>
                            <td colspan="9" style="text-align:center;color:#6b7280;padding:20px;">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="cp-footer">
                <div id="cpFooterInfo"><span style="color:#6b7280;">Menunggu data...</span></div>
                <div style="display:flex;gap:6px;align-items:center;" id="cpFooterNav">
                    <span class="cp-page disabled">Sebelumnya</span>
                    <span class="cp-page active">{{ $rowsPaginator->currentPage() }}</span>
                    <span class="cp-page disabled">Selanjutnya</span>
                </div>
            </div>
        </div>
    </div>

    <form id="cpFormKartu" method="POST" action="{{ route('rekap.cek_pelunasan.kartu_siswa') }}" target="_blank" style="display:none;" aria-hidden="true">
        @csrf
    </form>

    <script>
        (function () {
            var loadUrl = @json($cekPelunasanRowsUrl ?? '');
            var tbody = document.getElementById('cpTbody');
            var errEl = document.getElementById('cpFetchErr');
            var footerInfo = document.getElementById('cpFooterInfo');
            var footerNav = document.getElementById('cpFooterNav');
            var quickSearch = document.getElementById('cpSearchInput');
            var searchTimer = null;
            var cpTable = document.querySelector('.cp-table');

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
            function escAttr(s) {
                return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }

            function collectCheckedCustIds() {
                var ids = [];
                document.querySelectorAll('.cp-row-cb:checked').forEach(function (cb) {
                    var id = parseInt(cb.getAttribute('data-custid') || '0', 10);
                    if (id > 0) ids.push(id);
                });
                return Array.from(new Set(ids));
            }

            if (cpTable) {
                cpTable.addEventListener('change', function (e) {
                    var t = e.target;
                    if (t && t.id === 'cpSelAll') {
                        var on = t.checked;
                        document.querySelectorAll('.cp-row-cb').forEach(function (cb) { cb.checked = on; });
                    }
                });
            }

            var btnKartu = document.getElementById('cpBtnKartu');
            var formKartu = document.getElementById('cpFormKartu');
            if (btnKartu && formKartu) {
                btnKartu.addEventListener('click', function () {
                    var ids = collectCheckedCustIds();
                    if (ids.length === 0) {
                        alert('Pilih siswa dulu (centang baris) sebelum cetak kartu.');
                        return;
                    }
                    formKartu.querySelectorAll('input[name="custids[]"]').forEach(function (el) { el.remove(); });
                    ids.forEach(function (id) {
                        var inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'custids[]';
                        inp.value = String(id);
                        formKartu.appendChild(inp);
                    });
                    formKartu.submit();
                });
            }

            if (quickSearch) {
                quickSearch.addEventListener('input', function () {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function () {
                        var u = new URL(window.location.href);
                        var v = String(quickSearch.value || '').trim();
                        if (v) u.searchParams.set('cari', v);
                        else u.searchParams.delete('cari');
                        u.searchParams.delete('page');
                        window.location.href = u.toString();
                    }, 450);
                });
            }

            if (!loadUrl || !tbody) return;

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
                        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#b91c1c;padding:20px;">' +
                            esc(j.message || 'Gagal memuat data.') + '</td></tr>';
                        return;
                    }

                    var rows = j.rows || [];
                    if (rows.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#6b7280;padding:20px;">Tidak ada data untuk filter ini.</td></tr>';
                    } else {
                        var start = Number(j.first_item || 1);
                        tbody.innerHTML = rows.map(function (r, idx) {
                            var lunas = Number(r.lunas || 0) === 1;
                            return '<tr>' +
                                '<td class="cp-check"><input type="checkbox" class="cp-row-cb" data-custid="' + escAttr(String(r.custid || 0)) + '"></td>' +
                                '<td class="cp-center">' + esc(String(start + idx)) + '</td>' +
                                '<td>' + esc(r.tahun_pelajaran || '-') + '</td>' +
                                '<td>' + esc(r.nis || '-') + '</td>' +
                                '<td>' + esc(r.no_pendaftaran || '-') + '</td>' +
                                '<td>' + esc(r.nama || '-') + '</td>' +
                                '<td>' + esc(r.nama_tagihan || '-') + '</td>' +
                                '<td class="cp-num">' + fmtRp(r.tagihan) + '</td>' +
                                '<td class="cp-center"><span class="cp-pill ' + (lunas ? 'cp-pill-ok' : 'cp-pill-no') + '">' + (lunas ? 'LUNAS' : 'BELUM') + '</span></td>' +
                                '</tr>';
                        }).join('');
                    }

                    if (footerInfo) {
                        footerInfo.textContent = 'Menampilkan ' + esc(j.first_item) + ' sampai ' + esc(j.last_item);
                    }
                    if (footerNav) {
                        var prevH = j.prev_url ? '<a class="cp-page" href="' + escAttr(j.prev_url) + '">Sebelumnya</a>' : '<span class="cp-page disabled">Sebelumnya</span>';
                        var nextH = j.next_url ? '<a class="cp-page" href="' + escAttr(j.next_url) + '">Selanjutnya</a>' : '<span class="cp-page disabled">Selanjutnya</span>';
                        var cur = esc(String(j.page || 1));
                        footerNav.innerHTML = prevH + '<span class="cp-page active">' + cur + '</span>' + nextH;
                    }
                })
                .catch(function () {
                    if (errEl) {
                        errEl.style.display = 'block';
                        errEl.textContent = 'Gagal menghubungi server.';
                    }
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#b91c1c;padding:20px;">Gagal menghubungi server.</td></tr>';
                });
        })();
    </script>
@endsection

