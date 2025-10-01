<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\RiwayatBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DistribusiController extends Controller
{
    public function distribusi(Request $request, $id)
    {
        $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'nullable|date',
            'gudang_tujuan_id' => 'required|exists:gudang,id',
            'kategori_tujuan_id' => 'required|exists:kategori,id',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Jika tanggal tidak diisi, gunakan tanggal hari ini
            $tanggal = $request->tanggal ?: now()->format('Y-m-d');

            $barang = Barang::with('kategori.gudang')->findOrFail($id);
            $stokSebelum = $barang->stok;

            // Validasi stok
            if ($barang->stok < $request->jumlah) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $barang->stok
                ]);
            }

            // Validasi kategori tujuan
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

            if ($barang->kategori_id == $request->kategori_tujuan_id) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Tidak dapat mendistribusikan ke kategori yang sama'
                ]);
            }

            // Upload bukti
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-distribusi', 'public');
            }

            // Cari barang dengan nama dan kategori yang sama di tujuan
            $barangTujuan = Barang::where('nama', $barang->nama)
                ->where('kategori_id', $request->kategori_tujuan_id)
                ->first();

            if ($barangTujuan) {
                // Jika barang sudah ada di kategori tujuan, tambahkan stoknya
                $barangTujuan->stok += $request->jumlah;
                $barangTujuan->save();
                
                $message = 'Barang berhasil didistribusikan. Stok di kategori tujuan bertambah: ' . $request->jumlah;
                $barangTujuanId = $barangTujuan->id;
            } else {
                // Jika barang belum ada, buat barang baru dengan kode unik
                $newKode = $this->generateUniqueKode($barang->kode);
                
                $barangTujuan = Barang::create([
                    'nama' => $barang->nama,
                    'kode' => $newKode,
                    'kategori_id' => $request->kategori_tujuan_id,
                    'jenis_barang_id' => $barang->jenis_barang_id ?? null,
                    'stok' => $request->jumlah,
                    'satuan' => $barang->satuan,
                    'harga' => $barang->harga,
                ]);
                
                $message = "Barang berhasil didistribusikan dengan kode baru: {$newKode}";
                $barangTujuanId = $barangTujuan->id;
            }

            // Kurangi stok barang asal
            $barang->stok -= $request->jumlah;
            $barang->save();

            // SIMPAN KE RIWAYAT
            $riwayat = RiwayatBarang::create([
                'barang_id' => $barang->id,
                'jenis_transaksi' => 'distribusi',
                'jumlah' => $request->jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $barang->stok,
                'kategori_asal_id' => $barang->kategori_id,
                'kategori_tujuan_id' => $request->kategori_tujuan_id,
                'gudang_tujuan_id' => $request->gudang_tujuan_id,
                'barang_tujuan_id' => $barangTujuanId,
                'bukti' => $buktiPath,
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
            ]);

            // Log untuk debugging
            Log::info('Riwayat Distribusi Tersimpan', [
                'riwayat_id' => $riwayat->id,
                'barang_asal' => $barang->nama,
                'kode_asal' => $barang->kode,
                'barang_tujuan' => $barangTujuan->nama,
                'kode_tujuan' => $barangTujuan->kode,
                'jumlah' => $request->jumlah,
                'tanggal' => $tanggal
            ]);

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

            Log::error('Error distribusi: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    private function generateUniqueKode($originalKode)
    {
        // Cek apakah kode sudah ada
        $existingBarang = Barang::where('kode', $originalKode)->first();
        
        if (!$existingBarang) {
            // Jika belum ada, gunakan kode asli
            return $originalKode;
        }
        
        // Jika sudah ada, generate kode baru dengan suffix
        $counter = 1;
        $newKode = $originalKode . '-' . $counter;
        
        while (Barang::where('kode', $newKode)->exists()) {
            $counter++;
            $newKode = $originalKode . '-' . $counter;
        }
        
        return $newKode;
    }

    public function store(Request $request)
    {
        return $this->distribusi($request, $request->barang_id);
    }
}