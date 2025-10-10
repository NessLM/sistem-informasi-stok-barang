<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Illuminate\Support\Facades\Auth;

class BarangKeluarController extends Controller
{
    /**
     * Tampilkan history barang keluar
     */
    public function index(Request $request)
    {
        $menu = MenuHelper::pjMenu();
        $user = Auth::user();

        if (!$user->gudang_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Anda belum memiliki gudang yang ditugaskan.'
            ]);
        }

        // Ambil data barang keluar dari gudang user
        $barangKeluar = BarangKeluar::with(['barang', 'gudang', 'user'])
            ->where('gudang_id', $user->gudang_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('staff.pj.barang-keluar.index', compact('barangKeluar', 'menu'));
    }
}