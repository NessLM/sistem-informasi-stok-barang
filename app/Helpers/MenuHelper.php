<?php

namespace App\Helpers;

use App\Models\Gudang;

class MenuHelper
{
    public static function adminMenu()
    {
        // ambil semua gudang dari database
        $gudangs = Gudang::all();

        // mapping data gudang jadi children menu
        $gudangMenus = $gudangs->map(function ($g) {
            return [
                'label'  => 'Gudang ' . $g->nama,
                'icon'   => 'bi-grid',
                'route'  => 'admin.datakeseluruhan.show',
                'params' => ['id' => $g->id], // untuk route parameter
            ];
        })->toArray();

        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'admin.dashboard'],

            [
                'label' => 'Data Keseluruhan',
                'icon'  => 'bi-card-list',
                'route' => 'admin.datakeseluruhan.index',
                'children' => $gudangMenus
            ],

            ['label' => 'Riwayat',       'icon' => 'bi-clock-history', 'route' => 'admin.riwayat.index'],
            ['label' => 'Laporan',       'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.laporan'],
            ['label' => 'Data Pengguna', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];
    }
}
