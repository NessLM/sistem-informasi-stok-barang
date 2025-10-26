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

            // ===== STEP 3: PRIORITAS TINGGI - CEK KATEGORI EXISTING DI GUDANG PJ =====
            $namaKategori = $kategoriUtama->nama;
            
            Log::info("STEP 3A: Mencari kategori '{$namaKategori}' yang sudah ada di gudang PJ manapun...");
            
            // Cari kategori dengan nama yang sama di gudang selain Gudang Utama
            $kategoriExisting = Kategori::where('nama', $namaKategori)
                ->whereHas('gudang', function($query) {
                    $query->where('nama', 'NOT LIKE', '%Utama%')
                          ->where('nama', 'NOT LIKE', '%utama%');
                })
                ->with('gudang')
                ->first();
            
            if ($kategoriExisting && $kategoriExisting->gudang) {
                // BINGO! Kategori sudah ada di gudang PJ, langsung gunakan!
                $gudangTujuan = $kategoriExisting->gudang;
                $matchType = 'existing_category';
                $matchScore = 100;
                
                Log::info("✓✓✓ KATEGORI EXISTING DITEMUKAN! ✓✓✓");
                Log::info("Kategori '{$namaKategori}' sudah ada di gudang '{$gudangTujuan->nama}' (ID Kategori: {$kategoriExisting->id})");
                Log::info("LANGSUNG GUNAKAN GUDANG INI!");
                
            } else {
                Log::info("✗ Kategori '{$namaKategori}' belum ada di gudang PJ manapun");
                Log::info("STEP 3B: Mencari gudang berdasarkan nama kategori...");
                
                // Lanjut ke matching algorithm
                $result = $this->findMatchingGudang($namaKategori);
                
                $gudangTujuan = $result['gudang'];
                $matchType = $result['match_type'];
                $matchScore = $result['score'];

                if (!$gudangTujuan) {
                    // Jika tidak ada gudang yang cocok, gunakan default gudang
                    $availableGudang = Gudang::where('nama', 'NOT LIKE', '%Utama%')
                        ->where('nama', 'NOT LIKE', '%utama%')
                        ->get();
                        
                    if ($availableGudang->isEmpty()) {
                        throw new \Exception("Tidak ada gudang PJ yang tersedia. Silakan buat gudang terlebih dahulu.");
                    }
                    
                    // Gunakan gudang pertama sebagai default
                    $gudangTujuan = $availableGudang->first();
                    $matchType = 'default';
                    $matchScore = 0;
                    
                    Log::warning("⚠ MENGGUNAKAN GUDANG DEFAULT: {$gudangTujuan->nama} untuk kategori '{$namaKategori}'");
                    Log::warning("Saran: Ubah nama kategori menjadi salah satu dari: " . $availableGudang->pluck('nama')->join(', '));
                }
            }
            
            Log::info("=== SMART DETECTION SUCCESS ===");
            Log::info("Gudang Tujuan: '{$gudangTujuan->nama}' (ID: {$gudangTujuan->id})");
            Log::info("Kategori: '{$namaKategori}'");
            Log::info("Match Type: {$matchType}");
            Log::info("Match Score: {$matchScore}");

            // ===== STEP 4: Cari atau buat kategori di gudang tujuan =====
            if ($matchType === 'existing_category') {
                // Kategori sudah ada, langsung gunakan
                $kategoriTujuan = $kategoriExisting;
                Log::info("STEP 4: Menggunakan kategori existing: {$kategoriTujuan->nama} (ID: {$kategoriTujuan->id})");
            } else {
                // Cari kategori di gudang tujuan dengan nama yang sama
                $kategoriTujuan = Kategori::where('gudang_id', $gudangTujuan->id)
                    ->where('nama', $namaKategori)
                    ->first();

                if (!$kategoriTujuan) {
                    // Buat kategori baru di gudang tujuan dengan nama yang sama
                    Log::info("STEP 4: Kategori '{$namaKategori}' tidak ditemukan di {$gudangTujuan->nama}, membuat baru...");
                    
                    $kategoriTujuan = Kategori::create([
                        'gudang_id' => $gudangTujuan->id,
                        'nama' => $namaKategori
                    ]);
                    
                    Log::info("STEP 4: Kategori baru dibuat: {$kategoriTujuan->nama} (ID: {$kategoriTujuan->id}) di {$gudangTujuan->nama}");
                } else {
                    Log::info("STEP 4: Kategori tujuan ditemukan: {$kategoriTujuan->nama} (ID: {$kategoriTujuan->id}) di {$gudangTujuan->nama}");
                }
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

            // Pesan sukses dengan info match type
            $message = "Barang '{$barang->nama_barang}' berhasil didistribusikan ke {$gudangTujuan->nama} (Kategori: {$kategoriTujuan->nama}). Jumlah: {$validated['jumlah']}. Stok PB tersisa: {$afterCommitPb->stok}";
            
            if ($matchType === 'existing_category') {
                $message .= " ✓ Kategori sudah ada di gudang ini.";
            } elseif ($matchType === 'default') {
                $message .= " ⚠️ PERHATIAN: Menggunakan gudang default karena kategori '{$namaKategori}' tidak cocok dengan gudang manapun.";
            }

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => $message
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
     * Algoritma matching yang lebih akurat dengan prioritas berlapis
     * 
     * @return array ['gudang' => Gudang|null, 'match_type' => string, 'score' => int]
     */
    private function findMatchingGudang($namaKategori)
    {
        Log::info("=== MULAI PENCARIAN GUDANG UNTUK KATEGORI: '{$namaKategori}' ===");
        
        // Ambil semua gudang kecuali Gudang Utama
        $allGudang = Gudang::where('nama', 'NOT LIKE', '%Utama%')
            ->where('nama', 'NOT LIKE', '%utama%')
            ->get();
        
        Log::info("Total gudang tersedia (non-Utama): " . $allGudang->count());
        
        if ($allGudang->isEmpty()) {
            Log::error("FATAL: Tidak ada gudang selain Gudang Utama!");
            return ['gudang' => null, 'match_type' => 'none', 'score' => 0];
        }

        // Log semua gudang yang tersedia
        foreach ($allGudang as $g) {
            Log::info("  - Gudang tersedia: {$g->nama} (ID: {$g->id})");
        }

        // Normalisasi nama kategori
        $namaKategoriLower = strtolower(trim($namaKategori));
        $namaKategoriClean = $this->cleanGudangName($namaKategoriLower);
        
        Log::info("Kategori normalized: '{$namaKategoriLower}'");
        Log::info("Kategori cleaned: '{$namaKategoriClean}'");

        // ═══════════════════════════════════════════════════════════════
        // PRIORITAS 1: EXACT MATCH (100% akurat)
        // ═══════════════════════════════════════════════════════════════
        Log::info("--- PRIORITAS 1: EXACT MATCH ---");
        
        foreach ($allGudang as $gudang) {
            $namaGudangClean = $this->cleanGudangName(strtolower($gudang->nama));
            
            // Exact match setelah cleaning
            if ($namaKategoriClean === $namaGudangClean) {
                Log::info("✓ EXACT MATCH FOUND: Kategori '{$namaKategoriClean}' === Gudang '{$namaGudangClean}' ({$gudang->nama})");
                return ['gudang' => $gudang, 'match_type' => 'exact', 'score' => 100];
            }
            
            // Juga cek tanpa cleaning (untuk kasus spesifik)
            if ($namaKategoriLower === strtolower($gudang->nama)) {
                Log::info("✓ EXACT MATCH FOUND (raw): Kategori '{$namaKategoriLower}' === Gudang '{$gudang->nama}'");
                return ['gudang' => $gudang, 'match_type' => 'exact', 'score' => 100];
            }
        }
        
        Log::info("✗ Tidak ada exact match");

        // ═══════════════════════════════════════════════════════════════
        // PRIORITAS 2: MAPPING DATABASE (untuk kategori yang sudah ada)
        // ═══════════════════════════════════════════════════════════════
        Log::info("--- PRIORITAS 2: MAPPING DATABASE ---");
        
        $mapping = $this->getKategoriGudangMapping();
        
        foreach ($mapping as $kategoriPattern => $gudangKeywords) {
            $kategoriPatternLower = strtolower($kategoriPattern);
            
            // Exact match pada mapping
            if ($namaKategoriLower === $kategoriPatternLower) {
                Log::info("✓ Mapping exact match: '{$namaKategoriLower}' === '{$kategoriPatternLower}'");
                
                $gudang = $this->findGudangByKeywords($allGudang, $gudangKeywords);
                if ($gudang) {
                    Log::info("✓ MAPPING MATCH FOUND: Kategori '{$namaKategori}' → Gudang '{$gudang->nama}'");
                    return ['gudang' => $gudang, 'match_type' => 'mapping_exact', 'score' => 90];
                }
            }
            
            // Contains match pada mapping
            if (strpos($namaKategoriLower, $kategoriPatternLower) !== false) {
                Log::info("✓ Mapping contains match: '{$kategoriPatternLower}' dalam '{$namaKategoriLower}'");
                
                $gudang = $this->findGudangByKeywords($allGudang, $gudangKeywords);
                if ($gudang) {
                    Log::info("✓ MAPPING MATCH FOUND: Kategori '{$namaKategori}' → Gudang '{$gudang->nama}'");
                    return ['gudang' => $gudang, 'match_type' => 'mapping_contains', 'score' => 85];
                }
            }
        }
        
        Log::info("✗ Tidak ada mapping match");

        // ═══════════════════════════════════════════════════════════════
        // PRIORITAS 3: SMART WORD MATCHING (untuk kategori baru)
        // ═══════════════════════════════════════════════════════════════
        Log::info("--- PRIORITAS 3: SMART WORD MATCHING ---");
        
        // Extract kata-kata signifikan dari kategori (min 3 karakter, bukan stop word)
        $stopWords = ['dan', 'atau', 'untuk', 'dari', 'yang', 'dengan', 'pada', 'di', 'ke', 'the', 'of', 'in', 'a', 'an'];
        $kategoriWords = array_values(array_filter(
            preg_split('/[\s&,\-\/]+/', $namaKategoriLower),
            function($word) use ($stopWords) {
                return strlen($word) >= 3 && !in_array($word, $stopWords);
            }
        ));
        
        Log::info("Kata signifikan dari kategori: " . json_encode($kategoriWords));

        if (!empty($kategoriWords)) {
            $scoreboard = [];
            
            foreach ($allGudang as $gudang) {
                $namaGudangLower = strtolower($gudang->nama);
                $namaGudangClean = $this->cleanGudangName($namaGudangLower);
                $gudangWords = array_filter(
                    preg_split('/[\s&,\-\/]+/', $namaGudangClean),
                    function($word) use ($stopWords) {
                        return strlen($word) >= 3 && !in_array($word, $stopWords);
                    }
                );
                
                $score = 0;
                $matchDetails = [];
                
                foreach ($kategoriWords as $kWord) {
                    foreach ($gudangWords as $gWord) {
                        // Exact word match
                        if ($kWord === $gWord) {
                            $score += 100;
                            $matchDetails[] = "exact: '{$kWord}' === '{$gWord}' (+100)";
                        }
                        // Kata kategori mengandung kata gudang
                        elseif (strpos($kWord, $gWord) !== false && strlen($gWord) >= 4) {
                            $score += 60;
                            $matchDetails[] = "contains: '{$gWord}' in '{$kWord}' (+60)";
                        }
                        // Kata gudang mengandung kata kategori
                        elseif (strpos($gWord, $kWord) !== false && strlen($kWord) >= 4) {
                            $score += 50;
                            $matchDetails[] = "contains: '{$kWord}' in '{$gWord}' (+50)";
                        }
                        // Similar words (Levenshtein distance)
                        else {
                            $distance = levenshtein($kWord, $gWord);
                            $maxLen = max(strlen($kWord), strlen($gWord));
                            $similarity = (1 - $distance / $maxLen) * 100;
                            
                            if ($similarity >= 70) {
                                $similarityScore = (int)($similarity / 2);
                                $score += $similarityScore;
                                $matchDetails[] = "similar: '{$kWord}' ≈ '{$gWord}' ({$similarity}% = +{$similarityScore})";
                            }
                        }
                    }
                }
                
                if ($score > 0) {
                    $scoreboard[$gudang->id] = [
                        'gudang' => $gudang,
                        'score' => $score,
                        'details' => $matchDetails
                    ];
                    
                    Log::info("  Gudang '{$gudang->nama}' score: {$score}");
                    foreach ($matchDetails as $detail) {
                        Log::info("    - {$detail}");
                    }
                }
            }
            
            // Urutkan berdasarkan score
            uasort($scoreboard, function($a, $b) {
                return $b['score'] - $a['score'];
            });
            
            // Return gudang dengan score tertinggi jika >= 40 (threshold lebih rendah)
            if (!empty($scoreboard)) {
                $topMatch = reset($scoreboard);
                
                if ($topMatch['score'] >= 40) {
                    Log::info("✓ SMART MATCH FOUND: '{$topMatch['gudang']->nama}' dengan score {$topMatch['score']}");
                    return ['gudang' => $topMatch['gudang'], 'match_type' => 'smart', 'score' => $topMatch['score']];
                } else {
                    Log::warning("✗ Score tertinggi ({$topMatch['score']}) kurang dari threshold (40)");
                    Log::warning("→ Akan menggunakan gudang default");
                }
            }
        }
        
        Log::info("✗ Tidak ada smart match yang memenuhi threshold");

        // ═══════════════════════════════════════════════════════════════
        // TIDAK ADA MATCH - AKAN MENGGUNAKAN DEFAULT DI CALLER
        // ═══════════════════════════════════════════════════════════════
        Log::warning("═══════════════════════════════════════════════════════");
        Log::warning("WARNING: TIDAK ADA GUDANG YANG COCOK UNTUK KATEGORI '{$namaKategori}'");
        Log::warning("═══════════════════════════════════════════════════════");
        Log::warning("Gudang yang tersedia:");
        foreach ($allGudang as $g) {
            Log::warning("  - {$g->nama} (ID: {$g->id})");
        }
        Log::warning("Saran: Ubah nama kategori agar mengandung kata kunci dari salah satu gudang di atas");
        
        return ['gudang' => null, 'match_type' => 'none', 'score' => 0];
    }

    /**
     * Bersihkan nama gudang dari prefix "Gudang" dan whitespace
     */
    private function cleanGudangName($namaGudang)
    {
        $cleaned = trim($namaGudang);
        $cleaned = preg_replace('/^gudang\s+/i', '', $cleaned);
        $cleaned = trim($cleaned);
        return $cleaned;
    }

    /**
     * Database mapping kategori ke gudang
     * Format: 'nama kategori' => ['keyword1', 'keyword2', ...]
     */
    private function getKategoriGudangMapping()
    {
        return [
            // ATK
            'alat tulis kantor' => ['atk', 'alat tulis'],
            'atk' => ['atk', 'alat tulis'],
            'kertas' => ['atk', 'alat tulis'],
            'pena' => ['atk', 'alat tulis'],
            'pensil' => ['atk', 'alat tulis'],
            'spidol' => ['atk', 'alat tulis'],
            'penghapus' => ['atk', 'alat tulis'],
            'penggaris' => ['atk', 'alat tulis'],
            'stapler' => ['atk', 'alat tulis'],
            'lem' => ['atk', 'alat tulis'],
            'gunting' => ['atk', 'alat tulis'],
            'amplop' => ['atk', 'alat tulis'],
            'map' => ['atk', 'alat tulis'],
            'ordner' => ['atk', 'alat tulis'],
            
            // Listrik / Elektrik
            'kabel listrik' => ['listrik', 'elektrik', 'electrical'],
            'kabel' => ['listrik', 'elektrik'],
            'lampu' => ['listrik', 'elektrik'],
            'stopkontak' => ['listrik', 'elektrik'],
            'saklar' => ['listrik', 'elektrik'],
            'perlengkapan listrik' => ['listrik', 'elektrik'],
            'instalasi listrik' => ['listrik', 'elektrik'],
            'fitting' => ['listrik', 'elektrik'],
            'bohlam' => ['listrik', 'elektrik'],
            'mcb' => ['listrik', 'elektrik'],
            'terminal' => ['listrik', 'elektrik'],
            
            // Kebersihan
            'alat kebersihan' => ['kebersihan', 'sanitasi', 'cleaning'],
            'kebersihan' => ['kebersihan', 'sanitasi', 'cleaning'],
            'pembersih' => ['kebersihan', 'sanitasi', 'cleaning'],
            'bahan pembersih' => ['kebersihan', 'sanitasi', 'cleaning'],
            'perlengkapan sanitasi' => ['kebersihan', 'sanitasi', 'cleaning'],
            'sabun' => ['kebersihan', 'sanitasi', 'cleaning'],
            'detergen' => ['kebersihan', 'sanitasi', 'cleaning'],
            'pel' => ['kebersihan', 'sanitasi', 'cleaning'],
            'sapu' => ['kebersihan', 'sanitasi', 'cleaning'],
            'kemoceng' => ['kebersihan', 'sanitasi', 'cleaning'],
            'lap' => ['kebersihan', 'sanitasi', 'cleaning'],
            'tissue' => ['kebersihan', 'sanitasi', 'cleaning'],
            
            // Komputer / IT
            'perangkat keras' => ['komputer', 'it', 'elektronik', 'computer'],
            'komputer' => ['komputer', 'it', 'elektronik'],
            'aksesoris komputer' => ['komputer', 'it', 'elektronik'],
            'jaringan' => ['komputer', 'it', 'elektronik'],
            'server' => ['komputer', 'it', 'elektronik'],
            'monitor' => ['komputer', 'it', 'elektronik'],
            'keyboard' => ['komputer', 'it', 'elektronik'],
            'mouse' => ['komputer', 'it', 'elektronik'],
            'printer' => ['komputer', 'it', 'elektronik'],
            'laptop' => ['komputer', 'it', 'elektronik'],
            'hardisk' => ['komputer', 'it', 'elektronik'],
            'ram' => ['komputer', 'it', 'elektronik'],
        ];
    }

    /**
     * Cari gudang berdasarkan keywords
     */
    private function findGudangByKeywords($allGudang, $keywords)
    {
        foreach ($keywords as $keyword) {
            $keywordLower = strtolower($keyword);
            
            $gudang = $allGudang->first(function($g) use ($keywordLower) {
                $namaGudangLower = strtolower($g->nama);
                return strpos($namaGudangLower, $keywordLower) !== false;
            });
            
            if ($gudang) {
                Log::info("  → Found gudang '{$gudang->nama}' via keyword '{$keyword}'");
                return $gudang;
            }
        }
        
        return null;
    }
}