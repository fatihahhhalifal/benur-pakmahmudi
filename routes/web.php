<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StokBenurController; 
use App\Http\Controllers\BiayaOperasionalController;
use App\Http\Controllers\KeranjangController; 
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\MidtransDpController; 
use App\Http\Controllers\Admin\LaporanKeuanganController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\PengaturanTambakController; 
use App\Http\Controllers\MonitoringKolamController; 
use App\Http\Controllers\SatuanPasar\KatalogController;
use App\Http\Controllers\SatuanPasar\AdminPesananController;
use App\Http\Controllers\SatuanPasar\PesananController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// =========================================================================
// 1. GERBANG UTAMA LANDING PAGE
// =========================================================================
Route::get('/', function () {
    return view('welcome');
});

// =========================================================================
// 2. ROUTE DASHBOARD (Auth & Verified) - Redirect Otomatis Berbasis Role
// =========================================================================
Route::get('/dashboard', function () {
    if (Auth::user()->role === 'customer') {
        return redirect()->route('customer.katalog');
    }
    return app(DashboardController::class)->index();
})->middleware(['auth', 'verified'])->name('dashboard');

// =========================================================================
// 3. ROUTE KHUSUS MANAGEMENT (Hanya Admin & Pemilik)
// =========================================================================
Route::middleware(['auth', 'role:admin,pemilik'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/pengaturan-tambak', [PengaturanTambakController::class, 'index'])->name('pengaturan.index');
    Route::post('/pengaturan-tambak/profil', [PengaturanTambakController::class, 'saveProfil'])->name('pengaturan.saveProfil');
    
    Route::post('/pengaturan-tambak/jenis', [PengaturanTambakController::class, 'storeJenis'])->name('pengaturan.storeJenis');
    Route::put('/pengaturan-tambak/jenis/{id}', [PengaturanTambakController::class, 'updateJenis'])->name('pengaturan.updateJenis');
    Route::delete('/pengaturan-tambak/jenis/{id}', [PengaturanTambakController::class, 'destroyJenis'])->name('pengaturan.destroyJenis');

    Route::post('/pengaturan-tambak/ukuran', [PengaturanTambakController::class, 'storeUkuran'])->name('pengaturan.storeUkuran');
    Route::put('/pengaturan-tambak/ukuran/{id}', [PengaturanTambakController::class, 'updateUkuran'])->name('pengaturan.updateUkuran');
    Route::delete('/pengaturan-tambak/ukuran/{id}', [PengaturanTambakController::class, 'destroyUkuran'])->name('pengaturan.destroyUkuran');

    Route::post('/pengaturan-tambak/grade', [PengaturanTambakController::class, 'storeGrade'])->name('pengaturan.storeGrade');
    Route::put('/pengaturan-tambak/grade/{id}', [PengaturanTambakController::class, 'updateGrade'])->name('pengaturan.updateGrade');
    Route::delete('/pengaturan-tambak/grade/{id}', [PengaturanTambakController::class, 'destroyGrade'])->name('pengaturan.destroyGrade');
    
    Route::post('/pengaturan-tambak/harga', [PengaturanTambakController::class, 'storeHarga'])->name('pengaturan.storeHarga');
    Route::put('/pengaturan-tambak/harga/{id}', [PengaturanTambakController::class, 'updateHarga'])->name('pengaturan.updateHarga');
    Route::delete('/pengaturan-tambak/harga/{id}', [PengaturanTambakController::class, 'destroyHarga'])->name('pengaturan.destroyHarga');

    Route::get('/laporan-keuangan', [LaporanKeuanganController::class, 'index'])->name('laporan.index');
    Route::get('/laporan-keuangan/cetak-semua', [LaporanKeuanganController::class, 'cetakSemua'])->name('laporan.cetak.semua');
    Route::get('/laporan-keuangan/cetak-masuk', [LaporanKeuanganController::class, 'cetakMasuk'])->name('laporan.cetak.masuk');
    Route::get('/laporan-keuangan/cetak-keluar', [LaporanKeuanganController::class, 'cetakKeluar'])->name('laporan.cetak.keluar');

    Route::get('/admin/katalog/preview', [KatalogController::class, 'adminPreview'])->name('admin.katalog.preview');
});

