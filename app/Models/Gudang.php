<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gudang extends Model
{
    use HasFactory;

    // Pakai tabel 'gudang' (bukan plural)
    protected $table = 'gudang';

    protected $fillable = ['nama'];

    public function kategori()
    {
        return $this->hasMany(Kategori::class, 'gudang_id', 'id');
    }
}
