<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;

    protected $table = 'gudang';

    protected $fillable = [
        'nama',
    ];

    // Relasi ke Kategori
    public function kategori()
    {
        return $this->hasMany(Kategori::class, 'gudang_id');
    }

    // Relasi ke PJ Stok
    public function pjStok()
    {
        return $this->hasMany(PjStok::class, 'id_gudang');
    }

    // Relasi ke User (Penanggung Jawab)
    public function users()
    {
        return $this->hasMany(User::class, 'gudang_id');
    }

    // Relasi ke Transaksi Distribusi
    public function transaksiDistribusi()
    {
        return $this->hasMany(TransaksiDistribusi::class, 'id_gudang_tujuan');
    }

    // Relasi ke Transaksi Barang Keluar
    public function transaksiBarangKeluar()
    {
        return $this->hasMany(TransaksiBarangKeluar::class, 'id_gudang');
    }
}