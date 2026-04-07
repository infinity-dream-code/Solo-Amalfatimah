@extends('layouts.app')

@section('content')
    <style>
        .ds-wrap { margin-top: 16px; display: flex; flex-direction: column; gap: 16px; }
        .ds-card {
            background: #fff;
            border: 1px solid #e4eaf0;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        }
        .ds-card-h {
            padding: 14px 18px;
            border-bottom: 1px solid #eef2f7;
            font-weight: 700;
            font-size: 14px;
            font-family: 'Sora', sans-serif;
        }
        .ds-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
            padding: 16px 18px;
            align-items: end;
        }
        .ds-fld label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
        }
        .ds-fld select, .ds-fld input[type="text"] {
            width: 100%;
            height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 10px;
            font-size: 13px;
        }
        .ds-filter-actions { display: flex; gap: 10px; flex-wrap: wrap; padding: 0 18px 16px; }
        .ds-btn {
            height: 38px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
        }
        .ds-btn-primary { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .ds-toolbar {
            padding: 12px 18px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #eef2f7;
        }
        .ds-search {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }
        .ds-search input {
            width: 220px;
            height: 34px;
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            padding: 0 10px;
            font-size: 12px;
        }
        .ds-table-wrap { overflow-x: auto; }
        .ds-table { width: 100%; border-collapse: collapse; min-width: 1100px; font-size: 13px; }
        .ds-table th, .ds-table td {
            border-bottom: 1px solid #eef2f7;
            padding: 10px 10px;
            text-align: left;
            vertical-align: middle;
        }
        .ds-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .ds-col-no { width: 48px; text-align: center; }
        .ds-col-act { width: 100px; text-align: center; }
        .ds-btn-reset-login {
            font-size: 11px;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #4b5563;
            cursor: pointer;
        }
        .ds-btn-reset-login:disabled { opacity: 0.55; cursor: not-allowed; }
        .ds-empty { text-align: center; color: #6b7280; padding: 24px; }
        .ds-alert {
            margin: 0 18px 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #ecfdf5;
            color: #047857;
            font-size: 13px;
            font-weight: 600;
        }
        .ds-pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 18px 18px;
            flex-wrap: wrap;
        }
        .ds-pagination-info { font-size: 12px; color: #6b7280; }
        .ds-pagination { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .ds-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            padding: 0 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            background: #fff;
        }
        .ds-page-link.active { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .ds-page-link.disabled { color: #9ca3af; border-color: #e5e7eb; pointer-events: none; background: #f9fafb; }
        .ds-export {
            position: relative;
        }
        .ds-export-btn {
            height: 34px;
            padding: 0 12px;
            border-radius: 8px;
            background: #0ea5e9;
            color: #fff;
            border: 1px solid #0ea5e9;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .ds-export-menu {
            position: absolute;
            right: 0;
            top: 40px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
            min-width: 130px;
            z-index: 20;
            display: none;
            padding: 6px;
        }
        .ds-export.open .ds-export-menu { display: block; }
        .ds-export-item {
            width: 100%;
            display: block;
            text-align: left;
            text-decoration: none;
            border: 0;
            background: transparent;
            color: #374151;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .ds-export-item:hover { background: #f3f4f6; }
        .ds-copy-toast {
            position: fixed;
            bottom: 18px;
            right: 18px;
            background: #111827;
            color: #fff;
            font-size: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            opacity: 0;
            transform: translateY(8px);
            transition: all 0.2s ease;
            pointer-events: none;
            z-index: 40;
        }
        .ds-copy-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>

    <div class="page-heading">
        <h2>Data Siswa</h2>
        <p>Daftar siswa dari web service — filter, cari, dan pagination.</p>
    </div>

    <div class="ds-wrap">
        <div class="ds-card">
            <div class="ds-card-h">Filter</div>
            <form method="GET" action="{{ route('master.data_siswa') }}">
                @if (($keyword ?? '') !== '')
                    <input type="hidden" name="q" value="{{ $keyword }}">
                @endif
                <div class="ds-filter-grid">
                    <div class="ds-fld">
                        <label for="angkatan">Angkatan Siswa</label>
                        <select id="angkatan" name="angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['angkatan'] ?? []) as $opt)
                                <option value="{{ $opt }}" {{ ($angkatan ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ds-fld">
                        <label for="sekolah">Sekolah</label>
                        <select id="sekolah" name="sekolah">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['sekolah'] ?? []) as $opt)
                                <option value="{{ $opt }}" {{ ($sekolah ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ds-fld">
                        <label for="kelas">Kelas</label>
                        <select id="kelas" name="kelas">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['kelas'] ?? []) as $kr)
                                @php
                                    $d02 = $kr['DESC02'] ?? $kr['desc02'] ?? '';
                                    $c02 = $kr['CODE02'] ?? $kr['code02'] ?? '';
                                @endphp
                                @if ($d02 !== '')
                                    <option value="{{ $d02 }}" {{ ($kelas ?? '') === $d02 ? 'selected' : '' }}>
                                        {{ $c02 !== '' ? $c02.' — ' : '' }}{{ $d02 }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="ds-fld">
                        <label for="siswa">Siswa (NIS / Nama)</label>
                        <input id="siswa" type="text" name="siswa" value="{{ $siswa ?? '' }}" placeholder="Masukkan NIS/NAMA Siswa">
                    </div>
                </div>
                <div class="ds-filter-actions">
                    <a class="ds-btn" href="{{ route('master.data_siswa') }}">Reset</a>
                    <button type="submit" class="ds-btn ds-btn-primary">Cari</button>
                </div>
            </form>
        </div>

        <div class="ds-card">
            @if (session('status'))
                <div class="ds-alert">{{ session('status') }}</div>
            @endif

            <div class="ds-toolbar">
                <div class="ds-export" id="dsExport">
                    <button type="button" class="ds-export-btn" id="dsExportToggle">Export ▾</button>
                    <div class="ds-export-menu" id="dsExportMenu">
                        <button type="button" class="ds-export-item" id="dsCopyBtn">Copy</button>
                        <a class="ds-export-item" href="{{ route('master.data_siswa.export_excel', request()->query()) }}">Excel</a>
                        <a class="ds-export-item" href="{{ route('master.data_siswa.export_pdf', request()->query()) }}" target="_blank">Pdf</a>
                    </div>
                </div>
                <form method="GET" action="{{ route('master.data_siswa') }}" class="ds-search">
                    @if (($angkatan ?? '') !== '')<input type="hidden" name="angkatan" value="{{ $angkatan }}">@endif
                    @if (($sekolah ?? '') !== '')<input type="hidden" name="sekolah" value="{{ $sekolah }}">@endif
                    @if (($kelas ?? '') !== '')<input type="hidden" name="kelas" value="{{ $kelas }}">@endif
                    @if (($siswa ?? '') !== '')<input type="hidden" name="siswa" value="{{ $siswa }}">@endif
                    <span>Cari:</span>
                    <input type="text" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
                </form>
            </div>

            <div class="ds-table-wrap">
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th class="ds-col-no">No</th>
                            <th>NIS</th>
                            <th>NO VA</th>
                            <th>NAMA</th>
                            <th>No Pendaftaran</th>
                            <th>Unit</th>
                            <th>Kelas</th>
                            <th>Jenjang</th>
                            <th>Angkatan</th>
                            <th class="ds-col-act">Reset Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($siswaRows ?? []) as $index => $row)
                            <tr>
                                <td class="ds-col-no">{{ ($siswaRows->firstItem() ?? 1) + $index }}</td>
                                <td>{{ $row['nocust'] ?? '-' }}</td>
                                <td>{{ '7510050' . preg_replace('/\D+/', '', (string) ($row['nocust'] ?? '')) }}</td>
                                <td>{{ $row['nmcust'] ?? '-' }}</td>
                                <td>{{ $row['num2nd'] ?? '-' }}</td>
                                <td>{{ $row['code02'] ?? '-' }}</td>
                                <td>{{ $row['desc02'] ?? '-' }}</td>
                                <td>{{ $row['desc03'] ?? '-' }}</td>
                                <td>{{ $row['desc04'] ?? '-' }}</td>
                                <td class="ds-col-act">
                                    <button type="button" class="ds-btn-reset-login" disabled title="Menunggu endpoint web service">Reset</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="ds-empty">Data siswa tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (isset($siswaRows) && method_exists($siswaRows, 'hasPages') && $siswaRows->hasPages())
                <div class="ds-pagination-wrap">
                    <div class="ds-pagination-info">
                        Showing {{ $siswaRows->firstItem() }} to {{ $siswaRows->lastItem() }} of {{ $siswaRows->total() }} results
                    </div>
                    <div class="ds-pagination">
                        @php
                            $current = $siswaRows->currentPage();
                            $last = $siswaRows->lastPage();
                            $start = max(1, $current - 2);
                            $end = min($last, $current + 2);
                        @endphp
                        @if ($siswaRows->onFirstPage())
                            <span class="ds-page-link disabled">Prev</span>
                        @else
                            <a class="ds-page-link" href="{{ $siswaRows->appends(request()->query())->url($current - 1) }}">Prev</a>
                        @endif
                        @for ($p = $start; $p <= $end; $p++)
                            @if ($p === $current)
                                <span class="ds-page-link active">{{ $p }}</span>
                            @else
                                <a class="ds-page-link" href="{{ $siswaRows->appends(request()->query())->url($p) }}">{{ $p }}</a>
                            @endif
                        @endfor
                        @if ($siswaRows->hasMorePages())
                            <a class="ds-page-link" href="{{ $siswaRows->appends(request()->query())->url($current + 1) }}">Next</a>
                        @else
                            <span class="ds-page-link disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="ds-copy-toast" id="dsCopyToast">Tabel berhasil dicopy.</div>

    <script>
        (function () {
            const wrap = document.getElementById('dsExport');
            const toggle = document.getElementById('dsExportToggle');
            const copyBtn = document.getElementById('dsCopyBtn');
            const toast = document.getElementById('dsCopyToast');
            const table = document.querySelector('.ds-table');

            if (!wrap || !toggle || !copyBtn || !table) return;

            toggle.addEventListener('click', function () {
                wrap.classList.toggle('open');
            });

            document.addEventListener('click', function (event) {
                if (!wrap.contains(event.target)) {
                    wrap.classList.remove('open');
                }
            });

            copyBtn.addEventListener('click', async function () {
                const lines = [];
                const rows = table.querySelectorAll('tr');

                rows.forEach(function (tr) {
                    const cols = tr.querySelectorAll('th,td');
                    const values = [];
                    cols.forEach(function (cell) {
                        values.push((cell.textContent || '').trim().replace(/\s+/g, ' '));
                    });
                    if (values.length > 0) lines.push(values.join('\t'));
                });

                const text = lines.join('\n');

                try {
                    await navigator.clipboard.writeText(text);
                    if (toast) {
                        toast.classList.add('show');
                        setTimeout(function () {
                            toast.classList.remove('show');
                        }, 1300);
                    }
                } catch (e) {
                    // fallback for older browsers
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }

                wrap.classList.remove('open');
            });
        })();
    </script>
@endsection
