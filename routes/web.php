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




