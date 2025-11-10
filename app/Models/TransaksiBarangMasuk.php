<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiBarangMasuk extends Model
{
    use HasFactory;

    protected $table = 'transaksi_barang_masuk';

    protected $fillable = [
        'kode_barang',
        'jumlah',
        'tanggal',
        'user_id',
        'keterangan',
        'bukti',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'integer',
    ];

    // Relasi ke Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }

    /**
     * Relasi ke model Bagian
     */
    public function bagian()
    {
        return $this->belongsTo(Bagian::class, 'bagian_id');
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Method untuk proses barang masuk
    public static function prosesBarangMasuk($data)
    {
        \DB::beginTransaction();
        try {
            // 1. Simpan transaksi
            $transaksi = self::create($data);

            // 2. Update stok PB
            $pbStok = PbStok::where('kode_barang', $data['kode_barang'])->first();
            if ($pbStok) {
                $pbStok->tambahStok($data['jumlah']);
            } else {
                PbStok::create([
                    'kode_barang' => $data['kode_barang'],
                    'stok' => $data['jumlah'],
                ]);
            }

            \DB::commit();
            return $transaksi;
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }
}