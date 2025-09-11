<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Pb\DashboardController as PbDashboard;
use App\Http\Controllers\Pj\DashboardController as PjDashboard;
use App\Http\Controllers\Admin\DataKeseluruhan;
use App\Http\Controllers\Admin\RiwayatController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BarangController;

// ==== Auth ====
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::redirect('/', '/login');

// ==== Admin Routes ====
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:Admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboard::class, '__invoke'])->name('dashboard');
    
    // Data Pengguna
    Route::resource('users', UserController::class);
    
    // Riwayat
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');
    
    // Data Keseluruhan - dengan nama route yang sesuai
    Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('datakeseluruhan');
    
    // Barang
    Route::resource('barang', BarangController::class);
    
    // Route khusus untuk menu sidebar (jika diperlukan)
    Route::get('/datakeseluruhan/atk', [DataKeseluruhan::class, 'index'])->name('datakeseluruhan.atk');
    Route::get('/datakeseluruhan/listrik', [DataKeseluruhan::class, 'index'])->name('datakeseluruhan.listrik');
    Route::get('/datakeseluruhan/kebersihan', [DataKeseluruhan::class, 'index'])->name('datakeseluruhan.kebersihan');
    Route::get('/datakeseluruhan/komputer', [DataKeseluruhan::class, 'index'])->name('datakeseluruhan.komputer');
});

// ==== Pembantu Bendahara (PB) ====
Route::middleware(['auth', 'role:Pengelola Barang'])->group(function () {
    Route::get('/pb', [PbDashboard::class, '__invoke'])->name('staff.pb.dashboard');
});

// ==== Penanggung Jawab (PJ) ====
Route::middleware(['auth', 'role:Penanggung Jawab'])->group(function () {
    Route::get('/pj', [PjDashboard::class, '__invoke'])->name('staff.pj.dashboard');
});

// Route untuk akses langsung (jika diperlukan)
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/admin', [AdminDashboard::class, '__invoke'])->name('staff.admin.dashboard');
});