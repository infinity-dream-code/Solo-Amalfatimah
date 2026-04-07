@extends('layouts.app')

@section('content')
    <style>
        .pk-card { background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;box-shadow:0 8px 20px rgba(15,23,42,.05);}
        .pk-row { display:grid; grid-template-columns:1fr 1fr auto; gap:10px; margin-bottom:10px; align-items:end; }
        .pk-row2 { display:grid; grid-template-columns:1fr 320px; gap:10px; margin-bottom:10px; align-items:end; }
        .pk-fld label { display:block; font-size:12px; color:#4b5563; margin-bottom:6px; font-weight:700; }
        .pk-fld select,.pk-fld input { width:100%; height:38px; border:1px solid #d1d5db; border-radius:8px; padding:0 10px; font-size:13px; }
        .pk-btn { height:38px; border-radius:8px; border:1px solid #d1d5db; padding:0 14px; font-weight:700; font-size:13px; cursor:pointer; background:#fff; }
        .pk-btn-primary { background:#4f6ef7; border-color:#4f6ef7; color:#fff; }
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
    </style>

    <div class="page-heading">
        <h2>Pindah Kelas</h2>
        <p>Pindahkan siswa per pilihan atau seluruh kelas.</p>
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
                    <select name="kelas_sumber" id="pkKelasSumber" required>
                        <option value="">Pilih kelas asal</option>
                        @foreach (($kelasRows ?? []) as $k)
                            @php $kid=(int)($k['id']??0); $label=trim((string)($k['unit']??'')).' - '.trim((string)($k['kelas']??'')); @endphp
                            <option value="{{ $kid }}" {{ ($kelasSumber ?? 0)===$kid ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pk-fld">
                    <label>Kelas Tujuan</label>
                    <select name="kelas_tujuan" id="pkKelasTujuan" required>
                        <option value="">Pilih kelas tujuan</option>
                        @foreach (($kelasRows ?? []) as $k)
                            @php $kid=(int)($k['id']??0); $label=trim((string)($k['unit']??'')).' - '.trim((string)($k['kelas']??'')); @endphp
                            <option value="{{ $kid }}" {{ ($kelasTujuan ?? 0)===$kid ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="pk-btn pk-btn-primary" type="submit">Cari</button>
            </div>

            <div class="pk-row2">
                <div class="pk-fld">
                    <label>NIS / Nama Siswa (filter)</label>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari NIS / Nama">
                </div>
                <div class="pk-fld">
                    <label>Pemindahan</label>
                    <select name="mode">
                        <option value="pilihan" {{ ($mode ?? 'pilihan') === 'pilihan' ? 'selected' : '' }}>Pindahkan Hanya Anak Yang Dipilih</option>
                        <option value="semua" {{ ($mode ?? 'pilihan') === 'semua' ? 'selected' : '' }}>Pindahkan semua Anak Pada Kelas</option>
                    </select>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('master.pindah_kelas.store') }}" id="pkMoveForm">
            @csrf
            <input type="hidden" name="kelas_sumber" id="pkKelasSumberHidden" value="{{ $kelasSumber ?? 0 }}">
            <input type="hidden" name="kelas_tujuan" id="pkKelasTujuanHidden" value="{{ $kelasTujuan ?? 0 }}">
            <input type="hidden" name="search" value="{{ $search ?? '' }}">
            <input type="hidden" name="mode" value="{{ $mode ?? 'pilihan' }}">

            <div class="pk-table-wrap">
                <table class="pk-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
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
                                    @if (($mode ?? 'pilihan') === 'pilihan')
                                        <input type="checkbox" name="custids[]" value="{{ (int) ($row['custid'] ?? 0) }}">
                                    @endif
                                </td>
                                <td>{{ $row['nocust'] ?? '-' }}</td>
                                <td>{{ $row['nmcust'] ?? '-' }}</td>
                                <td>{{ $row['num2nd'] ?? '-' }}</td>
                                <td>{{ $row['desc02'] ?? $row['code03'] ?? '-' }}</td>
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
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="pk-pagi">
                        @php $cur=$siswaRows->currentPage(); $last=$siswaRows->lastPage(); @endphp
                        @if ($siswaRows->onFirstPage())<span class="pk-page disabled">Sebelumnya</span>@else<a class="pk-page" href="{{ $siswaRows->appends(request()->query())->url($cur-1) }}">Sebelumnya</a>@endif
                        @for ($p=max(1,$cur-1); $p<=min($last,$cur+1); $p++)
                            @if ($p===$cur)<span class="pk-page active">{{ $p }}</span>@else<a class="pk-page" href="{{ $siswaRows->appends(request()->query())->url($p) }}">{{ $p }}</a>@endif
                        @endfor
                        @if ($siswaRows->hasMorePages())<a class="pk-page" href="{{ $siswaRows->appends(request()->query())->url($cur+1) }}">Selanjutnya</a>@else<span class="pk-page disabled">Selanjutnya</span>@endif
                    </div>
                    <button class="pk-btn pk-btn-primary" type="submit">Pindah</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const searchForm = document.getElementById('pkSearchForm');
            const moveForm = document.getElementById('pkMoveForm');
            const sumber = document.getElementById('pkKelasSumber');
            const tujuan = document.getElementById('pkKelasTujuan');
            const sumberHidden = document.getElementById('pkKelasSumberHidden');
            const tujuanHidden = document.getElementById('pkKelasTujuanHidden');
            if (!searchForm || !moveForm || !sumber || !tujuan || !sumberHidden || !tujuanHidden) return;

            function sameKelas() {
                return sumber.value !== '' && tujuan.value !== '' && sumber.value === tujuan.value;
            }

            searchForm.addEventListener('submit', function (e) {
                if (sameKelas()) {
                    e.preventDefault();
                    alert('Kelas asal dan kelas tujuan tidak boleh sama.');
                }
            });

            moveForm.addEventListener('submit', function (e) {
                sumberHidden.value = sumber.value || sumberHidden.value;
                tujuanHidden.value = tujuan.value || tujuanHidden.value;
                if (sumberHidden.value !== '' && tujuanHidden.value !== '' && sumberHidden.value === tujuanHidden.value) {
                    e.preventDefault();
                    alert('Kelas asal dan kelas tujuan tidak boleh sama.');
                }
            });
        })();
    </script>
@endsection

