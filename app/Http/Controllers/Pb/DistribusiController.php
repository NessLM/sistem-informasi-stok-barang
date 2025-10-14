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
    /**
     * Proses distribusi barang dari PB ke PJ
     */
    public function store(Request $request, $kodeBarang)
    {
        Log::info("=== MULAI PROSES DISTRIBUSI ===");
        Log::info("Kode Barang: {$kodeBarang}");
        Log::info("Request Data: " . json_encode($request->all()));

        // Validasi input
        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'nullable|date',
            'gudang_tujuan_id' => 'required|exists:gudang,id',
            'kategori_tujuan_id' => 'required|exists:kategori,id',
            'keterangan' => 'nullable|string|max:500',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        Log::info("Validasi berhasil. Data: " . json_encode($validated));

        $buktiPath = null;

        try {
            DB::beginTransaction();
            Log::info("Transaction dimulai");

            // ===== STEP 1: Cari barang =====
            $barang = Barang::where('kode_barang', $kodeBarang)->lockForUpdate()->first();
            
            if (!$barang) {
                throw new \Exception("Barang dengan kode {$kodeBarang} tidak ditemukan");
            }
            
            Log::info("Barang ditemukan: {$barang->nama_barang} (ID Kategori: {$barang->id_kategori})");

            // ===== STEP 2: Validasi kategori tujuan =====
            $kategoriTujuan = Kategori::where('id', $validated['kategori_tujuan_id'])
                ->where('gudang_id', $validated['gudang_tujuan_id'])
                ->first();

            if (!$kategoriTujuan) {
                throw new \Exception("Kategori tujuan tidak valid untuk gudang ini");
            }
            
            Log::info("Kategori tujuan valid: {$kategoriTujuan->nama} (Gudang: {$kategoriTujuan->gudang->nama})");

            // ===== STEP 3: Cek stok PB dengan LOCK =====
            Log::info("STEP 3: Mencari PB Stok dengan kode_barang = '{$kodeBarang}'");
            
            $pbStok = PbStok::where('kode_barang', $kodeBarang)->lockForUpdate()->first();
            
            if (!$pbStok) {
                Log::error("STEP 3 ERROR: PbStok tidak ditemukan!");
                
                // Debug: Cek semua data di pb_stok
                $allPbStok = DB::table('pb_stok')->get();
                Log::info("DEBUG - Semua data di pb_stok: " . json_encode($allPbStok));
                
                throw new \Exception("Barang belum ada di PB Stok. Silakan tambahkan barang masuk terlebih dahulu.");
            }
            
            Log::info("STEP 3: PbStok ditemukan - ID: {$pbStok->id}, Stok AWAL: {$pbStok->stok}, Kode: '{$pbStok->kode_barang}'");
            
            // Validasi stok cukup
            if ($pbStok->stok < $validated['jumlah']) {
                throw new \Exception(
                    "Stok PB tidak mencukupi. Tersedia: {$pbStok->stok}, Diminta: {$validated['jumlah']}"
                );
            }
            
            Log::info("STEP 3: Validasi stok OK - Tersedia: {$pbStok->stok}, Diminta: {$validated['jumlah']}");

            // ===== STEP 4: Upload bukti jika ada =====
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-distribusi', 'public');
                Log::info("STEP 4: Bukti file diupload: {$buktiPath}");
            }

            // ===== STEP 5: Kurangi stok PB menggunakan RAW QUERY =====
            Log::info("STEP 5: Mulai kurangi stok PB");
            Log::info("STEP 5: Stok sebelum = {$pbStok->stok}");
            
            $stokBaruPb = $pbStok->stok - $validated['jumlah'];
            Log::info("STEP 5: Stok yang akan diset = {$stokBaruPb}");
            
            // Update dengan raw query untuk memastikan eksekusi
            $affectedPb = DB::table('pb_stok')
                ->where('id', $pbStok->id)
                ->update([
                    'stok' => $stokBaruPb,
                    'updated_at' => now()
                ]);
            
            Log::info("STEP 5: Affected rows = {$affectedPb}");
            
            if ($affectedPb === 0) {
                throw new \Exception("Gagal mengupdate stok PB (affected rows = 0)");
            }
            
            // Verifikasi dari database langsung
            $verifikasiPb = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            Log::info("STEP 5: Verifikasi dari DB - Stok sekarang: {$verifikasiPb->stok}");
            
            if ($verifikasiPb->stok != $stokBaruPb) {
                throw new \Exception("Verifikasi gagal! Expected: {$stokBaruPb}, Actual: {$verifikasiPb->stok}");
            }

            // ===== STEP 6: Tambah/Update stok PJ =====
            Log::info("STEP 6: Mencari PjStok - Kode: '{$kodeBarang}', Gudang ID: {$validated['gudang_tujuan_id']}");
            
            $pjStok = PjStok::where('kode_barang', $kodeBarang)
                ->where('id_gudang', $validated['gudang_tujuan_id'])
                ->lockForUpdate()
                ->first();

            if ($pjStok) {
                Log::info("STEP 6: PjStok DITEMUKAN - ID: {$pjStok->id}, Stok AWAL: {$pjStok->stok}");
                
                $stokBaruPj = $pjStok->stok + $validated['jumlah'];
                Log::info("STEP 6: Stok PJ yang akan diset = {$stokBaruPj}");
                
                // Update dengan raw query
                $affectedPj = DB::table('pj_stok')
                    ->where('id', $pjStok->id)
                    ->update([
                        'stok' => $stokBaruPj,
                        'updated_at' => now()
                    ]);
                
                Log::info("STEP 6: Affected rows PJ = {$affectedPj}");
                
                if ($affectedPj === 0) {
                    throw new \Exception("Gagal mengupdate stok PJ (affected rows = 0)");
                }
                
                // Verifikasi
                $verifikasiPj = DB::table('pj_stok')->where('id', $pjStok->id)->first();
                Log::info("STEP 6: Verifikasi PJ dari DB - Stok sekarang: {$verifikasiPj->stok}");
                
                if ($verifikasiPj->stok != $stokBaruPj) {
                    throw new \Exception("Verifikasi PJ gagal! Expected: {$stokBaruPj}, Actual: {$verifikasiPj->stok}");
                }
                
            } else {
                Log::info("STEP 6: PjStok TIDAK DITEMUKAN, membuat baru");
                
                $pjStokId = DB::table('pj_stok')->insertGetId([
                    'kode_barang' => $kodeBarang,
                    'id_gudang' => $validated['gudang_tujuan_id'],
                    'id_kategori' => $validated['kategori_tujuan_id'],
                    'stok' => $validated['jumlah'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                Log::info("STEP 6: PjStok baru dibuat - ID: {$pjStokId}, Stok: {$validated['jumlah']}");
                
                // Verifikasi
                $verifikasiPjBaru = DB::table('pj_stok')->where('id', $pjStokId)->first();
                Log::info("STEP 6: Verifikasi PJ baru dari DB - Stok: {$verifikasiPjBaru->stok}");
            }

            // ===== STEP 7: Simpan transaksi distribusi =====
            $tanggal = $validated['tanggal'] ?? now()->toDateString();
            
            Log::info("STEP 7: Membuat transaksi distribusi");
            
            $transaksiId = DB::table('transaksi_distribusi')->insertGetId([
                'kode_barang' => $kodeBarang,
                'id_gudang_tujuan' => $validated['gudang_tujuan_id'],
                'jumlah' => $validated['jumlah'],
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
                'keterangan' => $validated['keterangan'] ?? null,
                'bukti' => $buktiPath,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info("STEP 7: Transaksi dibuat - ID: {$transaksiId}");

            // ===== STEP 8: Verifikasi FINAL sebelum commit =====
            Log::info("STEP 8: VERIFIKASI FINAL");
            
            $finalPb = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            $finalPj = DB::table('pj_stok')
                ->where('kode_barang', $kodeBarang)
                ->where('id_gudang', $validated['gudang_tujuan_id'])
                ->first();
            
            Log::info("FINAL PB - ID: {$finalPb->id}, Stok: {$finalPb->stok}");
            Log::info("FINAL PJ - ID: {$finalPj->id}, Stok: {$finalPj->stok}");
            
            // Commit transaksi
            DB::commit();
            Log::info("=== TRANSACTION COMMITTED SUCCESSFULLY ===");
            
            // Log final setelah commit
            $afterCommitPb = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            $afterCommitPj = DB::table('pj_stok')->where('id', $finalPj->id)->first();
            Log::info("SETELAH COMMIT - PB Stok: {$afterCommitPb->stok}, PJ Stok: {$afterCommitPj->stok}");

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Barang {$barang->nama_barang} berhasil didistribusikan ke {$kategoriTujuan->gudang->nama}. Jumlah: {$validated['jumlah']}. Stok PB tersisa: {$afterCommitPb->stok}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("=== TRANSACTION ROLLED BACK ===");
            Log::error("Error Message: " . $e->getMessage());
            Log::error("Error Line: " . $e->getLine());
            Log::error("Stack Trace: " . $e->getTraceAsString());
            
            // Hapus file jika upload gagal
            if ($buktiPath) {
                Storage::disk('public')->delete($buktiPath);
                Log::info("Bukti file dihapus karena rollback");
            }

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage()
            ]);
        }
    }
}