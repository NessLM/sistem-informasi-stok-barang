<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategoris';
    protected $fillable = ['nama'];

    // Relasi: 1 kategori punya banyak jenis barang
    public function jenisBarang()
    {
        return $this->hasMany(JenisBarang::class, 'kategori_id', 'id');
    }
}
