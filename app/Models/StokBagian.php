<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokBagian extends Model
{
    use HasFactory;

    protected $table = 'stok_bagian'; // nama tabel sesuai database
    protected $fillable = [
        'kode_barang',
        'bagian_id',
        'stok',
    ];

    public function bagian()
    {
        return $this->belongsTo(Bagian::class, 'bagian_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }


}
