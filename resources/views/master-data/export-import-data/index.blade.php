@extends('layouts.app')

@section('content')
    <style>
        .eid-wrap { margin-top: 16px; }
        .eid-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .eid-head {
            padding: 14px 16px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .eid-title { font-size: 24px; font-weight: 800; color: #111827; }
        .eid-btn-import {
            border: 0;
            background: #22c55e;
            color: #fff;
            height: 36px;
            padding: 0 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .eid-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            flex-wrap: wrap;
            border-bottom: 1px solid #eef2f7;
        }
        .eid-left, .eid-right { display: flex; align-items: center; gap: 8px; color: #4b5563; font-size: 13px; }
        .eid-select, .eid-input {
            height: 34px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 10px;
            font-size: 12px;
        }
        .eid-input { width: 180px; }
        .eid-btn-tool {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            height: 34px;
            padding: 0 12px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
        }
        .eid-table-wrap { overflow-x: auto; }
        .eid-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
            font-size: 13px;
        }
        .eid-table th, .eid-table td {
            border-bottom: 1px solid #eef2f7;
            padding: 9px 10px;
            white-space: nowrap;
            text-align: left;
        }
        .eid-table th {
            background: #fafbfd;
            color: #4b5563;
            font-size: 12px;
            font-weight: 700;
        }
        .eid-empty { text-align: center; color: #6b7280; padding: 18px; }
        .eid-footer {
            padding: 12px 16px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .eid-info { color: #6b7280; font-size: 12px; }
        .eid-pagination { display: flex; align-items: center; gap: 6px; }
        .eid-page {
            min-width: 30px;
            height: 30px;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #4b5563;
            font-size: 12px;
            font-weight: 700;
            background: #fff;
        }
        .eid-page.active {
            background: #4f6ef7;
            color: #fff;
            border-color: #4f6ef7;
        }
        .eid-page.disabled {
            pointer-events: none;
            color: #9ca3af;
            border-color: #e5e7eb;
            background: #f9fafb;
        }
        .eid-alert {
            margin: 10px 16px 0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .eid-alert.ok { background: #ecfdf5; color: #047857; }
        .eid-alert.err { background: #fef2f2; color: #b91c1c; }
        .eid-modal {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.35);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 80;
            padding: 14px;
        }
        .eid-modal.open { display: flex; }
        .eid-modal-box {
            width: 100%;
            max-width: 540px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }
        .eid-modal-h {
            padding: 14px 18px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .eid-modal-h h3 { margin: 0; font-size: 28px; font-weight: 700; color: #111827; }
        .eid-close {
            border: 0;
            background: transparent;
            font-size: 18px;
            color: #6b7280;
            cursor: pointer;
        }
        .eid-modal-b { padding: 0 18px 16px; }
        .eid-rules { margin: 0 0 12px; padding-left: 18px; color: #374151; font-size: 13px; }
        .eid-rules li { margin: 8px 0; }
        .eid-file {
            border: 1px dashed #9ca3af;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            margin-bottom: 14px;
        }
        .eid-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .eid-btn {
            height: 38px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }
        .eid-btn-cancel { background: #fff; border: 1px solid #d1d5db; color: #4b5563; }
        .eid-btn-submit { background: #4f6ef7; border: 1px solid #4f6ef7; color: #fff; }
    </style>

    <div class="page-heading">
        <h2>Export Import Data</h2>
        <p>Import data siswa dari file sesuai format.</p>
    </div>

    <div class="eid-wrap">
        <div class="eid-card">
            <div class="eid-head">
                <div class="eid-title">Export Import Data</div>
                <button type="button" class="eid-btn-import" id="eidOpenImport">Import Data Siswa</button>
            </div>

            @if (session('status'))
                <div class="eid-alert ok">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="eid-alert err">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="eid-alert err">{{ $errors->first() }}</div>
            @endif

            <div class="eid-toolbar">
                <form method="GET" action="{{ route('master.export_import') }}" class="eid-left">
                    <span>Tampilkan</span>
                    <select class="eid-select" name="per_page" onchange="this.form.submit()">
                        <option value="10" {{ (int) ($perPage ?? 10) === 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ (int) ($perPage ?? 10) === 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ (int) ($perPage ?? 10) === 50 ? 'selected' : '' }}>50</option>
                    </select>
                    @if (($keyword ?? '') !== '')
                        <input type="hidden" name="q" value="{{ $keyword }}">
                    @endif
                    <span>entri</span>
                </form>

                <form method="GET" action="{{ route('master.export_import') }}" class="eid-right">
                    <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 10) }}">
                    <span>Cari:</span>
                    <input type="text" class="eid-input" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
                    <button class="eid-btn-tool" type="submit">Cari</button>
                </form>
            </div>

            <div class="eid-table-wrap">
                <table class="eid-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>NO PEND</th>
                            <th>NAMA</th>
                            <th>UNIT</th>
                            <th>KELAS</th>
                            <th>KELOMPOK</th>
                            <th>ANGKATAN</th>
                            <th>JENIS KELAMIN</th>
                            <th>ALAMAT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($importRows ?? []) as $index => $row)
                            <tr>
                                <td>{{ ($importRows->firstItem() ?? 1) + $index }}</td>
                                <td>{{ $row['nis'] ?? '-' }}</td>
                                <td>{{ $row['nodaf'] ?? '-' }}</td>
                                <td>{{ $row['nama'] ?? '-' }}</td>
                                <td>{{ $row['unit'] ?? '-' }}</td>
                                <td>{{ $row['kelas'] ?? '-' }}</td>
                                <td>{{ $row['kelompok'] ?? '-' }}</td>
                                <td>{{ $row['angkatan'] ?? '-' }}</td>
                                <td>{{ $row['gender'] ?? '-' }}</td>
                                <td>{{ $row['alamat'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="eid-empty">Tidak ada data yang tersedia pada tabel ini</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="eid-footer">
                <div class="eid-info">
                    Menampilkan {{ $importRows->firstItem() ?? 0 }} sampai {{ $importRows->lastItem() ?? 0 }} dari {{ $importRows->total() ?? 0 }} entri
                </div>
                <div class="eid-pagination">
                    @php
                        $current = $importRows->currentPage();
                        $last = $importRows->lastPage();
                    @endphp
                    @if ($importRows->onFirstPage())
                        <span class="eid-page disabled">Sebelumnya</span>
                    @else
                        <a class="eid-page" href="{{ $importRows->appends(request()->query())->url($current - 1) }}">Sebelumnya</a>
                    @endif
                    @for ($p = max(1, $current - 1); $p <= min($last, $current + 1); $p++)
                        @if ($p === $current)
                            <span class="eid-page active">{{ $p }}</span>
                        @else
                            <a class="eid-page" href="{{ $importRows->appends(request()->query())->url($p) }}">{{ $p }}</a>
                        @endif
                    @endfor
                    @if ($importRows->hasMorePages())
                        <a class="eid-page" href="{{ $importRows->appends(request()->query())->url($current + 1) }}">Selanjutnya</a>
                    @else
                        <span class="eid-page disabled">Selanjutnya</span>
                    @endif
                </div>
            </div>
            <div style="padding:0 16px 16px;display:flex;justify-content:flex-end;gap:10px;">
                <form method="POST" action="{{ route('master.export_import.clear') }}">
                    @csrf
                    <button type="submit" class="eid-btn eid-btn-cancel" style="min-width:120px;">Bersihkan Data</button>
                </form>
                <form method="POST" action="{{ route('master.export_import.save') }}">
                    @csrf
                    <button type="submit" class="eid-btn eid-btn-submit" style="min-width:120px;" {{ (($importRows->total() ?? 0) > 0) ? '' : 'disabled' }}>Simpan Data</button>
                </form>
            </div>
        </div>
    </div>

    <div class="eid-modal" id="eidImportModal" aria-hidden="true">
        <div class="eid-modal-box">
            <div class="eid-modal-h">
                <h3>Import Data Siswa</h3>
                <button type="button" class="eid-close" id="eidCloseImport" aria-label="Tutup">×</button>
            </div>
            <div class="eid-modal-b">
                <ul class="eid-rules">
                    <li>File harus berformat <b>XLSX</b>.</li>
                    <li>Ukuran file tidak boleh lebih dari <b>1024KB / 1MB</b>.</li>
                    <li>Kolom yang harus terisi: <b>NIS</b>.</li>
                    <li>Contoh file import:
                        <a href="{{ asset('format.xlsx') }}" target="_blank" rel="noopener">format.xlsx</a>
                    </li>
                </ul>

                <form method="POST" action="{{ route('master.export_import.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="eid-file">
                        <input type="hidden" name="preview_rows" id="eidPreviewRows">
                        <input type="file" name="file" id="eidFileInput" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    </div>
                    <div class="eid-actions">
                        <button type="button" class="eid-btn eid-btn-cancel" id="eidCancelImport">Batal</button>
                        <button type="submit" class="eid-btn eid-btn-submit">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        (function () {
            const modal = document.getElementById('eidImportModal');
            const openBtn = document.getElementById('eidOpenImport');
            const closeBtn = document.getElementById('eidCloseImport');
            const cancelBtn = document.getElementById('eidCancelImport');
            const fileInput = document.getElementById('eidFileInput');
            const previewInput = document.getElementById('eidPreviewRows');

            if (!modal || !openBtn || !closeBtn || !cancelBtn || !fileInput || !previewInput) return;

            const open = function () {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            };
            const close = function () {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            };

            openBtn.addEventListener('click', open);
            closeBtn.addEventListener('click', close);
            cancelBtn.addEventListener('click', close);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) close();
            });

            fileInput.addEventListener('change', function (event) {
                const file = event.target.files && event.target.files[0];
                if (!file || typeof XLSX === 'undefined') {
                    previewInput.value = '[]';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        const wb = XLSX.read(e.target.result, { type: 'binary' });
                        const ws = wb.Sheets[wb.SheetNames[0]];
                        const rows = XLSX.utils.sheet_to_json(ws, { defval: '' });
                        const normalized = rows.map(function (r) {
                            return {
                                nis: String(r.NIS || '').trim(),
                                nodaf: String(r.NODAF || '').trim(),
                                nama: String(r.Nama || r.NAMA || '').trim(),
                                unit: String(r.UNIT || '').trim(),
                                kelas: String(r.KELAS || '').trim(),
                                kelompok: String(r.KELOMPOK || '').trim(),
                                angkatan: String(r.ANGKATAN || '').trim(),
                                gender: String(r.GENDER || '').trim(),
                                alamat: String(r.ALAMAT || '').trim()
                            };
                        });
                        previewInput.value = JSON.stringify(normalized);
                    } catch (err) {
                        previewInput.value = '[]';
                    }
                };
                reader.readAsBinaryString(file);
            });
        })();
    </script>
@endsection

