<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\StokUser;
use Illuminate\Support\Facades\Auth;

class StokUserController extends Controller
{
    public function index()
    {
        $stokuser = StokUser::with('barang')->latest()->get();
        return view('pb.stokuser.index', compact('stokuser'));
    }

    public function create()
    {
        $barang = Barang::all(); // PB hanya bisa tambah stok barang yg sudah ada
        return view('pb.stokuser.create', compact('barang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'barang_id' => 'required|exists:barang,id',
            'jumlah'    => 'required|integer|min:1',
        ]);

        StokUser::create([
            'user_id'   => Auth::id(),
            'barang_id' => $request->barang_id,
            'jumlah'    => $request->jumlah,
        ]);

        return redirect()->route('pb.stokuser.index')->with('success', 'Stok berhasil ditambahkan!');
    }

    public function show(StokUser $stokuser)
    {
        return view('pb.stokuser.show', compact('stokuser'));
    }

    public function edit(StokUser $stokuser)
    {
        $barang = Barang::all();
        return view('pb.stokuser.edit', compact('stokuser', 'barang'));
    }

    public function update(Request $request, StokUser $stokuser)
    {
        $request->validate([
            'barang_id' => 'required|exists:barang,id',
            'jumlah'    => 'required|integer|min:1',
        ]);

        $stokuser->update([
            'barang_id' => $request->barang_id,
            'jumlah'    => $request->jumlah,
        ]);

        return redirect()->route('pb.stokuser.index')->with('success', 'Stok berhasil diperbarui!');
    }

    public function destroy(StokUser $stokuser)
    {
        $stokuser->delete();
        return redirect()->route('pb.stokuser.index')->with('success', 'Stok berhasil dihapus!');
    }
}
