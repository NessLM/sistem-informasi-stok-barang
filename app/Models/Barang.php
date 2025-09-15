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
        'jenis_barang_id', // ✅ tambahkan ini
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class);
    }
}
