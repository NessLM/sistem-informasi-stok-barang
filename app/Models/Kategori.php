// ==========================================
// File: app/Models/Kategori.php (Update)
// ==========================================
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';

    protected $fillable = [
        'gudang_id',
        'nama',
    ];

    // Relasi ke Gudang
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    // Relasi ke Barang
    public function barang()
    {
        return $this->hasMany(Barang::class, 'id_kategori');
    }

    // Relasi ke PJ Stok
    public function pjStok()
    {
        return $this->hasMany(PjStok::class, 'id_kategori');
  