<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\StokGudang;
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

            $tanggal = $request->tanggal ?: now()->format('Y-m-d');
            $barang = Barang::with('kategori.gudang')->findOrFail($id);
            
            // Validasi kategori tujuan
            $kategoriTujuan = Kategori::where('id', $request->kategori_tujuan_id)
                ->where('gudang_id', $request->gudang_tujuan_id)
                ->first();

            if (!$kategoriTujuan) {
                throw new \Exception('Kategori yang dipilih tidak sesuai dengan gudang tujuan');
            }

            if ($barang->kategori_id == $request->kategori_tujuan_id) {
                throw new \Exception('Tidak dapat mendistribusikan ke kategori yang sama');
            }

            // Auto-create stok gudang asal jika belum ada
            $gudangAsalId = $barang->kategori->gudang_id;
            
            $stokGudangAsal = StokGudang::firstOrCreate(
                [
                    'barang_id' => $barang->id,
                    'gudang_id' => $gudangAsalId
                ],
                ['stok' => $barang->stok ?? 0]
            );

            Log::info('Distribusi - Stok Asal', [
                'barang' => $barang->nama,
                'kode' => $barang->kode,
                'gudang' => $barang->kategori->gudang->nama,
                'stok_tersedia' => $stokGudangAsal->stok,
                'jumlah_diminta' => $request->jumlah
            ]);

            // Validasi stok
            if ($stokGudangAsal->stok < $request->jumlah) {
                throw new \Exception("Stok tidak mencukupi. Tersedia: {$stokGudangAsal->stok}, Diminta: {$request->jumlah}");
            }

            $stokSebelum = $stokGudangAsal->stok;

            // Upload bukti
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-distribusi', 'public');
            }

            // PERUBAHAN UTAMA: Cari barang dengan KODE YANG SAMA di kategori tujuan
            $barangTujuan = Barang::where('kode', $barang->kode)
                ->where('kategori_id', $request->kategori_tujuan_id)
                ->first();

            if (!$barangTujuan) {
                // Buat barang baru tapi GUNAKAN KODE YANG SAMA
                $barangTujuan = Barang::create([
                    'nama' => $barang->nama,
                    'kode' => $barang->kode, // KODE TETAP SAMA
                    'kategori_id' => $request->kategori_tujuan_id,
                    'jenis_barang_id' => $barang->jenis_barang_id ?? null,
                    'stok' => 0,
                    'satuan' => $barang->satuan,
                    'harga' => $barang->harga,
                ]);
                
                Log::info('Barang baru dibuat di tujuan dengan kode yang sama', [
                    'kode' => $barang->kode,
                    'nama' => $barang->nama,
                    'kategori_tujuan' => $kategoriTujuan->nama
                ]);
                
                $message = "Barang berhasil didistribusikan ke {$kategoriTujuan->gudang->nama} - {$kategoriTujuan->nama}";
            } else {
                Log::info('Barang sudah ada di tujuan, stok akan ditambah', [
                    'kode' => $barang->kode,
                    'stok_sebelum' => $barangTujuan->stok
                ]);
                
                $message = "Barang berhasil didistribusikan. Stok di {$kategoriTujuan->gudang->nama} bertambah: {$request->jumlah}";
            }

            // Kurangi stok gudang asal
            $stokGudangAsal->stok -= $request->jumlah;
            $stokGudangAsal->save();
            
            Log::info('Stok Asal Dikurangi', [
                'dari' => $stokSebelum,
                'ke' => $stokGudangAsal->stok
            ]);

            // Tambah stok gudang tujuan
            $stokGudangTujuan = StokGudang::firstOrCreate(
                [
                    'barang_id' => $barangTujuan->id,
                    'gudang_id' => $request->gudang_tujuan_id
                ],
                ['stok' => 0]
            );
            
            $stokTujuanSebelum = $stokGudangTujuan->stok;
            $stokGudangTujuan->stok += $request->jumlah;
            $stokGudangTujuan->save();
            
            Log::info('Stok Tujuan Ditambah', [
                'barang' => $barangTujuan->nama,
                'kode' => $barangTujuan->kode,
                'gudang' => $kategoriTujuan->gudang->nama,
                'dari' => $stokTujuanSebelum,
                'ke' => $stokGudangTujuan->stok
            ]);

            // Update stok di tabel barang (untuk backward compatibility)
            $barang->stok = $stokGudangAsal->stok;
            $barang->save();
            
            $barangTujuan->stok = $stokGudangTujuan->stok;
            $barangTujuan->save();

            // Simpan riwayat
            RiwayatBarang::create([
                'barang_id' => $barang->id,
                'jenis_transaksi' => 'distribusi',
                'jumlah' => $request->jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokGudangAsal->stok,
                'kategori_asal_id' => $barang->kategori_id,
                'kategori_tujuan_id' => $request->kategori_tujuan_id,
                'gudang_tujuan_id' => $request->gudang_tujuan_id,
                'barang_tujuan_id' => $barangTujuan->id,
                'bukti' => $buktiPath,
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
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

            Log::error('Error Distribusi', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        return $this->distribusi($request, $request->barang_id);
    }
}