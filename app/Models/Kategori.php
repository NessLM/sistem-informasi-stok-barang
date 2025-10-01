<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';

    protected $fillable = [
        'nama',
        'gudang_id'
    ];

    /**
     * Relasi ke gudang
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    /**
     * Relasi ke barang
     */
    public function barang()
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }

    /**
     * Relasi ke riwayat sebagai kategori asal
     */
    public function riwayatAsal()
    {
        return $this->hasMany(RiwayatBarang::class, 'kategori_asal_id');
    }

    /**
     * Relasi ke riwayat sebagai kategori tujuan
     */
    public function riwayatTujuan()
    {
        return $this->hasMany(RiwayatBarang::class, 'kategori_tujuan_id');
    }

    /**
     * Scope untuk filter berdasarkan gudang
     */
    public function scopeByGudang($query, $gudangId)
    {
        return $query->where('gudang_id', $gudangId);
    }

    /**
     * Accessor untuk mendapatkan nama lengkap (kategori - gudang)
     */
    public function getNamaLengkapAttribute()
    {
        return $this->nama . ' - ' . ($this->gudang->nama ?? 'Gudang Tidak Diketahui');
    }

    /**
     * Accessor untuk jumlah barang dalam kategori
     */
    public function getJumlahBarangAttribute()
    {
        return $this->barang()->count();
    }

    /**
     * Accessor untuk total stok dalam kategori
     */
    public function getTotalStokAttribute()
    {
        return $this->barang()->sum('stok');
    }
}