<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';

    // ✅ pastikan 'gudang_id' ada di sini
    protected $fillable = ['nama', 'gudang_id'];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function barang()
    {
        return $this->hasMany(Barang::class);
    }
}
