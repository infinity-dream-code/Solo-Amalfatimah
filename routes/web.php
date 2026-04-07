<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterData\BebanPostController;
use App\Http\Controllers\MasterData\DataSiswaController;
use App\Http\Controllers\MasterData\ExportImportDataController;
use App\Http\Controllers\MasterData\MasterKelasController;
use App\Http\Controllers\MasterData\MasterPostController;
use App\Http\Controllers\MasterData\PindahKelasController;
use App\Http\Controllers\MasterData\SettingAtributSiswaController;
use App\Http\Controllers\MasterData\TahunPelajaranController;
use App\Http\Controllers\Keuangan\LainnyaController;
use App\Http\Controllers\Keuangan\ManualPembayaranController;
use App\Http\Controllers\Keuangan\PenerimaanSiswaController;
use App\Http\Controllers\Keuangan\SaldoController;
use App\Http\Controllers\Keuangan\TagihanSiswaController;
use App\Http\Controllers\ManualInput\EditManualController;
use App\Http\Controllers\ManualInput\RekapDataController;
use App\Http\Controllers\RekapData\CekPelunasanController;
use App\Http\Controllers\RekapData\RekapDataController as RekapDataMenuController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['web', 'dummy.auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Master Data pages (ready for CRUD)
    Route::prefix('master')->name('master.')->group(function () {
        // Master Kelas
        Route::get('/kelas', [MasterKelasController::class, 'index'])->name('kelas');
        Route::get('/kelas/create', [MasterKelasController::class, 'create'])->name('kelas.create');
        Route::post('/kelas', [MasterKelasController::class, 'store'])->name('kelas.store');
        Route::get('/kelas/{id}/edit', [MasterKelasController::class, 'edit'])->name('kelas.edit');
        Route::put('/kelas/{id}', [MasterKelasController::class, 'update'])->name('kelas.update');
        Route::delete('/kelas/{id}', [MasterKelasController::class, 'destroy'])->name('kelas.destroy');

        // Tahun Pelajaran
        Route::get('/tahun-pelajaran', [TahunPelajaranController::class, 'index'])->name('tahun_pelajaran');
        Route::get('/tahun-pelajaran/create', [TahunPelajaranController::class, 'create'])->name('tahun_pelajaran.create');
        Route::post('/tahun-pelajaran', [TahunPelajaranController::class, 'store'])->name('tahun_pelajaran.store');

        // Master Post
        Route::get('/post', [MasterPostController::class, 'index'])->name('post');
        Route::get('/post/create', [MasterPostController::class, 'create'])->name('post.create');
        Route::post('/post', [MasterPostController::class, 'store'])->name('post.store');

        // Beban Post
        Route::get('/beban-post', [BebanPostController::class, 'index'])->name('beban_post');
        Route::get('/beban-post/create', [BebanPostController::class, 'create'])->name('beban_post.create');
        Route::post('/beban-post', [BebanPostController::class, 'store'])->name('beban_post.store');
        Route::get('/beban-post/{id}/edit', [BebanPostController::class, 'edit'])->name('beban_post.edit');
        Route::put('/beban-post/{id}', [BebanPostController::class, 'update'])->name('beban_post.update');
        Route::delete('/beban-post/{id}', [BebanPostController::class, 'destroy'])->name('beban_post.destroy');

        // Export Import Data
        Route::get('/export-import-data', [ExportImportDataController::class, 'index'])->name('export_import');
        Route::get('/export-import-data/export', [ExportImportDataController::class, 'export'])->name('export_import.export');
        Route::post('/export-import-data/import', [ExportImportDataController::class, 'import'])->name('export_import.import');
        Route::post('/export-import-data/save', [ExportImportDataController::class, 'save'])->name('export_import.save');
        Route::post('/export-import-data/clear', [ExportImportDataController::class, 'clear'])->name('export_import.clear');

        // Data Siswa
        Route::get('/data-siswa', [DataSiswaController::class, 'index'])->name('data_siswa');
        Route::get('/data-siswa/export/excel', [DataSiswaController::class, 'exportExcel'])->name('data_siswa.export_excel');
        Route::get('/data-siswa/export/pdf', [DataSiswaController::class, 'exportPdf'])->name('data_siswa.export_pdf');
        Route::get('/data-siswa/create', [DataSiswaController::class, 'create'])->name('data_siswa.create');
        Route::post('/data-siswa', [DataSiswaController::class, 'store'])->name('data_siswa.store');
        Route::get('/data-siswa/{id}/edit', [DataSiswaController::class, 'edit'])->name('data_siswa.edit');
        Route::put('/data-siswa/{id}', [DataSiswaController::class, 'update'])->name('data_siswa.update');
        Route::delete('/data-siswa/{id}', [DataSiswaController::class, 'destroy'])->name('data_siswa.destroy');

        // Setting Atribut Siswa
        Route::get('/setting-atribut-siswa', [SettingAtributSiswaController::class, 'index'])->name('setting_atribut_siswa');
        Route::post('/setting-atribut-siswa/import', [SettingAtributSiswaController::class, 'import'])->name('setting_atribut_siswa.import');
        Route::post('/setting-atribut-siswa/save', [SettingAtributSiswaController::class, 'save'])->name('setting_atribut_siswa.save');
        Route::post('/setting-atribut-siswa/clear', [SettingAtributSiswaController::class, 'clear'])->name('setting_atribut_siswa.clear');

        // Pindah Kelas
        Route::get('/pindah-kelas', [PindahKelasController::class, 'index'])->name('pindah_kelas');
        Route::post('/pindah-kelas', [PindahKelasController::class, 'store'])->name('pindah_kelas.store');
    });

    // Keuangan pages (ready)
    Route::prefix('keuangan')->name('keu.')->group(function () {
        // Tagihan Siswa
        Route::get('/tagihan-siswa/buat-tagihan', [TagihanSiswaController::class, 'buat'])->name('tagihan.buat');
        Route::get('/tagihan-siswa/buat-tagihan/fungsi', [TagihanSiswaController::class, 'fungsi'])->name('tagihan.fungsi');
        Route::get('/tagihan-siswa/buat-tagihan/daftar-harga', [TagihanSiswaController::class, 'daftarHarga'])->name('tagihan.daftar_harga');
        Route::post('/tagihan-siswa/buat-tagihan', [TagihanSiswaController::class, 'store'])->name('tagihan.store');
        Route::get('/tagihan-siswa/upload-tagihan-excel', [TagihanSiswaController::class, 'uploadExcel'])->name('tagihan.upload_excel');
        Route::post('/tagihan-siswa/upload-tagihan-excel/import', [TagihanSiswaController::class, 'uploadExcelImport'])->name('tagihan.upload_excel.import');
        Route::post('/tagihan-siswa/upload-tagihan-excel/save', [TagihanSiswaController::class, 'uploadExcelSave'])->name('tagihan.upload_excel.save');
        Route::post('/tagihan-siswa/upload-tagihan-excel/clear', [TagihanSiswaController::class, 'uploadExcelClear'])->name('tagihan.upload_excel.clear');
        Route::get('/tagihan-siswa/upload-tagihan-pmb', [TagihanSiswaController::class, 'uploadPmb'])->name('tagihan.upload_pmb');
        Route::post('/tagihan-siswa/upload-tagihan-pmb/import', [TagihanSiswaController::class, 'uploadPmbImport'])->name('tagihan.upload_pmb.import');
        Route::post('/tagihan-siswa/upload-tagihan-pmb/save', [TagihanSiswaController::class, 'uploadPmbSave'])->name('tagihan.upload_pmb.save');
        Route::post('/tagihan-siswa/upload-tagihan-pmb/clear', [TagihanSiswaController::class, 'uploadPmbClear'])->name('tagihan.upload_pmb.clear');
        Route::post('/tagihan-siswa/upload-tagihan-pmb', [TagihanSiswaController::class, 'uploadPmbSubmit'])->name('tagihan.upload_pmb.submit');
        Route::get('/tagihan-siswa/data-tagihan', [TagihanSiswaController::class, 'data'])->name('tagihan.data');
        Route::post('/tagihan-siswa/data-tagihan/urutan', [TagihanSiswaController::class, 'dataUrutan'])->name('tagihan.data_urutan');
        Route::post('/tagihan-siswa/data-tagihan/hapus', [TagihanSiswaController::class, 'dataHapus'])->name('tagihan.data_hapus');
        Route::post('/tagihan-siswa/data-tagihan/export-excel', [TagihanSiswaController::class, 'dataExportExcel'])->name('tagihan.data_export_excel');
        Route::post('/tagihan-siswa/data-tagihan/export-pdf', [TagihanSiswaController::class, 'dataExportPdf'])->name('tagihan.data_export_pdf');
        Route::post('/tagihan-siswa/data-tagihan/print-kartu', [TagihanSiswaController::class, 'dataPrintKartu'])->name('tagihan.data_print_kartu');
        Route::post('/tagihan-siswa/data-tagihan/print-rekap', [TagihanSiswaController::class, 'dataPrintRekap'])->name('tagihan.data_print_rekap');
        Route::get('/tagihan-siswa/data-tagihan/print', [TagihanSiswaController::class, 'dataPrint'])->name('tagihan.data_print');
        Route::get('/tagihan-siswa/export-tagihan', [TagihanSiswaController::class, 'export'])->name('tagihan.export');
        Route::match(['get', 'post'], '/tagihan-siswa/export-tagihan/print', [TagihanSiswaController::class, 'exportPrint'])->name('tagihan.export_print');
        Route::get('/tagihan-siswa/rekap-tagihan', [TagihanSiswaController::class, 'rekap'])->name('tagihan.rekap');

        // Manual Pembayaran
        Route::get('/manual-pembayaran', [ManualPembayaranController::class, 'index'])->name('manual');
        Route::post('/manual-pembayaran', [ManualPembayaranController::class, 'submit'])->name('manual.submit');
        Route::get('/manual-pembayaran-nis', [ManualPembayaranController::class, 'nis'])->name('manual_nis');
        Route::post('/manual-pembayaran-nis', [ManualPembayaranController::class, 'submit'])->name('manual_nis.submit');
        Route::get('/manual-pembayaran-non-siswa', [ManualPembayaranController::class, 'nonSiswa'])->name('manual_non_siswa');
        Route::post('/manual-pembayaran-non-siswa', [ManualPembayaranController::class, 'submit'])->name('manual_non_siswa.submit');

        // Penerimaan Siswa
        Route::get('/penerimaan-siswa/data', [PenerimaanSiswaController::class, 'data'])->name('penerimaan.data');
        Route::get('/penerimaan-siswa/data/rows', [PenerimaanSiswaController::class, 'dataRows'])->name('penerimaan.data_rows');
        Route::post('/penerimaan-siswa/kartu-siswa', [PenerimaanSiswaController::class, 'printKartuSiswa'])->name('penerimaan.kartu_siswa');
        Route::post('/penerimaan-siswa/kuitansi', [PenerimaanSiswaController::class, 'printKuitansi'])->name('penerimaan.kuitansi');
        Route::match(['get', 'post'], '/penerimaan-siswa/rekap-pdf', [PenerimaanSiswaController::class, 'printRekapPdf'])->name('penerimaan.rekap_pdf');
        Route::match(['get', 'post'], '/penerimaan_siswa/rekap_pdf', [PenerimaanSiswaController::class, 'printRekapPdf']);
        Route::get('/penerimaan-siswa/rekap', [PenerimaanSiswaController::class, 'rekap'])->name('penerimaan.rekap');
        Route::get('/penerimaan-siswa/rekap/rows', [PenerimaanSiswaController::class, 'rekapRows'])->name('penerimaan.rekap_rows');

        // Saldo
        Route::get('/saldo/virtual-account', [SaldoController::class, 'virtualAccount'])->name('saldo.va');
        Route::get('/saldo/virtual-account/rows', [SaldoController::class, 'virtualAccountRows'])->name('saldo.va.rows');
        Route::get('/saldo/virtual-account/detail/{custid}', [SaldoController::class, 'virtualAccountDetail'])->name('saldo.va.detail')->where('custid', '[0-9]+');
        Route::get('/saldo/virtual-account/detail/{custid}/rows', [SaldoController::class, 'virtualAccountDetailRows'])->name('saldo.va.detail_rows')->where('custid', '[0-9]+');
        Route::get('/saldo/data-transaksi', [SaldoController::class, 'transaksi'])->name('saldo.transaksi');
        Route::get('/saldo/data-transaksi/rows', [SaldoController::class, 'transaksiRows'])->name('saldo.transaksi.rows');

        // Lainnya
        Route::get('/hapus-tagihan', [LainnyaController::class, 'hapusTagihan'])->name('hapus_tagihan');
        Route::get('/hapus-tagihan/rows', [LainnyaController::class, 'hapusTagihanRows'])->name('hapus_tagihan.rows');
        Route::post('/hapus-tagihan/hapus', [LainnyaController::class, 'hapusTagihanSubmit'])->name('hapus_tagihan.submit');
        Route::get('/data-biaya-admin', [LainnyaController::class, 'biayaAdmin'])->name('biaya_admin');
        Route::get('/data-biaya-admin/rows', [LainnyaController::class, 'biayaAdminRows'])->name('biaya_admin.rows');
    });

    // Manual Input pages (ready)
    Route::prefix('manual-input')->name('manual_input.')->group(function () {
        Route::get('/edit-manual', [EditManualController::class, 'index'])->name('edit_manual');
        Route::put('/edit-manual', [EditManualController::class, 'update'])->name('edit_manual.update');
        Route::get('/rekap-data', [RekapDataController::class, 'index'])->name('rekap_data');
    });

    // Rekap Data pages (ready)
    Route::prefix('rekap-data')->name('rekap.')->group(function () {
        Route::get('/rekap-data', [RekapDataMenuController::class, 'index'])->name('rekap_data');
        Route::get('/cek-pelunasan', [CekPelunasanController::class, 'index'])->name('cek_pelunasan');
        Route::get('/cek-pelunasan/rows', [CekPelunasanController::class, 'rows'])->name('cek_pelunasan.rows');
        Route::post('/cek-pelunasan/kartu-siswa', [CekPelunasanController::class, 'printKartuSiswa'])->name('cek_pelunasan.kartu_siswa');
    });
});

