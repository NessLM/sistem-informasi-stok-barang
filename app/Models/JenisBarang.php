<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisBarang extends Model
{
    protected $table = 'jenis_barang';
    protected $fillable = ['nama', 'kategori_id'];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function barang()
    {
        return $this->hasMany(Barang::class, 'jenis_barang_id');
    }
}
