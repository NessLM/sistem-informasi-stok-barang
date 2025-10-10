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
use App\Http\Controllers\Pj\DataKeseluruhan          as PjDataKeseluruhanController;
use App\Http\Controllers\Pj\RiwayatController        as PjRiwayatController;
use App\Http\Controllers\Pj\BarangKeluarController   as PjBarangKeluarController;
use App\Http\Controllers\Pj\LaporanController        as PjLaporanController;


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

Route::middleware(['auth', 'role:Penanggung Jawab ATK,Penanggung Jawab Kebersihan,Penanggung Jawab Listrik,Penanggung Jawab Bahan Komputer'])
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

    Route::get('/datakeseluruhan/{slug}', [AdminDataKeseluruhanController::class, 'byGudang'])
        ->name('datakeseluruhan.gudang');

    Route::get('/datakeseluruhan/id/{id}', [AdminDataKeseluruhanController::class, 'show'])
        ->name('datakeseluruhan.show');

    // Filter kategori khusus (pakai BarangController@index)
    Route::get('/datakeseluruhan/atk',        [AdminBarangController::class, 'index'])->name('datakeseluruhan.atk');
    Route::get('/datakeseluruhan/listrik',    [AdminBarangController::class, 'index'])->name('datakeseluruhan.listrik');
    Route::get('/datakeseluruhan/kebersihan', [AdminBarangController::class, 'index'])->name('datakeseluruhan.kebersihan');
    Route::get('/datakeseluruhan/komputer',   [AdminBarangController::class, 'index'])->name('datakeseluruhan.komputer');

    // [NEW] Route alias untuk gudang (backward compatibility dengan menu lama)
    Route::get('/gudang/atk', fn() => redirect()->route('admin.datakeseluruhan.gudang', ['slug' => 'gudang-atk']))
        ->name('gudang.atk');
    Route::get('/gudang/listrik', fn() => redirect()->route('admin.datakeseluruhan.gudang', ['slug' => 'gudang-listrik']))
        ->name('gudang.listrik');
    Route::get('/gudang/kebersihan', fn() => redirect()->route('admin.datakeseluruhan.gudang', ['slug' => 'gudang-kebersihan']))
        ->name('gudang.kebersihan');
    Route::get('/gudang/komputer', fn() => redirect()->route('admin.datakeseluruhan.gudang', ['slug' => 'gudang-b-komputer']))
        ->name('gudang.komputer');

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

// [NEW] Route alias staff.admin.gudang.* untuk backward compatibility
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/staff/admin/gudang/atk', fn() => redirect()->route('admin.datakeseluruhan.gudang', ['slug' => 'gudang-atk']))
        ->name('staff.admin.gudang.atk');
    Route::get('/staff/admin/gudang/listrik', fn() => redirect()->route('admin.datakeseluruhan.gudang', ['slug' => 'gudang-listrik']))
        ->name('staff.admin.gudang.listrik');
    Route::get('/staff/admin/gudang/kebersihan', fn() => redirect()->route('admin.datakeseluruhan.gudang', ['slug' => 'gudang-kebersihan']))
        ->name('staff.admin.gudang.kebersihan');
    Route::get('/staff/admin/gudang/komputer', fn() => redirect()->route('admin.datakeseluruhan.gudang', ['slug' => 'gudang-b-komputer']))
        ->name('staff.admin.gudang.komputer');
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

    Route::get('/datakeseluruhan/{slug}', [AdminDataKeseluruhanController::class, 'gudang'])
    ->name('datakeseluruhan.gudang');

    // API Kategori by Gudang
    Route::get('/api/kategori-by-gudang/{gudangId}', [PbDataKeseluruhanController::class, 'getKategoriByGudang'])
        ->name('api.kategori.by.gudang');

    // API Search
    Route::get('/api/search-barang', [PbDataKeseluruhanController::class, 'searchSuggestions'])
        ->name('api.search.barang');

    // Stok User (resource)
    Route::resource('stokuser', PbStokUserController::class);

    // Barang Masuk
    Route::post('/barang-masuk/{id}', [PbStokUserController::class, 'barangMasuk'])
        ->name('barang.masuk');

    // Distribusi barang
    Route::post('/distribusi/{id}', [PbDistribusiController::class, 'distribusi'])
        ->name('barang.distribusi');

    // Riwayat & Laporan PB
    Route::get('/riwayat', [PbRiwayatController::class, 'index'])->name('riwayat.index');
    Route::get('/laporan', [PbLaporanController::class, 'index'])->name('laporan');
});


/* =========================================================================
 | PJ AREA (Penanggung Jawab) - CLEANED & FIXED
 * ========================================================================= */
Route::prefix('pj')->name('pj.')
    ->middleware(['auth', 'role:Penanggung Jawab ATK,Penanggung Jawab Kebersihan,Penanggung Jawab Listrik,Penanggung Jawab Bahan Komputer'])
    ->group(function () {
    
    // Dashboard
    Route::get('/dashboard', PjDashboardController::class)->name('dashboard');
    Route::get('/dashboard/filter', [PjDashboardController::class, 'filterData'])->name('dashboard.filter');

    // Data Keseluruhan / Data Gudang
    Route::get('/datakeseluruhan', [PjDataKeseluruhanController::class, 'index'])
        ->name('datakeseluruhan.index');

    // API Search Barang (untuk autocomplete)
    Route::get('/api/search-barang', [PjDataKeseluruhanController::class, 'searchSuggestions'])
        ->name('api.search-barang');

    // Barang Keluar - POST untuk submit dari modal
    Route::post('/barang-keluar/{barang}', [PjDataKeseluruhanController::class, 'barangKeluar'])
        ->name('barang-keluar.store');

    // Barang Keluar - Index untuk list history (placeholder untuk sekarang)
    Route::get('/barang-keluar', function() {
        $menu = \App\Helpers\MenuHelper::pjMenu();
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (!$user->gudang_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Anda belum memiliki gudang yang ditugaskan.'
            ]);
        }
        
        // Redirect ke data keseluruhan untuk sementara
        return redirect()->route('pj.datakeseluruhan.index');
    })->name('barang-keluar.index');

    // Riwayat
    Route::get('/riwayat', [PjRiwayatController::class, 'index'])
        ->name('riwayat.index');

    // Laporan (placeholder)
    Route::get('/laporan', function() {
        $menu = \App\Helpers\MenuHelper::pjMenu();
        return view('staff.pj.laporan.index', compact('menu'));
    })->name('laporan.index');
});