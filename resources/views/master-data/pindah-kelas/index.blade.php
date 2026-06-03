@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.bootstrap5.min.css">
    <style>
        .pk-card { background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;box-shadow:0 8px 20px rgba(15,23,42,.05);}
        .pk-row { display:grid; grid-template-columns:1fr 1fr auto; gap:10px; margin-bottom:10px; align-items:end; }
        .pk-row2 { display:grid; grid-template-columns:1fr; gap:10px; margin-bottom:10px; align-items:end; max-width:480px; }
        .pk-fld label { display:block; font-size:12px; color:#4b5563; margin-bottom:6px; font-weight:700; }
        .pk-fld select,.pk-fld input { width:100%; height:38px; border:1px solid #d1d5db; border-radius:8px; padding:0 10px; font-size:13px; }
        .pk-btn { height:38px; border-radius:8px; border:1px solid #d1d5db; padding:0 14px; font-weight:700; font-size:13px; cursor:pointer; background:#fff; }
        .pk-btn-primary { background:#4f6ef7; border-color:#4f6ef7; color:#fff; }
        .pk-btn-jamak { background:#059669; border-color:#059669; color:#fff; }
        .pk-btn-jamak:hover { background:#047857; border-color:#047857; }
        .pk-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .pk-hint { font-size:11px; color:#6b7280; margin-top:4px; }
        .pk-table-wrap { overflow:auto; margin-top:8px; }
        .pk-table { width:100%; min-width:980px; border-collapse:collapse; font-size:13px; }
        .pk-table th,.pk-table td { border-bottom:1px solid #eef2f7; padding:9px 10px; white-space:nowrap; }
        .pk-table th { background:#fafbfd; color:#4b5563; font-size:12px; font-weight:700; }
        .pk-foot { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-top:10px; flex-wrap:wrap; }
        .pk-pagi { display:flex; gap:6px; }
        .pk-page { min-width:30px;height:30px;border:1px solid #d1d5db;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;padding:0 10px;text-decoration:none;color:#4b5563;font-size:12px;font-weight:700;background:#fff; }
        .pk-page.active { background:#4f6ef7;border-color:#4f6ef7;color:#fff; }
        .pk-page.disabled { color:#9ca3af; border-color:#e5e7eb; pointer-events:none; background:#f9fafb; }
        .pk-alert { margin-bottom:10px; padding:10px 12px; border-radius:8px; font-size:13px; font-weight:700; }
        .pk-ok { background:#ecfdf5; color:#047857; }
        .pk-err { background:#fef2f2; color:#b91c1c; }
        .pk-ts.ts-wrapper { width:100%; }
        .pk-ts .ts-control { min-height:38px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; padding:4px 10px; box-shadow:none; }
        .pk-ts.focus .ts-control { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
        .pk-ts .ts-dropdown { border:1px solid #d1d5db; border-radius:8px; font-size:13px; box-shadow:0 12px 32px rgba(15,23,42,.12); }
        .pk-ts .ts-dropdown .option.active { background:#4f6ef7; color:#fff; }
        .pk-ts .ts-dropdown .option:hover { background:#eef2ff; color:#312e81; }
        .pk-ts .ts-dropdown input[type="text"] { font-size:13px; padding:8px 10px; }
    </style>

    <div class="page-heading">
        <h2>Pindah Kelas</h2>
    </div>

    <div class="pk-card">
        @if (session('status'))<div class="pk-alert pk-ok">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="pk-alert pk-err">{{ session('error') }}</div>@endif
        @if (($errorMsg ?? '') !== '')<div class="pk-alert pk-err">{{ $errorMsg }}</div>@endif
        @if ($errors->any())<div class="pk-alert pk-err">{{ $errors->first() }}</div>@endif

        <form method="GET" action="{{ route('master.pindah_kelas') }}" id="pkSearchForm">
            <div class="pk-row">
                <div class="pk-fld">
                    <label>Kelas Asal</label>
                    <select name="kelas_sumber" id="pkKelasSumber">
                        <option value="">Pilih kelas asal</option>
                        @foreach (($kelasRows ?? []) as $k)
                            @php
                                $kid = (int) ($k['id'] ?? 0);
                                $un = trim((string) ($k['unit'] ?? ''));
                                $kl = trim((string) ($k['kelas'] ?? ''));
                                $kp = trim((string) ($k['kelompok'] ?? ''));
                                $jg = trim((string) ($k['jenjang'] ?? ''));
                                $parts = array_values(array_filter([$un, $kl, $kp, $jg], static fn ($v) => $v !== ''));
                                $label = implode(' - ', $parts);
                            @endphp
                            @if ($kid > 0 && $label !== '')
                                <option value="{{ $kid }}" {{ ($kelasSumber ?? 0) === $kid ? 'selected' : '' }}>{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="pk-fld">
                    <label>Kelas Tujuan</label>
                    <select name="kelas_tujuan" id="pkKelasTujuan" required>
                        <option value="">Pilih kelas tujuan</option>
                        @foreach (($kelasRows ?? []) as $k)
                            @php
                                $kid = (int) ($k['id'] ?? 0);
                                $un = trim((string) ($k['unit'] ?? ''));
                                $kl = trim((string) ($k['kelas'] ?? ''));
                                $kp = trim((string) ($k['kelompok'] ?? ''));
                                $jg = trim((string) ($k['jenjang'] ?? ''));
                                $parts = array_values(array_filter([$un, $kl, $kp, $jg], static fn ($v) => $v !== ''));
                                $label = implode(' - ', $parts);
                            @endphp
                            @if ($kid > 0 && $label !== '')
                                <option value="{{ $kid }}" {{ ($kelasTujuan ?? 0) === $kid ? 'selected' : '' }}>{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <button class="pk-btn pk-btn-primary" type="submit">Cari</button>
            </div>

            <div class="pk-row2">
                <div class="pk-fld">
                    <label>NIS / Nama Siswa</label>
                    <select name="search" id="pkSearchSiswa">
                        <option value="">Cari NIS / Nama</option>
                        @if (($search ?? '') !== '')
                            <option value="{{ $search }}" selected>{{ $search }}</option>
                        @endif
                    </select>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('master.pindah_kelas.store') }}" id="pkMoveForm">
            @csrf
            <input type="hidden" name="kelas_sumber" id="pkKelasSumberHidden" value="{{ ($kelasSumber ?? 0) > 0 ? $kelasSumber : '' }}">
            <input type="hidden" name="kelas_tujuan" id="pkKelasTujuanHidden" value="{{ $kelasTujuan ?? 0 }}">
            <input type="hidden" name="search" value="{{ $search ?? '' }}">

            <div class="pk-table-wrap">
                <table class="pk-table">
                    <thead>
                        <tr>
                            <th style="width:40px;" title="Centang untuk pindah parsial">#</th>
                            <th>NIS</th>
                            <th>NAMA</th>
                            <th>NO DAFTAR</th>
                            <th>KELAS</th>
                            <th>ANGKATAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($siswaRows ?? []) as $row)
                            <tr>
                                <td>
                                    <input type="checkbox" name="custids[]" value="{{ (int) ($row['custid'] ?? 0) }}">
                                </td>
                                <td>{{ $row['nocust'] ?? '-' }}</td>
                                <td>{{ $row['nmcust'] ?? '-' }}</td>
                                <td>{{ $row['num2nd'] ?? '-' }}</td>
                                <td>
                                    @php
                                        $pkUnit = trim((string) ($row['unit_label'] ?? $row['code02'] ?? ''));
                                        $pkKelas = trim((string) ($row['desc02'] ?? ''));
                                        $pkKelompok = trim((string) ($row['desc03'] ?? ''));
                                        $pkKelasLbl = implode(' — ', array_values(array_filter([$pkUnit, $pkKelas, $pkKelompok], static fn ($v) => $v !== '')));
                                    @endphp
                                    {{ $pkKelasLbl !== '' ? $pkKelasLbl : '-' }}
                                </td>
                                <td>{{ $row['desc04'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;color:#6b7280;">Tidak ada data siswa.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pk-foot">
                <div style="font-size:12px;color:#6b7280;">Menampilkan {{ $siswaRows->firstItem() ?? 0 }} sampai {{ $siswaRows->lastItem() ?? 0 }} dari {{ $siswaRows->total() ?? 0 }} entri</div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <div class="pk-pagi">
                        @php $cur=$siswaRows->currentPage(); $last=$siswaRows->lastPage(); @endphp
                        @if ($siswaRows->onFirstPage())<span class="pk-page disabled">Sebelumnya</span>@else<a class="pk-page" href="{{ $siswaRows->appends(request()->query())->url($cur-1) }}">Sebelumnya</a>@endif
                        @for ($p=max(1,$cur-1); $p<=min($last,$cur+1); $p++)
                            @if ($p===$cur)<span class="pk-page active">{{ $p }}</span>@else<a class="pk-page" href="{{ $siswaRows->appends(request()->query())->url($p) }}">{{ $p }}</a>@endif
                        @endfor
                        @if ($siswaRows->hasMorePages())<a class="pk-page" href="{{ $siswaRows->appends(request()->query())->url($cur+1) }}">Selanjutnya</a>@else<span class="pk-page disabled">Selanjutnya</span>@endif
                    </div>
                    <div class="pk-actions">
                        <button class="pk-btn pk-btn-jamak" type="submit" name="mode" value="semua" title="Pindahkan seluruh siswa di kelas asal">Pindah Jamak</button>
                        <button class="pk-btn pk-btn-primary" type="submit" name="mode" value="pilihan" title="Pindahkan siswa yang dicentang">Pindah Parsial</button>
                    </div>
                </div>
                <p class="pk-hint"><strong>Jamak:</strong> semua siswa di kelas asal (wajib pilih kelas asal). <strong>Parsial:</strong> centang siswa di tabel terlebih dahulu.</p>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        (function () {
            const searchForm = document.getElementById('pkSearchForm');
            const moveForm = document.getElementById('pkMoveForm');
            const sumberEl = document.getElementById('pkKelasSumber');
            const tujuanEl = document.getElementById('pkKelasTujuan');
            const siswaEl = document.getElementById('pkSearchSiswa');
            const sumberHidden = document.getElementById('pkKelasSumberHidden');
            const tujuanHidden = document.getElementById('pkKelasTujuanHidden');
            const siswaOptionsUrl = @json(route('master.pindah_kelas.siswa_options'));
            if (!searchForm || !moveForm || !sumberEl || !tujuanEl || !siswaEl || !sumberHidden || !tujuanHidden) return;

            const tsBase = {
                allowEmptyOption: true,
                maxOptions: null,
                plugins: ['dropdown_input'],
                dropdownParent: 'body',
                render: {
                    option: function (data, escape) {
                        return '<div class="option">' + escape(data.text) + '</div>';
                    }
                }
            };

            const tsSumber = new TomSelect(sumberEl, Object.assign({}, tsBase, {
                placeholder: 'Pilih kelas asal',
                wrapperClass: 'pk-ts ts-wrapper'
            }));

            const tsTujuan = new TomSelect(tujuanEl, Object.assign({}, tsBase, {
                placeholder: 'Pilih kelas tujuan',
                wrapperClass: 'pk-ts ts-wrapper'
            }));

            const tsSiswa = new TomSelect(siswaEl, Object.assign({}, tsBase, {
                placeholder: 'Cari NIS / Nama',
                wrapperClass: 'pk-ts ts-wrapper',
                create: function (input) {
                    const v = (input || '').trim();
                    if (v === '') return false;
                    return { value: v, text: v };
                },
                load: function (query, callback) {
                    const q = (query || '').trim();
                    if (q.length < 1) {
                        callback();
                        return;
                    }
                    const ks = tsSumber.getValue() || '';
                    const url = siswaOptionsUrl + '?q=' + encodeURIComponent(q) + '&kelas_sumber=' + encodeURIComponent(ks);
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (items) { callback(items); })
                        .catch(function () { callback(); });
                },
                shouldLoad: function (query) {
                    return (query || '').trim().length >= 1;
                }
            }));

            function kelasValue(ts) {
                const v = ts.getValue();
                return Array.isArray(v) ? (v[0] || '') : (v || '');
            }

            function sameKelas() {
                const a = kelasValue(tsSumber);
                const b = kelasValue(tsTujuan);
                return a !== '' && b !== '' && a === b;
            }

            searchForm.addEventListener('submit', function (e) {
                if (sameKelas()) {
                    e.preventDefault();
                    alert('Kelas asal dan kelas tujuan tidak boleh sama.');
                }
            });

            moveForm.addEventListener('submit', function (e) {
                sumberHidden.value = kelasValue(tsSumber) || sumberHidden.value;
                tujuanHidden.value = kelasValue(tsTujuan) || tujuanHidden.value;
                const searchHidden = moveForm.querySelector('input[name="search"]');
                if (searchHidden) {
                    searchHidden.value = kelasValue(tsSiswa) || '';
                }
                if (!tujuanHidden.value) {
                    e.preventDefault();
                    alert('Kelas tujuan wajib dipilih.');
                    return;
                }
                if (sumberHidden.value !== '' && sumberHidden.value === tujuanHidden.value) {
                    e.preventDefault();
                    alert('Kelas asal dan kelas tujuan tidak boleh sama.');
                    return;
                }
                const mode = (e.submitter && e.submitter.name === 'mode') ? e.submitter.value : '';
                if (mode === 'semua') {
                    if (!sumberHidden.value) {
                        e.preventDefault();
                        alert('Pindah jamak membutuhkan kelas asal.');
                        return;
                    }
                    if (!confirm('Pindahkan SEMUA siswa di kelas asal ke kelas tujuan?')) {
                        e.preventDefault();
                    }
                    return;
                }
                if (mode === 'pilihan') {
                    const boxes = moveForm.querySelectorAll('input[name="custids[]"]:checked');
                    if (boxes.length === 0) {
                        e.preventDefault();
                        alert('Pindah parsial: centang minimal satu siswa.');
                    }
                }
            });
        })();
    </script>
@endsection
