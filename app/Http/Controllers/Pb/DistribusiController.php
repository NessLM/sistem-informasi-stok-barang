<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\Kategori;
use App\Models\Bagian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DistribusiController extends Controller
{
    /**
     * Proses distribusi barang dari PB ke Bagian
     */
    public function store(Request $request, $kodeBarang)
    {
        Log::info("=== MULAI PROSES DISTRIBUSI PB KE BAGIAN ===");
        Log::info("Kode Barang (raw param): {$kodeBarang}");
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

            // ===== STEP 1: Resolve barang dari param (kode / id) =====
            [$kodeBarang, $barang] = $this->resolveKodeBarangOrFail($kodeBarang);
            Log::info("Barang ditemukan: {$barang->nama_barang} (kode: {$kodeBarang}, ID Kategori: {$barang->id_kategori})");

            // ===== STEP 2: Ambil kategori asal =====
            $kategoriUtama = Kategori::find($barang->id_kategori);

            if (!$kategoriUtama) {
                throw new \Exception("Kategori barang tidak ditemukan");
            }

            Log::info("Kategori: {$kategoriUtama->nama} (ID: {$kategoriUtama->id})");

            // ===== STEP 3: Cek stok PB dengan LOCK =====
            Log::info("STEP 3: Mencari PB Stok dengan kode_barang = '{$kodeBarang}'");

            $pbStok = PbStok::where('kode_barang', $kodeBarang)->lockForUpdate()->first();

            if (!$pbStok) {
                throw new \Exception("Barang belum ada di PB Stok. Silakan tambahkan barang masuk terlebih dahulu.");
            }

            Log::info("STEP 3: PbStok ditemukan - ID: {$pbStok->id}, Stok AWAL: {$pbStok->stok}, Bagian ID: {$pbStok->bagian_id}");

            // Validasi bagian_id ada
            if (!$pbStok->bagian_id) {
                throw new \Exception("Record PB Stok ini tidak memiliki bagian tujuan. Silakan update data terlebih dahulu.");
            }

            // Validasi stok cukup
            if ($pbStok->stok < $validated['jumlah']) {
                throw new \Exception(
                    "Stok PB tidak mencukupi. Tersedia: {$pbStok->stok}, Diminta: {$validated['jumlah']}"
                );
            }

            Log::info("STEP 3: Validasi stok OK - Tersedia: {$pbStok->stok}, Diminta: {$validated['jumlah']}");

            $bagianTujuan = $pbStok->bagian_id;

            // ===== STEP 4: Upload bukti jika ada =====
            if ($request->hasFile('bukti')) {
                $file = $request->file('bukti');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiPath = $file->storeAs('bukti-distribusi', $fileName, 'public');

                Log::info("STEP 4: Bukti file diupload: {$buktiPath}");
                Log::info("STEP 4: Nama file: {$fileName}");
            }

            // ===== STEP 5: Kurangi stok PB =====
            Log::info("STEP 5: Mulai kurangi stok PB");

            $stokBaruPb = $pbStok->stok - $validated['jumlah'];

            DB::table('pb_stok')
                ->where('id', $pbStok->id)
                ->update([
                    'stok' => $stokBaruPb,
                    'updated_at' => now()
                ]);

            Log::info("STEP 5: Stok PB diupdate. Stok baru: {$stokBaruPb}");

            // ===== STEP 6: Tambah/Update stok Bagian =====
            Log::info("STEP 6: Mencari StokBagian - Kode: '{$kodeBarang}', Bagian ID: {$bagianTujuan}");

            $stokBagian = DB::table('stok_bagian')
                ->where('kode_barang', $kodeBarang)
                ->where('bagian_id', $bagianTujuan)
                ->lockForUpdate()
                ->first();

            if ($stokBagian) {
                Log::info("STEP 6: StokBagian DITEMUKAN - ID: {$stokBagian->id}, Stok AWAL: {$stokBagian->stok}");

                $stokBaruBagian = $stokBagian->stok + $validated['jumlah'];

                DB::table('stok_bagian')
                    ->where('id', $stokBagian->id)
                    ->update([
                        'stok' => $stokBaruBagian,
                        'harga' => $pbStok->harga,
                        'updated_at' => now()
                    ]);

                Log::info("STEP 6: StokBagian diupdate. Stok baru: {$stokBaruBagian}");

            } else {
                Log::info("STEP 6: StokBagian TIDAK DITEMUKAN, membuat baru");

                $stokBagianId = DB::table('stok_bagian')->insertGetId([
                    'kode_barang' => $kodeBarang,
                    'bagian_id' => $bagianTujuan,
                    'stok' => $validated['jumlah'],
                    'harga' => $pbStok->harga,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("STEP 6: StokBagian baru dibuat - ID: {$stokBagianId}, Stok: {$validated['jumlah']}");
            }

            // ===== STEP 7: Simpan transaksi distribusi =====
            $tanggal = $validated['tanggal'] ?? now()->toDateString();

            Log::info("STEP 7: Membuat transaksi distribusi");

            $transaksiId = DB::table('transaksi_distribusi')->insertGetId([
                'kode_barang' => $kodeBarang,
                'bagian_id' => $bagianTujuan,
                'jumlah' => $validated['jumlah'],
                'harga' => $pbStok->harga,
                'tanggal' => $tanggal,
                'user_id' => auth()->id(),
                'keterangan' => $validated['keterangan'] ?? null,
                'bukti' => $buktiPath,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("STEP 7: Transaksi dibuat - ID: {$transaksiId}");

            // Commit transaksi
            DB::commit();
            Log::info("=== TRANSACTION COMMITTED SUCCESSFULLY ===");

            // Ambil data final
            $afterCommitPb = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            
            // Ambil nama bagian
            $bagian = Bagian::find($bagianTujuan);
            $namaBagian = $bagian ? $bagian->nama : "Bagian ID {$bagianTujuan}";

            // Pesan sukses
            $message = "Barang '{$barang->nama_barang}' berhasil didistribusikan ke {$namaBagian}. Jumlah: {$validated['jumlah']}. Stok PB tersisa: {$afterCommitPb->stok}";

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
     * Helper: resolve param menjadi kode_barang + row Barang (dengan lock)
     */
    private function resolveKodeBarangOrFail(string $param): array
    {
        $p = trim($param);

        // 1) coba sebagai kode_barang
        $barang = Barang::where('kode_barang', $p)->lockForUpdate()->first();
        if ($barang) {
            return [$barang->kode_barang, $barang];
        }

        // 2) kalau angka: coba pb_stok.id -> ambil barang; jika tidak, coba barang.id
        if (ctype_digit($p)) {
            // pb_stok.id -> barang (butuh relasi 'barang' di model PbStok)
            $pb = PbStok::with('barang')->lockForUpdate()->find((int)$p);
            if ($pb && $pb->barang) {
                return [$pb->barang->kode_barang, $pb->barang];
            }

            // barang.id -> barang
            $b = Barang::lockForUpdate()->find((int)$p);
            if ($b) {
                return [$b->kode_barang, $b];
            }
        }

        throw new \Exception("Barang dengan kode {$param} tidak ditemukan");
    }
}