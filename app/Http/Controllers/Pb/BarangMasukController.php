<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PbStok;
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
        Log::info("Param diterima (kodeBarang/id): {$kodeBarang}");
        Log::info("Request Data: " . json_encode($request->all()));

        // Validasi input
        $validated = $request->validate([
            'bagian_id'  => 'required|exists:bagian,id',
            'jumlah'     => 'required|integer|min:1',
            'harga'      => 'required|numeric|min:0',
            'tanggal'    => 'nullable|date',
            'keterangan' => 'nullable|string|max:500',
            'bukti'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        Log::info("Validasi berhasil. Data: " . json_encode($validated));

        $buktiPath = null;

        try {
            DB::beginTransaction();
            Log::info("Transaction dimulai");

            // Pakai hari ini kalau tanggal kosong
            $tanggal = $request->tanggal ?: now()->format('Y-m-d');

            /**
             * NORMALISASI PARAM → dapatkan $barang & $kodeCanonical
             */
            $kodeCanonical = null;
            $barang = Barang::where('kode_barang', $kodeBarang)->lockForUpdate()->first();

            // FIX: Kalau tidak ketemu & param numeric -> coba id pb_stok, lalu id barang
            if (!$barang && is_numeric($kodeBarang)) {
                // 1) Cek apakah ini id dari pb_stok
                $pb = PbStok::where('id', (int)$kodeBarang)->lockForUpdate()->first();
                if ($pb) {
                    $kodeCanonical = $pb->kode_barang;
                    $barang = Barang::where('kode_barang', $kodeCanonical)->lockForUpdate()->first();
                }

                // 2) Kalau masih kosong, mungkin ini id dari barang
                if (!$barang) {
                    $byId = Barang::lockForUpdate()->find((int)$kodeBarang);
                    if ($byId) {
                        $barang = $byId;
                        $kodeCanonical = $byId->kode_barang;
                    }
                }
            }

            // Kalau awalnya sudah ketemu by kode_barang
            if ($barang && !$kodeCanonical) {
                $kodeCanonical = $barang->kode_barang;
            }

            if (!$barang || !$kodeCanonical) {
                throw new \Exception("Barang dengan kunci '{$kodeBarang}' tidak ditemukan");
            }

            Log::info("Barang ditemukan: {$barang->nama_barang} (kode: {$kodeCanonical})");
            Log::info("Bagian Tujuan ID: {$validated['bagian_id']}");

            /**
             * Ambil / buat PB Stok untuk kode_barang + bagian_id ini
             */
            $pbStok = PbStok::where('kode_barang', $kodeCanonical)
                ->where('bagian_id', $validated['bagian_id'])
                ->lockForUpdate()
                ->first();

            if (!$pbStok) {
                Log::info("PB Stok belum ada untuk bagian ini, membuat baru...");
                $pbStok = PbStok::create([
                    'kode_barang' => $kodeCanonical,
                    'bagian_id'   => $validated['bagian_id'],
                    'stok'        => 0,
                    'harga'       => $validated['harga'],
                ]);
                Log::info("PB Stok baru dibuat - ID: {$pbStok->id}, Bagian ID: {$pbStok->bagian_id}, Stok: 0");
            }

            $stokSebelum = $pbStok->stok;
            Log::info("Stok SEBELUM: {$stokSebelum}");

            /**
             * Upload bukti (opsional)
             */
            if ($request->hasFile('bukti')) {
                $file = $request->file('bukti');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiPath = $file->storeAs('bukti-barang-masuk', $fileName, 'public');
                Log::info("Bukti file diupload: {$buktiPath}");
            }

            /**
             * Update stok + harga
             */
            $stokBaru = $stokSebelum + (int)$validated['jumlah'];
            Log::info("Stok yang akan diset: {$stokBaru} (tambah {$validated['jumlah']})");

            $affected = DB::table('pb_stok')
                ->where('id', $pbStok->id)
                ->update([
                    'stok'       => $stokBaru,
                    'harga'      => $validated['harga'],
                    'updated_at' => now(),
                ]);

            Log::info("Affected rows (update pb_stok): {$affected}");
            if ($affected === 0) {
                throw new \Exception("Gagal mengupdate stok PB (affected rows = 0)");
            }

            // Verifikasi
            $ver = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            Log::info("Verifikasi dari DB - Stok sekarang: {$ver->stok}, Harga: {$ver->harga}");
            if ((int)$ver->stok !== (int)$stokBaru) {
                throw new \Exception("Verifikasi gagal! Expected: {$stokBaru}, Actual: {$ver->stok}");
            }

            /**
             * Catat transaksi masuk
             */
            $transaksiId = DB::table('transaksi_barang_masuk')->insertGetId([
                'kode_barang' => $kodeCanonical,
                'bagian_id'   => $validated['bagian_id'],
                'jumlah'      => (int)$validated['jumlah'],
                'harga'       => $validated['harga'],
                'tanggal'     => $tanggal,
                'keterangan'  => $validated['keterangan'] ?? null,
                'bukti'       => $buktiPath,
                'user_id'     => auth()->id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            Log::info("Transaksi barang masuk dibuat - ID: {$transaksiId}");

            DB::commit();
            Log::info("=== TRANSACTION COMMITTED SUCCESSFULLY ===");

            // Ambil data final
            $after = DB::table('pb_stok')->where('id', $pbStok->id)->first();
            $bagian = DB::table('bagian')->where('id', $validated['bagian_id'])->first();
            $namaBagian = $bagian ? $bagian->nama : "Bagian ID {$validated['bagian_id']}";

            return back()->with('toast', [
                'type'    => 'success',
                'title'   => 'Berhasil!',
                'message' => "Barang '{$barang->nama_barang}' berhasil masuk ke {$namaBagian}. Jumlah: {$validated['jumlah']} {$barang->satuan}. Stok bagian tersebut sekarang: {$after->stok}",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("=== TRANSACTION ROLLED BACK ===");
            Log::error("Error Message: " . $e->getMessage());
            Log::error("Error Line: " . $e->getLine());
            Log::error("Stack Trace: " . $e->getTraceAsString());

            if ($buktiPath) {
                Storage::disk('public')->delete($buktiPath);
                Log::info("Bukti file dihapus karena rollback");
            }

            return back()->with('toast', [
                'type'    => 'error',
                'title'   => 'Gagal!',
                'message' => $e->getMessage(),
            ]);
        }
    }
}