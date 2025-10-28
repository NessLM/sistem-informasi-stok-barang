<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\TransaksiBarangMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BarangMasukController extends Controller
{
    /**
     * Proses barang masuk ke PB Stok
     */
    public function store(Request $request, $kodeBarang)
    {
        Log::info("=== MULAI PROSES BARANG MASUK ===");
        Log::info("Kode Barang: {$kodeBarang}");
        Log::info("Request Data: " . json_encode($request->all()));

        $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string|max:500',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $buktiPath = null;

        try {
            DB::beginTransaction();
            Log::info("Transaction dimulai");

            // Jika tanggal tidak diisi, gunakan tanggal hari ini
            $tanggal = $request->tanggal ?: now()->format('Y-m-d');

            // ===== STEP 1: Cari barang =====
            $barang = Barang::where('kode_barang', $kodeBarang)->lockForUpdate()->first();

            if (!$barang) {
                throw new \Exception("Barang dengan kode {$kodeBarang} tidak ditemukan");
            }

            Log::info("Barang ditemukan: {$barang->nama_barang}");

            // ===== STEP 2: Cek/Buat PB Stok =====
            $pbStok = PbStok::where('kode_barang', $kodeBarang)->lockForUpdate()->first();

            if (!$pbStok) {
                Log::info("PB Stok belum ada, membuat baru...");

                // Buat PB Stok baru
                $pbStok = PbStok::create([
                    'kode_barang' => $kodeBarang,
                    'stok' => 0
                ]);

                Log::info("PB Stok baru dibuat - ID: {$pbStok->id}, Stok: 0");
            }

            $stokSebelum = $pbStok->stok;
            Log::info("Stok SEBELUM: {$stokSebelum}");

            // ===== STEP 3: Upload bukti jika ada =====
            // ===== STEP 3: Upload bukti jika ada =====
            if ($request->hasFile('bukti')) {
                $file = $request->file('bukti');

                // Generate nama file yang unik dan konsisten
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Simpan dengan nama yang sudah ditentukan
                $buktiPath = $file->storeAs('bukti-barang-masuk', $fileName, 'public');

                Log::info("STEP 3: Bukti file diupload: {$buktiPath}");
                Log::info("STEP 3: Nama file: {$fileName}");
            }

            // ===== STEP 4: Tambah stok menggunakan RAW QUERY =====
            $stokBaru = $stokSebelum + $request->jumlah;
            Log::info("Stok yang akan diset: {$stokBaru} (tambah {$request->jumlah})");

            $affected = DB::table('pb_stok')
                ->where('id', $pbStok->id)
                ->update([
                    'stok' => $stokBaru,
                    'updated_at' => now()
                ]);

            Log::info("Affected rows: {$affected}");

            if ($affected === 0) {
                throw new \Exception("Gagal mengupdate stok PB (affected rows = 0)");
            }

            // Verifikasi dari database
            $verifikasi = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            Log::info("Verifikasi dari DB - Stok sekarang: {$verifikasi->stok}");

            if ($verifikasi->stok != $stokBaru) {
                throw new \Exception("Verifikasi gagal! Expected: {$stokBaru}, Actual: {$verifikasi->stok}");
            }

            // ===== STEP 5: Simpan ke transaksi barang masuk =====
            // PERBAIKAN: Hapus kolom stok_sebelum dan stok_sesudah yang tidak ada di tabel
            $transaksiId = DB::table('transaksi_barang_masuk')->insertGetId([
                'kode_barang' => $kodeBarang,
                'jumlah' => $request->jumlah,
                'tanggal' => $tanggal,
                'keterangan' => $request->keterangan ?? null,
                'bukti' => $buktiPath,
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("Transaksi barang masuk dibuat - ID: {$transaksiId}");

            // ===== STEP 6: Verifikasi FINAL =====
            $finalStok = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            Log::info("FINAL STOK: {$finalStok->stok}");

            DB::commit();
            Log::info("=== TRANSACTION COMMITTED SUCCESSFULLY ===");

            // Log setelah commit
            $afterCommit = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            Log::info("SETELAH COMMIT - Stok: {$afterCommit->stok}");

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Stok {$barang->nama_barang} berhasil ditambahkan {$request->jumlah} {$barang->satuan}. Total stok: {$afterCommit->stok}"
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