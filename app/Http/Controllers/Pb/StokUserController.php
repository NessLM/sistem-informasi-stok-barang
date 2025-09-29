<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Distribusi;
use Illuminate\Http\Request;

class StokUserController extends Controller
{
    public function barangMasuk(Request $request, $id)
    {
        $request->validate([
            'jumlah' => 'required|integer|min:1'
        ]);

        $barang = Barang::findOrFail($id);
        $barang->stok += $request->jumlah;
        $barang->save();

        // Log distribusi (opsional)
        Distribusi::create([
            'barang_id' => $barang->id,
            'user_asal_id' => null,
            'user_tujuan_id' => auth()->id(),
            'jumlah' => $request->jumlah,
            'tanggal' => now(),
            'keterangan' => 'Barang Masuk'
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => "Stok {$barang->nama} berhasil ditambahkan {$request->jumlah} unit"
        ]);
    }

    // Method lain yang sudah ada...
    public function index()
    {
        // existing code
    }

    public function store(Request $request)
    {
        // existing code
    }
}