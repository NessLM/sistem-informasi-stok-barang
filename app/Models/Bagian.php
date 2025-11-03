<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bagian extends Model
{
    use HasFactory;

    protected $table = 'bagian';

    protected $fillable = [
        'nama',
    ];

    // Relasi ke User
    public function users()
    {
        return $this->hasMany(User::class, 'bagian_id');
    }

    // Relasi ke Transaksi Barang Keluar
    public function transaksiBarangKeluar()
    {
        return $this->hasMany(TransaksiBarangKeluar::class, 'bagian_id');
    }

    /**
     * Relasi ke PB Stok
     */
    public function pbStok()
    {
        return $this->hasMany(PbStok::class, 'bagian_id', 'id');
    }

    /**
     * Relasi ke Stok Bagian
     */
    public function stokBagian()
    {
        return $this->hasMany(StokBagian::class, 'bagian_id', 'id');
    }

    /**
     * Relasi ke Transaksi Distribusi
     */
    public function transaksiDistribusi()
    {
        return $this->hasMany(TransaksiDistribusi::class, 'bagian_id', 'id');
    }


}