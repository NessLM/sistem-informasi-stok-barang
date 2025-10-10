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

    /**
     * Relasi ke BarangKeluar
     */
    public function barangKeluars()
    {
        return $this->hasMany(BarangKeluar::class, 'bagian_id');
    }
}