<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Pb\DashboardController as PbDashboard;
use App\Http\Controllers\Pj\DashboardController as PjDashboard;
use App\Http\Controllers\Admin\DataKeseluruhan;

// ==== Auth ====
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::redirect('/', '/login');

// ==== Admin ====
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/admin', AdminDashboard::class)->name('staff.admin.dashboard');
});
use App\Http\Controllers\Admin\UserController;

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');

    // Data Pengguna
    Route::resource('users', UserController::class);
});


// ==== Pembantu Bendahara (PB) ====
Route::middleware(['auth', 'role:Pengelola Barang'])->group(function () {
    Route::get('/pb', PbDashboard::class)->name('staff.pb.dashboard');
});

// ==== Penanggung Jawab (PJ) ====
Route::middleware(['auth', 'role:Penanggung Jawab'])->group(function () {
    Route::get('/pj', PjDashboard::class)->name('staff.pj.dashboard');
});

Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/admin', AdminDashboard::class)->name('staff.admin.dashboard');
    Route::get('/admin/datakeseluruhan', [\App\Http\Controllers\Admin\DataKeseluruhan::class, 'index'])
        ->name('staff.admin.datakeseluruhan');
});

Route::prefix('admin')->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])
        ->name('staff.admin.datakeseluruhan');  // ✅ samakan dengan yang dipanggil di menu
});

use App\Http\Controllers\Admin\BarangController;

Route::prefix('admin')->group(function () {
    Route::resource('barang', BarangController::class);
});


Route::get('/barang/create', [BarangController::class, 'create'])->name('barang.create');
Route::post('/barang/store', [BarangController::class, 'store'])->name('barang.store');


Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('staff.admin.datakeseluruhan');
    Route::post('/datakeseluruhan/kategori', [DataKeseluruhan::class, 'store'])->name('staff.admin.kategori.store');
});

use App\Http\Controllers\Admin\JenisBarangController;

Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::post('/datakeseluruhan/jenisbarang', [JenisBarangController::class, 'store'])
        ->name('staff.admin.jenisbarang.store');
});


Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('staff.admin.datakeseluruhan');

    // Tambah kategori
    Route::post('/datakeseluruhan/kategori', [DataKeseluruhan::class, 'store'])->name('staff.admin.kategori.store');

    // Tambah jenis barang
    Route::post('/datakeseluruhan/jenis', [DataKeseluruhan::class, 'storeJenis'])
        ->name('staff.admin.jenis.store');
    // Atau jika pakai JenisBarangController terpisah:
    // Route::post('/datakeseluruhan/jenis', [JenisBarangController::class, 'store'])->name('staff.admin.jenis.store');
});


Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])
        ->name('staff.admin.datakeseluruhan');

    Route::post('/datakeseluruhan/kategori', [DataKeseluruhan::class, 'store'])
        ->name('staff.admin.kategori.store');

    Route::post('/datakeseluruhan/jenis', [DataKeseluruhan::class, 'storeJenis'])
        ->name('staff.admin.jenis.store');
});


Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])
        ->name('staff.admin.datakeseluruhan');

    Route::post('/datakeseluruhan/kategori', [DataKeseluruhan::class, 'store'])
        ->name('staff.admin.kategori.store');

    Route::post('/datakeseluruhan/jenis', [DataKeseluruhan::class, 'storeJenis'])
        ->name('staff.admin.jenis.store');
});

Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::post('/barang', [BarangController::class, 'store'])->name('staff.admin.barang.store');
});

Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('staff.admin.datakeseluruhan');

    Route::post('/datakeseluruhan/kategori', [DataKeseluruhan::class, 'storeKategori'])->name('staff.admin.kategori.store');
    Route::post('/datakeseluruhan/barang', [DataKeseluruhan::class, 'storeBarang'])->name('staff.admin.barang.store');
});

Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('staff.admin.datakeseluruhan');
    Route::post('/datakeseluruhan/kategori', [DataKeseluruhan::class, 'storeKategori'])->name('staff.admin.kategori.store');
    Route::post('/datakeseluruhan/barang', [DataKeseluruhan::class, 'storeBarang'])->name('staff.admin.barang.store');
});

Route::prefix('staff/admin')->name('staff.admin.')->group(function () {
    Route::resource('barang', BarangController::class);
});


Route::prefix('admin')->group(function () {
    Route::resource('barang', \App\Http\Controllers\Admin\BarangController::class);
});
Route::get('/admin/datakeseluruhan', [\App\Http\Controllers\Admin\BarangController::class, 'index'])
    ->name('barang.index');


Route::prefix('admin')->group(function () {
    Route::get('/datakeseluruhan', [BarangController::class, 'index'])->name('barang.index');
    Route::post('/barang', [BarangController::class, 'store'])->name('barang.store');
    Route::delete('/barang/{kode}', [BarangController::class, 'destroy'])->name('barang.destroy');
});
