// ==========================================
// File: app/Models/TransaksiBarangKeluar.php
// ==========================================
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiBarangKeluar extends Model
{
    use HasFactory;

    protected $table = 'transaksi_barang_keluar';

    protected $fillable = [
        'kode_barang',
        'id_gudang',
        'bagian_id',
        'nama_penerima',
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

    // Relasi ke Gudang
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang');
    }

    // Relasi ke Bagian
    public function bagian()
    {
        return $this->belongsTo(Bagian::class, 'bagian_id');
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Method untuk proses barang keluar
    public static function prosesBarangKeluar($data)
    {
        \DB::beginTransaction();
        try {
            // 1. Cek stok PJ
            $pjStok = PjStok::where('kode_barang', $data['kode_barang'])
                           ->where('id_gudang', $data['id_gudang'])
                           ->firstOrFail();

            if (!$pjStok->cekStokCukup($data['jumlah'])) {
                throw new \Exception('Stok gudang tidak mencukupi');
            }

            // 2. Simpan transaksi
            $transaksi = self::create($data);

            // 3. Kurangi stok PJ
            $pjStok->kurangiStok($data['jumlah']);

            \DB::commit();
            return $transaksi;
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }
}