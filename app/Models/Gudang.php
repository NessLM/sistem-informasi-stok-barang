<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gudang extends Model
{
    use HasFactory;

    // Gunakan tabel 'gudang' (bukan default 'gudangs')
    protected $table = 'gudang';

    protected $fillable = ['nama'];

    /**
     * Relasi ke kategori
     */
    public function kategori()
    {
        return $this->hasMany(Kategori::class, 'gudang_id', 'id');
    }

    /**
     * Relasi ke riwayat sebagai gudang tujuan
     */
    public function riwayatTujuan()
    {
        return $this->hasMany(RiwayatBarang::class, 'gudang_tujuan_id');
    }

    /**
     * Mendapatkan semua barang di gudang melalui kategori
     */
    public function barang()
    {
        return $this->hasManyThrough(
            Barang::class,
            Kategori::class,
            'gudang_id',   // Foreign key di tabel kategori
            'kategori_id', // Foreign key di tabel barang
            'id',          // Local key di tabel gudang
            'id'           // Local key di tabel kategori
        );
    }

    /**
     * Relasi ke user (Penanggung Jawab Gudang)
     */
    public function pj()
    {
        return $this->hasOne(User::class, 'gudang_id');
    }

    /**
     * Accessor: total barang di gudang
     */
    public function getTotalBarangAttribute()
    {
        return $this->barang()->count();
    }

    /**
     * Accessor: total stok di gudang
     */
    public function getTotalStokAttribute()
    {
        return $this->barang()->sum('stok');
    }

    /**
     * Accessor: jumlah kategori di gudang
     */
    public function getJumlahKategoriAttribute()
    {
        return $this->kategori()->count();
    }
}