// =========================================================================
// 4. ROUTE ADMIN, PEMILIK & OPERATOR (Monitoring, Produksi & Logistik)
// =========================================================================
Route::middleware(['auth', 'role:admin,pemilik,operator'])->group(function () {
    Route::get('/stok', [StokBenurController::class, 'index'])->name('stok.index');
    Route::post('/stok', [StokBenurController::class, 'store'])->name('stok.store');
    Route::put('/stok/{stok}', [StokBenurController::class, 'update'])->name('stok.update');
    Route::delete('/stok/{stok}', [StokBenurController::class, 'destroy'])->name('stok.destroy');
    Route::post('/stok/{id}/sampling', [StokBenurController::class, 'storeSampling'])->name('stok.sampling');
    Route::put('/sampling/{id}', [StokBenurController::class, 'updateSampling'])->name('sampling.update');
    
    Route::get('/biaya', [BiayaOperasionalController::class, 'index'])->name('biaya.index');

    Route::get('/monitoring-kolam', [MonitoringKolamController::class, 'index'])->name('kolam.index');
    Route::post('/kolam/store', [MonitoringKolamController::class, 'storeKolam'])->name('kolam.store');
    Route::put('/kolam/update/{id}', [MonitoringKolamController::class, 'updateKolam'])->name('kolam.update');
    Route::delete('/kolam/destroy/{id}', [MonitoringKolamController::class, 'destroyKolam'])->name('kolam.destroy');

    Route::post('/kolam/tebar', [MonitoringKolamController::class, 'storeTebar'])->name('kolam.tebar');
    Route::put('/admin/kolam/siklus-update/{id}', [MonitoringKolamController::class, 'siklusUpdate'])->name('kolam.siklusUpdate');
    Route::post('/kolam/kuras/{id}', [MonitoringKolamController::class, 'kurasKolam'])->name('kolam.kuras');

    Route::post('/kolam/sampling', [MonitoringKolamController::class, 'storeSampling'])->name('kolam.sampling');
    Route::put('/monitoring-kolam/sampling-update/{id}', [MonitoringKolamController::class, 'updateSampling'])->name('kolam.sampling.update');
    Route::delete('/monitoring-kolam/sampling-destroy/{id}', [MonitoringKolamController::class, 'destroySampling'])->name('kolam.sampling.destroy');

    Route::post('/kolam/bop', [MonitoringKolamController::class, 'storeBOP'])->name('kolam.bop');
    Route::put('/monitoring-kolam/bop-update/{id}', [MonitoringKolamController::class, 'updateBOP'])->name('kolam.bop.update');
    Route::delete('/kolam/bop/{id}', [MonitoringKolamController::class, 'destroyBOP'])->name('kolam.bop.destroy');

    Route::post('/monitoring-kolam/bop-acc/{id}', [MonitoringKolamController::class, 'accBOP'])->name('kolam.bop.acc');
    Route::get('/monitoring-kolam/bop-acc/{id}', [MonitoringKolamController::class, 'accBOP']); 

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/pesanan', [AdminPesananController::class, 'index'])->name('pesanan.index');
        Route::post('/pesanan/{id}/batal', [AdminPesananController::class, 'batalkanOlehAdmin'])->name('pesanan.batal');
        
        // Rute pemisahan status operasional: Muat, Kalkulasi Admin, dan Validasi Pembayaran
        // (Verifikasi DP manual sudah dihapus — DP sepenuhnya diverifikasi otomatis oleh Midtrans)
        Route::post('/pesanan/{id}/input-muat', [AdminPesananController::class, 'inputMuat'])->name('pesanan.input-muat');
        Route::post('/pesanan/{id}/kalkulasi-final', [AdminPesananController::class, 'kalkulasiFinal'])->name('pesanan.kalkulasi-final');
        Route::post('/pesanan/{id}/validasi-pelunasan', [AdminPesananController::class, 'validasiPelunasan'])->name('pesanan.validasi-pelunasan');
        
        Route::get('/pesanan/{id}', [PesananController::class, 'dashboardAdmin'])->name('pesanan.show');
        Route::get('/pesanan/{id}/invoice', [PesananController::class, 'cetakInvoice'])->name('pesanan.invoice');
        Route::get('/pesanan/{id}/surat-jalan', [PesananController::class, 'cetakSuratJalan'])->name('pesanan.suratjalan');
    });
});

