@extends('layouts.app')

@php
    $mpMode = $mpMode ?? 'pendaftaran';
    $mpIsNis = $mpMode === 'nis';
    $mpIsNonSiswa = $mpMode === 'non_siswa';
    $mpGetRoute = match ($mpMode) {
        'nis' => 'keu.manual_nis',
        'non_siswa' => 'keu.manual_non_siswa',
        default => 'keu.manual',
    };
    $mpPostRoute = match ($mpMode) {
        'nis' => 'keu.manual_nis.submit',
        'non_siswa' => 'keu.manual_non_siswa.submit',
        default => 'keu.manual.submit',
    };
@endphp

@section('content')
    <div class="page-heading">
        <h2>
            @if ($mpIsNis)
                Pembayaran Manual NIS
            @elseif ($mpIsNonSiswa)
                Pembayaran Manual No Pendaftaran
            @else
                Pembayaran Manual
            @endif
        </h2>
        <p>
            Keuangan /
            @if ($mpIsNis)
                Pembayaran Manual NIS — <b>NIS</b> di sini = nomor <b>NOCUST</b> (bukan kolom NIS terpisah). No. pendaftaran tidak dipakai.
            @elseif ($mpIsNonSiswa)
                Pembayaran Manual No Pendaftaran — hanya <b>No. Pendaftaran</b> atau nama.
            @else
                Pembayaran Manual — cari dengan NIS, no. pendaftaran (NUM2ND), NOCUST, atau nama.
            @endif
        </p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            @if (session('status'))
                <div style="margin-bottom:12px;color:#047857;font-weight:600;">{{ session('status') }}</div>
            @endif
            @if (session('manual_pembayaran_error'))
                <div style="margin-bottom:12px;color:#b91c1c;font-weight:600;">{{ session('manual_pembayaran_error') }}</div>
            @endif
            @if (!empty($manualPembayaranError ?? ''))
                <div style="margin-bottom:12px;color:#b91c1c;font-weight:600;">{{ $manualPembayaranError }}</div>
            @endif

            <style>
                .mp-tagihan-table { width:100%; border-collapse:separate; border-spacing:0; min-width:960px; font-size:14px; }
                .mp-tagihan-table thead th {
                    background:#f3f4f6; color:#374151; font-weight:600; text-align:left;
                    padding:10px 12px; border-bottom:1px solid #e5e7eb; white-space:nowrap;
                }
                .mp-tagihan-table tbody td {
                    padding:10px 12px; border-bottom:1px solid #f3f4f6; vertical-align:middle;
                }
                .mp-tagihan-table tbody tr:nth-child(even) { background:#fafafa; }
                .mp-tagihan-table tbody tr:hover { background:#f0fdf4; }
                .mp-tagihan-table .mp-th-check { width:44px; text-align:center; }
                .mp-tagihan-table .mp-col-tagihan {
                    text-align:right; white-space:nowrap; font-variant-numeric: tabular-nums;
                    min-width:8.5rem;
                }
                .mp-tagihan-table .mp-col-tagihan .mp-rp { color:#6b7280; font-weight:500; }
                .mp-tagihan-table .mp-col-tagihan .mp-amt { margin-left:4px; font-weight:600; color:#111827; display:inline-block; }
                .mp-tagihan-table .mp-col-thn {
                    text-align:center; white-space:nowrap; min-width:5rem;
                    font-variant-numeric: tabular-nums; color:#374151;
                }
                .mp-tagihan-table .mp-col-nominal { position:relative; z-index:1; }
                .mp-tagihan-table .mp-col-nominal .bill-nominal-input {
                    width:100%; max-width:9rem; padding:8px 10px; border:1px solid var(--border); border-radius:8px;
                    font-variant-numeric: tabular-nums; text-align:right;
                    background:#f9fafb; color:var(--text);
                    cursor:default;
                }
            </style>

            <form method="GET" action="{{ route($mpGetRoute) }}">
                <div style="display:grid;gap:12px;">
                    <div>
                        <div style="font-weight:700;margin-bottom:6px;">Siswa</div>
                        <div id="siswaAutoWrap" style="position:relative;">
                            <input id="siswaSearchInput" autocomplete="off" name="siswa_search" value="{{ $selectedSiswaLabel !== '' ? $selectedSiswaLabel : ($filters['siswa_search'] ?? '') }}" placeholder="@if ($mpIsNis) NIS / Nama @elseif ($mpIsNonSiswa) No. Pendaftaran / Nama @else NIS / No. Pendaftaran / Nama @endif" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:10px;">
                            <div id="siswaAutoList" style="display:none;position:absolute;left:0;right:0;top:calc(100% + 4px);z-index:50;background:#fff;border:1px solid #d1d5db;border-radius:10px;max-height:220px;overflow:auto;box-shadow:0 8px 24px rgba(0,0,0,.12);"></div>
                        </div>
                        <div style="margin-top:6px;color:#6b7280;font-size:12px;">
                            @if ($mpIsNis)
                                Hanya <b>NIS</b> atau nama siswa. No. pendaftaran <b>tidak</b> dipakai di halaman ini.
                            @elseif ($mpIsNonSiswa)
                                Ketik <b>No. Pendaftaran</b> atau nama, pilih dari dropdown, lalu klik <b>Cari Tagihan</b>.
                            @else
                                Ketik <b>NIS</b>, <b>No. Pendaftaran</b>, atau nama, pilih dari dropdown, lalu klik <b>Cari Tagihan</b>.
                            @endif
                        </div>
                        <input type="hidden" id="custidHidden" name="custid" value="{{ (int) ($selectedCustid ?? 0) }}">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Tahun Pelajaran</div>
                            <select name="thn_aka" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:10px;">
                                <option value="">Semua</option>
                                @foreach (($tahunAjaranOptions ?? []) as $t)
                                    @php $ta = trim((string) ($t['thn_aka'] ?? '')); @endphp
                                    <option value="{{ $ta }}" {{ ($filters['thn_aka'] ?? '') === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div></div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Saldo</div>
                            <input type="text" readonly value="Rp. {{ number_format((int) ($saldoVa ?? 0), 0, ',', '.') }}" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:10px;background:#f9fafb;">
                        </div>
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Total Tagihan</div>
                            <input id="totalTagihanBox" type="text" readonly value="Rp. {{ number_format((int) ($totalTagihan ?? 0), 0, ',', '.') }}" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:10px;background:#f9fafb;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Tanggal Bayar</div>
                            <input name="tanggal_bayar" value="{{ $filters['tanggal_bayar'] ?? '' }}" placeholder="tanggal/bulan/tahun" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:10px;">
                        </div>
                        <div>
                            <div style="font-weight:700;margin-bottom:6px;">Bank</div>
                            <select id="mpBankSelect" name="fidbank" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:10px;">
                                @foreach (($bankOptions ?? []) as $b)
                                    <option value="{{ $b['fidbank'] }}" {{ ($filters['fidbank'] ?? '') === $b['fidbank'] ? 'selected' : '' }}>{{ $b['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;">
                        <button class="btn btn-primary" type="submit">Cari Tagihan</button>
                    </div>

                </div>
            </form>

            <form id="formManualBayar" method="POST" action="{{ route($mpPostRoute) }}" style="margin-top:12px;">
                @csrf
                <input type="hidden" name="custid" value="{{ (int) ($selectedCustid ?? 0) }}">
                <input type="hidden" name="fidbank" id="mpBayarFidbank" value="{{ $filters['fidbank'] ?? '1140000' }}">
                <div style="overflow:auto;border:1px solid var(--border);border-radius:10px;background:#fff;">
                    <table class="mp-tagihan-table">
                        <thead>
                            <tr>
                                <th class="mp-th-check"></th>
                                <th>@if ($mpIsNis) NIS @elseif ($mpIsNonSiswa) NO. DAFTAR @else NOCUST @endif</th>
                                <th>NAMA</th>
                                <th>UNIT</th>
                                <th>KELAS</th>
                                <th>NAMA TAGIHAN</th>
                                <th style="text-align:right;">TAGIHAN</th>
                                <th style="text-align:center;">TAHUN AKA</th>
                                <th>NOMINAL BAYAR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($tagihanRows ?? []) as $r)
                                @php
                                    $billcd = trim((string) ($r['BILLCD'] ?? $r['billcd'] ?? ''));
                                    $billnm = trim((string) ($r['BILLNM'] ?? $r['billnm'] ?? '-'));
                                    $billam = (int) ($r['BILLAM'] ?? $r['billam'] ?? 0);
                                    $nocust = trim((string) ($selectedSiswa['NOCUST'] ?? $selectedSiswa['nocust'] ?? ''));
                                    $num2ndDisp = trim((string) ($selectedSiswa['NUM2ND'] ?? $selectedSiswa['num2nd'] ?? ''));
                                    $nama = trim((string) ($selectedSiswa['NMCUST'] ?? $selectedSiswa['nmcust'] ?? ''));
                                    $unit = trim((string) ($selectedSiswa['CODE02'] ?? $selectedSiswa['code02'] ?? ''));
                                    $kelas = trim((string) ($selectedSiswa['DESC02'] ?? $selectedSiswa['desc02'] ?? ''));
                                    if ($mpIsNis) {
                                        $kolomIdSiswa = $nocust;
                                    } elseif ($mpIsNonSiswa) {
                                        $kolomIdSiswa = $num2ndDisp !== '' ? $num2ndDisp : '—';
                                    } else {
                                        $kolomIdSiswa = $nocust;
                                    }
                                    $tahunAka = trim((string) ($r['BTA'] ?? $r['bta'] ?? $r['Bta'] ?? ''));
                                    if ($tahunAka === '') {
                                        $tahunAka = trim((string) ($filters['thn_aka'] ?? ''));
                                    }
                                @endphp
                                <tr>
                                    <td style="text-align:center;"><input class="bill-check" type="checkbox" name="selected_billcds[]" value="{{ $billcd }}" data-amount="{{ $billam }}" {{ ((int) ($r['is_selected'] ?? 0) === 1) ? 'checked' : '' }}></td>
                                    <td>{{ $kolomIdSiswa }}</td>
                                    <td>{{ $nama }}</td>
                                    <td>{{ $unit }}</td>
                                    <td>{{ $kelas }}</td>
                                    <td>{{ $billnm }}</td>
                                    <td class="mp-col-tagihan"><span class="mp-rp">Rp.</span><span class="mp-amt">{{ number_format($billam, 0, ',', '.') }}</span></td>
                                    <td class="mp-col-thn">{{ $tahunAka !== '' ? $tahunAka : '—' }}</td>
                                    <td class="mp-col-nominal"><input class="bill-nominal-input" type="text" readonly tabindex="-1" aria-readonly="true" value="{{ number_format($billam, 0, ',', '.') }}"></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="text-align:center;color:#6b7280;padding:12px;">
                                        @if ((int) ($selectedCustid ?? 0) > 0 && empty($manualPembayaranError ?? ''))
                                            Tidak ada tagihan belum lunas untuk siswa ini
                                            @if (trim((string) ($filters['thn_aka'] ?? '')) !== '')
                                                <br><span style="font-size:12px;">Coba ubah <b>Tahun Pelajaran</b> ke <b>Semua</b> jika filter terlalu sempit.</span>
                                            @endif
                                        @else
                                            Silahkan Pilih Siswa
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="btn-row" style="justify-content:flex-end;margin-top:12px;">
                    <button class="btn" type="button" id="mpBtnPratinjau">Pratinjau</button>
                    <button class="btn btn-primary" type="submit">Bayar</button>
                </div>
            </form>
        </div>
    </div>

    <form id="mpFormKuitansi" method="POST" action="{{ route('keu.manual.kuitansi') }}" target="_blank" style="display:none;" aria-hidden="true">
        @csrf
        <input type="hidden" name="custid" id="mpKuitansiCustid" value="{{ (int) ($manualPembayaranSuccessCustid ?? 0) }}">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            const mpMode = @json($mpMode ?? 'pendaftaran');
            const siswaSearchUrl = @json(route('keu.manual.siswa_search'));
            const siswaInput = document.getElementById('siswaSearchInput');
            const custidHidden = document.getElementById('custidHidden');
            const siswaList = document.getElementById('siswaAutoList');
            const siswaWrap = document.getElementById('siswaAutoWrap');
            let searchTimer = null;
            let searchSeq = 0;

            if (siswaInput && custidHidden && siswaList && siswaWrap) {
                const closeList = function () {
                    siswaList.style.display = 'none';
                    siswaList.innerHTML = '';
                };

                const renderRows = function (matched) {
                    if (!matched.length) {
                        siswaList.innerHTML = '<div style="padding:10px 12px;color:#6b7280;font-size:13px;">Siswa tidak ditemukan. Coba NIS / nama lain.</div>';
                        siswaList.style.display = 'block';
                        return;
                    }
                    siswaList.innerHTML = matched.map(function (r) {
                        const label = (r.label || '').replace(/"/g, '&quot;');
                        return '<button type="button" data-cid="' + r.cid + '" data-label="' + label + '" style="width:100%;text-align:left;padding:8px 10px;border:0;background:#fff;cursor:pointer;border-bottom:1px solid #f3f4f6;">' + (r.label || '—') + '</button>';
                    }).join('');
                    siswaList.style.display = 'block';
                    Array.from(siswaList.querySelectorAll('button[data-cid]')).forEach(function (btn) {
                        btn.addEventListener('mouseenter', function () { btn.style.background = '#eef2ff'; });
                        btn.addEventListener('mouseleave', function () { btn.style.background = '#fff'; });
                        btn.addEventListener('click', function () {
                            siswaInput.value = btn.getAttribute('data-label') || '';
                            custidHidden.value = btn.getAttribute('data-cid') || '';
                            closeList();
                        });
                    });
                };

                const fetchSiswa = function (q) {
                    const query = String(q || '').trim();
                    if (query.length < 1) {
                        closeList();
                        return;
                    }
                    const seq = ++searchSeq;
                    siswaList.innerHTML = '<div style="padding:10px 12px;color:#6b7280;font-size:13px;">Mencari…</div>';
                    siswaList.style.display = 'block';
                    const url = siswaSearchUrl + '?mode=' + encodeURIComponent(mpMode) + '&q=' + encodeURIComponent(query);
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                        .then(function (res) { return res.json(); })
                        .then(function (json) {
                            if (seq !== searchSeq) return;
                            renderRows(Array.isArray(json.rows) ? json.rows : []);
                        })
                        .catch(function () {
                            if (seq !== searchSeq) return;
                            siswaList.innerHTML = '<div style="padding:10px 12px;color:#b91c1c;font-size:13px;">Gagal memuat data siswa.</div>';
                        });
                };

                siswaInput.addEventListener('input', function () {
                    custidHidden.value = '';
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function () { fetchSiswa(siswaInput.value); }, 280);
                });
                siswaInput.addEventListener('focus', function () {
                    if (String(siswaInput.value || '').trim() !== '') {
                        fetchSiswa(siswaInput.value);
                    }
                });
                document.addEventListener('click', function (e) {
                    if (!siswaWrap.contains(e.target)) closeList();
                });

                const searchForm = siswaInput.closest('form');
                if (searchForm) {
                    searchForm.addEventListener('submit', function (e) {
                        if (!String(custidHidden.value || '').trim()) {
                            e.preventDefault();
                            alert('Pilih siswa dari dropdown dulu supaya tagihan bisa ditampilkan.');
                        }
                    });
                }
            }

            const bayarForm = document.getElementById('formManualBayar');
            const bankSelect = document.getElementById('mpBankSelect');
            const bayarFidbank = document.getElementById('mpBayarFidbank');

            function syncBayarFidbank() {
                if (bankSelect && bayarFidbank) {
                    bayarFidbank.value = bankSelect.value || bayarFidbank.value;
                }
            }
            if (bankSelect) {
                bankSelect.addEventListener('change', syncBayarFidbank);
                syncBayarFidbank();
            }

            function escapeHtml(str) {
                return String(str || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function parseNominalInput(val) {
                const digits = String(val || '').replace(/[^\d]/g, '');
                return digits ? parseInt(digits, 10) : 0;
            }

            const btnPreview = document.getElementById('mpBtnPratinjau');
            if (btnPreview && bayarForm) {
                btnPreview.addEventListener('click', function () {
                    syncBayarFidbank();
                    const custid = parseInt(bayarForm.querySelector('input[name="custid"]')?.value || '0', 10);
                    if (custid <= 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'warning', title: 'Pilih siswa', text: 'Pilih siswa dari dropdown lalu klik Cari Tagihan terlebih dahulu.' });
                        } else {
                            alert('Pilih siswa dari dropdown lalu klik Cari Tagihan terlebih dahulu.');
                        }
                        return;
                    }
                    const picked = bayarForm.querySelectorAll('input.bill-check:checked');
                    if (!picked.length) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'warning', title: 'Pilih tagihan', text: 'Centang minimal satu tagihan di tabel.' });
                        } else {
                            alert('Centang minimal satu tagihan di tabel.');
                        }
                        return;
                    }

                    const siswaLabel = siswaInput ? String(siswaInput.value || '').trim() : '—';
                    const bankLabel = bankSelect
                        ? (bankSelect.options[bankSelect.selectedIndex]?.text || bankSelect.value || '—')
                        : (bayarFidbank?.value || '—');
                    const tglBayar = document.querySelector('input[name="tanggal_bayar"]')?.value || '—';

                    let total = 0;
                    let rowsHtml = '';
                    picked.forEach(function (cb, idx) {
                        const tr = cb.closest('tr');
                        if (!tr) return;
                        const tds = tr.querySelectorAll('td');
                        const namaTagihan = tds[5]?.textContent?.trim() || '—';
                        const tagihanAmt = parseInt(cb.getAttribute('data-amount') || '0', 10);
                        const bayarAmt = tagihanAmt;
                        total += bayarAmt;
                        rowsHtml += '<tr>'
                            + '<td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;text-align:center;">' + (idx + 1) + '</td>'
                            + '<td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;">' + escapeHtml(namaTagihan) + '</td>'
                            + '<td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;text-align:right;white-space:nowrap;">Rp ' + bayarAmt.toLocaleString('id-ID') + '</td>'
                            + '</tr>';
                    });

                    const html = ''
                        + '<div style="text-align:left;font-size:13px;line-height:1.5;">'
                        + '<p style="margin:0 0 8px;"><strong>Siswa:</strong> ' + escapeHtml(siswaLabel) + '</p>'
                        + '<p style="margin:0 0 8px;"><strong>Tanggal bayar:</strong> ' + escapeHtml(tglBayar) + '</p>'
                        + '<p style="margin:0 0 12px;"><strong>Metode:</strong> ' + escapeHtml(bankLabel) + '</p>'
                        + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                        + '<thead><tr>'
                        + '<th style="padding:6px 8px;background:#f3f4f6;border-bottom:1px solid #d1d5db;width:36px;">#</th>'
                        + '<th style="padding:6px 8px;background:#f3f4f6;border-bottom:1px solid #d1d5db;text-align:left;">Nama Tagihan</th>'
                        + '<th style="padding:6px 8px;background:#f3f4f6;border-bottom:1px solid #d1d5db;text-align:right;">Nominal Bayar</th>'
                        + '</tr></thead><tbody>' + rowsHtml + '</tbody>'
                        + '<tfoot><tr>'
                        + '<td colspan="2" style="padding:8px;text-align:right;font-weight:700;border-top:2px solid #d1d5db;">Total</td>'
                        + '<td style="padding:8px;text-align:right;font-weight:700;border-top:2px solid #d1d5db;white-space:nowrap;">Rp ' + total.toLocaleString('id-ID') + '</td>'
                        + '</tr></tfoot></table>'
                        + '<p style="margin:12px 0 0;color:#6b7280;font-size:12px;">Ini hanya pratinjau. Klik <strong>Bayar</strong> untuk memproses pembayaran.</p>'
                        + '</div>';

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Pratinjau Pembayaran',
                            html: html,
                            width: 560,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#059669',
                        });
                    } else {
                        alert('Pratinjau: total Rp ' + total.toLocaleString('id-ID'));
                    }
                });
            }

            if (bayarForm) {
                bayarForm.addEventListener('submit', function (e) {
                    syncBayarFidbank();
                    const picked = bayarForm.querySelectorAll('input.bill-check:checked');
                    if (!picked.length) {
                        e.preventDefault();
                        alert('Pilih minimal satu tagihan yang akan dibayar (centang kotak di kiri baris).');
                    }
                });
            }

            const checks = Array.from(document.querySelectorAll('.bill-check'));
            const totalBox = document.getElementById('totalTagihanBox');
            if (totalBox && checks.length > 0) {
                function formatRp(num) {
                    return 'Rp. ' + Number(num || 0).toLocaleString('id-ID');
                }
                function syncTotal() {
                    let sum = 0;
                    checks.forEach(function (cb) {
                        if (cb.checked) sum += parseInt(cb.getAttribute('data-amount') || '0', 10);
                    });
                    totalBox.value = formatRp(sum);
                }
                checks.forEach(function (cb) { cb.addEventListener('change', syncTotal); });
                syncTotal();
            }

            @if (!empty($manualPembayaranSuccess))
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Pembayaran Berhasil',
                    text: @json($manualPembayaranSuccessMessage ?: 'Pembayaran manual berhasil diproses.'),
                    showCancelButton: true,
                    confirmButtonText: 'Cetak Kuitansi',
                    cancelButtonText: 'Tutup',
                    confirmButtonColor: '#059669',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        const f = document.getElementById('mpFormKuitansi');
                        const cidInput = document.getElementById('mpKuitansiCustid');
                        if (f && cidInput) {
                            cidInput.value = @json((int) ($manualPembayaranSuccessCustid ?? 0));
                            f.submit();
                        }
                    }
                });
            }
            @endif
        })();
    </script>
@endsection

