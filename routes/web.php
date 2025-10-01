<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/* =========================================================================
 | Controller Aliases (semua di-alias biar aman nama duplikat)
 * ========================================================================= */
// Admin
use App\Http\Controllers\Admin\DashboardController   as AdminDashboardController;
use App\Http\Controllers\Admin\DataKeseluruhan       as AdminDataKeseluruhanController;
use App\Http\Controllers\Admin\RiwayatController     as AdminRiwayatController;
use App\Http\Controllers\Admin\UserController        as AdminUserController;
use App\Http\Controllers\Admin\BarangController      as AdminBarangController;
use App\Http\Controllers\Admin\LaporanController     as AdminLaporanController;
use App\Http\Controllers\Admin\RoleController        as AdminRoleController;

// PB
use App\Http\Controllers\Pb\DashboardController      as PbDashboardController;
use App\Http\Controllers\Pb\DataKeseluruhan          as PbDataKeseluruhanController;
use App\Http\Controllers\Pb\StokUserController       as PbStokUserController;
use App\Http\Controllers\Pb\DistribusiController     as PbDistribusiController;
use App\Http\Controllers\Pb\RiwayatController        as PbRiwayatController;
use App\Http\Controllers\Pb\LaporanController        as PbLaporanController;
use App\Http\Controllers\Pb\BarangMasukController    as PbBarangMasukController;

// PJ
use App\Http\Controllers\Pj\DashboardController      as PjDashboardController;


/* =========================================================================
 | AUTH
 * ========================================================================= */
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.attempt');
Route::post('/logout',[AuthController::class, 'destroy'])->name('logout');

Route::redirect('/', '/login');


/* =========================================================================
 | Backward-compat / alias lama (dipertahankan)
 * ========================================================================= */
Route::middleware(['auth', 'role:Admin'])
    ->get('/staff/admin/datakeseluruhan', fn () => to_route('admin.datakeseluruhan.index'))
    ->name('staff.admin.datakeseluruhan');

Route::middleware(['auth', 'role:Admin'])
    ->get('/admin', fn () => to_route('admin.dashboard'))
    ->name('staff.admin.dashboard');

Route::middleware(['auth', 'role:Pengelola Barang'])
    ->get('/pb', fn () => to_route('pb.dashboard'))
    ->name('staff.pb.dashboard');

Route::middleware(['auth', 'role:Penanggung Jawab'])
    ->get('/pj', fn () => to_route('pj.dashboard'))
    ->name('staff.pj.dashboard');

/* =========================================================================
 | ADMIN AREA
 * ========================================================================= */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:Admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/dashboard/filter', [AdminDashboardController::class, 'filterData'])->name('dashboard.filter');

    // Data Keseluruhan (overview, slug, legacy id)
    Route::get('/datakeseluruhan', [AdminDataKeseluruhanController::class, 'index'])
        ->name('datakeseluruhan.index');

    Route::get('/datakeseluruhan/gudang/{slug}', [AdminDataKeseluruhanController::class, 'gudang'])
        ->name('datakeseluruhan.gudang');

    Route::get('/datakeseluruhan/{id}', [AdminDataKeseluruhanController::class, 'show'])
        ->name('datakeseluruhan.show');

    // Filter kategori khusus (pakai BarangController@index)
    Route::get('/datakeseluruhan/atk',        [AdminBarangController::class, 'index'])->name('datakeseluruhan.atk');
    Route::get('/datakeseluruhan/listrik',    [AdminBarangController::class, 'index'])->name('datakeseluruhan.listrik');
    Route::get('/datakeseluruhan/kebersihan', [AdminBarangController::class, 'index'])->name('datakeseluruhan.kebersihan');
    Route::get('/datakeseluruhan/komputer',   [AdminBarangController::class, 'index'])->name('datakeseluruhan.komputer');

    // API Search
    Route::get('/api/search-barang', [AdminDataKeseluruhanController::class, 'searchSuggestions'])
        ->name('api.search.barang');

    // Jenis & Kategori
    Route::post('/datakeseluruhan/jenis', [AdminDataKeseluruhanController::class, 'storeJenis'])
        ->name('jenis.store');

    Route::post('/kategori/store',  [AdminDataKeseluruhanController::class, 'storeKategori'])
        ->name('kategori.store');

    Route::delete('/kategori/{id}', [AdminDataKeseluruhanController::class, 'destroyKategori'])
        ->name('kategori.destroy');

    // Barang (store & destroy di DataKeseluruhan; resource lainnya di BarangController)
    Route::post('/barang', [AdminDataKeseluruhanController::class, 'storeBarang'])->name('barang.store');
    Route::delete('/barang/{id}', [AdminDataKeseluruhanController::class, 'destroyBarang'])->name('barang.destroy');

    Route::resource('barang', AdminBarangController::class)->except(['store', 'destroy']);

    // Riwayat
    Route::get('/riwayat', [AdminRiwayatController::class, 'index'])->name('riwayat.index');

    // Users & Roles
    Route::resource('users', AdminUserController::class);
    Route::resource('roles', AdminRoleController::class);

    // Laporan (invokable)
    Route::get('/laporan', AdminLaporanController::class)->name('laporan');
});


/* =========================================================================
 | PB AREA (Pengelola Barang)
 * ========================================================================= */
Route::prefix('pb')->name('pb.')->middleware(['auth', 'role:Pengelola Barang'])->group(function () {

    // Dashboard
    Route::get('/dashboard', PbDashboardController::class)->name('dashboard');
    Route::get('/dashboard/filter', [PbDashboardController::class, 'filterData'])->name('dashboard.filter');

    // Data Keseluruhan
    Route::get('/datakeseluruhan', [PbDataKeseluruhanController::class, 'index'])
        ->name('datakeseluruhan.index');

    Route::get('/datakeseluruhan/{slug}', [PbDataKeseluruhanController::class, 'gudang'])
        ->name('datakeseluruhan.gudang');

    // API Kategori by Gudang
    Route::get('/api/kategori-by-gudang/{gudangId}', [PbDataKeseluruhanController::class, 'getKategoriByGudang'])
        ->name('api.kategori.by.gudang');

    // Stok User (resource)
    Route::resource('stokuser', PbStokUserController::class);

    // Barang Masuk - MENGGUNAKAN BarangMasukController
    Route::post('/barang-masuk/{id}', [PbBarangMasukController::class, 'store'])
        ->name('barang.masuk');

    // Distribusi barang
    Route::post('/distribusi/{id}', [PbDistribusiController::class, 'distribusi'])
        ->name('barang.distribusi');
    
    Route::post('/distribusi/store', [PbDistribusiController::class, 'store'])
        ->name('distribusi.store');

    // Riwayat & Laporan PB
    Route::get('/riwayat', [PbRiwayatController::class, 'index'])->name('riwayat.index');
    Route::get('/laporan', [PbLaporanController::class, 'index'])->name('laporan');
});


/* =========================================================================
 | PJ AREA (Penanggung Jawab)
 * ========================================================================= */
Route::prefix('pj')->name('pj.')->middleware(['auth', 'role:Penanggung Jawab'])->group(function () {
    Route::get('/dashboard', PjDashboardController::class)->name('dashboard');
    // [TAMBAHAN] Route filter untuk dashboard PJ
    Route::get('/dashboard/filter', [PjDashboardController::class, 'filterData'])->name('dashboard.filter');
});