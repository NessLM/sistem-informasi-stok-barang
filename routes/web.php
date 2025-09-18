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
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::redirect('/', '/login'); // default ke login

/* =========================================================================
 |  ALIAS KOMPATIBILITAS
 * ========================================================================= */
Route::middleware(['auth', 'role:Admin'])
    ->get('/staff/admin/datakeseluruhan', fn() => to_route('admin.datakeseluruhan.index'))
    ->name('staff.admin.datakeseluruhan');

Route::middleware(['auth', 'role:Admin'])
    ->get('/admin', fn() => to_route('admin.dashboard'))
    ->name('staff.admin.dashboard');

/* =========================================================================
 |  ADMIN AREA
 * ========================================================================= */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:Admin'])->group(function () {

    /* ===== Dashboard ===== */
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('/dashboard/filter', [AdminDashboard::class, 'filterData'])->name('dashboard.filter');

/* ===== Data Keseluruhan ===== */
Route::get('/datakeseluruhan', [DataKeseluruhan::class, 'index'])->name('datakeseluruhan.index');
Route::get('/datakeseluruhan/{id}', [DataKeseluruhan::class, 'show'])->name('datakeseluruhan.show');

    // filter kategori khusus
    Route::get('/datakeseluruhan/atk',        [BarangController::class, 'index'])->name('datakeseluruhan.atk');
    Route::get('/datakeseluruhan/listrik',    [BarangController::class, 'index'])->name('datakeseluruhan.listrik');
    Route::get('/datakeseluruhan/kebersihan', [BarangController::class, 'index'])->name('datakeseluruhan.kebersihan');
    Route::get('/datakeseluruhan/komputer',   [BarangController::class, 'index'])->name('datakeseluruhan.komputer');

    /* ===== API Search ===== */
    Route::get('/api/search-barang', [DataKeseluruhan::class, 'searchSuggestions'])->name('api.search.barang');

    /* ===== Jenis & Kategori ===== */
    Route::post('/datakeseluruhan/jenis', [DataKeseluruhan::class, 'storeJenis'])->name('jenis.store');
    Route::post('/kategori/store',        [DataKeseluruhan::class, 'storeKategori'])->name('kategori.store');
    Route::delete('/kategori/{id}',       [DataKeseluruhan::class, 'destroyKategori'])->name('kategori.destroy');

    /* ===== Barang ===== */
    Route::post('/barang', [DataKeseluruhan::class, 'storeBarang'])->name('barang.store');
    Route::resource('barang', BarangController::class)->except(['store']);

    /* ===== Riwayat ===== */
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');

    /* ===== Users ===== */
    Route::resource('users', UserController::class);

    /* ===== Roles ===== */
    Route::resource('roles', RoleController::class);

    /* ===== Laporan ===== */
    Route::get('/laporan', LaporanController::class)->name('laporan');
});

/* =========================================================================
 |  PB & PJ
 * ========================================================================= */
Route::middleware(['auth', 'role:Pengelola Barang'])
    ->get('/pb', PbDashboard::class)
    ->name('staff.pb.dashboard');

Route::middleware(['auth', 'role:Penanggung Jawab'])
    ->get('/pj', PjDashboard::class)
    ->name('staff.pj.dashboard');

;
