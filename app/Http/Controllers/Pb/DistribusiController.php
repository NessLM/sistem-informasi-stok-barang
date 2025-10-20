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

        // Validasi input
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
            
            Log::info("Barang ditemukan: {$barang->nama_barang} (ID Kategori: {$barang->id_kategori})");

            // ===== STEP 2: Ambil kategori asal dari Gudang Utama =====
            $kategoriUtama = Kategori::find($barang->id_kategori);
            
            if (!$kategoriUtama) {
                throw new \Exception("Kategori barang tidak ditemukan");
            }
            
            Log::info("Kategori Utama: {$kategoriUtama->nama} (ID: {$kategoriUtama->id}, Gudang ID: {$kategoriUtama->gudang_id})");

            // ===== STEP 3: SMART AUTO-DETECT GUDANG berdasarkan nama kategori =====
            $namaKategori = $kategoriUtama->nama;
            
            Log::info("SMART DETECTION: Mencari gudang untuk kategori '{$namaKategori}'");
            
            // Strategi pencarian gudang yang lebih akurat:
            // 1. Extract kata kunci dari nama kategori
            // 2. Cari gudang yang mengandung kata kunci tersebut
            
            $gudangTujuan = $this->findMatchingGudang($namaKategori);

            if (!$gudangTujuan) {
                throw new \Exception("Tidak dapat menemukan gudang yang sesuai untuk kategori '{$namaKategori}'. Pastikan ada gudang dengan nama yang mengandung kata kunci dari kategori.");
            }
            
            Log::info("SMART DETECTION SUCCESS: Gudang Tujuan = '{$gudangTujuan->nama}' (ID: {$gudangTujuan->id}) untuk kategori '{$namaKategori}'");

            // ===== STEP 4: Cari kategori di gudang tujuan dengan nama yang sama =====
            $kategoriTujuan = Kategori::where('gudang_id', $gudangTujuan->id)
                ->where('nama', $namaKategori)
                ->first();

            if (!$kategoriTujuan) {
                // Buat kategori baru di gudang tujuan dengan nama yang sama
                Log::info("Kategori '{$namaKategori}' tidak ditemukan di {$gudangTujuan->nama}, membuat baru...");
                
                $kategoriTujuan = Kategori::create([
                    'gudang_id' => $gudangTujuan->id,
                    'nama' => $namaKategori
                ]);
                
                Log::info("Kategori baru dibuat: {$kategoriTujuan->nama} (ID: {$kategoriTujuan->id}) di {$gudangTujuan->nama}");
            } else {
                Log::info("Kategori tujuan ditemukan: {$kategoriTujuan->nama} (ID: {$kategoriTujuan->id}) di {$gudangTujuan->nama}");
            }

            // ===== STEP 5: Cek stok PB dengan LOCK =====
            Log::info("STEP 5: Mencari PB Stok dengan kode_barang = '{$kodeBarang}'");
            
            $pbStok = PbStok::where('kode_barang', $kodeBarang)->lockForUpdate()->first();
            
            if (!$pbStok) {
                throw new \Exception("Barang belum ada di PB Stok. Silakan tambahkan barang masuk terlebih dahulu.");
            }
            
            Log::info("STEP 5: PbStok ditemukan - ID: {$pbStok->id}, Stok AWAL: {$pbStok->stok}");
            
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
            
            $stokBaruPb = $pbStok->stok - $validated['jumlah'];
            
            $affectedPb = DB::table('pb_stok')
                ->where('id', $pbStok->id)
                ->update([
                    'stok' => $stokBaruPb,
                    'updated_at' => now()
                ]);
            
            Log::info("STEP 7: Stok PB diupdate. Stok baru: {$stokBaruPb}");

            // ===== STEP 8: Tambah/Update stok PJ =====
            Log::info("STEP 8: Mencari PjStok - Kode: '{$kodeBarang}', Gudang ID: {$gudangTujuan->id}");
            
            $pjStok = PjStok::where('kode_barang', $kodeBarang)
                ->where('id_gudang', $gudangTujuan->id)
                ->lockForUpdate()
                ->first();

            if ($pjStok) {
                Log::info("STEP 8: PjStok DITEMUKAN - ID: {$pjStok->id}, Stok AWAL: {$pjStok->stok}");
                
                $stokBaruPj = $pjStok->stok + $validated['jumlah'];
                
                $affectedPj = DB::table('pj_stok')
                    ->where('id', $pjStok->id)
                    ->update([
                        'stok' => $stokBaruPj,
                        'id_kategori' => $kategoriTujuan->id,
                        'updated_at' => now()
                    ]);
                
                Log::info("STEP 8: PjStok diupdate. Stok baru: {$stokBaruPj}");
                
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

            // Commit transaksi
            DB::commit();
            Log::info("=== TRANSACTION COMMITTED SUCCESSFULLY ===");
            
            // Ambil data final
            $afterCommitPb = DB::table('pb_stok')->where('id', $pbStok->id)->first();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Barang '{$barang->nama_barang}' berhasil didistribusikan ke {$gudangTujuan->nama} (Kategori: {$kategoriTujuan->nama}). Jumlah: {$validated['jumlah']}. Stok PB tersisa: {$afterCommitPb->stok}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("=== TRANSACTION ROLLED BACK ===");
            Log::error("Error Message: " . $e->getMessage());
            Log::error("Error Line: " . $e->getLine());
            
            // Hapus file jika upload gagal
            if ($buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * IMPROVED: Cari gudang yang paling cocok berdasarkan nama kategori
     * Menggunakan algoritma matching yang lebih akurat
     */
    private function findMatchingGudang($namaKategori)
    {
        Log::info("Finding matching gudang for kategori: {$namaKategori}");
        
        // Ambil semua gudang kecuali Gudang Utama
        $allGudang = Gudang::where('nama', 'NOT LIKE', '%Utama%')
            ->where('nama', 'NOT LIKE', '%utama%')
            ->get();
        
        Log::info("Total gudang available: " . $allGudang->count());
        
        if ($allGudang->isEmpty()) {
            Log::error("Tidak ada gudang selain Gudang Utama!");
            return null;
        }

        // Normalisasi nama kategori untuk matching
        $namaKategoriLower = strtolower($namaKategori);
        $namaKategoriWords = preg_split('/[\s&,]+/', $namaKategoriLower);
        
        Log::info("Kata kunci dari kategori: " . json_encode($namaKategoriWords));

        // Mapping khusus untuk kategori umum
        $mappingKhusus = [
            'alat tulis kantor' => 'atk',
            'atk' => 'atk',
            'kabel' => 'listrik',
            'lampu' => 'listrik',
            'stopkontak' => 'listrik',
            'saklar' => 'listrik',
            'perlengkapan' => 'listrik',
            'instalasi' => 'listrik',
            'alat kebersihan' => 'kebersihan',
            'pembersih' => 'kebersihan',
            'bahan pembersih' => 'kebersihan',
            'perlengkapan sanitasi' => 'kebersihan',
            'perangkat keras' => 'komputer',
            'aksesoris' => 'komputer',
            'jaringan' => 'komputer',
            'server' => 'komputer',
            'monitor' => 'komputer',
            'keyboard' => 'komputer',
            'mouse' => 'komputer',
        ];

        // Cek mapping khusus terlebih dahulu
        foreach ($mappingKhusus as $keyword => $gudangKeyword) {
            if (stripos($namaKategoriLower, $keyword) !== false) {
                Log::info("Match ditemukan via mapping khusus: '{$keyword}' -> '{$gudangKeyword}'");
                
                $gudang = $allGudang->first(function($g) use ($gudangKeyword) {
                    return stripos(strtolower($g->nama), $gudangKeyword) !== false;
                });
                
                if ($gudang) {
                    Log::info("Gudang match: {$gudang->nama} (ID: {$gudang->id})");
                    return $gudang;
                }
            }
        }

        // Jika tidak ada mapping khusus, coba matching berdasarkan kata kunci
        $bestMatch = null;
        $maxScore = 0;

        foreach ($allGudang as $gudang) {
            $namaGudangLower = strtolower($gudang->nama);
            $score = 0;
            
            // Hitung score berdasarkan jumlah kata kunci yang cocok
            foreach ($namaKategoriWords as $word) {
                if (strlen($word) >= 3 && stripos($namaGudangLower, $word) !== false) {
                    $score += strlen($word); // Semakin panjang kata yang cocok, semakin tinggi scorenya
                    Log::info("Word '{$word}' found in '{$gudang->nama}', score +{$score}");
                }
            }
            
            if ($score > $maxScore) {
                $maxScore = $score;
                $bestMatch = $gudang;
                Log::info("New best match: {$gudang->nama} with score {$score}");
            }
        }

        if ($bestMatch) {
            Log::info("Best match found: {$bestMatch->nama} (ID: {$bestMatch->id}) with score {$maxScore}");
            return $bestMatch;
        }

        // Jika tidak ada yang cocok, ambil gudang pertama sebagai fallback
        Log::warning("Tidak ada match yang cocok, menggunakan fallback ke gudang pertama");
        $fallbackGudang = $allGudang->first();
        Log::info("Fallback gudang: {$fallbackGudang->nama} (ID: {$fallbackGudang->id})");
        
        return $fallbackGudang;
    }
}