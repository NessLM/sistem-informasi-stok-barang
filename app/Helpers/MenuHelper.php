<?php

namespace App\Helpers;

class MenuHelper
{
    public static function adminMenu()
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
            [
                'label' => 'Data Keseluruhan',
                'icon'  => 'bi-card-list',
                'children' => [
                    ['label' => 'Gudang ATK',        'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.atk'],
                    ['label' => 'Gudang Listrik',    'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.listrik'],
                    ['label' => 'Gudang Kebersihan', 'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.kebersihan'],
                    ['label' => 'Gudang B Komputer', 'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.komputer'],
                ]
            ],
            ['label' => 'Riwayat',       'icon' => 'bi-clock-history', 'route' => 'admin.riwayat.index'],
            ['label' => 'Laporan',       'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.laporan'],
            ['label' => 'Data Pengguna', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];
    }


    public static function pbMenu()
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],

            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK',         'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
                ['label' => 'Gudang Listrik',     'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
                ['label' => 'Gudang Kebersihan',  'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
                ['label' => 'Gudang B Komputer',  'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
            ]],

            ['label' => 'Riwayat',        'icon' => 'bi-clock-history', 'route' => 'staff.admin.dashboard'],
            ['label' => 'Laporan',        'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'staff.admin.dashboard'],
        ];
    }

    public static function pjMenu()
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],

            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK',         'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
                ['label' => 'Gudang B Komputer',  'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
            ]],
            ['label' => 'Riwayat',        'icon' => 'bi-clock-history', 'route' => 'staff.admin.dashboard'],
        ];
    }
}
