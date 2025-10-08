<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokGudang extends Model
{
    use HasFactory;

    protected $table = 'stok_gudang';

    protected $fillable = [
        'barang_id',
        'gudang_id',
        'stok',
    ];

    protected $casts = [
        'stok' => 'integer',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    /**
     * Tambah stok
     */
    public function tambahStok($jumlah)
    {
        $this->stok += $jumlah;
        $this->save();
        return $this;
    }

    /**
     * Kurangi stok
     */
    public function kurangiStok($jumlah)
    {
        if ($this->stok < $jumlah) {
            throw new \Exception('Stok tidak mencukupi');
        }
        $this->stok -= $jumlah;
        $this->save();
        return $this;
    }

    /**
     * Cek apakah stok tersedia
     */
    public function cukup($jumlah)
    {
        return $this->stok >= $jumlah;
    }
}