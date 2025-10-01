<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    protected $fillable = [
        'kode',
        'nama',
        'harga',
        'stok',
        'satuan',
        'kategori_id',
        'jenis_barang_id',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'stok' => 'integer',
    ];

    /**
     * Relasi ke kategori
     */
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    /**
     * Relasi ke jenis barang
     */
    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }

    /**
     * Relasi ke riwayat barang (sebagai barang asal)
     */
    public function riwayat()
    {
        return $this->hasMany(RiwayatBarang::class, 'barang_id');
    }

    /**
     * Relasi ke riwayat barang (sebagai barang tujuan)
     */
    public function riwayatTujuan()
    {
        return $this->hasMany(RiwayatBarang::class, 'barang_tujuan_id');
    }

    /**
     * Scope untuk filter stok rendah
     */
    public function scopeStokRendah($query, $minimum = 10)
    {
        return $query->where('stok', '<=', $minimum);
    }

    /**
     * Scope untuk filter stok habis
     */
    public function scopeStokHabis($query)
    {
        return $query->where('stok', 0);
    }

    /**
     * Accessor untuk format harga Rupiah
     */
    public function getHargaFormattedAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }

    /**
     * Accessor untuk status stok
     */
    public function getStatusStokAttribute()
    {
        if ($this->stok == 0) {
            return 'habis';
        } elseif ($this->stok <= 10) {
            return 'rendah';
        }
        return 'tersedia';
    }
}