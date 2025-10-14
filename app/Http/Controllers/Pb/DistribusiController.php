<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\PjStok;
use App\Models\Kategori;
use App\Models\Gudang;
use App\Models\TransaksiDistribusi;
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
            'keterangan' => 'nullable|string|max:500',
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
            
            // Validasi kategori tujuan
            $kategoriTujuan = Kategori::where('id', $request->kategori_tujuan_id)
                ->where('gudang_id', $request->gudang_tujuan_id)
                ->first();

            if (!$kategoriTujuan) {
                throw new \Exception('Kategori yang dipilih tidak sesuai dengan gudang tujuan');
            }

            // Cek stok PB
            $pbStok = PbStok::where('kode_barang', $kodeBarang)->first();
            
            if (!$pbStok || $pbStok->stok < $request->jumlah) {
                throw new \Exception('Stok PB tidak mencukupi. Tersedia: ' . ($pbStok ? $pbStok->stok : 0) . ', Diminta: ' . $request->jumlah);
            }

            Log::info('===== DISTRIBUSI START =====');
            Log::info('Barang Kode: ' . $kodeBarang);
            Log::info('PB Stok Sebelum: ' . $pbStok->stok);

            // Upload bukti
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-distribusi', 'public');
            }

            // 1. Kurangi stok PB
            $pbStokSebelum = $pbStok->stok;
            $pbStok->kurangiStok($request->jumlah);
            
            Log::info('PB Stok Sesudah: ' . $pbStok->stok);

            // 2. Tambah stok PJ (Penyimpanan Gudang Tujuan)
            $pjStok = PjStok::where('kode_barang', $kodeBarang)
                ->where('id_gudang', $request->gudang_tujuan_id)
                ->first();
            
            $pjStokSebelum = $pjStok ? $pjStok->stok : 0;
            
            if ($pjStok) {
                $pjStok->tambahStok($request->jumlah);
            } else {
                $pjStok = PjStok::create([
                    'kode_barang' => $kodeBarang,
                    'id_gudang' => $request->gudang_tujuan_id,
                    'id_kategori' => $request->kategori_tujuan_id,
                    'stok' => $request->jumlah,
                ]);
            }
            
            Log::info('PJ Stok Tujuan: ' . $pjStok->stok);

            // 3. Simpan transaksi distribusi
            Log::info('Menyimpan transaksi distribusi...');
            
            $transaksiData = [
                'kode_barang' => $kodeBarang,
                'id_gudang_tujuan' => $request->gudang_tujuan_id,
                'jumlah' => $request->jumlah,
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
                'keterangan' => $request->keterangan ?? '',
                'bukti' => $buktiPath,
            ];

            Log::info('Data Transaksi:', $transaksiData);

            $transaksi = TransaksiDistribusi::create($transaksiData);

            Log::info('Transaksi Berhasil Disimpan ID: ' . $transaksi->id);
            Log::info('===== DISTRIBUSI END =====');

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Barang {$barang->nama_barang} berhasil didistribusikan ke {$kategoriTujuan->gudang->nama} - {$kategoriTujuan->nama}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($buktiPath) && $buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            Log::error('===== ERROR DISTRIBUSI =====');
            Log::error('Error: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());

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