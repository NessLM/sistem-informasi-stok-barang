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
     * SMART DISTRIBUTION: Gudang tujuan otomatis berdasarkan nama kategori
     */
    public function store(Request $request, $kodeBarang)
    {
        Log::info("=== MULAI PROSES SMART DISTRIBUTION ===");
        Log::info("Kode Barang: {$kodeBarang}");
        Log::info("Request Data: " . json_encode($request->all()));

        // Validasi input (tidak perlu gudang_tujuan_id dan kategori_tujuan_id)
        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'nullable|date',
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
            
            Log::info("Barang ditemukan: {$barang->nama_barang} (ID Kategori Asal: {$barang->id_kategori})");

            // ===== STEP 2: Ambil kategori asal =====
            $kategoriAsal = Kategori::find($barang->id_kategori);
            
            if (!$kategoriAsal) {
                throw new \Exception("Kategori asal barang tidak ditemukan");
            }
            
            Log::info("Kategori asal: {$kategoriAsal->nama} (ID: {$kategoriAsal->id})");

            // ===== STEP 3: SMART AUTO-DETECT GUDANG berdasarkan nama kategori =====
            // Cari gudang yang namanya mengandung nama kategori (case-insensitive)
            // Contoh: Kategori "ATK" → cari "Gudang ATK", "ATK", "Gudang Alat Tulis Kantor"
            $namaKategori = $kategoriAsal->nama;
            
            Log::info("SMART DETECTION: Mencari gudang untuk kategori '{$namaKategori}'");
            
            // Prioritas pencarian:
            // 1. Gudang dengan nama persis sama dengan kategori
            // 2. Gudang yang mengandung nama kategori
            // 3. Gudang pertama selain Gudang Utama
            
            $gudangTujuan = Gudang::where('nama', 'NOT LIKE', '%Utama%')
                ->where('nama', 'NOT LIKE', '%utama%')
                ->where(function($query) use ($namaKategori) {
                    // Cari yang namanya mengandung nama kategori
                    $query->where('nama', 'LIKE', "%{$namaKategori}%")
                          ->orWhere('nama', 'LIKE', strtolower("%{$namaKategori}%"))
                          ->orWhere('nama', 'LIKE', strtoupper("%{$namaKategori}%"));
                })
                ->first();
            
            // Jika tidak ditemukan berdasarkan nama kategori, ambil gudang pertama
            if (!$gudangTujuan) {
                Log::info("SMART DETECTION: Tidak menemukan gudang spesifik untuk '{$namaKategori}', menggunakan gudang default");
                
                $gudangTujuan = Gudang::where('nama', 'NOT LIKE', '%Utama%')
                    ->where('nama', 'NOT LIKE', '%utama%')
                    ->orderBy('id', 'asc')
                    ->first();
            }

            if (!$gudangTujuan) {
                throw new \Exception("Tidak ada gudang tujuan yang tersedia. Pastikan sudah ada gudang selain Gudang Utama.");
            }
            
            Log::info("SMART DETECTION SUCCESS: Gudang Tujuan = '{$gudangTujuan->nama}' (ID: {$gudangTujuan->id}) untuk kategori '{$namaKategori}'");

            // ===== STEP 4: Cari/Buat kategori di gudang tujuan =====
            // Cari kategori dengan nama yang sama di gudang tujuan
            $kategoriTujuan = Kategori::where('gudang_id', $gudangTujuan->id)
                ->where('nama', $kategoriAsal->nama)
                ->first();

            if (!$kategoriTujuan) {
                // Jika tidak ada kategori dengan nama yang sama, buat otomatis
                Log::info("Kategori '{$kategoriAsal->nama}' tidak ditemukan di {$gudangTujuan->nama}, membuat baru...");
                
                $kategoriTujuan = Kategori::create([
                    'gudang_id' => $gudangTujuan->id,
                    'nama' => $kategoriAsal->nama
                ]);
                
                Log::info("Kategori baru dibuat: {$kategoriTujuan->nama} (ID: {$kategoriTujuan->id}) di {$gudangTujuan->nama}");
            } else {
                Log::info("Kategori tujuan ditemukan: {$kategoriTujuan->nama} (ID: {$kategoriTujuan->id}) di {$gudangTujuan->nama}");
            }

            // ===== STEP 5: Cek stok PB dengan LOCK =====
            Log::info("STEP 5: Mencari PB Stok dengan kode_barang = '{$kodeBarang}'");
            
            $pbStok = PbStok::where('kode_barang', $kodeBarang)->lockForUpdate()->first();
            
            if (!$pbStok) {
                Log::error("STEP 5 ERROR: PbStok tidak ditemukan!");
                
                // Debug: Cek semua data di pb_stok
                $allPbStok = DB::table('pb_stok')->get();
                Log::info("DEBUG - Semua data di pb_stok: " . json_encode($allPbStok));
                
                throw new \Exception("Barang belum ada di PB Stok. Silakan tambahkan barang masuk terlebih dahulu.");
            }
            
            Log::info("STEP 5: PbStok ditemukan - ID: {$pbStok->id}, Stok AWAL: {$pbStok->stok}, Kode: '{$pbStok->kode_barang}'");
            
            // Validasi stok cukup
            if ($pbStok->stok < $validated['jumlah']) {
                throw new \Exception(
                    "Stok PB tidak mencukupi. Tersedia: {$pbStok->stok}, Diminta: {$validated['jumlah']}"
                );
            }
            
            Log::info("STEP 5: Validasi stok OK - Tersedia: {$pbStok->stok}, Diminta: {$validated['jumlah']}");

            // ===== STEP 6: Upload bukti jika ada =====
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-distribusi', 'public');
                Log::info("STEP 6: Bukti file diupload: {$buktiPath}");
            }

            // ===== STEP 7: Kurangi stok PB =====
            Log::info("STEP 7: Mulai kurangi stok PB");
            Log::info("STEP 7: Stok sebelum = {$pbStok->stok}");
            
            $stokBaruPb = $pbStok->stok - $validated['jumlah'];
            Log::info("STEP 7: Stok yang akan diset = {$stokBaruPb}");
            
            $affectedPb = DB::table('pb_stok')
                ->where('id', $pbStok->id)
                ->update([
                    'stok' => $stokBaruPb,
                    'updated_at' => now()
                ]);
            
            Log::info("STEP 7: Affected rows = {$affectedPb}");
            
            if ($affectedPb === 0) {
                throw new \Exception("Gagal mengupdate stok PB (affected rows = 0)");
            }
            
            // Verifikasi
            $verifikasiPb = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            Log::info("STEP 7: Verifikasi dari DB - Stok sekarang: {$verifikasiPb->stok}");
            
            if ($verifikasiPb->stok != $stokBaruPb) {
                throw new \Exception("Verifikasi gagal! Expected: {$stokBaruPb}, Actual: {$verifikasiPb->stok}");
            }

            // ===== STEP 8: Tambah/Update stok PJ =====
            Log::info("STEP 8: Mencari PjStok - Kode: '{$kodeBarang}', Gudang ID: {$gudangTujuan->id}");
            
            $pjStok = PjStok::where('kode_barang', $kodeBarang)
                ->where('id_gudang', $gudangTujuan->id)
                ->lockForUpdate()
                ->first();

            if ($pjStok) {
                Log::info("STEP 8: PjStok DITEMUKAN - ID: {$pjStok->id}, Stok AWAL: {$pjStok->stok}");
                
                $stokBaruPj = $pjStok->stok + $validated['jumlah'];
                Log::info("STEP 8: Stok PJ yang akan diset = {$stokBaruPj}");
                
                $affectedPj = DB::table('pj_stok')
                    ->where('id', $pjStok->id)
                    ->update([
                        'stok' => $stokBaruPj,
                        'id_kategori' => $kategoriTujuan->id,
                        'updated_at' => now()
                    ]);
                
                Log::info("STEP 8: Affected rows PJ = {$affectedPj}");
                
                if ($affectedPj === 0) {
                    throw new \Exception("Gagal mengupdate stok PJ (affected rows = 0)");
                }
                
                // Verifikasi
                $verifikasiPj = DB::table('pj_stok')->where('id', $pjStok->id)->first();
                Log::info("STEP 8: Verifikasi PJ dari DB - Stok sekarang: {$verifikasiPj->stok}");
                
                if ($verifikasiPj->stok != $stokBaruPj) {
                    throw new \Exception("Verifikasi PJ gagal! Expected: {$stokBaruPj}, Actual: {$verifikasiPj->stok}");
                }
                
            } else {
                Log::info("STEP 8: PjStok TIDAK DITEMUKAN, membuat baru");
                
                $pjStokId = DB::table('pj_stok')->insertGetId([
                    'kode_barang' => $kodeBarang,
                    'id_gudang' => $gudangTujuan->id,
                    'id_kategori' => $kategoriTujuan->id,
                    'stok' => $validated['jumlah'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                Log::info("STEP 8: PjStok baru dibuat - ID: {$pjStokId}, Stok: {$validated['jumlah']}, Kategori: {$kategoriTujuan->nama}");
                
                // Verifikasi
                $verifikasiPjBaru = DB::table('pj_stok')->where('id', $pjStokId)->first();
                Log::info("STEP 8: Verifikasi PJ baru dari DB - Stok: {$verifikasiPjBaru->stok}");
            }

            // ===== STEP 9: Simpan transaksi distribusi =====
            $tanggal = $validated['tanggal'] ?? now()->toDateString();
            
            Log::info("STEP 9: Membuat transaksi distribusi");
            
            $transaksiId = DB::table('transaksi_distribusi')->insertGetId([
                'kode_barang' => $kodeBarang,
                'id_gudang_tujuan' => $gudangTujuan->id,
                'jumlah' => $validated['jumlah'],
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
                'keterangan' => $validated['keterangan'] ?? null,
                'bukti' => $buktiPath,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info("STEP 9: Transaksi dibuat - ID: {$transaksiId}");

            // ===== STEP 10: Verifikasi FINAL =====
            Log::info("STEP 10: VERIFIKASI FINAL");
            
            $finalPb = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            $finalPj = DB::table('pj_stok')
                ->where('kode_barang', $kodeBarang)
                ->where('id_gudang', $gudangTujuan->id)
                ->first();
            
            Log::info("FINAL PB - ID: {$finalPb->id}, Stok: {$finalPb->stok}");
            Log::info("FINAL PJ - ID: {$finalPj->id}, Stok: {$finalPj->stok}, Kategori ID: {$finalPj->id_kategori}");
            
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
                'message' => "Barang {$barang->nama_barang} berhasil didistribusikan ke {$gudangTujuan->nama} (Kategori: {$kategoriTujuan->nama}). Jumlah: {$validated['jumlah']}. Stok PB tersisa: {$afterCommitPb->stok}"
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