// =========================================================================
// 5. ROUTE KHUSUS CUSTOMER (Marketplace Hilir Preorder)
// =========================================================================
Route::middleware(['auth', 'role:customer'])->group(function () {
    // Katalog Live Read & Beli Langsung (Checkout Bypass)
    Route::get('/katalog', [KatalogController::class, 'index'])->name('customer.katalog');
    Route::get('/katalog/{siklus_id}', [KatalogController::class, 'show'])->name('customer.katalog.show');
    Route::post('/katalog/checkout', [KatalogController::class, 'checkout'])->name('customer.katalog.checkout');
 
    // Keranjang Belanja Sementara
    Route::get('/keranjang', [KeranjangController::class, 'index'])->name('customer.keranjang');
    Route::post('/keranjang/add', [KeranjangController::class, 'addToCart'])->name('customer.keranjang.add');
    Route::post('/keranjang/update', [KeranjangController::class, 'updateQuantity'])->name('customer.keranjang.update');
    Route::delete('/keranjang/{id}', [KeranjangController::class, 'destroy'])->name('customer.keranjang.destroy');
 
    // Checkout Integrasi dari Keranjang & Beli Langsung
    Route::post('/checkout/init', [CheckoutController::class, 'initCheckout'])->name('customer.checkout.init');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('customer.checkout.index');
    Route::post('/checkout/process', [CheckoutController::class, 'processPayment'])->name('customer.checkout.process');
 
    // Timeline & Detail Pelacak Pesanan Saya
    Route::get('/pesanan-saya', [KatalogController::class, 'riwayatPesanan'])->name('customer.pesanan.index');
    Route::get('/pesanan-saya/{id}', [KatalogController::class, 'detailPesanan'])->name('customer.pesanan.detail');
    Route::post('/pesanan-saya/{id}/batal', [KatalogController::class, 'batalkanOlehCustomer'])->name('customer.pesanan.batal');
 
    // MIDTRANS — DP (20%)
    Route::post('/pesanan-saya/{id}/midtrans/token', [MidtransDpController::class, 'buatToken'])->name('customer.pesanan.midtrans.token');
    Route::post('/pesanan-saya/{id}/midtrans/cek-status', [MidtransDpController::class, 'cekStatus'])->name('customer.pesanan.midtrans.cekstatus');
 
    // MIDTRANS — Pelunasan (setelah kalkulasi admin)
    Route::post('/pesanan-saya/{id}/midtrans/token-pelunasan', [MidtransDpController::class, 'buatTokenPelunasan'])->name('customer.pesanan.midtrans.token.pelunasan');
    Route::post('/pesanan-saya/{id}/midtrans/cek-status-pelunasan', [MidtransDpController::class, 'cekStatusPelunasan'])->name('customer.pesanan.midtrans.cekstatus.pelunasan');
 
    // Cetak Dokumen Finansial
    Route::get('/pesanan-saya/{id}/invoice', [PesananController::class, 'cetakInvoice'])->name('customer.pesanan.invoice');
    Route::get('/pesanan-saya/{id}/surat-jalan', [PesananController::class, 'cetakSuratJalan'])->name('customer.pesanan.suratjalan');
});
// =========================================================================
// 6. ROUTE PROFILE UNIVERSAL
// =========================================================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/midtrans/notification', [MidtransDpController::class, 'notificationHandler'])
    ->name('midtrans.notification');

require __DIR__.'/auth.php';
