<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\RiwayatBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BarangMasukController extends Controller
{
    public function store(Request $request, $id)
    {
        $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string|max:255',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Jika tanggal tidak diisi, gunakan tanggal hari ini
            $tanggal = $request->tanggal ?: now()->format('Y-m-d');

            $barang = Barang::findOrFail($id);
            $stokSebelum = $barang->stok;

            // Upload bukti
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-barang-masuk', 'public');
            }

            // Tambah stok
            $barang->stok += $request->jumlah;
            $barang->save();

            // SIMPAN KE RIWAYAT
            $riwayat = RiwayatBarang::create([
                'barang_id' => $barang->id,
                'jenis_transaksi' => 'masuk',
                'jumlah' => $request->jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $barang->stok,
                'keterangan' => $request->keterangan,
                'bukti' => $buktiPath,
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
            ]);

            // Log untuk debugging
            Log::info('Riwayat Barang Masuk Tersimpan', [
                'riwayat_id' => $riwayat->id,
                'barang' => $barang->nama,
                'jumlah' => $request->jumlah,
                'tanggal' => $tanggal
            ]);

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Stok {$barang->nama} berhasil ditambahkan {$request->jumlah} {$barang->satuan}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($buktiPath) && $buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            Log::error('Error barang masuk: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}