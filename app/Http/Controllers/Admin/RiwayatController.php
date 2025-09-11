<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Riwayat;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::adminMenu();

        $query = Riwayat::query();

        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            $query->where('alur_barang', $request->alur_barang);
        }

        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir': $query->where('tanggal', '>=', now()->subWeek());  break;
                case '1_bulan_terakhir':  $query->where('tanggal', '>=', now()->subMonth()); break;
                case '1_tahun_terakhir':  $query->where('tanggal', '>=', now()->subYear());  break;
            }
        }

        $riwayat = $query->orderBy('tanggal', 'desc')->orderBy('waktu', 'desc')->get();

        return view('staff.admin.riwayat', compact('riwayat', 'menu'));
    }
}
