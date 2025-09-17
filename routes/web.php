<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// ===== Admin Controllers =====
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\DataKeseluruhan;
use App\Http\Controllers\Admin\RiwayatController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BarangController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\RoleController;

// ===== PB & PJ =====
use App\Http\Controllers\Pb\DashboardController as PbDashboard;
use App\Http\Controllers\Pj\DashboardController as PjDashboard;


/* =========================================================================
 |  AUTH
 * ========================================================================= */
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.attempt');
Route::post('/logout',[AuthController::class, 'destroy'])->name('logout');

Route::redirect('/', '/login'); // default ke login


/* =========================================================================
 |  ALIAS KOMPATIBILITAS (JANGAN DIHAPUS)
 |  Banyak kode lama memanggil 'staff.admin.dashboard' → arahkan ke 'admin.dashboard'
 * ========================================================================= */
Route::middleware(['auth','role:Admin'])
    ->get('/admin', fn () => to_route('admin.dashboard'))
    ->name('staff.admin.dashboard');


/* =========================================================================
 |  ADMIN AREA
 |  - Semua nama rute diawali 'admin.'
 |  - Semua rute di-protect middleware auth + role:Admin
 * ========================================================================= */
Route::prefix('admin')->name('admin.')->middleware(['auth','role:Admin'])->group(function () {

    /* ===== Dashboard ===== */
    // GET /admin/dashboard  → admin.dashboard
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');

    /* ===== Data Keseluruhan =====
       Catatan:
       - Halaman utama pakai DataKeseluruhan@index (sesuai Blade yang kamu pakai)
       - Sub-tab (atk/listrik/...) tetap ada seperti semula (pointing ke BarangController@index)
    */
    Route::get('/datakeseluruhan',            [DataKeseluruhan::class, 'index'])->name('datakeseluruhan');
    Route::get('/datakeseluruhan/atk',        [BarangController::class, 'index'])->name('datakeseluruhan.atk');
    Route::get('/datakeseluruhan/listrik',    [BarangController::class, 'index'])->name('datakeseluruhan.listrik');
    Route::get('/datakeseluruhan/kebersihan', [BarangController::class, 'index'])->name('datakeseluruhan.kebersihan');
    Route::get('/datakeseluruhan/komputer',   [BarangController::class, 'index'])->name('datakeseluruhan.komputer');

    // Aksi simpan dari halaman Data Keseluruhan (nama rute dipertahankan)
    Route::post('/datakeseluruhan/jenis', [DataKeseluruhan::class, 'storeJenis'])->name('jenis.store');

    // Kategori (dipakai di Blade: admin.kategori.store / admin.kategori.destroy)
    Route::post  ('/kategori/store', [DataKeseluruhan::class, 'storeKategori'])->name('kategori.store');
    Route::delete('/kategori/{id}',   [DataKeseluruhan::class, 'destroyKategori'])->name('kategori.destroy');

    // Barang:
    // - Store: gunakan handler DataKeseluruhan@storeBarang seperti yang dipakai form di Blade
    //   (supaya tidak tabrakan dengan resource('barang')->store → kita exclude store di resource)
    Route::post('/barang', [DataKeseluruhan::class, 'storeBarang'])->name('barang.store');

    /* ===== Riwayat ===== */
    // GET /admin/riwayat  → admin.riwayat.index
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');

    /* ===== Users ===== */
    // admin.users.index|create|store|show|edit|update|destroy
    Route::resource('users', UserController::class);

    /* ===== Barang (CRUD standar, TANPA store) =====
       - admin.barang.index|create|show|edit|update|destroy
       - 'store' sudah didefinisikan di atas ke DataKeseluruhan@storeBarang (admin.barang.store)
    */
    Route::resource('barang', BarangController::class)->except(['store']);

    /* ===== Roles (CRUD standar) ===== */
    Route::resource('roles', RoleController::class);

    /* ===== Laporan ===== */
    // GET /admin/laporan  → admin.laporan
    Route::get('/laporan', LaporanController::class)->name('laporan');
});




/* =========================================================================
 |  PB & PJ (tetap)
 * ========================================================================= */
Route::middleware(['auth','role:Pengelola Barang'])
    ->get('/pb', PbDashboard::class)
    ->name('staff.pb.dashboard');

Route::middleware(['auth','role:Penanggung Jawab'])
    ->get('/pj', PjDashboard::class)
    ->name('staff.pj.dashboard');

