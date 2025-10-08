<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\StokGudang;
use App\Models\RiwayatBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StokUserController extends Controller
{
    public function barangMasuk(Request $request, $id)
    {
        $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $tanggal = $request->tanggal ?: now()->format('Y-m-d');
            $barang = Barang::with('kategori.gudang')->findOrFail($id);
            $gudangId = $barang->kategori->gudang_id;

            // Get atau create stok gudang
            $stokGudang = StokGudang::firstOrCreate(
                [
                    'barang_id' => $barang->id,
                    'gudang_id' => $gudangId
                ],
                ['stok' => 0]
            );

            $stokSebelum = $stokGudang->stok;

            // Upload bukti
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-barang-masuk', 'public');
            }

            // Tambah stok di gudang
            $stokGudang->tambahStok($request->jumlah);

            // Update stok di tabel barang (opsional)
            $barang->stok = $stokGudang->stok;
            $barang->save();

            // Simpan riwayat
            RiwayatBarang::create([
                'barang_id' => $barang->id,
                'jenis_transaksi' => 'masuk',
                'jumlah' => $request->jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokGudang->stok,
                'keterangan' => $request->keterangan,
                'bukti' => $buktiPath,
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Stok {$barang->nama} berhasil ditambahkan {$request->jumlah} unit di {$barang->kategori->gudang->nama}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($buktiPath) && $buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            Log::error('Error barang masuk: ' . $e->getMessage());

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}