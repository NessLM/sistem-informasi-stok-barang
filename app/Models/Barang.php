<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';
    protected $primaryKey = 'kode';   // kode sebagai PK
    public $incrementing = false;     // bukan auto increment
    protected $keyType = 'string';

    protected $fillable = [
        'kode',
        'nama',
        'harga',
        'stok',
        'satuan',
        'kategori_id',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }
}
