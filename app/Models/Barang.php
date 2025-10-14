// ==========================================
// File: app/Models/Barang.php
// ==========================================
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';
    protected $primaryKey = 'kode_barang';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_barang',
        'id_kategori',
        'nama_barang',
        'harga_barang',
        'satuan',
    ];

    protected $casts = [
        'harga_barang' => 'decimal:2',
    ];

    // Relasi ke Kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    // Relasi ke PB Stok
    public function pbStok()
    {
        return $this->hasOne(PbStok::class, 'kode_barang', 'kode_barang');
    }

    // Relasi ke PJ Stok
    public function pjStok()
    {
        return $this->hasMany(PjStok::class, 'kode_barang', 'kode_barang');
    }

    // Relasi ke Transaksi Barang Masuk
    public function transaksiBarangMasuk()
    {
        return $this->hasMany(TransaksiBarangMasuk::class, 'kode_barang', 'kode_barang');
    }

    // Relasi ke Transaksi Distribusi
    public function transaksiDistribusi()
    {
        return $this->hasMany(TransaksiDistribusi::class, 'kode_barang', 'kode_barang');
    }

    // Relasi ke Transaksi Barang Keluar
    public function transaksiBarangKeluar()
    {
        return $this->hasMany(TransaksiBarangKeluar::class, 'kode_barang', 'kode_barang');
    }

    // Accessor untuk total stok PB
    public function getTotalStokPbAttribute()
    {
        return $this->pbStok ? $this->pbStok->stok : 0;
    }

    // Accessor untuk total stok PJ (semua gudang)
    public function getTotalStokPjAttribute()
    {
        return $this->pjStok->sum('stok');
    }
}