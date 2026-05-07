@extends('layouts.app')

@section('content')
    <style>
        .bp-form-card { width: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); }
        .bp-form-body { padding: 18px; }
        .bp-field-wrap { display: grid; gap: 10px; padding: 12px; border: 1px solid #eef2f7; border-radius: 10px; background: #fafafa; }
        .bp-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #374151; }
        .bp-required { color: #ef4444; }
        .bp-input { width: 100%; height: 42px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; outline: none; font-size: 14px; }
        .bp-search-select { position: relative; }
        .bp-search-toggle {
            width: 100%;
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 12px;
            background: #fff;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }
        .bp-search-select.open .bp-search-toggle,
        .bp-search-toggle:focus { border-color: #4f6ef7; outline: none; }
        .bp-search-label.placeholder { color: #9ca3af; }
        .bp-search-panel {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            z-index: 20;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
            padding: 8px;
            display: none;
        }
        .bp-search-select.open .bp-search-panel { display: block; }
        .bp-search-input {
            width: 100%;
            height: 36px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0 10px;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .bp-search-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #eef2f7;
            border-radius: 6px;
            padding: 4px;
        }
        .bp-search-item {
            width: 100%;
            border: 0;
            background: #fff;
            text-align: left;
            padding: 8px 10px;
            font-size: 13px;
            border-radius: 6px;
            cursor: pointer;
        }
        .bp-search-item:hover,
        .bp-search-item.active { background: #eef4ff; color: #1d4ed8; }
        .bp-search-empty { padding: 8px 10px; color: #6b7280; font-size: 12px; }
        .bp-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
        .bp-btn { height: 42px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .bp-btn-cancel { border: 1px solid #d1d5db; background: #fff; color: #4b5563; }
        .bp-btn-save { border: 1px solid #4f6ef7; background: #4f6ef7; color: #fff; }
        .bp-error-box { margin-top: 12px; margin-bottom: 12px; padding: 10px 12px; border-radius: 8px; background: #fef2f2; color: #b91c1c; font-size: 13px; }
    </style>

    <div class="page-heading">
        <h2>Tambah Beban Post</h2>
        <p>Isi data Tahun Akademik, Kelas, Kode Akun, dan Nominal.</p>
    </div>

    <div class="bp-form-card">
        <div class="bp-form-body">
            <form method="POST" action="{{ route('master.beban_post.store') }}">
                @csrf
                @if ($errors->any())
                    <div class="bp-error-box">{{ $errors->first() }}</div>
                @endif
                <div class="bp-field-wrap">
                    <div>
                        <label class="bp-label">Tahun Akademik <span class="bp-required">*</span></label>
                        <select name="thn_masuk" class="bp-input" required>
                            <option value="">Pilih Tahun Akademik</option>
                            @foreach (($thnAkaOptions ?? []) as $thn)
                                @php
                                    $label = is_array($thn)
                                        ? (string) ($thn['thn_masuk'] ?? $thn['THN_MASUK'] ?? $thn['thn_aka'] ?? $thn['THN_AKA'] ?? '')
                                        : (string) $thn;
                                @endphp
                                @if ($label !== '')
                                    <option value="{{ $label }}" {{ old('thn_masuk') === $label ? 'selected' : '' }}>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bp-label">Kelas <span class="bp-required">*</span></label>
                        <div class="bp-search-select" id="kelas-search-select">
                            <input type="hidden" name="kode_prod" id="kelas-search-value" value="{{ old('kode_prod') }}" required>
                            <button type="button" class="bp-search-toggle" id="kelas-search-toggle">
                                <span class="bp-search-label placeholder" id="kelas-search-label">Pilih Kelas</span>
                                <span>▾</span>
                            </button>
                            <div class="bp-search-panel" id="kelas-search-panel">
                                <input type="text" class="bp-search-input" id="kelas-search-input" placeholder="Cari kelas...">
                                <div class="bp-search-list" id="kelas-search-list">
                                    @foreach (($kelasOptions ?? []) as $kls)
                                        @php
                                            $kp = (string) ($kls['id'] ?? '');
                                            $nk = (string) ($kls['kelas'] ?? '');
                                            $un = (string) ($kls['unit'] ?? '');
                                            $kl = (string) ($kls['kelompok'] ?? '');
                                            $jg = (string) ($kls['jenjang'] ?? '');
                                            $parts = array_values(array_filter([$un, $nk, $kl, $jg], static fn ($v) => $v !== ''));
                                            $label = implode(' - ', $parts);
                                        @endphp
                                        @if ($kp !== '' && $label !== '')
                                            <button type="button" class="bp-search-item" data-value="{{ $kp }}" data-label="{{ $label }}">{{ $label }}</button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="bp-label">Kode Akun <span class="bp-required">*</span></label>
                        <select name="kode_akun" class="bp-input" required>
                            <option value="">Pilih Kode Akun</option>
                            @foreach (($akunOptions ?? []) as $akn)
                                @php
                                    $ka = (string) ($akn['KodeAkun'] ?? $akn['kodeakun'] ?? '');
                                    $na = (string) ($akn['NamaAkun'] ?? $akn['namaakun'] ?? '');
                                @endphp
                                <option value="{{ $ka }}" {{ old('kode_akun') === $ka ? 'selected' : '' }}>{{ $ka . ($na !== '' ? ' - '.$na : '') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bp-label">Nominal <span class="bp-required">*</span></label>
                        <input name="nominal" type="text" class="bp-input" value="{{ old('nominal') }}" placeholder="Rp. Nominal" required>
                    </div>
                </div>

                <div class="bp-actions">
                    <a class="bp-btn bp-btn-cancel" href="{{ route('master.beban_post') }}">Batal</a>
                    <button class="bp-btn bp-btn-save" type="submit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var nominalInput = document.querySelector('input[name="nominal"]');
            var kelasWrap = document.getElementById('kelas-search-select');
            var kelasToggle = document.getElementById('kelas-search-toggle');
            var kelasPanel = document.getElementById('kelas-search-panel');
            var kelasInput = document.getElementById('kelas-search-input');
            var kelasList = document.getElementById('kelas-search-list');
            var kelasValue = document.getElementById('kelas-search-value');
            var kelasLabel = document.getElementById('kelas-search-label');
            var kelasItems = kelasList ? Array.from(kelasList.querySelectorAll('.bp-search-item')) : [];

            if (kelasWrap && kelasToggle && kelasPanel && kelasInput && kelasList && kelasValue && kelasLabel) {
                var emptyNode = document.createElement('div');
                emptyNode.className = 'bp-search-empty';
                emptyNode.textContent = 'Kelas tidak ditemukan';

                var setSelectedKelas = function(val) {
                    kelasValue.value = val;
                    var active = null;
                    kelasItems.forEach(function(item) {
                        var yes = item.getAttribute('data-value') === val;
                        item.classList.toggle('active', yes);
                        if (yes) active = item;
                    });
                    if (active) {
                        kelasLabel.textContent = active.getAttribute('data-label') || 'Pilih Kelas';
                        kelasLabel.classList.remove('placeholder');
                    } else {
                        kelasLabel.textContent = 'Pilih Kelas';
                        kelasLabel.classList.add('placeholder');
                    }
                };

                var filterKelas = function() {
                    var q = (kelasInput.value || '').trim().toLowerCase();
                    var shown = 0;
                    kelasItems.forEach(function(item) {
                        var label = (item.getAttribute('data-label') || '').toLowerCase();
                        var ok = q === '' || label.indexOf(q) !== -1;
                        item.style.display = ok ? '' : 'none';
                        if (ok) shown++;
                    });
                    if (shown === 0) {
                        if (!kelasList.contains(emptyNode)) kelasList.appendChild(emptyNode);
                    } else if (kelasList.contains(emptyNode)) {
                        kelasList.removeChild(emptyNode);
                    }
                };

                var closeKelas = function() {
                    kelasWrap.classList.remove('open');
                    kelasInput.value = '';
                    filterKelas();
                };

                kelasToggle.addEventListener('click', function() {
                    kelasWrap.classList.toggle('open');
                    if (kelasWrap.classList.contains('open')) {
                        filterKelas();
                        setTimeout(function() { kelasInput.focus(); }, 0);
                    }
                });
                kelasInput.addEventListener('input', filterKelas);
                kelasItems.forEach(function(item) {
                    item.addEventListener('click', function() {
                        setSelectedKelas(item.getAttribute('data-value') || '');
                        closeKelas();
                    });
                });
                document.addEventListener('click', function(e) {
                    if (!kelasWrap.contains(e.target)) closeKelas();
                });
                setSelectedKelas(kelasValue.value || '');
            }

            if (!nominalInput) return;

            var formatRibuan = function (value) {
                var digits = (value || '').replace(/\D+/g, '');
                if (!digits) return '';
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };

            nominalInput.addEventListener('input', function () {
                nominalInput.value = formatRibuan(nominalInput.value);
            });

            nominalInput.value = formatRibuan(nominalInput.value);
        })();
    </script>
@endsection

