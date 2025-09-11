<?php
// app/Http/Controllers/Admin/RiwayatController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Riwayat;
use Illuminate\Http\Request;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $query = Riwayat::query();
        
        // Filter alur barang
        if ($request->has('alur_barang') && $request->alur_barang != 'Semua') {
            $query->where('alur_barang', $request->alur_barang);
        }
        
        // Filter periode waktu
        if ($request->has('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $query->where('tanggal', '>=', now()->subWeek());
                    break;
                case '1_bulan_terakhir':
                    $query->where('tanggal', '>=', now()->subMonth());
                    break;
                case '1_tahun_terakhir':
                    $query->where('tanggal', '>=', now()->subYear());
                    break;
            }
        }
        
        $riwayat = $query->orderBy('tanggal', 'desc')
                        ->orderBy('waktu', 'desc')
                        ->get();
        
        // Menu yang diperbaiki dengan route names yang benar
        $menu = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK',         'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.atk'],
                ['label' => 'Gudang Listrik',     'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.listrik'],
                ['label' => 'Gudang Kebersihan',  'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.kebersihan'],
                ['label' => 'Gudang B Komputer',  'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.komputer'],
            ]],
            ['label' => 'Riwayat',        'icon' => 'bi-clock-history', 'route' => 'admin.riwayat.index'],
            ['label' => 'Laporan',        'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.dashboard'],
            ['label' => 'Data Pengguna',  'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];

        return view('staff.pj.riwayat', compact('riwayat', 'menu'));
    }
}