// ==========================================
// File: app/Models/PjStok.php
// ==========================================
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PjStok extends Model
{
    use HasFactory;

    protected $table = 'pj_stok';

    protected $fillable = [
        'id_gudang',
        'kode_barang',
        'id_kategori',
        'stok',
    ];

    protected $casts = [
        'stok' => 'integer',
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

    // Relasi ke Kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    // Method untuk menambah stok
    public function tambahStok($jumlah)
    {
        $this->stok += $jumlah;
        $this->save();
        return $this;
    }

    // Method untuk mengurangi stok
    public function kurangiStok($jumlah)
    {
        if ($this->stok < $jumlah) {
            throw new \Exception('Stok tidak mencukupi. Stok tersedia: ' . $this->stok);
        }
        $this->stok -= $jumlah;
        $this->save();
        return $this;
    }

    // Cek apakah stok cukup
    public function cekStokCukup($jumlah)
    {
        return $this->stok >= $jumlah;
    }
}