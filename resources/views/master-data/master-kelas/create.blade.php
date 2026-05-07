@extends('layouts.app')

@section('content')
    <style>
        .mk-form-card {
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .mk-form-body {
            padding: 18px;
        }

        .mk-field-wrap {
            display: grid;
            gap: 10px;
            padding: 12px;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            background: #fafafa;
        }

        .mk-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #374151;
        }

        .mk-required {
            color: #ef4444;
        }

        .mk-input {
            width: 100%;
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 12px;
            outline: none;
            font-size: 14px;
        }

        .mk-input:focus {
            border-color: #4f6ef7;
        }

        .mk-search-select {
            position: relative;
        }

        .mk-search-select-toggle {
            width: 100%;
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            padding: 0 12px;
            outline: none;
            font-size: 14px;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #111827;
        }

        .mk-search-select-toggle:focus,
        .mk-search-select.open .mk-search-select-toggle {
            border-color: #4f6ef7;
        }

        .mk-search-select-toggle .placeholder {
            color: #9ca3af;
        }

        .mk-search-select-panel {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 6px);
            z-index: 20;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
            padding: 8px;
            display: none;
        }

        .mk-search-select.open .mk-search-select-panel {
            display: block;
        }

        .mk-search-select-input {
            width: 100%;
            height: 36px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0 10px;
            outline: none;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .mk-search-select-list {
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #eef2f7;
            border-radius: 6px;
            padding: 4px;
        }

        .mk-search-select-item {
            border: 0;
            width: 100%;
            text-align: left;
            padding: 8px 10px;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            color: #111827;
        }

        .mk-search-select-item:hover,
        .mk-search-select-item.active {
            background: #eef4ff;
            color: #1d4ed8;
        }

        .mk-search-select-empty {
            padding: 8px 10px;
            font-size: 12px;
            color: #6b7280;
        }

        .mk-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 14px;
        }

        .mk-btn {
            height: 42px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .mk-btn-cancel {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #4b5563;
        }

        .mk-btn-save {
            border: 1px solid #4f6ef7;
            background: #4f6ef7;
            color: #fff;
        }

        .mk-error-box {
            margin-top: 12px;
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
        }
    </style>

    <div class="page-heading">
        <h2>Tambah Master Kelas</h2>
        <p>Isi data Unit, Kelas, dan Kelompok.</p>
    </div>

    <div class="mk-form-card">
        <div class="mk-form-body">
            <form method="POST" action="{{ route('master.kelas.store') }}">
                @csrf

                @if ($errors->any())
                    <div class="mk-error-box">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mk-field-wrap">
                    <div>
                        <label class="mk-label">Unit <span class="mk-required">*</span></label>
                        <div class="mk-search-select" id="unit-search-select">
                            <input type="hidden" name="unit" value="{{ old('unit') }}" required>
                            <button type="button" class="mk-search-select-toggle" id="unit-select-toggle">
                                <span id="unit-select-label" class="{{ old('unit') === '' ? 'placeholder' : '' }}">
                                    {{ old('unit') !== '' ? old('unit') : 'Pilih Unit' }}
                                </span>
                                <span>▾</span>
                            </button>
                            <div class="mk-search-select-panel" id="unit-select-panel">
                                <input type="text" class="mk-search-select-input" id="unit-select-search" placeholder="Cari unit...">
                                <div class="mk-search-select-list" id="unit-select-list">
                                    @foreach (($unitOptions ?? []) as $unit)
                                        <button type="button" class="mk-search-select-item" data-value="{{ $unit }}">{{ $unit }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mk-label">Kelas <span class="mk-required">*</span></label>
                        <input name="kelas" type="text" class="mk-input" value="{{ old('kelas') }}" placeholder="Kelas" required>
                    </div>

                    <div>
                        <label class="mk-label">Kelompok <span class="mk-required">*</span></label>
                        <input name="kelompok" type="text" class="mk-input" value="{{ old('kelompok') }}" placeholder="Kelompok" required>
                    </div>

                    <input type="hidden" name="jenjang" value="{{ old('jenjang') }}">
                </div>

                <div class="mk-actions">
                    <a class="mk-btn mk-btn-cancel" href="{{ route('master.kelas') }}">Batal</a>
                    <button class="mk-btn mk-btn-save" type="submit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var selectWrap = document.getElementById('unit-search-select');
            var selectToggle = document.getElementById('unit-select-toggle');
            var selectPanel = document.getElementById('unit-select-panel');
            var selectSearch = document.getElementById('unit-select-search');
            var selectList = document.getElementById('unit-select-list');
            var selectLabel = document.getElementById('unit-select-label');
            var selectHidden = document.querySelector('input[name="unit"]');

            if (selectWrap && selectToggle && selectPanel && selectSearch && selectList && selectLabel && selectHidden) {
                var optionButtons = Array.from(selectList.querySelectorAll('.mk-search-select-item'));
                var emptyNode = document.createElement('div');
                emptyNode.className = 'mk-search-select-empty';
                emptyNode.textContent = 'Unit tidak ditemukan';

                var setValue = function(value) {
                    selectHidden.value = value;
                    if (value) {
                        selectLabel.textContent = value;
                        selectLabel.classList.remove('placeholder');
                    } else {
                        selectLabel.textContent = 'Pilih Unit';
                        selectLabel.classList.add('placeholder');
                    }
                    optionButtons.forEach(function(btn) {
                        btn.classList.toggle('active', btn.getAttribute('data-value') === value);
                    });
                };

                var filterOptions = function() {
                    var keyword = (selectSearch.value || '').trim().toLowerCase();
                    var visibleCount = 0;
                    optionButtons.forEach(function(btn) {
                        var val = (btn.getAttribute('data-value') || '').toLowerCase();
                        var show = keyword === '' || val.indexOf(keyword) !== -1;
                        btn.style.display = show ? '' : 'none';
                        if (show) visibleCount++;
                    });
                    if (visibleCount === 0) {
                        if (!selectList.contains(emptyNode)) selectList.appendChild(emptyNode);
                    } else if (selectList.contains(emptyNode)) {
                        selectList.removeChild(emptyNode);
                    }
                };

                var openPanel = function() {
                    selectWrap.classList.add('open');
                    filterOptions();
                    setTimeout(function() {
                        selectSearch.focus();
                    }, 0);
                };

                var closePanel = function() {
                    selectWrap.classList.remove('open');
                    selectSearch.value = '';
                    filterOptions();
                };

                selectToggle.addEventListener('click', function() {
                    if (selectWrap.classList.contains('open')) {
                        closePanel();
                    } else {
                        openPanel();
                    }
                });

                selectSearch.addEventListener('input', filterOptions);

                optionButtons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        setValue(btn.getAttribute('data-value') || '');
                        closePanel();
                    });
                });

                document.addEventListener('click', function(e) {
                    if (!selectWrap.contains(e.target)) {
                        closePanel();
                    }
                });

                setValue(selectHidden.value || '');
            }

            var kelasInput = document.querySelector('input[name="kelas"]');
            var jenjangInput = document.querySelector('input[name="jenjang"]');
            if (!kelasInput || !jenjangInput) return;

            var syncJenjang = function() {
                jenjangInput.value = kelasInput.value.trim();
            };

            kelasInput.addEventListener('input', syncJenjang);
            syncJenjang();
        })();
    </script>
@endsection

