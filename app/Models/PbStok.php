<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbStok extends Model
{
    use HasFactory;

    protected $table = 'pb_stok';

    protected $fillable = [
        'kode_barang',
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