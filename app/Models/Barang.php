<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';
    protected $fillable = ['nama', 'kode', 'harga', 'stok', 'satuan', 'jenis_barang_id'];

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }
}
