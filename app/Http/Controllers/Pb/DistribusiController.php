<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DistribusiController extends Controller
{
    public function distribusi(Request $request, $id)
    {
        $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'required|date',
            'gudang_tujuan_id' => 'required|exists:gudang,id',
            'kategori_tujuan_id' => 'required|exists:kategori,id',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $barang = Barang::with('kategori.gudang')->findOrFail($id);

            // Cek stok mencukupi
            if ($barang->stok < $request->jumlah) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $barang->stok
                ]);
            }

            // Validasi kategori tujuan ada di gudang tujuan
            $kategoriTujuan = Kategori::where('id', $request->kategori_tujuan_id)
                ->where('gudang_id', $request->gudang_tujuan_id)
                ->first();

            if (!$kategoriTujuan) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Kategori yang dipilih tidak sesuai dengan gudang tujuan'
                ]);
            }

            // Validasi: tidak bisa distribusi ke kategori yang sama
            if ($barang->kategori_id == $request->kategori_tujuan_id) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Tidak dapat mendistribusikan ke kategori yang sama'
                ]);
            }

            // Upload bukti jika ada
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-distribusi', 'public');
            }

            // ✅ PERBAIKAN: Cari barang berdasarkan KODE saja (karena kode UNIQUE)
            $barangTujuan = Barang::where('kode', $barang->kode)
                ->where('kategori_id', $request->kategori_tujuan_id)
                ->first();

            if ($barangTujuan) {
                // Jika barang dengan kode yang sama sudah ada di kategori tujuan
                $barangTujuan->stok += $request->jumlah;
                $barangTujuan->save();
                
                $message = 'Barang berhasil didistribusikan. Stok di kategori tujuan bertambah: ' . $request->jumlah;
            } else {
                // ✅ Cek apakah kode sudah ada di tabel barang (di kategori lain)
                $kodeExists = Barang::where('kode', $barang->kode)->exists();
                
                if ($kodeExists) {
                    // Jika kode sudah ada di kategori lain, buat kode baru
                    $newKode = $this->generateUniqueKode($barang->kode);
                    
                    Barang::create([
                        'nama' => $barang->nama,
                        'kode' => $newKode,
                        'kategori_id' => $request->kategori_tujuan_id,
                        'jenis_barang_id' => $barang->jenis_barang_id ?? null,
                        'stok' => $request->jumlah,
                        'satuan' => $barang->satuan,
                        'harga' => $barang->harga,
                    ]);
                    
                    $message = "Barang berhasil didistribusikan dengan kode baru: {$newKode} (stok: {$request->jumlah})";
                } else {
                    // Jika kode belum ada sama sekali, gunakan kode asli
                    Barang::create([
                        'nama' => $barang->nama,
                        'kode' => $barang->kode,
                        'kategori_id' => $request->kategori_tujuan_id,
                        'jenis_barang_id' => $barang->jenis_barang_id ?? null,
                        'stok' => $request->jumlah,
                        'satuan' => $barang->satuan,
                        'harga' => $barang->harga,
                    ]);
                    
                    $message = 'Barang berhasil didistribusikan dan dibuat di kategori tujuan dengan stok: ' . $request->jumlah;
                }
            }

            // Kurangi stok barang asal
            $barang->stok -= $request->jumlah;
            $barang->save();

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($buktiPath) && $buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            \Log::error('Error distribusi: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate unique code by adding suffix
     */
    private function generateUniqueKode($originalKode)
    {
        $counter = 1;
        $newKode = $originalKode;
        
        // Cek apakah kode sudah ada, jika ya tambahkan suffix
        while (Barang::where('kode', $newKode)->exists()) {
            $newKode = $originalKode . '-' . $counter;
            $counter++;
        }
        
        return $newKode;
    }

    public function store(Request $request)
    {
        return $this->distribusi($request, $request->barang_id);
    }
}