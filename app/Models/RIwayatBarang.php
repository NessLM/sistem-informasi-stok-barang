<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatBarang extends Model
{
    use HasFactory;

    protected $table = 'riwayat_barang';

    protected $fillable = [
        'barang_id',
        'jenis_transaksi',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'keterangan',
        'kategori_asal_id',
        'kategori_tujuan_id',
        'gudang_tujuan_id',
        'barang_tujuan_id',
        'bukti',
        'tanggal',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'integer',
        'stok_sebelum' => 'integer',
        'stok_sesudah' => 'integer',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function barangTujuan()
    {
        return $this->belongsTo(Barang::class, 'barang_tujuan_id');
    }

    public function kategoriAsal()
    {
        return $this->belongsTo(Kategori::class, 'kategori_asal_id');
    }

    public function kategoriTujuan()
    {
        return $this->belongsTo(Kategori::class, 'kategori_tujuan_id');
    }

    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}