<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\Bagian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DistribusiController extends Controller
{
    public function store(Request $request, $paramKodeBarang)
    {
        Log::info("=== MULAI PROSES DISTRIBUSI PB KE BAGIAN ===");
        Log::info("Param Kode Barang: {$paramKodeBarang}");
        Log::info("Request Data: " . json_encode($request->all()));

        // STEP 0: Validasi
        $validated = $request->validate([
            'bagian_id'  => 'required|exists:bagian,id',
            'jumlah'     => 'required|integer|min:1',
            'tanggal'    => 'nullable|date',
            'keterangan' => 'nullable|string|max:500',
            'bukti'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'harga'      => 'nullable|numeric|min:0',
        ]);

        $buktiPath = null;

        try {
            DB::beginTransaction();
            Log::info("== TRANSACTION BEGIN ==");

            // STEP 1: Resolve barang
            [$kodeBarang, $barang] = $this->resolveKodeBarangOrFail($paramKodeBarang);
            Log::info("Barang: {$barang->nama_barang} (kode: {$kodeBarang})");

            // STEP 2: Total stok PB (lock)
            $totalPb = PbStok::where('kode_barang', $kodeBarang)
                ->lockForUpdate()
                ->sum('stok');
            if ($totalPb <= 0) throw new \Exception("Barang belum ada di PB Stok.");
            if ($totalPb < (int)$validated['jumlah'])
                throw new \Exception("Stok PB tidak mencukupi. Tersedia: {$totalPb}, Diminta: {$validated['jumlah']}");

            // STEP 3: Tujuan & harga
            $bagianTujuan = (int) $validated['bagian_id'];
            $hargaSatuan = $request->filled('harga')
                ? (float) $request->harga
                : (float) (PbStok::where('kode_barang', $kodeBarang)->max('harga') ?? 0);

            // STEP 4: Upload bukti
            if ($request->hasFile('bukti')) {
                $file = $request->file('bukti');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiPath = $file->storeAs('bukti-distribusi', $fileName, 'public');
            }

            // STEP 5: Kurangi stok PB agregat
            $sisa = (int) $validated['jumlah'];

            // 5a) Prioritaskan baris pb_stok dengan harga yg sama
            $primary = PbStok::where('kode_barang', $kodeBarang)
                ->where('harga', $hargaSatuan)
                ->orderByDesc('stok')
                ->lockForUpdate()
                ->get();

            foreach ($primary as $row) {
                if ($sisa <= 0) break;
                $ambil = min($row->stok, $sisa);
                if ($ambil > 0) {
                    DB::table('pb_stok')->where('id', $row->id)->update([
                        'stok'       => $row->stok - $ambil,
                        'updated_at' => now(),
                    ]);
                    Log::info("PB cut (match price) id={$row->id} -= {$ambil}");
                    $sisa -= $ambil;
                }
            }

            // 5b) Jika masih kurang, ambil dari baris lain
            if ($sisa > 0) {
                $fallback = PbStok::where('kode_barang', $kodeBarang)
                    ->where(function ($q) use ($hargaSatuan) {
                        $q->whereNull('harga')->orWhere('harga', '<>', $hargaSatuan);
                    })
                    ->orderByDesc('stok')
                    ->lockForUpdate()
                    ->get();

                foreach ($fallback as $row) {
                    if ($sisa <= 0) break;
                    $ambil = min($row->stok, $sisa);
                    if ($ambil > 0) {
                        DB::table('pb_stok')->where('id', $row->id)->update([
                            'stok'       => $row->stok - $ambil,
                            'updated_at' => now(),
                        ]);
                        Log::info("PB cut (fallback) id={$row->id} -= {$ambil}");
                        $sisa -= $ambil;
                    }
                }
            }

            if ($sisa > 0) {
                throw new \Exception("Stok PB tidak mencukupi (sisa kebutuhan: {$sisa}).");
            }


            // STEP 6: Update/insert stok_bagian
            $stokBagian = DB::table('stok_bagian')
                ->where('kode_barang', $kodeBarang)
                ->where('bagian_id', $bagianTujuan)
                ->lockForUpdate()
                ->first();

            $stokBagianHasHarga = in_array('harga', Schema::getColumnListing('stok_bagian'));

            if ($stokBagian) {
                $update = [
                    'stok'       => $stokBagian->stok + (int)$validated['jumlah'],
                    'updated_at' => now(),
                ];
                if ($stokBagianHasHarga) $update['harga'] = $hargaSatuan;

                DB::table('stok_bagian')->where('id', $stokBagian->id)->update($update);
            } else {
                $insert = [
                    'kode_barang' => $kodeBarang,
                    'bagian_id'   => $bagianTujuan,
                    'stok'        => (int)$validated['jumlah'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
                if ($stokBagianHasHarga) $insert['harga'] = $hargaSatuan;

                DB::table('stok_bagian')->insertGetId($insert);
            }

            // STEP 7: Catat ke transaksi_distribusi SAJA
            $tanggal = $validated['tanggal'] ?? now()->toDateString();
            $tdPayload = [
                'kode_barang' => $kodeBarang,
                'bagian_id'   => $bagianTujuan,
                'jumlah'      => (int)$validated['jumlah'],
                'tanggal'     => $tanggal,
                'user_id'     => auth()->id(),
                'keterangan'  => $validated['keterangan'] ?? null,
                'bukti'       => $buktiPath,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
            if (in_array('harga', Schema::getColumnListing('transaksi_distribusi'))) {
                $tdPayload['harga'] = $hargaSatuan;
            }
            $transaksiId = DB::table('transaksi_distribusi')->insertGetId($tdPayload);
            Log::info("Transaksi Distribusi dibuat: id={$transaksiId}");

            // STEP 8: Commit + toast
            DB::commit();

            $totalAfter = PbStok::where('kode_barang', $kodeBarang)->sum('stok');
            $namaBagian = optional(Bagian::find($bagianTujuan))->nama ?? "Bagian ID {$bagianTujuan}";
            $message = "Barang '{$barang->nama_barang}' berhasil didistribusikan ke {$namaBagian}. Jumlah: {$validated['jumlah']}. Stok PB tersisa: {$totalAfter}";

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($buktiPath) Storage::disk('public')->delete($buktiPath);

            Log::error("Distribusi gagal: {$e->getMessage()} @ line " . $e->getLine());
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function resolveKodeBarangOrFail(string $param): array
    {
        $p = trim($param);

        $barang = Barang::where('kode_barang', $p)->lockForUpdate()->first();
        if ($barang) return [$barang->kode_barang, $barang];

        if (ctype_digit($p)) {
            $pb = PbStok::with('barang')->lockForUpdate()->find((int)$p);
            if ($pb && $pb->barang) return [$pb->barang->kode_barang, $pb->barang];

            $b = Barang::lockForUpdate()->find((int)$p);
            if ($b) return [$b->kode_barang, $b];
        }

        throw new \Exception("Barang dengan kode {$param} tidak ditemukan");
    }
}
