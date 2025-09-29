<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DistribusiController extends Controller
{
    public function distribusi(Request $request, $id)
    {
        $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'required|date',
            'gudang_tujuan_id' => 'required|exists:gudang,id',
            'kategori_tujuan_id' => 'required|exists:kategori,id',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $barang = Barang::findOrFail($id);

            // Cek stok mencukupi
            if ($barang->stok < $request->jumlah) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $barang->stok
                ]);
            }

            // Validasi: tidak bisa distribusi ke kategori yang sama
            if ($barang->kategori_id == $request->kategori_tujuan_id) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Tidak dapat mendistribusikan ke kategori yang sama'
                ]);
            }

            // Kurangi stok barang asal
            $barang->stok -= $request->jumlah;
            $barang->save();

            // Upload bukti jika ada
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-distribusi', 'public');
            }

            // Cari barang dengan nama DAN kode yang sama di kategori tujuan
            $barangTujuan = Barang::where('nama', $barang->nama)
                ->where('kode', $barang->kode)
                ->where('kategori_id', $request->kategori_tujuan_id)
                ->first();

            if ($barangTujuan) {
                // Jika barang sudah ada, tambah stoknya
                $barangTujuan->stok += $request->jumlah;
                $barangTujuan->save();
                
                $message = 'Barang berhasil didistribusikan. Stok di kategori tujuan bertambah: ' . $request->jumlah;
            } else {
                // Jika belum ada, buat barang baru di kategori tujuan
                Barang::create([
                    'nama' => $barang->nama,
                    'kode' => $barang->kode,
                    'kategori_id' => $request->kategori_tujuan_id,
                    'jenis_barang_id' => $barang->jenis_barang_id ?? null,
                    'stok' => $request->jumlah,
                    'satuan' => $barang->satuan,
                    'harga' => $barang->harga,
                ]);
                
                $message = 'Barang berhasil didistribusikan dan dibuat di kategori tujuan dengan stok: ' . $request->jumlah;
            }

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hapus file bukti jika upload gagal
            if (isset($buktiPath) && $buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            \Log::error('Error distribusi: ' . $e->getMessage());

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan saat distribusi barang'
            ]);
        }
    }

    public function store(Request $request)
    {
        return $this->distribusi($request, $request->barang_id);
    }
}