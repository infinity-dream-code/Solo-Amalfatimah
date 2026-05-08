<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('Amal Fataimah Surakarta.jpg.jpeg') }}" alt="Logo">
        <div>
            <div class="sidebar-brand-title">Solo Amal Fatimah</div>
            <div class="sidebar-brand-sub">Sistem Keuangan</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Beranda
        </a>

        <button type="button" class="sidebar-item has-children {{ request()->routeIs('master.*') ? 'open active' : '' }}" id="mdToggle" onclick="toggleMasterData()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
            Master Data
            <span class="chevron">›</span>
        </button>
        <div class="sidebar-subnav {{ request()->routeIs('master.*') ? 'open' : '' }}" id="mdSubnav">
            <a href="{{ route('master.kelas') }}" class="{{ request()->routeIs('master.kelas*') ? 'active' : '' }}">Master Kelas</a>
            <a href="{{ route('master.sekolah') }}" class="{{ request()->routeIs('master.sekolah*') ? 'active' : '' }}">Master Sekolah</a>
            <a href="{{ route('master.tahun_pelajaran') }}" class="{{ request()->routeIs('master.tahun_pelajaran*') ? 'active' : '' }}">Tahun Pelajaran</a>
            <a href="{{ route('master.post') }}" class="{{ request()->routeIs('master.post*') ? 'active' : '' }}">Master Post</a>
            <a href="{{ route('master.beban_post') }}" class="{{ request()->routeIs('master.beban_post*') ? 'active' : '' }}">Beban Post</a>
            <a href="{{ route('master.export_import') }}" class="{{ request()->routeIs('master.export_import*') ? 'active' : '' }}">Export Import Data</a>
            <a href="{{ route('master.data_siswa') }}" class="{{ request()->routeIs('master.data_siswa*') ? 'active' : '' }}">Data Siswa</a>
            <a href="{{ route('master.setting_atribut_siswa') }}" class="{{ request()->routeIs('master.setting_atribut_siswa*') ? 'active' : '' }}">Setting Atribut Siswa</a>
            <a href="{{ route('master.pindah_kelas') }}" class="{{ request()->routeIs('master.pindah_kelas*') ? 'active' : '' }}">Pindah Kelas</a>
        </div>

        <button type="button" class="sidebar-item has-children {{ request()->routeIs('keu.*') ? 'open active' : '' }}" id="keuToggle" onclick="toggleKeuangan()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            Keuangan
            <span class="chevron">›</span>
        </button>
        <div class="sidebar-subnav {{ request()->routeIs('keu.*') ? 'open' : '' }}" id="keuSubnav">
            <button type="button" class="sidebar-item has-children {{ request()->routeIs('keu.tagihan.*') ? 'open' : '' }}" id="tagihanSiswaToggle" onclick="toggleTagihanSiswa()">
                Tagihan Siswa
                <span class="chevron">›</span>
            </button>
            <div class="sidebar-subnav {{ request()->routeIs('keu.tagihan.*') ? 'open' : '' }}" id="tagihanSiswaSubnav" style="padding-left:14px;">
                <a href="{{ route('keu.tagihan.buat') }}" class="{{ request()->routeIs('keu.tagihan.buat') ? 'active' : '' }}">Buat Tagihan</a>
                <a href="{{ route('keu.tagihan.upload_excel') }}" class="{{ request()->routeIs('keu.tagihan.upload_excel') ? 'active' : '' }}">Upload Tagihan Excel</a>
                <a href="{{ route('keu.tagihan.upload_pmb') }}" class="{{ request()->routeIs('keu.tagihan.upload_pmb') ? 'active' : '' }}">Upload Tagihan PMB</a>
                <a href="{{ route('keu.tagihan.data') }}" class="{{ request()->routeIs('keu.tagihan.data') ? 'active' : '' }}">Data Tagihan</a>
                <a href="{{ route('keu.tagihan.export') }}" class="{{ request()->routeIs('keu.tagihan.export') ? 'active' : '' }}">Export Tagihan</a>
                <a href="{{ route('keu.tagihan.rekap') }}" class="{{ request()->routeIs('keu.tagihan.rekap') ? 'active' : '' }}">Rekap Tagihan</a>
            </div>

            <a href="{{ route('keu.manual') }}" class="{{ request()->routeIs('keu.manual') ? 'active' : '' }}">Manual Pembayaran</a>
            <a href="{{ route('keu.manual_nis') }}" class="{{ request()->routeIs('keu.manual_nis') ? 'active' : '' }}">Manual Pembayaran NIS</a>
            <a href="{{ route('keu.manual_non_siswa') }}" class="{{ request()->routeIs('keu.manual_non_siswa') ? 'active' : '' }}">Manual Pembayaran No Pendaftaran</a>

            <button type="button" class="sidebar-item has-children {{ request()->routeIs('keu.penerimaan.*') ? 'open' : '' }}" id="penerimaanSiswaToggle" onclick="togglePenerimaanSiswa()">
                Penerimaan Siswa
                <span class="chevron">›</span>
            </button>
            <div class="sidebar-subnav {{ request()->routeIs('keu.penerimaan.*') ? 'open' : '' }}" id="penerimaanSiswaSubnav" style="padding-left:14px;">
                <a href="{{ route('keu.penerimaan.data') }}" class="{{ request()->routeIs('keu.penerimaan.data') ? 'active' : '' }}">Data Penerimaan</a>
                <a href="{{ route('keu.penerimaan.rekap') }}" class="{{ request()->routeIs(['keu.penerimaan.rekap', 'keu.penerimaan.rekap_rows']) ? 'active' : '' }}">Rekap Penerimaan</a>
            </div>

            <button type="button" class="sidebar-item has-children {{ request()->routeIs('keu.saldo.*') ? 'open' : '' }}" id="saldoToggle" onclick="toggleSaldo()">
                Saldo
                <span class="chevron">›</span>
            </button>
            <div class="sidebar-subnav {{ request()->routeIs('keu.saldo.*') ? 'open' : '' }}" id="saldoSubnav" style="padding-left:14px;">
                <a href="{{ route('keu.saldo.va') }}" class="{{ request()->routeIs(['keu.saldo.va', 'keu.saldo.va.rows', 'keu.saldo.va.detail', 'keu.saldo.va.detail_rows']) ? 'active' : '' }}">Saldo Virtual Account</a>
                <a href="{{ route('keu.saldo.transaksi') }}" class="{{ request()->routeIs('keu.saldo.transaksi') ? 'active' : '' }}">Data Transaksi</a>
            </div>

            <a href="{{ route('keu.hapus_tagihan') }}" class="{{ request()->routeIs(['keu.hapus_tagihan', 'keu.hapus_tagihan.rows', 'keu.hapus_tagihan.submit']) ? 'active' : '' }}">Hapus Tagihan</a>
            <a href="{{ route('keu.biaya_admin') }}" class="{{ request()->routeIs('keu.biaya_admin') ? 'active' : '' }}">Data Biaya Admin</a>
        </div>

        <button type="button" class="sidebar-item has-children {{ request()->routeIs('manual_input.*') ? 'open active' : '' }}" id="manualInputToggle" onclick="toggleManualInput()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Manual Input
            <span class="chevron">›</span>
        </button>
        <div class="sidebar-subnav {{ request()->routeIs('manual_input.*') ? 'open' : '' }}" id="manualInputSubnav" style="padding-left:14px;">
            <a href="{{ route('manual_input.edit_manual') }}" class="{{ request()->routeIs('manual_input.edit_manual') ? 'active' : '' }}">Edit Manual</a>
        </div>

        <button type="button" class="sidebar-item has-children {{ request()->routeIs('rekap.*') ? 'open active' : '' }}" id="rekapDataToggle" onclick="toggleRekapData()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Rekap Data
            <span class="chevron">›</span>
        </button>
        <div class="sidebar-subnav {{ request()->routeIs('rekap.*') ? 'open' : '' }}" id="rekapDataSubnav" style="padding-left:14px;">
            <a href="{{ route('rekap.cek_pelunasan') }}" class="{{ request()->routeIs('rekap.cek_pelunasan') ? 'active' : '' }}">Cek Pelunasan</a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="sidebar-item logout" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;font-family:inherit;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar
            </button>
        </form>
    </div>
</aside>

