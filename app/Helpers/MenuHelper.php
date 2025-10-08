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
            // Buat slug dari nama gudang secara otomatis
            $slug = self::createSlugFromGudangName($g->nama);
            
            return [
                'label'  => $g->nama,
                'icon'   => 'bi-grid',
                'route'  => 'admin.datakeseluruhan.gudang',
                'params' => ['slug' => $slug],
                'gudang_id' => $g->id
            ];
        })->toArray();

        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'admin.dashboard'],

            [
                'label' => 'Data Keseluruhan',
                'icon'  => 'bi-card-list',
                'route' => 'admin.datakeseluruhan.index', // route utama untuk overview
                'children' => $gudangMenus
            ],

            ['label' => 'Riwayat',       'icon' => 'bi-clock-history', 'route' => 'admin.riwayat.index'],
            ['label' => 'Laporan',       'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.laporan'],
            ['label' => 'Data Pengguna', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];
    }

    /**
     * Menu untuk Pengelola Barang (PB)
     * Hanya menampilkan Data Keseluruhan tanpa submenu gudang
     */
    public static function pbMenu()
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'pb.dashboard'],
            ['label' => 'Data Gudang', 'icon' => 'bi-card-list', 'route' => 'pb.datakeseluruhan.index'],
            ['label' => 'Riwayat', 'icon' => 'bi-clock-history', 'route' => 'pb.riwayat.index'],
            ['label' => 'Laporan', 'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'pb.laporan'],
        ];
    }

    /**
     * Menu untuk Penanggung Jawab (PJ)
     * Dashboard hanya menampilkan data gudang sesuai role PJ
     */
    public static function pjMenu()
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'pj.dashboard'],
            ['label' => 'Riwayat', 'icon' => 'bi-clock-history', 'route' => 'pj.riwayat.index'],
        ];
    }

    /**
     * Buat slug dari nama gudang secara otomatis
     * 
     * @param string $gudangName
     * @return string
     */
    private static function createSlugFromGudangName($gudangName)
    {
        // Hilangkan kata "Gudang" di awal (case insensitive)
        $cleaned = preg_replace('/^gudang\s+/i', '', $gudangName);
        
        // Konversi ke lowercase dan ganti spasi dengan dash
        $slug = strtolower(str_replace([' ', '_'], '-', $cleaned));
        
        // Hilangkan karakter khusus kecuali dash
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        // Hilangkan dash berlebih
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Hilangkan dash di awal dan akhir
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Konversi slug kembali ke nama gudang (untuk pencarian di database)
     * 
     * @param string $slug
     * @return string
     */
    public static function slugToGudangName($slug)
    {
        // Cari gudang berdasarkan slug yang dibuat
        $gudangs = Gudang::all();
        
        foreach ($gudangs as $gudang) {
            $gudangSlug = self::createSlugFromGudangName($gudang->nama);
            if ($gudangSlug === $slug) {
                return $gudang->nama;
            }
        }
        
        // Jika tidak ditemukan, coba konversi manual
        return ucfirst(str_replace('-', ' ', $slug));
    }
}