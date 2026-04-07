@extends('layouts.app')

@section('content')
    <style>
        .bt-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;box-shadow:0 8px 20px rgba(15,23,42,.05)}
        .bt-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .bt-fld label{display:block;font-size:12px;color:#4b5563;margin-bottom:6px;font-weight:700}
        .bt-fld select,.bt-fld input{width:100%;height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;font-size:13px}
        .bt-actions{display:flex;justify-content:flex-end;gap:8px;margin:10px 0}
        .bt-btn{height:38px;border-radius:8px;border:1px solid #d1d5db;padding:0 14px;font-weight:700;font-size:13px;cursor:pointer;background:#fff}
        .bt-btn-primary{background:#4f6ef7;border-color:#4f6ef7;color:#fff}
        .bt-table{width:100%;border-collapse:collapse;font-size:13px}
        .bt-table th,.bt-table td{border-bottom:1px solid #eef2f7;padding:9px 10px;white-space:nowrap}
        .bt-table th{background:#fafbfd;color:#4b5563;font-size:12px;font-weight:700}
        .bt-money{display:flex;align-items:center;border:1px solid #d1d5db;border-radius:8px;overflow:hidden;min-width:180px;background:#fff}
        .bt-money-prefix{background:#f3f4f6;color:#374151;padding:7px 10px;font-size:12px;border-right:1px solid #d1d5db}
        .bt-money-value{padding:0 10px;font-size:13px;color:#111827}
        .bt-wrap{overflow:auto;margin-top:8px}
        .bt-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-top:10px}
        .bt-pg{display:flex;gap:6px}.bt-page{min-width:30px;height:30px;border:1px solid #d1d5db;border-radius:999px;padding:0 10px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#4b5563;font-size:12px;font-weight:700;background:#fff}
        .bt-page.active{background:#4f6ef7;border-color:#4f6ef7;color:#fff}.bt-page.disabled{color:#9ca3af;border-color:#e5e7eb;pointer-events:none;background:#f9fafb}
        .bt-alert{margin-bottom:10px;padding:10px 12px;border-radius:8px;font-size:13px;font-weight:700}.bt-ok{background:#ecfdf5;color:#047857}.bt-err{background:#fef2f2;color:#b91c1c}
    </style>

    <div class="page-heading">
        <h2>Buat Tagihan</h2>
        <p>Filter siswa dan pilih akun tagihan.</p>
    </div>

    <div class="bt-card">
        @if (session('status'))<div class="bt-alert bt-ok">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="bt-alert bt-err">{{ session('error') }}</div>@endif
        @if (($errorMsg ?? '') !== '')<div class="bt-alert bt-err">{{ $errorMsg }}</div>@endif
        @if ($errors->any())<div class="bt-alert bt-err">{{ $errors->first() }}</div>@endif

        <form method="GET" action="{{ route('keu.tagihan.buat') }}">
            <div class="bt-grid">
                <div class="bt-fld">
                    <label>Tahun Pelajaran</label>
                    <select name="thn_akademik" id="thn-akademik">
                        <option value="">Pilih Tahun Pelajaran</option>
                        @foreach (($filterOptions['thn_akademik'] ?? []) as $th)
                            @php $val = (string)($th['thn_aka'] ?? ''); @endphp
                            <option value="{{ $val }}" {{ (($filters['thn_akademik'] ?? '') === $val) ? 'selected' : '' }}>{{ $val }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bt-fld">
                    <label>Tahun Angkatan</label>
                    <select name="thn_angkatan">
                        <option value="">Pilih Tahun Angkatan</option>
                        @foreach (($filterOptions['thn_angkatan'] ?? []) as $ta)
                            <option value="{{ $ta }}" {{ (($filters['thn_angkatan'] ?? '') === $ta) ? 'selected' : '' }}>{{ $ta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bt-fld">
                    <label>NIS / Nama</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nis / Nama">
                </div>
                <div class="bt-fld">
                    <label>Kelas</label>
                    <select name="kelas_id">
                        <option value="">Pilih Kelas</option>
                        @foreach (($filterOptions['kelas'] ?? []) as $k)
                            @php $id=(string)($k['id'] ?? ''); $lbl=(string)(($k['unit']??'').' '.($k['kelas']??'')); @endphp
                            <option value="{{ $id }}" {{ (($filters['kelas_id'] ?? '') === $id) ? 'selected' : '' }}>{{ trim($lbl) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bt-fld">
                    <label>Fungsi</label>
                    <input type="text" name="fungsi" id="fungsi-input" value="{{ $filters['fungsi'] ?? '' }}" readonly>
                </div>
                <div class="bt-fld">
                    <label>Tagihan</label>
                    <select name="tagihan" id="tagihan-select">
                        <option value="">Semua</option>
                        @foreach (($filterOptions['tagihan'] ?? []) as $bta)
                            @php
                                $tagihanValue = is_array($bta) ? (string)($bta['tagihan'] ?? $bta['nama'] ?? '') : (string)$bta;
                            @endphp
                            @if ($tagihanValue !== '')
                                <option value="{{ $tagihanValue }}" {{ (($filters['tagihan'] ?? '') === $tagihanValue) ? 'selected' : '' }}>{{ $tagihanValue }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="bt-actions">
                <a class="bt-btn" href="{{ route('keu.tagihan.buat') }}">Reset</a>
                <button class="bt-btn bt-btn-primary" type="submit">Cari</button>
            </div>
        </form>

        <form method="POST" action="{{ route('keu.tagihan.store') }}" id="form-buat-tagihan">
            @csrf
            <input type="hidden" name="thn_akademik" value="{{ $filters['thn_akademik'] ?? '' }}">
            <input type="hidden" name="thn_angkatan" value="{{ $filters['thn_angkatan'] ?? '' }}">
            <input type="hidden" name="kelas_id" value="{{ $filters['kelas_id'] ?? '' }}">
            <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
            <input type="hidden" name="fungsi" id="hidden-fungsi" value="{{ $filters['fungsi'] ?? '' }}">
            <input type="hidden" name="tagihan" value="{{ $filters['tagihan'] ?? '' }}">

            <div class="bt-wrap">
                <table class="bt-table">
                    <thead><tr><th><input type="checkbox" id="check-all-siswa"></th><th>NIS</th><th>NAMA</th><th>KELAS</th><th>JENJANG</th><th>ANGKATAN</th></tr></thead>
                    <tbody>
                        @forelse (($siswaRows ?? []) as $row)
                            <tr>
                                <td>
                                    @php
                                        $rawCode01 = (string) ($row['CODE01'] ?? $row['code01'] ?? '');
                                        $kodeProdFallback = ltrim(preg_replace('/\D+/', '', $rawCode01), '0');
                                    @endphp
                                    <input
                                        type="checkbox"
                                        class="check-siswa"
                                        name="custids[]"
                                        value="{{ (int) ($row['CUSTID'] ?? $row['custid'] ?? 0) }}"
                                        data-kelas-id="{{ (string) ($row['kelas_id'] ?? $row['KELAS_ID'] ?? ($filters['kelas_id'] ?? '')) }}"
                                        data-kode-prod="{{ $kodeProdFallback }}"
                                        data-angkatan="{{ (string) ($row['ANGKATAN'] ?? $row['angkatan'] ?? '') }}"
                                    >
                                </td>
                                <td>{{ $row['NIS'] ?? $row['nis'] ?? '-' }}</td>
                                <td>{{ $row['NAMA'] ?? $row['nama'] ?? '-' }}</td>
                                <td>{{ $row['KELAS'] ?? $row['kelas'] ?? '-' }}</td>
                                <td>{{ $row['JENJANG'] ?? $row['jenjang'] ?? '-' }}</td>
                                <td>{{ $row['ANGKATAN'] ?? $row['angkatan'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;color:#6b7280;">Tidak ada siswa yang sesuai kriteria pencarian</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bt-foot">
                <div style="font-size:12px;color:#6b7280;">
                    Showing {{ $siswaRows->firstItem() ?? 0 }} to {{ $siswaRows->lastItem() ?? 0 }} of {{ $siswaRows->total() }} results
                </div>
                @if ($siswaRows->lastPage() > 1)
                    <div class="bt-pg">
                        @php
                            $current = $siswaRows->currentPage();
                            $last = $siswaRows->lastPage();
                            $start = max(1, $current - 2);
                            $end = min($last, $current + 2);
                        @endphp
                        @if ($siswaRows->onFirstPage())
                            <span class="bt-page disabled">Prev</span>
                        @else
                            <a class="bt-page" href="{{ $siswaRows->appends(request()->query())->url($current - 1) }}">Prev</a>
                        @endif

                        @for ($i = $start; $i <= $end; $i++)
                            @if ($i === $current)
                                <span class="bt-page active">{{ $i }}</span>
                            @else
                                <a class="bt-page" href="{{ $siswaRows->appends(request()->query())->url($i) }}">{{ $i }}</a>
                            @endif
                        @endfor

                        @if ($siswaRows->hasMorePages())
                            <a class="bt-page" href="{{ $siswaRows->appends(request()->query())->url($current + 1) }}">Next</a>
                        @else
                            <span class="bt-page disabled">Next</span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="bt-wrap" style="margin-top:14px;" id="akun-section">
                <table class="bt-table">
                    <thead><tr><th><input type="checkbox" id="check-all-akun"></th><th>KODE</th><th>NAMA AKUN</th><th>NOMINAL</th></tr></thead>
                    <tbody id="akun-tbody">
                        @forelse (($daftarHargaRows ?? []) as $row)
                            @php $kode = (string)($row['KodeAkun'] ?? $row['kodeakun'] ?? ''); @endphp
                            <tr>
                                <td><input type="checkbox" class="check-akun" name="kode_akuns[]" value="{{ $kode }}"></td>
                                <td>{{ $kode !== '' ? $kode : '-' }}</td>
                                <td>{{ $row['NamaAkun'] ?? $row['namaakun'] ?? '-' }}</td>
                                <td>
                                    <div class="bt-money">
                                        <span class="bt-money-prefix">Rp</span>
                                        <span class="bt-money-value">{{ number_format((int) ($row['nominal'] ?? 0), 0, ',', '.') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;color:#6b7280;">Pilih siswa untuk menampilkan kode tagihan</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bt-actions" style="margin-top:14px;">
                <button class="bt-btn bt-btn-primary" type="submit" id="btn-buat-tagihan">Buat Tagihan</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const bindSelectAll = (masterId, itemClass) => {
                const master = document.getElementById(masterId);
                const items = Array.from(document.querySelectorAll('.' + itemClass));
                if (!master || items.length === 0) return;

                master.addEventListener('change', function () {
                    items.forEach((el) => { el.checked = master.checked; });
                });

                items.forEach((el) => {
                    el.addEventListener('change', function () {
                        master.checked = items.length > 0 && items.every((c) => c.checked);
                    });
                });
            };

            bindSelectAll('check-all-siswa', 'check-siswa');
            bindSelectAll('check-all-akun', 'check-akun');

            const thnAkademikSelect = document.getElementById('thn-akademik');
            const tagihanSelect = document.getElementById('tagihan-select');
            const fungsiInput = document.getElementById('fungsi-input');
            const kelasFilterSelect = document.querySelector('form[method="GET"] select[name="kelas_id"]');

            const fetchFungsi = async () => {
                if (!thnAkademikSelect || !tagihanSelect || !fungsiInput) return;
                const thnAkademik = (thnAkademikSelect.value || '').trim();
                const tagihan = (tagihanSelect.value || '').trim();

                if (!thnAkademik) {
                    fungsiInput.value = '';
                    return;
                }

                fungsiInput.value = '...';
                try {
                    const url = new URL('{{ route('keu.tagihan.fungsi') }}', window.location.origin);
                    url.searchParams.set('thn_akademik', thnAkademik);
                    url.searchParams.set('tagihan', tagihan);
                    url.searchParams.set('_t', Date.now().toString());
                    const res = await fetch(url.toString(), {
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json' },
                    });
                    const json = await res.json();
                    fungsiInput.value = (json && json.fungsi) ? json.fungsi : '';
                } catch (e) {
                    fungsiInput.value = '';
                }
            };

            if (thnAkademikSelect) thnAkademikSelect.addEventListener('change', fetchFungsi);
            if (tagihanSelect) tagihanSelect.addEventListener('change', fetchFungsi);
            if (kelasFilterSelect) kelasFilterSelect.addEventListener('change', fetchFungsi);
            fetchFungsi();

            const siswaChecks = Array.from(document.querySelectorAll('.check-siswa'));
            const akunSection = document.getElementById('akun-section');
            const checkAllAkun = document.getElementById('check-all-akun');
            const btnBuat = document.getElementById('btn-buat-tagihan');
            const akunTbody = document.getElementById('akun-tbody');
            const hiddenKelas = document.querySelector('input[name="kelas_id"]');
            const hiddenAngkatan = document.querySelector('input[name="thn_angkatan"]');
            const hiddenAkademik = document.querySelector('input[name="thn_akademik"]');
            const hiddenTagihan = document.querySelector('input[name="tagihan"]');
            const kelasSelect = document.querySelector('select[name="kelas_id"]');
            const angkatanSelect = document.querySelector('select[name="thn_angkatan"]');
            const akademikSelect = document.querySelector('select[name="thn_akademik"]');
            const postForm = document.getElementById('form-buat-tagihan');
            const hiddenFungsi = document.getElementById('hidden-fungsi');

            const renderAkunRows = (rows) => {
                if (!akunTbody) return;
                if (!Array.isArray(rows) || rows.length === 0) {
                    akunTbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#6b7280;">Kode tagihan tidak ditemukan untuk kelas/tahun ini</td></tr>';
                    return;
                }
                akunTbody.innerHTML = rows.map((row) => {
                    const kode = String(row.KodeAkun ?? row.kodeakun ?? '');
                    const nama = String(row.NamaAkun ?? row.namaakun ?? '-');
                    const nominalNum = Number(row.nominal ?? 0);
                    const nominal = nominalNum.toLocaleString('id-ID');
                    return `<tr>
                        <td><input type="checkbox" class="check-akun" name="kode_akuns[]" value="${kode}"></td>
                        <td>${kode || '-'}</td>
                        <td>${nama}</td>
                        <td>
                            <div class="bt-money">
                                <span class="bt-money-prefix">Rp</span>
                                <span class="bt-money-value">${nominal}</span>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            };

            const renderAkunMessage = (message, isError = false) => {
                if (!akunTbody) return;
                const color = isError ? '#b91c1c' : '#6b7280';
                akunTbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:${color};">${message}</td></tr>`;
            };

            const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

            const loadDaftarHargaBySiswa = async () => {
                const selectedSiswa = siswaChecks.find((el) => el.checked);
                if (!selectedSiswa) return;
                const kelasId = (
                    selectedSiswa.dataset.kelasId ||
                    selectedSiswa.dataset.kodeProd ||
                    kelasSelect?.value ||
                    ''
                ).trim();
                const angkatan = (selectedSiswa.dataset.angkatan || '').trim();
                if (hiddenKelas) hiddenKelas.value = kelasId;
                if (hiddenAngkatan) {
                    hiddenAngkatan.value = (angkatan || angkatanSelect?.value || '').trim();
                }

                if (!kelasId) {
                    renderAkunRows([]);
                    return;
                }
                try {
                    const url = new URL('{{ route('keu.tagihan.daftar_harga') }}', window.location.origin);
                    url.searchParams.set('kelas_id', kelasId);
                    url.searchParams.set('thn_angkatan', (hiddenAngkatan?.value || angkatan || '').trim());
                    url.searchParams.set('thn_akademik', (hiddenAkademik?.value || '').trim());
                    url.searchParams.set('tagihan', (hiddenTagihan?.value || '').trim());
                    const fetchOnce = async () => {
                        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                        const json = await res.json();
                        if (!res.ok || (json && json.ok === false)) {
                            throw new Error((json && json.message) ? json.message : 'WS gagal merespons');
                        }
                        return json;
                    };
                    let json;
                    try {
                        json = await fetchOnce();
                    } catch (_) {
                        await wait(350);
                        json = await fetchOnce();
                    }
                    renderAkunRows((json && Array.isArray(json.rows)) ? json.rows : []);
                    bindSelectAll('check-all-akun', 'check-akun');
                    Array.from(document.querySelectorAll('.check-akun')).forEach((el) => {
                        el.addEventListener('change', updateAkunVisibility);
                    });
                    updateAkunVisibility();
                } catch (_) {
                    renderAkunMessage('Gagal ambil kode tagihan dari WS. Coba klik ulang siswa atau cari lagi.', true);
                    updateAkunVisibility();
                }
            };

            const updateAkunVisibility = () => {
                const hasSelectedSiswa = siswaChecks.some((el) => el.checked);
                if (akunSection) {
                    akunSection.style.display = hasSelectedSiswa ? '' : 'none';
                }
                if (!hasSelectedSiswa) {
                    Array.from(document.querySelectorAll('.check-akun')).forEach((el) => { el.checked = false; });
                    if (checkAllAkun) checkAllAkun.checked = false;
                    renderAkunRows([]);
                }

                const currentAkunChecks = Array.from(document.querySelectorAll('.check-akun'));
                const hasSelectedAkun = currentAkunChecks.some((el) => el.checked);
                if (btnBuat) {
                    btnBuat.disabled = !(hasSelectedSiswa && hasSelectedAkun);
                    btnBuat.style.opacity = btnBuat.disabled ? '0.6' : '1';
                    btnBuat.style.cursor = btnBuat.disabled ? 'not-allowed' : 'pointer';
                }
            };

            siswaChecks.forEach((el) => {
                el.addEventListener('change', () => {
                    updateAkunVisibility();
                    if (el.checked) {
                        loadDaftarHargaBySiswa();
                    }
                });
            });
            Array.from(document.querySelectorAll('.check-akun')).forEach((el) => {
                el.addEventListener('change', updateAkunVisibility);
            });
            if (checkAllAkun) {
                checkAllAkun.addEventListener('change', () => {
                    setTimeout(updateAkunVisibility, 0);
                });
            }

            if (postForm) {
                postForm.addEventListener('submit', (e) => {
                    const selectedSiswa = Array.from(document.querySelectorAll('.check-siswa:checked'));
                    const selectedAkun = Array.from(document.querySelectorAll('.check-akun:checked'));

                    if (hiddenAkademik && akademikSelect) hiddenAkademik.value = (akademikSelect.value || '').trim();
                    if (hiddenTagihan && tagihanSelect) hiddenTagihan.value = (tagihanSelect.value || '').trim();
                    if (hiddenFungsi && fungsiInput) hiddenFungsi.value = (fungsiInput.value || '').trim();
                    if (hiddenAngkatan && angkatanSelect && !hiddenAngkatan.value) hiddenAngkatan.value = (angkatanSelect.value || '').trim();
                    if (hiddenKelas && kelasSelect && !hiddenKelas.value) hiddenKelas.value = (kelasSelect.value || '').trim();

                    if (selectedSiswa.length > 0) {
                        const first = selectedSiswa[0];
                        const kelasId = (
                            first.dataset.kelasId ||
                            first.dataset.kodeProd ||
                            kelasSelect?.value ||
                            ''
                        ).trim();
                        const angkatan = (first.dataset.angkatan || angkatanSelect?.value || '').trim();
                        if (hiddenKelas) hiddenKelas.value = kelasId;
                        if (hiddenAngkatan) hiddenAngkatan.value = angkatan;
                    }

                    if (selectedSiswa.length === 0) {
                        e.preventDefault();
                        alert('Pilih minimal satu siswa.');
                        return;
                    }
                    if (selectedAkun.length === 0) {
                        e.preventDefault();
                        alert('Pilih minimal satu kode tagihan.');
                        return;
                    }
                    if (!hiddenAkademik?.value) {
                        e.preventDefault();
                        alert('Tahun Pelajaran wajib diisi.');
                        return;
                    }
                    if (!hiddenKelas?.value) {
                        e.preventDefault();
                        alert('Kelas wajib diisi.');
                    }
                });
            }
            updateAkunVisibility();
        })();
    </script>
@endsection

