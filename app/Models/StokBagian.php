<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokBagian extends Model
{
    use HasFactory;

    protected $table = 'stok_bagian'; // nama tabel sesuai database
    protected $fillable = [
        'bagian_id',
        'kode_barang',
        'batch_number', // 👈 TAMBAHKAN INI
        'stok',
        'harga',
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
