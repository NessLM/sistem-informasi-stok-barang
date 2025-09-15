<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Riwayat;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Carbon\Carbon;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::adminMenu();

        $query = Riwayat::query();

        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            $query->where('alur_barang', $request->alur_barang);
        }

        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $query->where('gudang', $request->gudang);
        }

        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subWeek()->format('Y-m-d'));
                    break;
                case '1_bulan_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subMonth()->format('Y-m-d'));
                    break;
                case '1_tahun_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subYear()->format('Y-m-d'));
                    break;
            }
        }

        // Mengurutkan berdasarkan tanggal dan waktu secara descending
        // Pastikan menggunakan format yang benar
        $riwayat = $query->orderBy('tanggal', 'asc')
            ->orderBy('waktu', 'asc')
            ->get();

        // Mendapatkan daftar gudang unik untuk filter
        $gudangList = Riwayat::select('gudang')->distinct()->orderBy('gudang')->get();

        return view('staff.admin.riwayat', compact('riwayat', 'menu', 'gudangList'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'jumlah' => 'required|integer',
            'bukti' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // validasi foto
        ]);

        if ($request->hasFile('bukti')) {
            $path = $request->file('bukti')->store('bukti', 'public');
            $validated['bukti'] = basename($path); // hanya simpan nama file
        }

        Riwayat::create($validated);

        return redirect()->route('admin.riwayat.index')
            ->with('success', 'Riwayat berhasil disimpan');
    }
}
