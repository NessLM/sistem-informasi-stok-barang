// ==========================================
// File: app/Models/Bagian.php (Update)
// ==========================================
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bagian extends Model
{
    use HasFactory;

    protected $table = 'bagian';

    protected $fillable = [
        'nama',
    ];

    // Relasi ke User
    public function users()
    {
        return $this->hasMany(User::class, 'bagian_id');
    }

    // Relasi ke Transaksi Barang Keluar
    public function transaksiBarangKeluar()
    {
        return $this->hasMany(TransaksiBarangKeluar::class, 'bagian_id');
    }
}