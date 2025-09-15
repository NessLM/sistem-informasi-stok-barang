<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Admin
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\DataKeseluruhan;
use App\Http\Controllers\Admin\RiwayatController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BarangController;
use App\Http\Controllers\Admin\LaporanController;

// Pengelola Barang
use App\Http\Controllers\Pb\DashboardController as PbDashboard;

// Penanggung Jawab
use App\Http\Controllers\Pj\DashboardController as PjDashboard;

/* ============== AUTH ============== */
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.attempt');
Route::post('/logout',[AuthController::class, 'destroy'])->name('logout');

Route::redirect('/', '/login');

/* ---------------------------------------------------------
   ALIAS KOMPATIBILITAS (JANGAN DIHAPUS):
   Banyak kode lama masih memanggil 'staff.admin.dashboard'.
   Alias ini mengarahkan ke 'admin.dashboard'.
--------------------------------------------------------- */
Route::middleware(['auth','role:Admin'])
    ->get('/admin', fn () => to_route('admin.dashboard'))
    ->name('staff.admin.dashboard');

/* ============== ADMIN ============== */
/* Semua NAMA rute → diawali 'admin.' (bukan 'staff.admin.') */
Route::prefix('admin')->name('admin.')->middleware(['auth','role:Admin'])->group(function () {

    // Dashboard → admin.dashboard
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');

    /* Data Keseluruhan
       Halaman utama: pakai BarangController@index (paling ramai datanya) */
    Route::get('/datakeseluruhan',            [BarangController::class, 'index'])->name('datakeseluruhan');
    Route::get('/datakeseluruhan/atk',        [BarangController::class, 'index'])->name('datakeseluruhan.atk');
    Route::get('/datakeseluruhan/listrik',    [BarangController::class, 'index'])->name('datakeseluruhan.listrik');
    Route::get('/datakeseluruhan/kebersihan', [BarangController::class, 'index'])->name('datakeseluruhan.kebersihan');
    Route::get('/datakeseluruhan/komputer',   [BarangController::class, 'index'])->name('datakeseluruhan.komputer');

    /* Aksi simpan (dipakai form di halaman Data Keseluruhan) */
    Route::post('/datakeseluruhan/kategori', [DataKeseluruhan::class, 'storeKategori'])->name('kategori.store');
    Route::post('/datakeseluruhan/jenis',    [DataKeseluruhan::class, 'storeJenis'])->name('jenis.store');
    Route::post('/datakeseluruhan/barang',   [DataKeseluruhan::class, 'storeBarang'])->name('barang.store');

    // Riwayat & Users
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');

    Route::resource('users',  UserController::class);     // → admin.users.index|create|store|show|edit|update|destroy

    // Barang (CRUD standar) → admin.barang.index|create|store|show|edit|update|destroy
    Route::resource('barang', BarangController::class);

    Route::get('/laporan', LaporanController::class)->name('laporan');
});

/* ========== PB & PJ (tetap) ========== */
Route::middleware(['auth','role:Pengelola Barang'])->get('/pb', PbDashboard::class)->name('staff.pb.dashboard');
Route::middleware(['auth','role:Penanggung Jawab'])->get('/pj', PjDashboard::class)->name('staff.pj.dashboard');

Route::resource('barang', BarangController::class);
Route::get('/admin/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('admin.datakeseluruhan.index');

Route::prefix('admin')->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('admin.datakeseluruhan');
    Route::post('/kategori/store', [DataKeseluruhan::class, 'storeKategori'])->name('admin.kategori.store');
    Route::post('/barang/store', [DataKeseluruhan::class, 'storeBarang'])->name('admin.barang.store');
    Route::delete('/barang/{kode}', [DataKeseluruhan::class, 'destroyBarang'])->name('barang.destroy');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('barang', BarangController::class)->names('admin.barang');

});

Route::prefix('admin')->name('admin.')->group(function () {
    // Barang
    Route::resource('barang', BarangController::class);

    // Data keseluruhan
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('datakeseluruhan');
    Route::post('/kategori/store', [DataKeseluruhan::class, 'storeKategori'])->name('kategori.store');
});


Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('datakeseluruhan');

    Route::post('/kategori/store', [DataKeseluruhan::class, 'storeKategori'])->name('kategori.store');
    Route::delete('/kategori/{id}', [DataKeseluruhan::class, 'destroyKategori'])->name('kategori.destroy');

    Route::post('/barang/store', [DataKeseluruhan::class, 'storeBarang'])->name('barang.store');
    Route::put('/barang/{kode}', [DataKeseluruhan::class, 'updateBarang'])->name('barang.update');
    Route::delete('/barang/{kode}', [DataKeseluruhan::class, 'destroyBarang'])->name('barang.destroy');
});