<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\PjStok;
use App\Models\Gudang;
use App\Models\TransaksiBarangMasuk;
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
            
            // Cari barang berdasarkan ID atau kode
            $barang = null;
            if (is_numeric($id)) {
                $barang = Barang::findOrFail($id);
            } else {
                $barang = Barang::where('kode_barang', $id)->firstOrFail();
            }
            
            $kodeBarang = $barang->kode_barang;
            
            // Cari Gudang Utama
            $gudangUtama = Gudang::where('nama', 'Gudang Utama')
                ->orWhere('nama', 'LIKE', '%Utama%')
                ->first();
            
            if (!$gudangUtama) {
                throw new \Exception('Gudang Utama tidak ditemukan');
            }

            // Upload bukti
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-barang-masuk', 'public');
            }

            Log::info('===== BARANG MASUK START =====');
            Log::info('Barang Kode: ' . $kodeBarang);
            Log::info('Jumlah: ' . $request->jumlah);

            // 1. Update stok PB (Pusat Barang)
            $pbStok = PbStok::where('kode_barang', $kodeBarang)->first();
            
            if ($pbStok) {
                $pbStok->tambahStok($request->jumlah);
            } else {
                $pbStok = PbStok::create([
                    'kode_barang' => $kodeBarang,
                    'stok' => $request->jumlah,
                ]);
            }

            Log::info('PB Stok Updated: ' . $pbStok->stok);

            // 2. Update stok PJ (Penyimpanan Gudang) di Gudang Utama
            if ($barang->kategori) {
                $pjStok = PjStok::where('kode_barang', $kodeBarang)
                    ->where('id_gudang', $gudangUtama->id)
                    ->first();
                
                if ($pjStok) {
                    $pjStok->tambahStok($request->jumlah);
                } else {
                    $pjStok = PjStok::create([
                        'kode_barang' => $kodeBarang,
                        'id_gudang' => $gudangUtama->id,
                        'id_kategori' => $barang->id_kategori,
                        'stok' => $request->jumlah,
                    ]);
                }
                
                Log::info('PJ Stok Updated: ' . $pjStok->stok);
            }

            // 3. SIMPAN TRANSAKSI BARANG MASUK - INI ADALAH YANG PENTING
            Log::info('Menyimpan transaksi barang masuk...');
            
            $transaksiData = [
                'kode_barang' => $kodeBarang,
                'jumlah' => $request->jumlah,
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
                'keterangan' => $request->keterangan ?? '',
                'bukti' => $buktiPath,
            ];

            Log::info('Data Transaksi:', $transaksiData);

            $transaksi = TransaksiBarangMasuk::create($transaksiData);

            Log::info('Transaksi Berhasil Disimpan ID: ' . $transaksi->id);
            Log::info('===== BARANG MASUK END =====');

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Stok {$barang->nama_barang} berhasil ditambahkan {$request->jumlah} {$barang->satuan}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($buktiPath) && $buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            Log::error('===== ERROR BARANG MASUK =====');
            Log::error('Error: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}