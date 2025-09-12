<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';
    protected $fillable = ['nama', 'kategori_id', 'jumlah', 'kode', 'harga', 'stok', 'satuan'];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
}
